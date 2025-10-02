#!/usr/bin/env python3
"""Valida os assets JSON utilizados pelo app demo.

O objetivo é garantir que os arquivos em `flutter/assets/data/` estejam
sintaticamente corretos e consistentes entre si, evitando divergências com o
painel Strapi descrito na documentação.
"""

from __future__ import annotations

import argparse
import json
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Iterable, List, Mapping, MutableSet

ASSET_FILENAMES = {
    "planos": "planos.json",
    "dashboard": "dashboard_home.json",
    "operations": "operations_readiness.json",
    "questoes": "questoes.json",
    "simulados": "simulados.json",
}


@dataclass
class ValidationResult:
    errors: List[str]

    @property
    def ok(self) -> bool:
        return not self.errors

    def add(self, message: str) -> None:
        self.errors.append(message)

    def extend(self, messages: Iterable[str]) -> None:
        self.errors.extend(messages)


class AssetValidator:
    def __init__(self, assets_dir: Path) -> None:
        self.assets_dir = assets_dir
        self.errors: List[str] = []
        self.plan_ids: MutableSet[str] = set()
        self.questions_ids: MutableSet[str] = set()

    def validate(self) -> ValidationResult:
        self._ensure_files_exist()
        if self.errors:
            return ValidationResult(self.errors)

        planos = self._load_json("planos", expected_type=dict)
        dashboard = self._load_json("dashboard", expected_type=dict)
        operations = self._load_json("operations", expected_type=dict)
        questoes = self._load_json("questoes", expected_type=list)
        simulados = self._load_json("simulados", expected_type=list)

        self._validate_planos(planos)
        self._validate_dashboard(dashboard)
        self._validate_operations(operations)
        self._validate_questoes(questoes)
        self._validate_simulados(simulados)

        return ValidationResult(self.errors)
    def _ensure_files_exist(self) -> None:
        for logical_name, filename in ASSET_FILENAMES.items():
            path = self.assets_dir / filename
            if not path.exists():
                self.add_error(
                    f"Arquivo esperado não encontrado: {path} (alias '{logical_name}')"
                )

    def _load_json(self, alias: str, expected_type: type[Any]) -> Any:
        path = self.assets_dir / ASSET_FILENAMES[alias]
        try:
            with path.open("r", encoding="utf-8") as handler:
                data = json.load(handler)
        except json.JSONDecodeError as exc:
            self.add_error(f"JSON inválido em {path}: {exc}")
            return expected_type() if expected_type in (list, dict) else None

        if not isinstance(data, expected_type):
            self.add_error(
                f"O arquivo {path} deveria conter um {expected_type.__name__}, "
                f"mas recebeu {type(data).__name__}."
            )
            return expected_type() if expected_type in (list, dict) else None
        return data

    def _validate_planos(self, payload: Mapping[str, Any]) -> None:
        planos = payload.get("planos")
        if not isinstance(planos, list) or not planos:
            self.add_error("planos.json deve conter a chave 'planos' com ao menos um item.")
            return

        pago_detectado = False
        gratis_detectado = False
        for plan in planos:
            if not isinstance(plan, Mapping):
                self.add_error("Entrada inválida em planos.json: cada plano deve ser um objeto.")
                continue
            plan_id = plan.get("id")
            if not plan_id or not isinstance(plan_id, str):
                self.add_error("Plano sem 'id' válido encontrado em planos.json.")
                continue
            if plan_id in self.plan_ids:
                self.add_error(f"ID de plano duplicado: {plan_id}.")
            self.plan_ids.add(plan_id)

            tipo = plan.get("tipo")
            if tipo == "pago":
                pago_detectado = True
                pix = plan.get("pix")
                if not isinstance(pix, Mapping):
                    self.add_error(f"Plano pago '{plan_id}' sem bloco 'pix' válido.")
                else:
                    for field in ("chave", "codigoCopiaCola", "valor"):
                        if not pix.get(field):
                            self.add_error(
                                f"Plano pago '{plan_id}' sem campo obrigatório pix['{field}']."
                            )
            elif tipo in {"gratis", "gratis_aluno"}:
                gratis_detectado = True

        if not pago_detectado:
            self.add_error("Nenhum plano pago encontrado em planos.json.")
        if not gratis_detectado:
            self.add_error("Nenhum plano gratuito encontrado em planos.json.")

    def _validate_dashboard(self, payload: Mapping[str, Any]) -> None:
        usuario = payload.get("usuario")
        if not isinstance(usuario, Mapping):
            self.add_error("dashboard_home.json precisa da chave 'usuario'.")
            return

        assinatura = usuario.get("assinaturaAtual")
        if not isinstance(assinatura, Mapping):
            self.add_error("'usuario.assinaturaAtual' deve ser um objeto em dashboard_home.json.")
        else:
            plano_id = assinatura.get("planoId")
            if plano_id and plano_id not in self.plan_ids:
                self.add_error(
                    "planoId registrado em dashboard_home.json não consta em planos.json: "
                    f"{plano_id}"
                )

        atalhos = payload.get("atalhosRapidos", [])
        for idx, atalho in enumerate(atalhos):
            if not isinstance(atalho, Mapping):
                self.add_error(f"Atalho #{idx + 1} não é um objeto válido.")
                continue
            for field in ("icone", "titulo", "rota"):
                if not atalho.get(field):
                    self.add_error(
                        f"Atalho '{atalho}' está sem o campo obrigatório '{field}'."
                    )

    def _validate_operations(self, payload: Mapping[str, Any]) -> None:
        overall = payload.get("overall")
        if not isinstance(overall, Mapping):
            self.add_error("operations_readiness.json precisa da chave 'overall'.")
        components = payload.get("components")
        if not isinstance(components, list) or not components:
            self.add_error("operations_readiness.json deve listar componentes de readiness.")
            return

        total_weight = 0.0
        weighted_score = 0.0
        for component in components:
            if not isinstance(component, Mapping):
                self.add_error("Cada componente de readiness deve ser um objeto.")
                continue
            weight = float(component.get("weight", 0))
            percentage = float(component.get("percentage", 0))
            total_weight += weight
            weighted_score += weight * (percentage / 100)

        if total_weight > 0:
            expected_percentage = round(weighted_score / total_weight * 100, 2)
            reported = overall.get("percentage") if isinstance(overall, Mapping) else None
            if isinstance(reported, (int, float)) and abs(reported - expected_percentage) > 1:
                self.add_error(
                    "Percentual geral de readiness não condiz com a média ponderada dos componentes. "
                    f"Esperado ≈ {expected_percentage}, reportado = {reported}."
                )
    def _validate_questoes(self, payload: List[Any]) -> None:
        if not payload:
            self.add_error("questoes.json deve conter pelo menos uma questão.")
            return
        for question in payload:
            if not isinstance(question, Mapping):
                self.add_error("Questão inválida detectada: deve ser um objeto JSON.")
                continue
            qid = question.get("id")
            if not qid or not isinstance(qid, str):
                self.add_error("Questão sem 'id' válido encontrada.")
                continue
            if qid in self.questions_ids:
                self.add_error(f"ID de questão duplicado: {qid}.")
            self.questions_ids.add(qid)
            alternativas = question.get("alternativas")
            if not isinstance(alternativas, list) or not alternativas:
                self.add_error(f"Questão '{qid}' sem alternativas cadastradas.")

    def _validate_simulados(self, payload: List[Any]) -> None:
        if not payload:
            self.add_error("simulados.json deve conter ao menos um simulado.")
            return
        simulados_ids: MutableSet[str] = set()
        for simulado in payload:
            if not isinstance(simulado, Mapping):
                self.add_error("Simulado inválido detectado: deve ser um objeto JSON.")
                continue
            sid = simulado.get("id")
            if not sid or not isinstance(sid, str):
                self.add_error("Simulado sem 'id' válido encontrado.")
                continue
            if sid in simulados_ids:
                self.add_error(f"ID de simulado duplicado: {sid}.")
            simulados_ids.add(sid)
            configuracao = simulado.get("configuracao")
            if not isinstance(configuracao, Mapping):
                self.add_error(f"Simulado '{sid}' sem bloco 'configuracao'.")
            estatisticas = simulado.get("estatisticas")
            if not isinstance(estatisticas, Mapping):
                self.add_error(f"Simulado '{sid}' sem bloco 'estatisticas'.")

    def add_error(self, message: str) -> None:
        self.errors.append(message)


def parse_args(argv: list[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Valida os assets JSON do app Flutter.")
    parser.add_argument(
        "--assets-dir",
        type=Path,
        default=Path(__file__).resolve().parents[1] / "flutter" / "assets" / "data",
        help="Diretório onde os arquivos JSON residem (padrão: flutter/assets/data).",
    )
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    args = parse_args(argv or sys.argv[1:])
    validator = AssetValidator(args.assets_dir)
    result = validator.validate()
    if result.ok:
        print("Validação concluída sem erros. Todos os assets estão consistentes.")
        return 0
    print("Falhas encontradas durante a validação:\n")
    for item in result.errors:
        print(f"- {item}")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
