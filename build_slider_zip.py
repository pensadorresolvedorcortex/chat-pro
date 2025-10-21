#!/usr/bin/env python3
"""
Utility to package the Slider Revolution export without storing a binary zip in git.

Running this script writes `slider_step_by_step.zip` next to the export text file so
it can be imported into Slider Revolution.
"""
from __future__ import annotations

import io
import zipfile
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent
EXPORT_FILENAME = "slider_step_by_step_export.txt"
ZIP_FILENAME = "slider_step_by_step.zip"


def main() -> None:
    export_path = REPO_ROOT / EXPORT_FILENAME
    if not export_path.exists():
        raise FileNotFoundError(
            f"Export file '{EXPORT_FILENAME}' was not found next to this script."
        )

    zip_path = REPO_ROOT / ZIP_FILENAME
    export_bytes = export_path.read_bytes()

    # Always create the archive from scratch so the output is deterministic.
    with io.BytesIO() as buffer:
        with zipfile.ZipFile(buffer, mode="w", compression=zipfile.ZIP_DEFLATED) as zf:
            zf.writestr("slider_export.txt", export_bytes)
        zip_path.write_bytes(buffer.getvalue())

    print(f"Wrote {zip_path.name} ({zip_path.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
