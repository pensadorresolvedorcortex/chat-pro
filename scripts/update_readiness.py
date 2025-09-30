#!/usr/bin/env python3
"""Synchronize readiness percentage and notes across project docs."""
from __future__ import annotations

import argparse
import datetime as _dt
import re
from pathlib import Path
from typing import Optional

ROOT = Path(__file__).resolve().parents[1]

OPENAPI_PATH = ROOT / "docs" / "openapi.yaml"
MEGA_RESUMO_PATH = ROOT / "docs" / "mega-resumo-codex.md"
README_PATH = ROOT / "README.md"
IMPROVEMENTS_PATH = ROOT / "docs" / "frontend-backend-improvements.md"

THIN_SPACE = "\u202f"


def _read(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _write(path: Path, content: str, *, dry_run: bool) -> None:
    if dry_run:
        return
    path.write_text(content, encoding="utf-8")


def _format_notes(note: str) -> str:
    note_lines = [line.rstrip() for line in note.splitlines()] or [""]
    return "notes: >-\n" + "\n".join(f"      {line}" if line else "      " for line in note_lines) + "\n"


def update_openapi(percent: int, date: str, note: Optional[str], *, dry_run: bool) -> None:
    content = _read(OPENAPI_PATH)
    new_content, count = re.subn(
        r"(androidApkReadinessPercent:\s*)\d+",
        rf"\g<1>{percent}",
        content,
        count=1,
    )
    if count == 0:
        raise RuntimeError("Não foi possível atualizar androidApkReadinessPercent em docs/openapi.yaml")

    new_content, count = re.subn(
        r"(lastUpdated:\s*)\d{4}-\d{2}-\d{2}",
        rf"\g<1>{date}",
        new_content,
        count=1,
    )
    if count == 0:
        raise RuntimeError("Não foi possível atualizar lastUpdated em docs/openapi.yaml")

    if note is not None:
        formatted = _format_notes(note)
        new_content, count = re.subn(
            r"notes:\s*>-\n(?: {6}.*\n)+",
            formatted,
            new_content,
            count=1,
        )
        if count == 0:
            raise RuntimeError("Não foi possível atualizar notes em docs/openapi.yaml")

    _write(OPENAPI_PATH, new_content, dry_run=dry_run)


def update_mega_resumo(percent: int, *, dry_run: bool) -> None:
    content = _read(MEGA_RESUMO_PATH)
    percent_str = f"{percent}{THIN_SPACE}%"

    def repl(match: re.Match[str]) -> str:
        return f"{match.group(1)}{percent_str}"

    new_content, count = re.subn(
        r"(\*\*Progresso estimado para APK Android:\*\* )\d+\u202f%",
        repl,
        content,
        count=1,
    )
    if count == 0:
        raise RuntimeError("Não foi possível atualizar o percentual no mega resumo")

    _write(MEGA_RESUMO_PATH, new_content, dry_run=dry_run)


def update_readme(percent: int, *, dry_run: bool) -> None:
    content = _read(README_PATH)
    percent_str = f"{percent}{THIN_SPACE}%"

    def repl(match: re.Match[str]) -> str:
        return f"{match.group(1)}{percent_str}"

    new_content, count = re.subn(
        r"(indica \*\*)\d+\u202f%",
        repl,
        content,
        count=1,
    )
    if count == 0:
        raise RuntimeError("Não foi possível atualizar o percentual no README")

    _write(README_PATH, new_content, dry_run=dry_run)


def update_improvements(percent: int, *, dry_run: bool) -> None:
    content = _read(IMPROVEMENTS_PATH)
    percent_str = f"({percent}{THIN_SPACE}%)"
    new_content, count = re.subn(
        r"\(\d+\u202f%\)",
        percent_str,
        content,
        count=1,
    )
    if count == 0:
        raise RuntimeError("Não foi possível atualizar o percentual em docs/frontend-backend-improvements.md")

    _write(IMPROVEMENTS_PATH, new_content, dry_run=dry_run)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Atualiza o percentual de prontidão do APK Android")
    parser.add_argument("percent", type=int, help="Novo percentual de prontidão (0-100)")
    parser.add_argument(
        "--notes",
        help="Notas para registrar na seção x-projectStatus.notes do OpenAPI. Use '\\n' para múltiplas linhas.",
    )
    parser.add_argument(
        "--date",
        default=_dt.date.today().isoformat(),
        help="Data a ser registrada no campo lastUpdated (formato YYYY-MM-DD). Padrão: data de hoje.",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Apenas exibe as alterações detectadas sem escrever nos arquivos",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    if not (0 <= args.percent <= 100):
        raise SystemExit("Percentual deve estar entre 0 e 100")

    note = args.notes.replace("\\n", "\n") if args.notes is not None else None

    update_openapi(args.percent, args.date, note, dry_run=args.dry_run)
    update_mega_resumo(args.percent, dry_run=args.dry_run)
    update_readme(args.percent, dry_run=args.dry_run)
    update_improvements(args.percent, dry_run=args.dry_run)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
