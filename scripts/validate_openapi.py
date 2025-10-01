#!/usr/bin/env python3
"""Validate structural consistency of docs/openapi.yaml."""
from __future__ import annotations

from datetime import date, datetime
from pathlib import Path
from typing import Dict

try:
    import yaml
except ModuleNotFoundError as exc:  # pragma: no cover - defensive import guard
    raise SystemExit(
        "Dependência PyYAML ausente. Execute 'pip install -r requirements-dev.txt' antes de rodar o validador."
    ) from exc

ROOT = Path(__file__).resolve().parents[1]
OPENAPI_PATH = ROOT / "docs" / "openapi.yaml"


def _load_openapi() -> Dict[str, object]:
    with OPENAPI_PATH.open(encoding="utf-8") as handle:
        return yaml.safe_load(handle)


def _normalise_method(method: str) -> str:
    return method.lower()


HTTP_METHODS = {
    "get",
    "put",
    "post",
    "delete",
    "options",
    "head",
    "patch",
    "trace",
}


def main() -> int:
    document = _load_openapi()
    errors: list[str] = []

    info = document.get("info", {})
    status = info.get("x-projectStatus", {}) if isinstance(info, dict) else {}

    percent = status.get("androidApkReadinessPercent")
    if not isinstance(percent, int) or not (0 <= percent <= 100):
        errors.append(
            "androidApkReadinessPercent deve ser um inteiro entre 0 e 100"
        )

    last_updated = status.get("lastUpdated")
    if isinstance(last_updated, str):
        try:
            datetime.strptime(last_updated, "%Y-%m-%d")
        except ValueError:
            errors.append("lastUpdated deve seguir o formato YYYY-MM-DD")
    elif isinstance(last_updated, date):
        pass
    elif hasattr(last_updated, "isoformat"):
        try:
            str(last_updated.isoformat())
        except Exception:  # pragma: no cover - defensive guard
            errors.append("lastUpdated deve ser serializável para data (YYYY-MM-DD)")
    else:
        errors.append("lastUpdated deve ser uma data no formato YYYY-MM-DD")

    notes = status.get("notes")
    if notes is not None and not isinstance(notes, str):
        errors.append("notes deve ser uma string quando presente")

    global_tags = set()
    for tag in document.get("tags", []) or []:
        if isinstance(tag, dict) and tag.get("name"):
            global_tags.add(tag["name"])

    seen_operation_ids: Dict[str, str] = {}
    for path, path_item in (document.get("paths") or {}).items():
        if not isinstance(path_item, dict):
            continue
        for method, operation in path_item.items():
            if _normalise_method(method) not in HTTP_METHODS:
                continue
            if not isinstance(operation, dict):
                continue

            op_id = operation.get("operationId")
            method_label = f"{method.upper()} {path}"
            if not op_id or not isinstance(op_id, str):
                errors.append(f"{method_label} deve definir operationId")
            else:
                if op_id in seen_operation_ids:
                    errors.append(
                        "operationId duplicado '{op}' encontrado em {first} e {second}".format(
                            op=op_id,
                            first=seen_operation_ids[op_id],
                            second=method_label,
                        )
                    )
                else:
                    seen_operation_ids[op_id] = method_label

            tags = operation.get("tags")
            if not isinstance(tags, list) or not tags:
                errors.append(f"{method_label} deve declarar ao menos uma tag")
            else:
                for tag in tags:
                    if tag not in global_tags:
                        errors.append(
                            f"{method_label} utiliza tag desconhecida '{tag}'"
                        )

            responses = operation.get("responses")
            if not isinstance(responses, dict) or not responses:
                errors.append(f"{method_label} deve declarar respostas")

    schemas = ((document.get("components") or {}).get("schemas") or {})
    for name, schema in schemas.items():
        if not isinstance(schema, dict):
            continue
        required = schema.get("required")
        properties = (
            schema.get("properties") if isinstance(schema.get("properties"), dict) else {}
        )
        if isinstance(required, list):
            for prop in required:
                if not isinstance(prop, str):
                    continue
                if prop not in properties:
                    errors.append(
                        f"Schema {name} requer campo '{prop}' que não está listado em properties"
                    )
        if schema.get("type") == "object" and not properties and "allOf" not in schema:
            errors.append(
                f"Schema {name} do tipo object deve listar propriedades ou usar allOf"
            )

        enum = schema.get("enum")
        if isinstance(enum, list) and len(enum) != len(set(enum)):
            errors.append(f"Schema {name} possui valores duplicados em enum")

    examples = ((document.get("components") or {}).get("examples") or {})
    for name, example in examples.items():
        if not isinstance(example, dict):
            continue
        if "externalValue" in example and "value" in example:
            errors.append(
                f"Exemplo {name} não deve definir value e externalValue simultaneamente"
            )
        if "externalValue" not in example and "value" not in example:
            errors.append(
                f"Exemplo {name} deve definir value ou externalValue"
            )

    if errors:
        print("Foram encontradas inconsistências no contrato OpenAPI:\n")
        for message in errors:
            print(f" - {message}")
        return 1

    print("Contrato OpenAPI validado com sucesso.")
    print(f"Total de operações verificadas: {len(seen_operation_ids)}")
    print(f"Total de schemas analisados: {len(schemas)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
