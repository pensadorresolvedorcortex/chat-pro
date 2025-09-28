#!/usr/bin/env python3
"""Validate sample data synchronization between app and CMS examples."""
from __future__ import annotations

import json
from pathlib import Path
from collections import defaultdict
from typing import Dict, List, Set

ROOT = Path(__file__).resolve().parents[1]
EXAMPLES_DIR = ROOT / "docs" / "examples"


def load_json(path: Path):
    with path.open(encoding="utf-8") as handle:
        return json.load(handle)


def collect_user_tokens(payload) -> Set[str]:
    if isinstance(payload, dict):
        tokens: Set[str] = set()
        for value in payload.values():
            tokens.update(collect_user_tokens(value))
        return tokens
    if isinstance(payload, list):
        tokens: Set[str] = set()
        for item in payload:
            tokens.update(collect_user_tokens(item))
        return tokens
    if isinstance(payload, str) and payload.startswith("user-"):
        return {payload}
    return set()


def main() -> int:
    errors: List[str] = []

    def check(condition: bool, message: str) -> None:
        if condition:
            print(f"[✓] {message}")
        else:
            errors.append(message)
            print(f"[✗] {message}")

    users = load_json(EXAMPLES_DIR / "usuarios.json")
    users_by_id: Dict[str, dict] = {user["id"]: user for user in users}
    plan_records = load_json(EXAMPLES_DIR / "planos.json")
    plan_ids: Set[str] = {plan["id"] for plan in plan_records}

    dashboard = load_json(EXAMPLES_DIR / "dashboard_home.json")
    assinaturas = load_json(EXAMPLES_DIR / "assinaturas_pix.json")
    assinatura_rows = assinaturas.get("data", [])
    assinatura_ids: Set[str] = {row["id"] for row in assinatura_rows}
    assinaturas_por_usuario: Dict[str, List[dict]] = {}
    for row in assinatura_rows:
        assinaturas_por_usuario.setdefault(row.get("usuarioId"), []).append(row)

    charges = load_json(EXAMPLES_DIR / "cobrancas_pix.json").get("cobrancas", [])

    check(
        dashboard["usuario"]["id"] in users_by_id,
        "Usuário principal do dashboard existe em usuarios.json",
    )

    for spotlight in dashboard.get("destaquesAlunos", []):
        uid = spotlight.get("usuarioId")
        check(
            uid in users_by_id,
            f"Destaque {spotlight.get('nome')} vinculado ao usuário {uid}",
        )

    for card in dashboard.get("planosDestaque", []):
        pid = card.get("planoId")
        check(pid in plan_ids, f"Plano em destaque {pid} cadastrado em planos.json")

    for resumo in dashboard.get("assinaturasRecentes", []):
        aid = resumo.get("assinaturaId")
        uid = resumo.get("usuarioId")
        pid = resumo.get("planoId")
        check(aid in assinatura_ids, f"Assinatura recente {aid} existe nas seeds")
        check(uid in users_by_id, f"Assinatura {aid} aponta para usuário {uid}")
        check(pid in plan_ids, f"Assinatura {aid} aponta para plano {pid}")

    for row in assinatura_rows:
        uid = row.get("usuarioId")
        pid = row.get("planoId")
        check(uid in users_by_id, f"Assinatura {row['id']} tem usuário {uid}")
        check(pid in plan_ids, f"Assinatura {row['id']} tem plano {pid}")

    for charge in charges:
        pid = charge.get("planoId")
        aid = charge.get("assinaturaId")
        uid = charge.get("usuarioId")
        check(pid in plan_ids, f"Cobrança {charge['id']} usa plano {pid}")
        if aid is not None:
            check(aid in assinatura_ids, f"Cobrança {charge['id']} referencia assinatura {aid}")
        if uid is not None:
            check(uid in users_by_id, f"Cobrança {charge['id']} referencia usuário {uid}")
        if aid is not None and uid is not None and uid in users_by_id:
            assinaturas_do_usuario = assinaturas_por_usuario.get(uid, [])
            check(
                any(row["id"] == aid for row in assinaturas_do_usuario),
                f"Cobrança {charge['id']} pertence ao usuário {uid}",
            )

    flutter_planos = load_json(ROOT / "flutter" / "assets" / "data" / "planos.json")
    flutter_plan_map = {
        plan["id"]: plan for plan in flutter_planos.get("planos", [])
    }
    for doc_plan in plan_records:
        pid = doc_plan["id"]
        exists = pid in flutter_plan_map
        check(exists, f"Plano {pid} disponível no asset Flutter")
        if not exists:
            continue

        flutter_plan = flutter_plan_map[pid]
        mismatches: List[str] = []

        for key, expected_value in doc_plan.items():
            actual_value = flutter_plan.get(key)
            if actual_value != expected_value:
                mismatches.append(key)

        extra_keys = sorted(set(flutter_plan) - set(doc_plan))
        if extra_keys:
            mismatches.append(f"chaves extras: {', '.join(extra_keys)}")

        if mismatches:
            check(
                False,
                f"Plano {pid} no asset Flutter diverge dos docs ({', '.join(mismatches)})",
            )
        else:
            check(True, f"Plano {pid} no asset Flutter alinha com os docs")

    flutter_dashboard = load_json(
        ROOT / "flutter" / "assets" / "data" / "dashboard_home.json"
    )
    check(
        flutter_dashboard == dashboard,
        "Asset dashboard_home.json do Flutter sincronizado com docs/examples",
    )

    referenced_users_by_file: Dict[str, Set[str]] = defaultdict(set)

    def register_user_refs(filename: str, data) -> None:
        referenced_users_by_file[filename].update(collect_user_tokens(data))

    for example_file in sorted(EXAMPLES_DIR.glob("*.json")):
        data = load_json(example_file)
        register_user_refs(example_file.name, data)

    referenced_users_by_file.pop("usuarios.json", None)

    for filename, user_ids in sorted(referenced_users_by_file.items()):
        if not user_ids:
            check(True, f"{filename}: nenhum usuário referenciado")
            continue
        for uid in sorted(user_ids):
            check(uid in users_by_id, f"{filename}: usuário {uid} cadastrado em usuarios.json")

    if errors:
        print("\nForam encontradas inconsistências: ")
        for message in errors:
            print(f" - {message}")
        return 1

    print("\nTodas as referências entre app e CMS estão sincronizadas.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
