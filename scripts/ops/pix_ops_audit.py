#!/usr/bin/env python3
"""Generate an operational Pix readiness snapshot.

This helper inspects the shared JSON examples and readiness notes to surface
potential gaps before running end-to-end rehearsals. It exits with a non-zero
code when critical Pix data is missing so CI pipelines can block deploys.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from collections import Counter
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Dict, List, Sequence

REPO_ROOT = Path(__file__).resolve().parents[2]
EXAMPLES_DIR = REPO_ROOT / "docs" / "examples"
READINESS_PATH = REPO_ROOT / "docs" / "status" / "platform-readiness.md"

REQUIRED_CHARGE_FIELDS = ("codigoCopiaCola", "txid")


@dataclass
class AuditIssue:
    scope: str
    identifier: str
    message: str

    def to_dict(self) -> Dict[str, Any]:
        return {
            "scope": self.scope,
            "identifier": self.identifier,
            "message": self.message,
        }


def load_json(path: Path) -> Any:
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def summarise_counts(items: Sequence[Dict[str, Any]], field: str) -> Dict[str, int]:
    counter: Counter[str] = Counter()
    for item in items:
        value = str(item.get(field, "desconhecido")).lower()
        counter[value] += 1
    return dict(sorted(counter.items()))


def audit_charges(charges: Sequence[Dict[str, Any]]) -> List[AuditIssue]:
    issues: List[AuditIssue] = []
    for charge in charges:
        identifier = str(charge.get("id") or charge.get("txid") or "sem-id")
        for field in REQUIRED_CHARGE_FIELDS:
            if not str(charge.get(field, "")).strip():
                issues.append(
                    AuditIssue(
                        scope="cobrancas_pix",
                        identifier=identifier,
                        message=f"Campo obrigatório ausente: {field}",
                    )
                )
        qr_source = charge.get("qrCodeBase64") or charge.get("qrCode", {}).get("base64")
        if not str(qr_source or "").strip():
            issues.append(
                AuditIssue(
                    scope="cobrancas_pix",
                    identifier=identifier,
                    message="Campo obrigatório ausente: qrCodeBase64",
                )
            )
    return issues


def normalise_collection(payload: Any, *keys: str) -> List[Dict[str, Any]]:
    if isinstance(payload, list):
        return [item for item in payload if isinstance(item, dict)]
    if isinstance(payload, dict):
        search_keys = keys or ("data",)
        for key in search_keys:
            value = payload.get(key)
            if isinstance(value, list):
                return [item for item in value if isinstance(item, dict)]
        return []
    return []


def extract_readiness_snapshot(markdown: str) -> Dict[str, Any]:
    clean = markdown.replace('*', '')
    table_match = re.findall(
        r"\|\s*Flutter .*?\|\s*([0-9]+)\s*[%]",
        clean,
        flags=re.IGNORECASE | re.DOTALL,
    )
    strapi_match = re.findall(
        r"\|\s*CMS Strapi .*?\|\s*([0-9]+)\s*[%]",
        clean,
        flags=re.IGNORECASE | re.DOTALL,
    )
    ops_match = re.findall(
        r"\|\s*QA, observabilidade .*?\|\s*([0-9]+)\s*[%]",
        clean,
        flags=re.IGNORECASE | re.DOTALL,
    )

    return {
        "flutter_ios_percent": int(table_match[0]) if table_match else None,
        "strapi_percent": int(strapi_match[0]) if strapi_match else None,
        "ops_percent": int(ops_match[0]) if ops_match else None,
    }


def generate_report() -> Dict[str, Any]:
    planos = normalise_collection(load_json(EXAMPLES_DIR / "planos.json"), "planos")
    assinaturas = normalise_collection(
        load_json(EXAMPLES_DIR / "assinaturas_pix.json"), "assinaturas", "data"
    )
    cobrancas = normalise_collection(
        load_json(EXAMPLES_DIR / "cobrancas_pix.json"), "cobrancas"
    )

    readiness = extract_readiness_snapshot(READINESS_PATH.read_text(encoding="utf-8"))

    issues = audit_charges(cobrancas)

    return {
        "totals": {
            "planos": len(planos),
            "assinaturas_pix": len(assinaturas),
            "cobrancas_pix": len(cobrancas),
        },
        "status_breakdown": {
            "assinaturas": summarise_counts(assinaturas, "status"),
            "cobrancas": summarise_counts(cobrancas, "status"),
        },
        "readiness": readiness,
        "issues": [issue.to_dict() for issue in issues],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--json",
        action="store_true",
        help="Output the report as JSON for consumption in CI",
    )
    args = parser.parse_args()

    report = generate_report()

    if args.json:
        json.dump(report, fp=sys.stdout, ensure_ascii=False, indent=2)
        sys.stdout.write("\n")
    else:
        print("Pix – visão operacional")
        print("=" * 28)
        print(
            f"Planos: {report['totals']['planos']} | Assinaturas: {report['totals']['assinaturas_pix']} | "
            f"Cobranças: {report['totals']['cobrancas_pix']}"
        )
        print("Status das assinaturas:")
        for status, amount in report["status_breakdown"]["assinaturas"].items():
            print(f"  - {status}: {amount}")
        print("Status das cobranças:")
        for status, amount in report["status_breakdown"]["cobrancas"].items():
            print(f"  - {status}: {amount}")
        print("Readiness restante:")
        readiness = report["readiness"]
        print(
            f"  Flutter iOS: {readiness['flutter_ios_percent']}% | Strapi: {readiness['strapi_percent']}% | "
            f"Operações: {readiness['ops_percent']}%"
        )
        if report["issues"]:
            print("Alertas críticos:")
            for issue in report["issues"]:
                print(
                    f"  - [{issue['scope']}] {issue['identifier']}: {issue['message']}"
                )
        else:
            print("Nenhum alerta crítico nos exemplos Pix.")

    return 1 if report["issues"] else 0


if __name__ == "__main__":
    sys.exit(main())
