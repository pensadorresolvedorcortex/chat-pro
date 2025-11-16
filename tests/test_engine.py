import math

import pytest

from pricing_engine.engine import (
    OfferContext,
    PricingGoal,
    PricingGoalType,
    PricingRequest,
    Product,
    build_example_shopee_marketplace,
    build_warnings,
    calculate_price,
    compute_breakdown,
    evaluate_condition,
    find_price_for_margin,
)


@pytest.fixture
def product() -> Product:
    return Product(
        id="sku-123",
        sku="SKU-123",
        name="Camiseta",
        cost_of_goods=20.0,
        operational_cost=3.0,
        shipping_cost_seller=0.0,
        tax_rate_on_sale=0.08,
    )


@pytest.fixture
def context() -> OfferContext:
    return OfferContext(
        marketplace_id="shopee_br",
        participates_in_free_shipping=True,
        quantity=1,
    )


@pytest.fixture
def marketplace():
    return build_example_shopee_marketplace()


def test_compute_breakdown_matches_manual_calculation(product, context, marketplace):
    breakdown = compute_breakdown(60.0, product, context, marketplace)

    assert pytest.approx(breakdown.marketplace_fees.total) == 16.0
    assert pytest.approx(breakdown.taxes) == 4.8
    assert pytest.approx(breakdown.net_revenue) == 39.2
    assert pytest.approx(breakdown.profit) == 16.2
    assert pytest.approx(breakdown.margin_over_price, rel=1e-3) == 0.27
    assert pytest.approx(breakdown.margin_over_cost, rel=1e-3) == 16.2 / 23.0


def test_find_price_for_margin_brings_margin_close_to_target(product, context, marketplace):
    target = 0.4
    price = find_price_for_margin(product, context, marketplace, target)
    breakdown = compute_breakdown(price, product, context, marketplace)

    assert math.isclose(breakdown.margin_over_cost, target, abs_tol=0.01)


def test_evaluate_condition_in_operator_handles_non_iterable():
    assert evaluate_condition("a", "IN", {"a", "b"}) is True
    assert evaluate_condition("a", "IN", 123) is False


def test_build_warnings_highlights_negative_profit(product, context, marketplace):
    breakdown = compute_breakdown(5.0, product, context, marketplace)
    warnings = build_warnings(breakdown, target_margin=None)

    assert any("Lucro negativo" in message for message in warnings)


def test_calculate_price_analyze_goal_returns_breakdown(product, context, marketplace):
    request = PricingRequest(
        product=product,
        offer_context=context,
        marketplace=marketplace,
        goal=PricingGoal(type=PricingGoalType.ANALYZE_PRICE, given_price=60.0),
    )

    response = calculate_price(request)

    assert response.success is True
    assert response.breakdown is not None
    assert pytest.approx(response.breakdown.profit) == 16.2


def test_calculate_price_target_margin_goal_uses_binary_search(product, context, marketplace):
    goal = PricingGoal(type=PricingGoalType.TARGET_MARGIN, target_margin_over_cost=0.35)
    request = PricingRequest(
        product=product,
        offer_context=context,
        marketplace=marketplace,
        goal=goal,
    )

    response = calculate_price(request)

    assert response.suggested_price is not None
    breakdown = response.breakdown
    assert breakdown is not None
    assert math.isclose(breakdown.margin_over_cost, 0.35, abs_tol=0.01)
