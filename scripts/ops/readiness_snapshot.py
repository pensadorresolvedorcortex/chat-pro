#!/usr/bin/env python3
"""Generate a readiness snapshot consolidating Flutter iOS, Strapi and operations progress."""

from __future__ import annotations

import argparse
import json
import sys
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Iterable, List

ROOT = Path(__file__).resolve().parents[2]
EXAMPLES_DIR = ROOT / "docs" / "examples"
OPERATIONS_EXAMPLE = EXAMPLES_DIR / "operations_readiness.json"


@dataclass
class Check:
  key: str
  label: str
  done: bool
  details: str | None = None

  def to_dict(self) -> Dict[str, object]:
    payload: Dict[str, object] = {
      "key": self.key,
      "label": self.label,
      "status": "done" if self.done else "todo",
    }
    if self.details:
      payload["details"] = self.details
    return payload


def load_json(path: Path) -> Dict[str, object]:
  return json.loads(path.read_text(encoding="utf-8"))


def flutter_checks(repo_root: Path) -> List[Check]:
  return [
    Check(
      "notificationsChannel",
      "AppDelegate com canais Pix e notificações ricas",
      (repo_root / "flutter/ios/Runner/AppDelegate.swift").exists(),
    ),
    Check(
      "pixPaywallCache",
      "Paywall Pix com cache Hive",
      (repo_root / "flutter/lib/features/paywall/data/pix_charge_store.dart").exists()
      and (repo_root / "flutter/lib/features/paywall/data/plan_cache_store.dart").exists(),
    ),
    Check(
      "studyModules",
      "Módulos de estudo implementados",
      (repo_root / "flutter/lib/features/questions").exists()
      and (repo_root / "flutter/lib/features/simulados").exists(),
    ),
    Check(
      "studyAssets",
      "Assets locais de questões e simulados empacotados",
      (repo_root / "flutter/assets/data/questoes.json").exists()
      and (repo_root / "flutter/assets/data/simulados.json").exists(),
    ),
    Check(
      "instrumentedTests",
      "Testes widget/instrumentados Pix",
      (repo_root / "flutter/test/features/paywall").exists(),
    ),
  ]


def load_counts() -> Dict[str, int]:
  plans = load_json(EXAMPLES_DIR / "planos.json")
  subscriptions = load_json(EXAMPLES_DIR / "assinaturas_pix.json")
  charges = load_json(EXAMPLES_DIR / "cobrancas_pix.json")

  if isinstance(plans, list):
    plan_records = plans
  else:
    plan_records = plans.get("planos") or []

  if isinstance(subscriptions, list):
    subscription_records = subscriptions
  else:
    subscription_records = (
      subscriptions.get("assinaturas")
      or subscriptions.get("data")
      or []
    )

  if isinstance(charges, list):
    charge_records = charges
  else:
    charge_records = charges.get("cobrancas") or charges.get("data") or []

  planos_total = len(plan_records)
  planos_gratis_aprovados = sum(
    1
    for plan in plan_records
    if plan.get("tipo") == "gratis_aluno" and plan.get("statusAprovacao") == "aprovado"
  )
  assinaturas_ativas = sum(1 for item in subscription_records if item.get("status") == "ativa")
  assinaturas_pendentes = sum(1 for item in subscription_records if item.get("status") == "pendente")
  cobrancas_confirmadas = sum(1 for item in charge_records if item.get("status") == "confirmado")
  cobrancas_pendentes = sum(1 for item in charge_records if item.get("status") == "pendente")

  return {
    "planosTotal": planos_total,
    "planosGratisAprovados": planos_gratis_aprovados,
    "assinaturasAtivas": assinaturas_ativas,
    "assinaturasPendentes": assinaturas_pendentes,
    "cobrancasConfirmadas": cobrancas_confirmadas,
    "cobrancasPendentes": cobrancas_pendentes,
  }


def strapi_checks(counts: Dict[str, int]) -> List[Check]:
  return [
    Check(
      "plansSeeded",
      "Planos Pix cadastrados",
      counts.get("planosTotal", 0) > 0,
      f"Total: {counts.get('planosTotal', 0)}",
    ),
    Check(
      "freePlanApproved",
      "Plano Grátis para Alunos aprovado",
      counts.get("planosGratisAprovados", 0) > 0,
      f"Aprovados: {counts.get('planosGratisAprovados', 0)}",
    ),
    Check(
      "activeSubscriptions",
      "Assinaturas Pix ativas",
      counts.get("assinaturasAtivas", 0) > 0,
      f"Ativas: {counts.get('assinaturasAtivas', 0)}",
    ),
    Check(
      "confirmedCharges",
      "Cobranças Pix confirmadas",
      counts.get("cobrancasConfirmadas", 0) > 0,
      f"Confirmadas: {counts.get('cobrancasConfirmadas', 0)}",
    ),
  ]


def operations_checks(repo_root: Path) -> List[Check]:
  return [
    Check(
      "pixAuditScript",
      "Script de auditoria Pix disponível",
      (repo_root / "scripts/ops/pix_ops_audit.py").exists(),
    ),
    Check(
      "readinessScript",
      "Script de snapshot de prontidão",
      (repo_root / "scripts/ops/readiness_snapshot.py").exists(),
    ),
    Check(
      "releaseRunbook",
      "Runbook de lançamento publicado",
      (repo_root / "docs/release-runbook.md").exists(),
    ),
    Check(
      "finalizationPlaybook",
      "Playbook de finalização disponível",
      (repo_root / "docs/finalizacao-app.md").exists(),
    ),
  ]


def compute_percentage(baseline: int, checks: Iterable[Check]) -> Dict[str, object]:
  checks_list = list(checks)
  total = len(checks_list)
  done = sum(1 for check in checks_list if check.done)
  computed = int(round((done / total) * 100)) if total > 0 else baseline
  percentage = max(baseline, computed)
  pending = [check.key for check in checks_list if not check.done]
  return {
    "percentage": percentage,
    "computed": computed,
    "checks": [check.to_dict() for check in checks_list],
    "pending": pending,
  }


def is_check_done(checks_index: Dict[str, Dict[str, Any]], key: str) -> bool:
  status = checks_index.get(key, {}).get("status")
  return status == "done"


def parse_iso(date_str: str | None) -> datetime | None:
  if not date_str:
    return None
  try:
    cleaned = date_str.replace("Z", "+00:00")
    return datetime.fromisoformat(cleaned)
  except ValueError:
    return None


def format_iso(value: datetime | None) -> str | None:
  if value is None:
    return None
  return value.astimezone(timezone.utc).isoformat().replace("+00:00", "Z")


def to_float(value: Any) -> float | None:
  if isinstance(value, (int, float)):
    return float(value)
  if isinstance(value, str):
    try:
      return float(value.replace(',', '.'))
    except ValueError:
      return None
  return None


def to_int(value: Any) -> int | None:
  float_value = to_float(value)
  if float_value is None:
    return None
  return int(round(float_value))


def enrich_milestones(
  milestones: List[Dict[str, Any]],
  counts: Dict[str, int],
  checks_index: Dict[str, Dict[str, Any]],
) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []
  for milestone in milestones:
    milestone_id = milestone.get("id") or "milestone"
    blockers = list({b for b in milestone.get("blockers", []) if isinstance(b, str)})
    status = milestone.get("status") or "pending"
    progress = 0.0

    if milestone_id == "ios_notifications_channel":
      notifications_ready = is_check_done(checks_index, "notificationsChannel")
      caches_ready = is_check_done(checks_index, "pixPaywallCache")
      modules_ready = is_check_done(checks_index, "studyModules")
      if notifications_ready:
        status = "done"
        progress = 1.0
      else:
        if caches_ready:
          progress += 0.4
        if modules_ready:
          progress += 0.3
        status = "in_progress" if progress > 0 else "pending"
        blocker = "Habilitar canal de notificações Pix no iOS"
        if blocker not in blockers:
          blockers.append(blocker)
    elif milestone_id == "pix_checkout_live":
      plans_seeded = counts.get("planosTotal", 0) > 0
      free_plan_ready = is_check_done(checks_index, "freePlanApproved")
      active_subscriptions = counts.get("assinaturasAtivas", 0) > 0
      confirmed_charges = counts.get("cobrancasConfirmadas", 0) > 0

      if plans_seeded:
        progress += 0.25
      else:
        blockers.append("Publicar planos Pix no CMS")

      if free_plan_ready:
        progress += 0.15
      else:
        blockers.append("Aprovar Plano Grátis para Alunos")

      if active_subscriptions:
        progress += 0.25
      else:
        blockers.append("Ativar assinaturas Pix reais")

      if confirmed_charges:
        progress += 0.35
      else:
        blockers.append("Confirmar ao menos uma cobrança Pix")

      if all([plans_seeded, free_plan_ready, active_subscriptions, confirmed_charges]):
        status = "done"
        progress = 1.0
      elif progress > 0:
        status = "in_progress"
      else:
        status = "pending"
    elif milestone_id == "ops_observability":
      audit_ready = is_check_done(checks_index, "pixAuditScript")
      snapshot_ready = is_check_done(checks_index, "readinessScript")
      runbook_ready = is_check_done(checks_index, "releaseRunbook")
      checklist_ready = is_check_done(checks_index, "finalizationPlaybook")
      completed = sum(1 for flag in [audit_ready, snapshot_ready, runbook_ready, checklist_ready] if flag)
      progress = completed / 4 if completed else 0.0
      if completed == 4:
        status = "done"
        progress = 1.0
      elif completed > 0:
        status = "in_progress"
      else:
        status = "pending"
      if not runbook_ready:
        blockers.append("Publicar runbook operacional atualizado")
      if not checklist_ready:
        blockers.append("Finalizar checklist de finalização")
    else:
      completion = milestone.get("completion")
      if isinstance(completion, (int, float)):
        progress = float(completion) / 100.0

    completion_value = 100 if status == "done" else max(10, round(min(progress, 1.0) * 100))
    target_date = parse_iso(milestone.get("targetDate"))
    overdue = bool(target_date and status != "done" and target_date.astimezone(timezone.utc) < datetime.now(timezone.utc))

    enriched.append(
      {
        **milestone,
        "status": status,
        "completion": completion_value,
        "overdue": overdue,
        "blockers": blockers,
      }
    )

  return enriched


def enrich_alerts(
  alerts: List[Dict[str, Any]],
  counts: Dict[str, int],
  checks_index: Dict[str, Dict[str, Any]],
) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []
  for alert in alerts:
    alert_id = alert.get("id") or "alert"
    level = alert.get("level") or "info"
    message = alert.get("message") or "Alerta operacional"
    details = alert.get("details")
    active = alert.get("active", True)

    if alert_id == "pending_pix_charges":
      pending = counts.get("cobrancasPendentes", 0)
      active = pending > 0
      if active:
        details = f"Há {pending} cobranças Pix pendentes aguardando conciliação."
    elif alert_id == "missing_ios_tests":
      active = not is_check_done(checks_index, "instrumentedTests")
      if active and not details:
        details = "Implemente a suíte mínima de testes instrumentados para Pix no iOS."

    if not active:
      continue

    enriched.append(
      {
        **alert,
        "level": level,
        "message": message,
        "details": details,
        "active": True,
      }
    )

  return enriched


def enrich_incidents(incidents: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []
  now = datetime.now(timezone.utc)

  for incident in incidents:
    if not isinstance(incident, dict):
      continue

    status = str(incident.get("status") or "").lower()
    if status not in {"investigating", "monitoring", "resolved"}:
      status = "resolved" if incident.get("resolvedAt") else "monitoring"

    impact = str(incident.get("impact") or "").lower()
    if impact not in {"none", "minor", "major", "critical"}:
      impact = "minor"

    started_at = parse_iso(incident.get("startedAt"))
    resolved_at = parse_iso(incident.get("resolvedAt"))
    updated_at = parse_iso(incident.get("updatedAt")) or resolved_at or now

    duration = incident.get("durationMinutes")
    if duration is None and started_at is not None:
      reference = resolved_at or updated_at or now
      duration = max(1, int(round((reference - started_at).total_seconds() / 60)))

    actions = [
      str(action)
      for action in incident.get("actions", [])
      if isinstance(action, str) and action.strip()
    ]

    active = incident.get("active", True)
    if status == "resolved":
      active = False

    enriched.append(
      {
        **incident,
        "status": status,
        "impact": impact,
        "startedAt": format_iso(started_at),
        "resolvedAt": format_iso(resolved_at),
        "updatedAt": format_iso(updated_at),
        "durationMinutes": duration,
        "actions": actions,
        "active": bool(active),
      }
    )

  enriched.sort(
    key=lambda item: (
      0 if item.get("active") else 1,
      -(
        parse_iso(item.get("startedAt"))
        or parse_iso(item.get("updatedAt"))
        or datetime.fromtimestamp(0, timezone.utc)
      ).timestamp(),
    )
  )

  return enriched


def enrich_maintenance(windows: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []
  now = datetime.now(timezone.utc)

  for window in windows:
    if not isinstance(window, dict):
      continue

    status = str(window.get("status") or "").lower()
    start_at = parse_iso(window.get("windowStart"))
    end_at = parse_iso(window.get("windowEnd"))

    if status not in {"scheduled", "in_progress", "in-progress", "completed"}:
      if end_at and end_at < now:
        status = "completed"
      elif start_at and start_at <= now:
        status = "in_progress"
      else:
        status = "scheduled"

    if status == "in-progress":
      status = "in_progress"

    impact = str(window.get("impact") or "").lower()
    if impact not in {"none", "minor", "major", "critical"}:
      impact = "minor"

    duration = window.get("durationMinutes")
    if duration is None and start_at and end_at:
      duration = max(1, int(round((end_at - start_at).total_seconds() / 60)))

    systems = [
      str(system)
      for system in window.get("systems", [])
      if isinstance(system, str) and system.strip()
    ]

    enriched.append(
      {
        **window,
        "status": status,
        "impact": impact,
        "windowStart": format_iso(start_at),
        "windowEnd": format_iso(end_at),
        "durationMinutes": duration,
        "systems": systems,
        "isActive": window.get("isActive", status == "in_progress"),
        "isUpcoming": window.get(
          "isUpcoming",
          status == "scheduled" and start_at is not None and start_at > now,
        ),
      }
    )

  enriched.sort(
    key=lambda item: (
      0 if item.get("isActive") else 1 if item.get("isUpcoming") else 2,
      (
        parse_iso(item.get("windowStart"))
        or datetime.fromtimestamp(0, timezone.utc)
      ).timestamp(),
    )
  )

  return enriched


def enrich_on_call(entries: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []
  now = datetime.now(timezone.utc)

  for entry in entries:
    if not isinstance(entry, dict):
      continue

    started_at = parse_iso(entry.get("startedAt"))
    ends_at = parse_iso(entry.get("endsAt"))
    status = str(entry.get("status") or "").lower()
    primary = bool(entry.get("primary"))

    if status not in {"active", "standby", "offline"}:
      if started_at and started_at <= now and (not ends_at or ends_at >= now):
        status = "active"
      elif started_at and started_at > now:
        status = "standby"
      elif ends_at and ends_at < now:
        status = "offline"
      else:
        status = "active" if primary else "standby"

    duration_minutes = entry.get("shiftDurationMinutes")
    if duration_minutes is None and started_at and ends_at:
      duration_minutes = max(1, int(round((ends_at - started_at).total_seconds() / 60)))
    else:
      parsed_duration = to_int(duration_minutes)
      duration_minutes = parsed_duration if parsed_duration is not None else None

    enriched.append(
      {
        **entry,
        "name": str(entry.get("name") or "Plantonista").strip(),
        "role": str(entry.get("role") or "On-call").strip(),
        "contact": str(entry.get("contact") or "").strip(),
        "status": status,
        "primary": primary,
        "startedAt": format_iso(started_at),
        "endsAt": format_iso(ends_at),
        "shiftDurationMinutes": duration_minutes,
        "escalationPolicy": (
          str(entry.get("escalationPolicy")) if entry.get("escalationPolicy") else None
        ),
      }
    )

  enriched.sort(
    key=lambda item: (
      0 if item.get("primary") else 1,
      {"active": 0, "standby": 1, "offline": 2}.get(item.get("status"), 3),
      (
        parse_iso(item.get("startedAt"))
        or datetime.fromtimestamp(0, timezone.utc)
      ).timestamp(),
    )
  )

  return enriched


def _normalize_automation_status(value: str | None) -> str:
  if value is None:
    return "operational"
  normalized = value.lower().replace("-", "_")
  if normalized in {"operational", "in_progress", "degraded", "blocked"}:
    return normalized
  if normalized in {"ok", "healthy", "green"}:
    return "operational"
  if normalized in {"warning", "yellow", "at_risk"}:
    return "degraded"
  if normalized in {"critical", "red", "down"}:
    return "blocked"
  return "operational"


def enrich_automations(entries: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []

  for index, entry in enumerate(entries):
    if not isinstance(entry, dict):
      continue

    owners_raw = entry.get("owners")
    if isinstance(owners_raw, str):
      owners = [owner.strip() for owner in owners_raw.split(",") if owner.strip()]
    elif isinstance(owners_raw, list):
      owners = [
        str(owner).strip()
        for owner in owners_raw
        if isinstance(owner, (str, int, float)) and str(owner).strip()
      ]
    else:
      owners = []

    success_rate_raw = to_float(entry.get("successRate"))
    if success_rate_raw is None:
      success_rate = None
    elif success_rate_raw > 1:
      success_rate = min(1.0, success_rate_raw / 100.0)
    else:
      success_rate = max(0.0, min(success_rate_raw, 1.0))

    coverage_raw = to_float(entry.get("coverage"))
    coverage = None
    if coverage_raw is not None:
      coverage = max(0.0, min(coverage_raw, 100.0))

    signals = [
      str(signal).strip()
      for signal in entry.get("signals", [])
      if isinstance(signal, (str, int, float)) and str(signal).strip()
    ]
    playbooks = [
      str(playbook).strip()
      for playbook in entry.get("playbooks", [])
      if isinstance(playbook, (str, int, float)) and str(playbook).strip()
    ]

    status = _normalize_automation_status(entry.get("status"))
    last_run_at = format_iso(parse_iso(entry.get("lastRunAt")))
    next_run_at = format_iso(parse_iso(entry.get("nextRunAt")))

    enriched.append(
      {
        **entry,
        "id": str(entry.get("id") or f"automation-{index}"),
        "title": str(entry.get("title") or "Automação").strip(),
        "description": (
          str(entry.get("description")) if entry.get("description") else None
        ),
        "status": status,
        "owners": owners,
        "lastRunAt": last_run_at,
        "nextRunAt": next_run_at,
        "successRate": success_rate,
        "coverage": coverage,
        "signals": signals,
        "playbooks": playbooks,
      }
    )

  status_order = {"blocked": 0, "degraded": 1, "in_progress": 2, "operational": 3}

  enriched.sort(
    key=lambda item: (
      status_order.get(item.get("status"), 4),
      -(
        parse_iso(item.get("lastRunAt")) or datetime.fromtimestamp(0, timezone.utc)
      ).timestamp(),
      item.get("title", ""),
    )
  )

  return enriched


def _normalize_slo_status(value: str | None) -> str | None:
  if value is None:
    return None
  normalized = value.lower().replace("-", "_")
  if normalized in {"healthy", "at_risk", "breaching"}:
    return normalized
  return None


def enrich_slos(entries: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []

  for entry in entries:
    if not isinstance(entry, dict):
      continue

    target = to_float(entry.get("target")) or 0.0
    current = to_float(entry.get("current")) or 0.0
    direction_raw = str(entry.get("direction") or "").lower()
    direction = "below" if direction_raw == "below" else "above"
    breaches = max(0, to_int(entry.get("breaches")) or 0)
    window_days_raw = to_int(entry.get("windowDays"))

    status = _normalize_slo_status(entry.get("status"))
    if status is None:
      meets_target = current >= target if direction == "above" else current <= target
      near_target = (
        current >= target * 0.95 if direction == "above" else current <= target * 1.05
      )

      if meets_target and breaches == 0:
        status = "healthy"
      elif not meets_target and breaches > 0:
        status = "breaching"
      else:
        status = "at_risk" if near_target else "breaching"

    notes = [
      str(note).strip()
      for note in entry.get("notes", [])
      if isinstance(note, str) and note.strip()
    ]

    enriched.append(
      {
        **entry,
        "service": str(entry.get("service") or "Serviço").strip(),
        "indicator": str(entry.get("indicator") or "Indicador").strip(),
        "target": target,
        "current": current,
        "direction": direction,
        "status": status,
        "breaches": breaches,
        "windowDays": window_days_raw if window_days_raw is not None else None,
        "notes": notes,
      }
    )

  enriched.sort(
    key=lambda item: (
      {"healthy": 0, "at_risk": 1, "breaching": 2}.get(item.get("status"), 3),
      item.get("service", ""),
    )
  )

  return enriched


def _normalize_slo_breach_status(value: str | None) -> str:
  if value is None:
    return "open"
  normalized = value.lower().replace("-", "_")
  if normalized in {"open", "acknowledged", "resolved"}:
    return normalized
  if normalized in {"ack", "acked"}:
    return "acknowledged"
  if normalized in {"closed", "fixed", "done"}:
    return "resolved"
  return "open"


def _normalize_slo_breach_impact(value: str | None) -> str:
  if value is None:
    return "minor"
  normalized = value.lower()
  if normalized in {"none", "minor", "major", "critical"}:
    return normalized
  if normalized in {"low", "info"}:
    return "minor"
  if normalized in {"high", "severe"}:
    return "major"
  return "minor"


def enrich_slo_breaches(entries: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
  enriched: List[Dict[str, Any]] = []

  for index, entry in enumerate(entries):
    if not isinstance(entry, dict):
      continue

    detected_at = parse_iso(entry.get("detectedAt"))
    resolved_at = parse_iso(entry.get("resolvedAt"))
    status = _normalize_slo_breach_status(entry.get("status"))
    impact = _normalize_slo_breach_impact(entry.get("impact"))
    window_days = to_int(entry.get("windowDays"))
    breach_percentage = to_float(entry.get("breachPercentage"))
    owner = entry.get("owner")
    actions = [
      str(action).strip()
      for action in entry.get("actions", [])
      if isinstance(action, str) and action.strip()
    ]

    enriched.append(
      {
        **entry,
        "id": str(entry.get("id") or entry.get("sloId") or f"breach-{index}"),
        "sloId": str(entry.get("sloId") or entry.get("id") or "slo"),
        "service": str(entry.get("service") or "Serviço").strip(),
        "indicator": str(entry.get("indicator") or "Indicador").strip(),
        "status": status,
        "impact": impact,
        "windowDays": window_days,
        "breachPercentage": breach_percentage,
        "detectedAt": format_iso(detected_at),
        "resolvedAt": format_iso(resolved_at),
        "owner": str(owner).strip() if owner else None,
        "actions": actions,
        "open": status != "resolved",
      }
    )

  status_order = {"open": 0, "acknowledged": 1, "resolved": 2}

  def _sort_key(item: Dict[str, Any]) -> tuple:
    detected = parse_iso(item.get("detectedAt")) or datetime.fromtimestamp(0, timezone.utc)
    return (
      status_order.get(str(item.get("status")), 3),
      -detected.timestamp(),
      item.get("service", ""),
    )

  enriched.sort(key=_sort_key)

  return enriched


def build_snapshot() -> Dict[str, object]:
  repo_root = ROOT
  fallback = load_json(OPERATIONS_EXAMPLE)
  counts = load_counts()

  weights = fallback.get("overall", {}).get("weights", {})
  components = fallback.get("components", [])
  enriched_components: List[Dict[str, object]] = []

  for component in components:
    key = component.get("key")
    baseline = int(component.get("baseline") or component.get("percentage") or 0)
    if key == "flutter_ios":
      computed = compute_percentage(baseline, flutter_checks(repo_root))
    elif key == "strapi_backend":
      computed = compute_percentage(baseline, strapi_checks(counts))
    elif key == "operations":
      computed = compute_percentage(baseline, operations_checks(repo_root))
    else:
      computed = {"percentage": baseline, "computed": baseline, "checks": [], "pending": []}

    enriched_components.append(
      {
        **component,
        "baseline": baseline,
        **computed,
      }
    )

  def weighted_average(field: str) -> float:
    total_weight = 0.0
    accumulator = 0.0
    for component in enriched_components:
      weight = float(component.get("weight") or weights.get(component.get("key"), 0))
      if weight <= 0:
        continue
      value = float(component.get(field, 0))
      accumulator += value * weight
      total_weight += weight
    return accumulator / total_weight if total_weight else 0.0

  baseline_overall = float(fallback.get("overall", {}).get("baseline") or fallback.get("overall", {}).get("percentage") or 0)
  computed_overall = weighted_average("computed")
  percentage_overall = max(int(round(baseline_overall)), int(round(computed_overall)))

  checks_index: Dict[str, Dict[str, Any]] = {}
  for component in enriched_components:
    for check in component.get("checks", []):
      if isinstance(check, dict) and "key" in check:
        checks_index[str(check["key"])] = check

  milestones = enrich_milestones(
    [item for item in fallback.get("milestones", []) if isinstance(item, dict)],
    counts,
    checks_index,
  )
  alerts = enrich_alerts(
    [item for item in fallback.get("alerts", []) if isinstance(item, dict)],
    counts,
    checks_index,
  )
  incidents = enrich_incidents(
    [item for item in fallback.get("incidents", []) if isinstance(item, dict)]
  )
  maintenance_seed = list(fallback.get("maintenance", []) or [])
  maintenance_seed += list(fallback.get("maintenanceWindows", []) or [])
  maintenance_windows = enrich_maintenance(
    [item for item in maintenance_seed if isinstance(item, dict)]
  )
  on_call = enrich_on_call(
    [item for item in fallback.get("onCall", []) if isinstance(item, dict)]
  )
  automations = enrich_automations(
    [item for item in fallback.get("automations", []) if isinstance(item, dict)]
  )
  slos = enrich_slos(
    [item for item in fallback.get("slos", []) if isinstance(item, dict)]
  )
  slo_breaches = enrich_slo_breaches(
    [item for item in fallback.get("sloBreaches", []) if isinstance(item, dict)]
  )

  snapshot = {
    "timestamp": fallback.get("timestamp"),
    "overall": {
      "percentage": percentage_overall,
      "baseline": int(round(baseline_overall)),
      "computed": int(round(computed_overall)),
      "weights": weights,
      "notes": fallback.get("overall", {}).get("notes", []),
    },
    "components": enriched_components,
    "counts": counts,
    "sources": fallback.get("sources", []),
    "milestones": milestones,
    "alerts": alerts,
    "incidents": incidents,
    "maintenanceWindows": maintenance_windows,
    "onCall": on_call,
    "automations": automations,
    "slos": slos,
    "sloBreaches": slo_breaches,
  }

  return snapshot


def main() -> None:
  parser = argparse.ArgumentParser(description=__doc__)
  parser.add_argument("--json", action="store_true", help="Output JSON instead of a table")
  args = parser.parse_args()

  snapshot = build_snapshot()

  if args.json:
    json.dump(snapshot, fp=sys.stdout, ensure_ascii=False, indent=2)
    print()
    return

  overall = snapshot["overall"]
  print("Prontidão consolidada")
  print("====================")
  print(f"Percentual atual: {overall['percentage']} % (baseline {overall['baseline']} %)")
  print()
  print("Componentes")
  print("-----------")
  for component in snapshot["components"]:
    label = component.get("label") or component.get("key")
    print(f"- {label}: {component['percentage']} % (baseline {component['baseline']} %, calculado {component['computed']} %)")
    pending = component.get("pending") or []
    if pending:
      print(f"  Pendências: {', '.join(pending)}")
  print()
  print("Contagens Pix")
  print("--------------")
  for key, value in snapshot["counts"].items():
    print(f"- {key}: {value}")
  print()
  on_call = snapshot.get("onCall") or []
  if on_call:
    print("Plantão Pix")
    print("-----------")
    for entry in on_call:
      name = entry.get("name") or entry.get("id")
      role = entry.get("role") or "On-call"
      status = entry.get("status") or ""
      status_label = {
        "active": "Ativo",
        "standby": "Standby",
        "offline": "Offline",
      }.get(status, status)
      print(f"- {name} ({status_label}) – {role}")
      contact = entry.get("contact")
      if contact:
        print(f"  Contato: {contact}")
      start = parse_iso(entry.get("startedAt"))
      end = parse_iso(entry.get("endsAt"))
      if start and end:
        print(f"  Janela: {start:%d/%m %H:%M} – {end:%d/%m %H:%M}")
      elif start:
        print(f"  Início: {start:%d/%m %H:%M}")
      elif end:
        print(f"  Até: {end:%d/%m %H:%M}")
      duration = entry.get("shiftDurationMinutes")
      if duration:
        print(f"  Duração: {duration} min")
    print()
  slos = snapshot.get("slos") or []
  if slos:
    print("SLOs Pix")
    print("--------")
    for slo in slos:
      service = slo.get("service") or slo.get("id")
      status = slo.get("status") or ""
      status_label = {
        "healthy": "Saudável",
        "at_risk": "Em risco",
        "breaching": "Violando",
      }.get(status, status)
      print(f"- {service} [{status_label}]")
      indicator = slo.get("indicator")
      if indicator:
        print(f"  Indicador: {indicator}")
      target = slo.get("target")
      current = slo.get("current")
      if target is not None and current is not None:
        print(f"  Meta: {target:.2f} · Atual: {current:.2f}")
      window_days = slo.get("windowDays")
      if window_days:
        print(f"  Janela: {window_days} dias")
      breaches = slo.get("breaches")
      if breaches:
        print(f"  Violações: {breaches}")
      notes = slo.get("notes") or []
      for note in notes:
        print(f"  Nota: {note}")
    print()
  slo_breaches = snapshot.get("sloBreaches") or []
  if slo_breaches:
    print("Violações de SLO")
    print("-----------------")
    for breach in slo_breaches:
      service = breach.get("service") or breach.get("sloId")
      indicator = breach.get("indicator") or "SLO"
      status = breach.get("status") or "open"
      impact = breach.get("impact") or "minor"
      status_label = {
        "open": "Aberta",
        "acknowledged": "Reconhecida",
        "resolved": "Resolvida",
      }.get(status, status)
      print(f"- {service} – {indicator} [{status_label} · impacto {impact}]")
      detected_at = parse_iso(breach.get("detectedAt"))
      resolved_at = parse_iso(breach.get("resolvedAt"))
      if detected_at and resolved_at:
        print(
          "  Intervalo: "
          f"{detected_at:%d/%m %H:%M} – {resolved_at:%d/%m %H:%M}"
        )
      elif detected_at:
        print(f"  Detectado em: {detected_at:%d/%m %H:%M}")
      owner = breach.get("owner")
      if owner:
        print(f"  Responsável: {owner}")
      breach_percentage = breach.get("breachPercentage")
      if breach_percentage is not None:
        print(f"  Desvio: {breach_percentage:.1f}%")
      actions = breach.get("actions") or []
      for action in actions:
        print(f"  Ação: {action}")
    print()
  sources = snapshot.get("sources", [])
  if sources:
    print("Fontes")
    print("------")
    for source in sources:
      value = source.get("value")
      source_type = source.get("type")
      print(f"- {source_type}: {value}")


if __name__ == "__main__":
  main()
