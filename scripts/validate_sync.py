#!/usr/bin/env python3
"""Validate sample data synchronization between app and CMS examples."""
from __future__ import annotations

import json
import re
from pathlib import Path
from collections import defaultdict
from typing import Dict, List, Set

ROOT = Path(__file__).resolve().parents[1]
EXAMPLES_DIR = ROOT / "docs" / "examples"


def load_json(path: Path):
    with path.open(encoding="utf-8") as handle:
        return json.load(handle)


def load_list(path: Path) -> List[dict]:
    data = load_json(path)
    if isinstance(data, list):
        return data
    if isinstance(data, dict):
        for key in ("data", "items", "registros", "entries", "lista", "metas", "cards"):
            value = data.get(key)
            if isinstance(value, list):
                return value
    raise ValueError(f"Não foi possível extrair uma lista de {path}")


def almost_equal(first: float, second: float, tolerance: float = 1e-6) -> bool:
    return abs(float(first) - float(second)) < tolerance


def ensure_list(value) -> List:
    if isinstance(value, list):
        return value
    return []


USER_TOKEN_PREFIXES: tuple[str, ...] = (
    "user-",
    "tutor-",
    "mentor-",
    "admin-",
    "superadmin-",
    "agente-",
)


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
    if isinstance(payload, str):
        for prefix in USER_TOKEN_PREFIXES:
            if payload.startswith(prefix):
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
    check(
        len(users_by_id) == len(users),
        "Usuários possuem identificadores únicos",
    )
    plan_records = load_json(EXAMPLES_DIR / "planos.json")
    plan_by_id: Dict[str, dict] = {}
    plan_ids: Set[str] = set()
    for plan in plan_records:
        pid = plan.get("id")
        check(pid is not None, "Plano listado possui identificador")
        if pid is None:
            continue
        check(pid not in plan_ids, f"Plano {pid} possui ID único")
        plan_ids.add(pid)
        plan_by_id[pid] = plan

        beneficios = plan.get("beneficios", [])
        check(
            isinstance(beneficios, list) and beneficios,
            f"Plano {pid} possui benefícios cadastrados",
        )

        tipo = plan.get("tipo")
        preco = plan.get("preco")
        pix_info = plan.get("pix")

        if tipo == "pago":
            check(
                isinstance(preco, (int, float)) and preco > 0,
                f"Plano pago {pid} tem preço maior que zero",
            )
            check(
                isinstance(pix_info, dict),
                f"Plano pago {pid} inclui metadados Pix",
            )
            if isinstance(pix_info, dict):
                for key in ("chave", "tipoChave", "codigoCopiaCola", "valor", "moeda"):
                    check(
                        pix_info.get(key) not in (None, ""),
                        f"Plano pago {pid} traz campo Pix '{key}'",
                    )
                if isinstance(pix_info.get("valor"), (int, float)) and isinstance(preco, (int, float)):
                    check(
                        abs(float(pix_info["valor"]) - float(preco)) < 1e-6,
                        f"Plano pago {pid} mantém preço e valor Pix alinhados",
                    )
        elif tipo == "gratis_aluno":
            check(preco == 0, f"Plano grátis {pid} permanece com preço zero")
            check(pix_info is None, f"Plano grátis {pid} não possui metadados Pix")
        else:
            check(
                tipo in {"pago", "gratis_aluno"},
                f"Plano {pid} possui tipo reconhecido",
            )

        status_aprovacao = plan.get("statusAprovacao")
        if status_aprovacao == "aprovado":
            check(
                plan.get("aprovadoPor") not in (None, "")
                and plan.get("aprovadoEm") not in (None, ""),
                f"Plano {pid} aprovado registra responsável e data",
            )
        elif status_aprovacao == "pendente":
            check(
                plan.get("aprovadoPor") in (None, "")
                and plan.get("aprovadoEm") in (None, ""),
                f"Plano {pid} pendente não possui dados de aprovação",
            )
        else:
            check(
                status_aprovacao in {"aprovado", "pendente", "reprovado"},
                f"Plano {pid} utiliza status de aprovação válido",
            )

    question_records = load_json(EXAMPLES_DIR / "questoes.json")
    questions_by_id: Dict[str, dict] = {}
    for question in question_records:
        qid = question.get("id")
        check(isinstance(qid, str) and qid, "Questão cadastrada possui identificador")
        if not isinstance(qid, str) or not qid:
            continue
        check(qid not in questions_by_id, f"Questão {qid} possui ID único")
        alternatives = ensure_list(question.get("alternativas"))
        check(alternatives, f"Questão {qid} possui alternativas cadastradas")
        if alternatives:
            correct_count = sum(1 for option in alternatives if isinstance(option, dict) and option.get("correta") is True)
            check(correct_count >= 1, f"Questão {qid} marca ao menos uma alternativa correta")
        questions_by_id[qid] = question

    dashboard = load_json(EXAMPLES_DIR / "dashboard_home.json")
    assinaturas = load_json(EXAMPLES_DIR / "assinaturas_pix.json")
    assinatura_rows = assinaturas.get("data", [])
    assinatura_by_id: Dict[str, dict] = {}
    assinatura_ids: Set[str] = {row["id"] for row in assinatura_rows}
    assinaturas_por_usuario: Dict[str, List[dict]] = {}
    for row in assinatura_rows:
        assinaturas_por_usuario.setdefault(row.get("usuarioId"), []).append(row)
        rid = row.get("id")
        if isinstance(rid, str) and rid:
            assinatura_by_id[rid] = row

    charges = load_json(EXAMPLES_DIR / "cobrancas_pix.json").get("cobrancas", [])
    charges_by_id: Dict[str, dict] = {}
    for charge in charges:
        cid = charge.get("id")
        if isinstance(cid, str) and cid:
            charges_by_id[cid] = charge

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
        plan_entry = plan_by_id.get(pid)
        if not plan_entry:
            continue
        expected_price = plan_entry.get("preco")
        card_price = card.get("preco")
        if isinstance(expected_price, (int, float)) and isinstance(card_price, (int, float)):
            check(
                almost_equal(expected_price, card_price),
                f"Plano em destaque {pid} replica preço dos docs",
            )
        expected_currency = plan_entry.get("moeda")
        if expected_currency:
            check(
                card.get("moeda") == expected_currency,
                f"Plano em destaque {pid} replica moeda dos docs",
            )
        expected_status = plan_entry.get("statusAprovacao")
        if expected_status:
            check(
                card.get("statusAprovacao") == expected_status,
                f"Plano em destaque {pid} replica status de aprovação",
            )

    for resumo in dashboard.get("assinaturasRecentes", []):
        aid = resumo.get("assinaturaId")
        uid = resumo.get("usuarioId")
        pid = resumo.get("planoId")
        check(aid in assinatura_ids, f"Assinatura recente {aid} existe nas seeds")
        check(uid in users_by_id, f"Assinatura {aid} aponta para usuário {uid}")
        check(pid in plan_ids, f"Assinatura {aid} aponta para plano {pid}")
        assinatura_entry = assinatura_by_id.get(aid)
        if assinatura_entry:
            resumo_status = resumo.get("status")
            if resumo_status:
                check(
                    assinatura_entry.get("status") == resumo_status,
                    f"Resumo de assinatura {aid} replica status da assinatura",
                )
            resumo_pagamento = resumo.get("statusPagamento")
            if resumo_pagamento:
                check(
                    assinatura_entry.get("statusPagamento") == resumo_pagamento,
                    f"Resumo de assinatura {aid} replica statusPagamento",
                )

    for row in assinatura_rows:
        uid = row.get("usuarioId")
        pid = row.get("planoId")
        assinatura_id = row.get("id", "<sem-id>")
        check(uid in users_by_id, f"Assinatura {assinatura_id} tem usuário {uid}")
        check(pid in plan_ids, f"Assinatura {assinatura_id} tem plano {pid}")

        metodo_pagamento = row.get("metodoPagamento")
        plano_entry = plan_by_id.get(pid)
        plano_tipo = plano_entry.get("tipo") if isinstance(plano_entry, dict) else None
        pix_info = row.get("pixInfo")
        cobranca_pix_id = row.get("cobrancaPixId")
        current_charge = (
            charges_by_id.get(cobranca_pix_id)
            if isinstance(cobranca_pix_id, str) and cobranca_pix_id
            else None
        )
        if cobranca_pix_id:
            check(
                cobranca_pix_id in charges_by_id,
                f"Assinatura {assinatura_id} referencia cobrança {cobranca_pix_id}",
            )
        if current_charge:
            charge_uid = current_charge.get("usuarioId")
            if charge_uid:
                check(
                    charge_uid == uid,
                    f"Assinatura {assinatura_id} alinhada ao usuário da cobrança {cobranca_pix_id}",
                )
            charge_plan_id = current_charge.get("planoId")
            if charge_plan_id:
                check(
                    charge_plan_id == pid,
                    f"Assinatura {assinatura_id} alinhada ao plano da cobrança {cobranca_pix_id}",
                )
            charge_subscription = current_charge.get("assinaturaId")
            if charge_subscription and charge_subscription != assinatura_id:
                check(
                    False,
                    f"Cobrança {cobranca_pix_id} aponta para assinatura {charge_subscription}, não {assinatura_id}",
                )

        if metodo_pagamento == "pix":
            check(
                plano_tipo == "pago",
                f"Assinatura {assinatura_id} PIX referencia plano pago",
            )
            check(
                row.get("cobrancaPixId") not in (None, ""),
                f"Assinatura {assinatura_id} PIX possui cobrança ativa",
            )
            check(
                isinstance(pix_info, dict),
                f"Assinatura {assinatura_id} PIX mantém dados pixInfo",
            )
            if isinstance(pix_info, dict) and isinstance(plano_entry, dict):
                plan_pix = plano_entry.get("pix") or {}
                if isinstance(plan_pix, dict):
                    check(
                        pix_info.get("chavePix") not in (None, ""),
                        f"Assinatura {assinatura_id} informa chave Pix",
                    )
                    check(
                        pix_info.get("moeda") == plan_pix.get("moeda"),
                        f"Assinatura {assinatura_id} reaproveita moeda Pix do plano",
                    )
                if isinstance(pix_info.get("valor"), (int, float)) and isinstance(plano_entry.get("preco"), (int, float)):
                    check(
                        abs(float(pix_info["valor"]) - float(plano_entry["preco"])) < 1e-6,
                        f"Assinatura {assinatura_id} mantém valor Pix alinhado ao plano",
                    )
                ultima_cobranca = pix_info.get("ultimaCobrancaId")
                if ultima_cobranca:
                    if cobranca_pix_id:
                        check(
                            ultima_cobranca == cobranca_pix_id,
                            f"Assinatura {assinatura_id} mantém ultimaCobrancaId igual à cobrança atual",
                        )
                    else:
                        check(
                            ultima_cobranca in charges_by_id,
                            f"Assinatura {assinatura_id} referencia ultimaCobrancaId existente",
                        )
                if current_charge:
                    codigo = pix_info.get("codigoCopiaCola")
                    charge_code = current_charge.get("codigoCopiaCola")
                    if codigo and charge_code:
                        check(
                            codigo == charge_code,
                            f"Assinatura {assinatura_id} replica codigoCopiaCola da cobrança {cobranca_pix_id}",
                        )
                    qr_url = pix_info.get("qrCodeUrl")
                    if qr_url:
                        check(
                            qr_url == (current_charge.get("qrCode") or {}).get("url"),
                            f"Assinatura {assinatura_id} replica qrCodeUrl da cobrança {cobranca_pix_id}",
                        )
                    qr_base64 = pix_info.get("qrCodeBase64")
                    if qr_base64:
                        check(
                            qr_base64 == (current_charge.get("qrCode") or {}).get("base64"),
                            f"Assinatura {assinatura_id} replica qrCodeBase64 da cobrança {cobranca_pix_id}",
                        )
                    charge_valor = current_charge.get("valor")
                    if isinstance(charge_valor, (int, float)) and isinstance(pix_info.get("valor"), (int, float)):
                        check(
                            abs(float(charge_valor) - float(pix_info["valor"])) < 1e-6,
                            f"Assinatura {assinatura_id} replica valor Pix da cobrança {cobranca_pix_id}",
                        )
                    charge_moeda = current_charge.get("moeda")
                    if charge_moeda and pix_info.get("moeda"):
                        check(
                            charge_moeda == pix_info.get("moeda"),
                            f"Assinatura {assinatura_id} replica moeda Pix da cobrança {cobranca_pix_id}",
                        )
        elif metodo_pagamento == "gratis_aluno":
            check(
                plano_tipo == "gratis_aluno",
                f"Assinatura {assinatura_id} gratuita referencia plano grátis",
            )
            check(
                pix_info in (None, {}),
                f"Assinatura {assinatura_id} gratuita não possui dados Pix",
            )
            aprovacao = row.get("aprovacao")
            check(
                isinstance(aprovacao, dict)
                and aprovacao.get("status") in {"aprovado", "pendente"},
                f"Assinatura {assinatura_id} gratuita registra aprovação",
            )
        else:
            check(
                metodo_pagamento in {"pix", "gratis_aluno"},
                f"Assinatura {assinatura_id} usa método de pagamento conhecido",
            )

    for charge in charges:
        pid = charge.get("planoId")
        aid = charge.get("assinaturaId")
        uid = charge.get("usuarioId")
        check(pid in plan_ids, f"Cobrança {charge['id']} usa plano {pid}")
        plano_entry = plan_by_id.get(pid)
        if isinstance(plano_entry, dict) and isinstance(charge.get("valor"), (int, float)) and isinstance(
            plano_entry.get("preco"), (int, float)
        ):
            check(
                abs(float(charge["valor"]) - float(plano_entry["preco"])) < 1e-6,
                f"Cobrança {charge['id']} replica valor do plano {pid}",
            )
        if aid is not None:
            check(aid in assinatura_ids, f"Cobrança {charge['id']} referencia assinatura {aid}")
            assinatura_entry = assinatura_by_id.get(aid)
            if assinatura_entry:
                assinatura_user = assinatura_entry.get("usuarioId")
                assinatura_plan = assinatura_entry.get("planoId")
                if assinatura_user:
                    check(
                        assinatura_user == uid,
                        f"Cobrança {charge['id']} alinhada ao usuário da assinatura {aid}",
                    )
                if assinatura_plan:
                    check(
                        assinatura_plan == pid,
                        f"Cobrança {charge['id']} alinhada ao plano da assinatura {aid}",
                    )
        if uid is not None:
            check(uid in users_by_id, f"Cobrança {charge['id']} referencia usuário {uid}")
        if aid is not None and uid is not None and uid in users_by_id:
            assinaturas_do_usuario = assinaturas_por_usuario.get(uid, [])
            check(
                any(row["id"] == aid for row in assinaturas_do_usuario),
                f"Cobrança {charge['id']} pertence ao usuário {uid}",
            )

        status = charge.get("status")
        if status == "confirmado":
            check(
                charge.get("confirmadoEm") not in (None, ""),
                f"Cobrança {charge['id']} confirmada possui timestamp",
            )
        elif status == "expirado":
            check(
                charge.get("expiradoEm") not in (None, ""),
                f"Cobrança {charge['id']} expirada registra data",
            )
        elif status == "pendente":
            check(
                charge.get("confirmadoEm") in (None, ""),
                f"Cobrança {charge['id']} pendente ainda não confirmada",
            )
        else:
            check(
                status in {"pendente", "confirmado", "expirado", "cancelado"},
                f"Cobrança {charge['id']} utiliza status conhecido",
            )

        for key in ("codigoCopiaCola", "chavePix"):
            check(
                charge.get(key) not in (None, ""),
                f"Cobrança {charge['id']} inclui campo obrigatório {key}",
            )

        qr_code = charge.get("qrCode")
        check(
            isinstance(qr_code, dict) and qr_code.get("url"),
            f"Cobrança {charge['id']} fornece dados de QR Code",
        )

    dashboard_user_id = dashboard.get("usuario", {}).get("id")
    assinatura_atual = dashboard.get("usuario", {}).get("assinaturaAtual", {})
    assinatura_atual_plano = assinatura_atual.get("planoId")
    if assinatura_atual_plano:
        check(
            assinatura_atual_plano in plan_ids,
            f"Plano atual do usuário {dashboard_user_id} existe nas seeds",
        )
        user_subscriptions = assinaturas_por_usuario.get(dashboard_user_id, [])
        check(
            user_subscriptions,
            f"Usuário {dashboard_user_id} possui assinaturas cadastradas",
        )
        matching_subscriptions = [
            sub
            for sub in user_subscriptions
            if sub.get("planoId") == assinatura_atual_plano
        ]
        check(
            matching_subscriptions,
            f"Plano atual do usuário {dashboard_user_id} encontrado entre as assinaturas",
        )
        status_atual = assinatura_atual.get("status")
        if status_atual:
            check(
                any(sub.get("status") == status_atual for sub in matching_subscriptions),
                f"Status da assinatura atual do usuário {dashboard_user_id} sincronizado",
            )
        renova_em = assinatura_atual.get("renovaEm")
        if renova_em:
            check(
                any(
                    isinstance(sub.get("fim"), str)
                    and sub["fim"].split("T")[0] == renova_em
                    for sub in matching_subscriptions
                ),
                f"Data de renovação do usuário {dashboard_user_id} alinha com a assinatura",
            )

    cadernos_examples = load_list(EXAMPLES_DIR / "cadernos.json")
    for caderno in cadernos_examples:
        caderno_id = caderno.get("id", "<sem-id>")
        usuario_id = caderno.get("usuarioId")
        check(
            usuario_id in users_by_id,
            f"Caderno {caderno_id} vinculado ao usuário {usuario_id}",
        )
        questoes_lista = ensure_list(caderno.get("questoes"))
        check(questoes_lista, f"Caderno {caderno_id} possui questões associadas")
        for questao in questoes_lista:
            questao_id = questao.get("id")
            check(
                questao_id in questions_by_id,
                f"Questão {questao_id} do caderno {caderno_id} cadastrada em questoes.json",
            )

    simulados_examples = load_list(EXAMPLES_DIR / "simulados.json")
    for simulado in simulados_examples:
        simulado_id = simulado.get("id", "<sem-id>")
        usuario_id = simulado.get("usuarioId")
        check(
            usuario_id in users_by_id,
            f"Simulado {simulado_id} vinculado ao usuário {usuario_id}",
        )

    desafios_examples = load_list(EXAMPLES_DIR / "desafios.json")
    desafio_ids: Set[str] = set()
    for desafio in desafios_examples:
        desafio_id = desafio.get("id", "")
        check(
            isinstance(desafio_id, str) and desafio_id,
            "Desafio possui identificador",
        )
        if not isinstance(desafio_id, str) or not desafio_id:
            continue
        check(desafio_id not in desafio_ids, f"Desafio {desafio_id} possui ID único")
        desafio_ids.add(desafio_id)

        organizador_id = desafio.get("organizadorId")
        check(
            isinstance(organizador_id, str) and organizador_id in users_by_id,
            f"Desafio {desafio_id} possui organizador cadastrado",
        )
        participantes = ensure_list(desafio.get("participantes"))
        check(participantes, f"Desafio {desafio_id} possui participantes")
        for participante in participantes:
            participante_id = participante.get("usuarioId")
            check(
                isinstance(participante_id, str)
                and participante_id in users_by_id,
                f"Participante {participante_id} do desafio {desafio_id} cadastrado em usuarios.json",
            )
            posicao = participante.get("posicao")
            check(
                isinstance(posicao, int) and posicao > 0,
                f"Participante {participante_id} do desafio {desafio_id} possui posição válida",
            )
            questoes = participante.get("questoesResolvidas")
            if questoes is not None:
                check(
                    isinstance(questoes, int) and questoes >= 0,
                    f"Participante {participante_id} do desafio {desafio_id} possui questões numéricas",
                )
            acertos = participante.get("acertos")
            if acertos is not None:
                check(
                    isinstance(acertos, int) and acertos >= 0,
                    f"Participante {participante_id} do desafio {desafio_id} possui acertos numéricos",
                )

    mentorias_examples = load_list(EXAMPLES_DIR / "mentorias.json")
    mentoria_ids: Set[str] = set()
    for mentoria in mentorias_examples:
        mentoria_id = mentoria.get("id", "")
        check(
            isinstance(mentoria_id, str) and mentoria_id,
            "Mentoria possui identificador",
        )
        if not isinstance(mentoria_id, str) or not mentoria_id:
            continue
        check(mentoria_id not in mentoria_ids, f"Mentoria {mentoria_id} possui ID único")
        mentoria_ids.add(mentoria_id)

        mentor_id = mentoria.get("mentorId")
        check(
            isinstance(mentor_id, str) and mentor_id in users_by_id,
            f"Mentoria {mentoria_id} referencia mentor cadastrado",
        )

        slots = ensure_list(mentoria.get("slots"))
        check(slots, f"Mentoria {mentoria_id} possui slots cadastrados")
        for slot in slots:
            reservado_por = slot.get("reservadoPor")
            if reservado_por:
                check(
                    isinstance(reservado_por, str)
                    and reservado_por in users_by_id,
                    f"Mentoria {mentoria_id} slot reservado por usuário cadastrado",
                )
            status = slot.get("status")
            check(
                status in {"reservado", "disponivel", "cancelado"},
                f"Mentoria {mentoria_id} slot possui status conhecido",
            )

    metas_examples = load_list(EXAMPLES_DIR / "metas.json")
    for meta in metas_examples:
        meta_id = meta.get("id", "<sem-id>")
        usuario_id = meta.get("usuarioId")
        check(
            usuario_id in users_by_id,
            f"Meta {meta_id} vinculada ao usuário {usuario_id}",
        )

    desempenho = load_json(EXAMPLES_DIR / "desempenho.json")
    usuario_desempenho = desempenho.get("usuarioId")
    check(
        usuario_desempenho in users_by_id,
        f"Painel de desempenho referencia usuário {usuario_desempenho}",
    )

    lives_examples = load_list(EXAMPLES_DIR / "lives.json")
    lives_by_link: Dict[str, dict] = {
        entry.get("link"): entry for entry in lives_examples if entry.get("link")
    }
    for live in dashboard.get("proximasLives", []):
        link = live.get("link")
        check(link in lives_by_link, f"Live {link} presente em lives.json")
        matched_live = lives_by_link.get(link)
        if not matched_live:
            continue
        live_title = live.get("titulo")
        matched_title = matched_live.get("titulo")
        if live_title and matched_title:
            check(
                live_title.strip().lower() == matched_title.strip().lower(),
                f"Live {link} replica título do catálogo",
            )
        live_instrutor = live.get("instrutor")
        if live_instrutor and matched_live.get("instrutor"):
            check(
                live_instrutor == matched_live.get("instrutor"),
                f"Live {link} replica instrutor do catálogo",
            )
        live_data = live.get("data")
        inicio = matched_live.get("inicio")
        if live_data and inicio:
            check(
                live_data == inicio,
                f"Live {link} replica data de início do catálogo",
            )

    biblioteca_entries = load_list(EXAMPLES_DIR / "biblioteca.json")
    check(biblioteca_entries, "Materiais de biblioteca disponíveis em biblioteca.json")
    formatos_validos = {"pdf", "video", "audio", "checklist", "modelo"}
    status_validos = {"rascunho", "publicado", "arquivado"}

    for entry in biblioteca_entries:
        mid = entry.get("id")
        check(mid not in (None, ""), "Material da biblioteca possui identificador")
        if not mid:
            continue

        check(entry.get("titulo"), f"Material {mid} possui título")
        check(entry.get("arquivoUrl"), f"Material {mid} possui arquivoUrl")

        formato = entry.get("formato")
        check(
            formato in formatos_validos,
            f"Material {mid} utiliza formato válido",
        )

        tags = entry.get("tags", [])
        check(
            isinstance(tags, list) and len(tags) > 0,
            f"Material {mid} possui tags cadastradas",
        )

        disciplinas = entry.get("disciplinaIds", [])
        check(
            isinstance(disciplinas, list) and len(disciplinas) > 0,
            f"Material {mid} referencia disciplinas",
        )

        assunto_ids = entry.get("assuntoIds", [])
        check(
            isinstance(assunto_ids, list),
            f"Material {mid} referencia assuntos como lista",
        )

        status = entry.get("status")
        check(
            status in status_validos,
            f"Material {mid} utiliza status conhecido",
        )

        autor = entry.get("autor")
        if autor:
            autor_id = autor.get("id")
            check(
                autor_id not in (None, "") and autor.get("nome") not in (None, ""),
                f"Material {mid} inclui autor com id e nome",
            )
            if autor_id:
                check(
                    autor_id in users_by_id,
                    f"Material {mid} autor {autor_id} cadastrado em usuarios.json",
                )

        for numeric_key in ("downloads", "visualizacoes"):
            value = entry.get(numeric_key)
            check(
                isinstance(value, int) and value >= 0,
                f"Material {mid} possui {numeric_key} numérico",
            )

        publicado_em = entry.get("publicadoEm")
        if publicado_em:
            check(
                re.match(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$", publicado_em) is not None,
                f"Material {mid} possui publicadoEm em ISO-8601",
            )

    dataset_presence_checks = [
        (
            "metas.json",
            "Metas de estudo",
            lambda entry: entry.get("usuarioId") == dashboard_user_id,
        ),
        (
            "cadernos.json",
            "Cadernos de estudo",
            lambda entry: entry.get("usuarioId") == dashboard_user_id,
        ),
        (
            "simulados.json",
            "Simulados",
            lambda entry: entry.get("usuarioId") == dashboard_user_id,
        ),
        (
            "desafios.json",
            "Desafios",
            lambda entry: any(
                participante.get("usuarioId") == dashboard_user_id
                for participante in entry.get("participantes", [])
            ),
        ),
        (
            "mentorias.json",
            "Mentorias",
            lambda entry: any(
                slot.get("reservadoPor") == dashboard_user_id
                for slot in entry.get("slots", [])
            ),
        ),
    ]

    for filename, description, predicate in dataset_presence_checks:
        entries = load_list(EXAMPLES_DIR / filename)
        check(entries, f"{description} possuem dados em {filename}")
        check(
            any(predicate(entry) for entry in entries),
            f"{description} incluem o usuário {dashboard_user_id}",
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

    flutter_questions = load_json(
        ROOT / "flutter" / "assets" / "data" / "questoes.json"
    )
    check(
        flutter_questions == load_json(EXAMPLES_DIR / "questoes.json"),
        "Asset questoes.json do Flutter sincronizado com docs/examples",
    )

    flutter_simulados = load_json(
        ROOT / "flutter" / "assets" / "data" / "simulados.json"
    )
    check(
        flutter_simulados == load_json(EXAMPLES_DIR / "simulados.json"),
        "Asset simulados.json do Flutter sincronizado com docs/examples",
    )

    operations_example = load_json(EXAMPLES_DIR / "operations_readiness.json")
    flutter_operations = load_json(
        ROOT / "flutter" / "assets" / "data" / "operations_readiness.json"
    )
    check(
        flutter_operations == operations_example,
        "Asset operations_readiness.json do Flutter sincronizado com docs/examples",
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

    openapi_path = ROOT / "docs" / "openapi.yaml"
    openapi_text = openapi_path.read_text(encoding="utf-8")
    mega_text = (ROOT / "docs" / "mega-resumo-codex.md").read_text(encoding="utf-8")
    readme_text = (ROOT / "README.md").read_text(encoding="utf-8")

    openapi_match = re.search(r"androidApkReadinessPercent:\s*(\d+)", openapi_text)
    mega_match = re.search(
        r"Progresso estimado para APK Android:\**\s*(\d+)",
        mega_text,
    )
    readme_match = re.search(r"indica \*\*(\d+)", readme_text)

    if not openapi_match:
        check(False, "Percentual de prontidão ausente na OpenAPI")
        openapi_percent = None
    else:
        openapi_percent = int(openapi_match.group(1))

    if not mega_match:
        check(False, "Percentual de prontidão ausente no mega resumo")
    elif openapi_percent is not None:
        mega_percent = int(mega_match.group(1))
        check(
            openapi_percent == mega_percent,
            "Percentual de prontidão consistente entre OpenAPI e mega resumo",
        )

    if not readme_match:
        check(False, "Percentual de prontidão ausente no README")
    elif openapi_percent is not None:
        readme_percent = int(readme_match.group(1))
        check(
            openapi_percent == readme_percent,
            "Percentual de prontidão consistente entre OpenAPI e README",
        )

    example_matches = re.findall(r"externalValue:\s*(.+)", openapi_text)
    for raw_path in example_matches:
        normalized = raw_path.strip().strip("'\"")
        if normalized.startswith("./"):
            normalized = normalized[2:]
        example_path = openapi_path.parent / normalized
        check(example_path.exists(), f"Exemplo {normalized} referenciado na OpenAPI existe")
        if example_path.suffix.lower() == ".json" and example_path.exists():
            try:
                load_json(example_path)
            except json.JSONDecodeError as exc:  # pragma: no cover - defensive
                check(False, f"Exemplo JSON {normalized} é válido ({exc})")
            else:
                check(True, f"Exemplo JSON {normalized} válido")

    if errors:
        print("\nForam encontradas inconsistências: ")
        for message in errors:
            print(f" - {message}")
        return 1

    print("\nTodas as referências entre app e CMS estão sincronizadas.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
