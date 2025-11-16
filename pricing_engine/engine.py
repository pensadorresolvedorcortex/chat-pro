from __future__ import annotations

from dataclasses import dataclass, field
from enum import Enum
from typing import Any, List, Optional, Tuple


class FeeBase(str, Enum):
    """Defines the basis for fee calculations."""

    ITEM_PRICE = "ITEM_PRICE"
    ORDER_SUBTOTAL = "ORDER_SUBTOTAL"
    SHIPPING_VALUE = "SHIPPING_VALUE"
    FIXED = "FIXED"


class FeeType(str, Enum):
    """Indicates whether a fee is percent-based or fixed."""

    PERCENT = "PERCENT"
    FIXED = "FIXED"


class PricingGoalType(str, Enum):
    """Supported modes for price calculations."""

    ANALYZE_PRICE = "ANALYZE_PRICE"
    TARGET_MARGIN = "TARGET_MARGIN"


class RoundingMode(str, Enum):
    ROUND = "ROUND"
    CEIL = "CEIL"
    FLOOR = "FLOOR"


@dataclass
class Product:
    id: str
    sku: str
    name: str
    cost_of_goods: float
    operational_cost: float
    shipping_cost_seller: float
    tax_rate_on_sale: float


@dataclass
class OfferContext:
    marketplace_id: str
    category_id: Optional[str] = None
    participates_in_free_shipping: bool = False
    participates_in_campaign: bool = False
    campaign_type: Optional[str] = None
    quantity: int = 1


@dataclass
class Condition:
    field: str
    operator: str
    value: Any


@dataclass
class FeeRule:
    id: str
    description: str
    fee_type: FeeType
    base: FeeBase
    rate: float = 0.0
    amount: float = 0.0
    min_amount: Optional[float] = None
    max_amount: Optional[float] = None
    conditions: List[Condition] = field(default_factory=list)


@dataclass
class RoundingRule:
    decimals: int = 2
    mode: RoundingMode = RoundingMode.ROUND


@dataclass
class MarketplaceConfig:
    id: str
    name: str
    currency: str = "BRL"
    fee_rules: List[FeeRule] = field(default_factory=list)
    tax_included_in_price: bool = False
    rounding_rule: RoundingRule = field(default_factory=RoundingRule)


@dataclass
class PricingGoal:
    type: PricingGoalType
    target_margin_over_cost: Optional[float] = None
    target_margin_over_price: Optional[float] = None
    given_price: Optional[float] = None


@dataclass
class PricingRequest:
    product: Product
    offer_context: OfferContext
    marketplace: MarketplaceConfig
    goal: PricingGoal


@dataclass
class FeeDetail:
    rule_id: str
    description: str
    amount: float


@dataclass
class MarketplaceFeesBreakdown:
    total: float
    by_rule: List[FeeDetail]


@dataclass
class PricingBreakdown:
    product_cost: float
    operational_cost: float
    shipping_cost_seller: float
    marketplace_fees: MarketplaceFeesBreakdown
    taxes: float
    net_revenue: float
    profit: float
    margin_over_price: float
    margin_over_cost: float


@dataclass
class PricingResponse:
    success: bool
    message: str
    warnings: List[str]
    used_price: float
    suggested_price: Optional[float]
    breakdown: Optional[PricingBreakdown]


def calculate_price(request: PricingRequest) -> PricingResponse:
    product = request.product
    ctx = request.offer_context
    mp = request.marketplace
    goal = request.goal

    if goal.type == PricingGoalType.ANALYZE_PRICE:
        if goal.given_price is None:
            return PricingResponse(
                success=False,
                message="given_price is required for ANALYZE_PRICE",
                warnings=[],
                used_price=0.0,
                suggested_price=None,
                breakdown=None,
            )

        price = goal.given_price
        breakdown = compute_breakdown(price, product, ctx, mp)
        warnings = build_warnings(breakdown, target_margin=None)

        return PricingResponse(
            success=True,
            message="Price analyzed successfully",
            warnings=warnings,
            used_price=price,
            suggested_price=None,
            breakdown=breakdown,
        )

    if goal.type == PricingGoalType.TARGET_MARGIN:
        if goal.target_margin_over_cost is None:
            return PricingResponse(
                success=False,
                message="target_margin_over_cost is required for TARGET_MARGIN",
                warnings=[],
                used_price=0.0,
                suggested_price=None,
                breakdown=None,
            )

        target_margin = goal.target_margin_over_cost
        suggested_price = find_price_for_margin(product, ctx, mp, target_margin)
        breakdown = compute_breakdown(suggested_price, product, ctx, mp)
        warnings = build_warnings(breakdown, target_margin=target_margin)

        return PricingResponse(
            success=True,
            message="Suggested price calculated successfully",
            warnings=warnings,
            used_price=suggested_price,
            suggested_price=suggested_price,
            breakdown=breakdown,
        )

    return PricingResponse(
        success=False,
        message=f"Unsupported goal type: {goal.type}",
        warnings=[],
        used_price=0.0,
        suggested_price=None,
        breakdown=None,
    )


def compute_breakdown(
    price: float, product: Product, ctx: OfferContext, mp: MarketplaceConfig
) -> PricingBreakdown:
    quantity = ctx.quantity
    total_price = price * quantity

    product_cost = product.cost_of_goods * quantity
    operational_cost = product.operational_cost * quantity
    shipping_cost_seller = product.shipping_cost_seller * quantity

    fees_total, fees_by_rule = compute_marketplace_fees(price, ctx, mp)

    taxes = total_price * product.tax_rate_on_sale
    net_revenue = total_price - fees_total - taxes
    total_cost = product_cost + operational_cost + shipping_cost_seller
    profit = net_revenue - total_cost
    margin_over_price = profit / total_price if total_price > 0 else 0.0
    margin_over_cost = profit / total_cost if total_cost > 0 else 0.0

    return PricingBreakdown(
        product_cost=product_cost,
        operational_cost=operational_cost,
        shipping_cost_seller=shipping_cost_seller,
        marketplace_fees=MarketplaceFeesBreakdown(total=fees_total, by_rule=fees_by_rule),
        taxes=taxes,
        net_revenue=net_revenue,
        profit=profit,
        margin_over_price=margin_over_price,
        margin_over_cost=margin_over_cost,
    )


def compute_marketplace_fees(
    price: float, ctx: OfferContext, mp: MarketplaceConfig
) -> Tuple[float, List[FeeDetail]]:
    total = 0.0
    details: List[FeeDetail] = []

    for rule in mp.fee_rules:
        if not rule_applies(rule, ctx):
            continue

        base_value = get_base_value(rule.base, price, ctx)

        if rule.fee_type == FeeType.PERCENT:
            amount = base_value * rule.rate
        elif rule.fee_type == FeeType.FIXED:
            amount = rule.amount
        else:
            amount = 0.0

        if rule.min_amount is not None:
            amount = max(amount, rule.min_amount)
        if rule.max_amount is not None:
            amount = min(amount, rule.max_amount)

        total += amount
        details.append(
            FeeDetail(
                rule_id=rule.id,
                description=rule.description,
                amount=amount,
            )
        )

    return total, details


def rule_applies(rule: FeeRule, ctx: OfferContext) -> bool:
    for cond in rule.conditions:
        value = getattr(ctx, cond.field, None)
        if not evaluate_condition(value, cond.operator, cond.value):
            return False
    return True


def evaluate_condition(left: Any, operator: str, right: Any) -> bool:
    if operator == "==":
        return left == right
    if operator == "!=":
        return left != right
    if operator == ">":
        return left is not None and right is not None and left > right
    if operator == "<":
        return left is not None and right is not None and left < right
    if operator == ">=":
        return left is not None and right is not None and left >= right
    if operator == "<=":
        return left is not None and right is not None and left <= right
    if operator.upper() == "IN":
        try:
            return left in right
        except TypeError:
            return False

    return False


def get_base_value(base: FeeBase, price: float, ctx: OfferContext) -> float:
    if base == FeeBase.ITEM_PRICE:
        return price * ctx.quantity
    if base == FeeBase.ORDER_SUBTOTAL:
        return price * ctx.quantity
    if base == FeeBase.SHIPPING_VALUE:
        return 0.0
    if base == FeeBase.FIXED:
        return 1.0

    return 0.0


def find_price_for_margin(
    product: Product,
    ctx: OfferContext,
    mp: MarketplaceConfig,
    target_margin_over_cost: float,
) -> float:
    low = 1.0
    high = 10000.0

    for _ in range(40):
        mid = (low + high) / 2.0
        breakdown = compute_breakdown(mid, product, ctx, mp)
        current_margin = breakdown.margin_over_cost

        if current_margin < target_margin_over_cost:
            low = mid
        else:
            high = mid

    return round_price(high, mp.rounding_rule)


def round_price(price: float, rule: RoundingRule) -> float:
    factor = 10 ** rule.decimals

    if rule.mode == RoundingMode.ROUND:
        return round(price * factor) / factor

    import math

    if rule.mode == RoundingMode.CEIL:
        return math.ceil(price * factor) / factor

    if rule.mode == RoundingMode.FLOOR:
        return math.floor(price * factor) / factor

    return price


def build_warnings(breakdown: PricingBreakdown, target_margin: Optional[float]) -> List[str]:
    warnings: List[str] = []

    if breakdown.profit < 0:
        warnings.append("Lucro negativo: preço abaixo do ponto de equilíbrio.")

    if target_margin is not None:
        diff = breakdown.margin_over_cost - target_margin
        if abs(diff) > 0.005:
            warnings.append(
                "Margem atingida está um pouco distante da meta (diferença > 0,5%)."
            )

    return warnings


def build_example_shopee_marketplace() -> MarketplaceConfig:
    rules: List[FeeRule] = []

    rules.append(
        FeeRule(
            id="shopee_commission_standard",
            description="Comissão padrão Shopee 14%",
            fee_type=FeeType.PERCENT,
            base=FeeBase.ITEM_PRICE,
            rate=0.14,
            max_amount=100.0,
            conditions=[],
        )
    )

    rules.append(
        FeeRule(
            id="shopee_commission_free_shipping_extra",
            description="Comissão extra Frete Grátis Shopee 6%",
            fee_type=FeeType.PERCENT,
            base=FeeBase.ITEM_PRICE,
            rate=0.06,
            conditions=[
                Condition(
                    field="participates_in_free_shipping",
                    operator="==",
                    value=True,
                )
            ],
        )
    )

    rules.append(
        FeeRule(
            id="shopee_fixed_fee_per_item",
            description="Taxa fixa por item",
            fee_type=FeeType.FIXED,
            base=FeeBase.FIXED,
            amount=4.0,
            conditions=[],
        )
    )

    rounding = RoundingRule(
        decimals=2,
        mode=RoundingMode.ROUND,
    )

    return MarketplaceConfig(
        id="shopee_br",
        name="Shopee Brasil",
        currency="BRL",
        fee_rules=rules,
        tax_included_in_price=False,
        rounding_rule=rounding,
    )


__all__ = [
    "FeeBase",
    "FeeType",
    "PricingGoalType",
    "RoundingMode",
    "Product",
    "OfferContext",
    "Condition",
    "FeeRule",
    "RoundingRule",
    "MarketplaceConfig",
    "PricingGoal",
    "PricingRequest",
    "FeeDetail",
    "MarketplaceFeesBreakdown",
    "PricingBreakdown",
    "PricingResponse",
    "calculate_price",
    "compute_breakdown",
    "compute_marketplace_fees",
    "rule_applies",
    "evaluate_condition",
    "get_base_value",
    "find_price_for_margin",
    "round_price",
    "build_warnings",
    "build_example_shopee_marketplace",
]
