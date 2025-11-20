#!/usr/bin/env bash
set -euo pipefail

# Determine repository root relative to this script
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

use_file=true
if ! command -v file >/dev/null 2>&1; then
  echo "The 'file' utility is unavailable; falling back to a heuristic byte-scan." >&2
  use_file=false
fi

# Files with these MIME types are considered text-like and allowed
ALLOWED_MIME_PREFIXES=(
  "text/"
)
ALLOWED_MIME_TYPES=(
  "application/javascript"
  "application/json"
  "application/xml"
  "image/svg+xml"
)

has_binary=0

if $use_file; then
  while IFS= read -r -d '' file; do
    mime_type=$(file --brief --mime-type "$file")
    allowed=false

    for prefix in "${ALLOWED_MIME_PREFIXES[@]}"; do
      if [[ "$mime_type" == ${prefix}* ]]; then
        allowed=true
        break
      fi
    done

    if ! $allowed; then
      for allowed_type in "${ALLOWED_MIME_TYPES[@]}"; do
        if [[ "$mime_type" == "$allowed_type" ]]; then
          allowed=true
          break
        fi
      done
    fi

    if ! $allowed; then
      echo "Binary or unsupported file detected: $file ($mime_type)" >&2
      has_binary=1
    fi
  done < <(git ls-files -z)
else
  python - <<'PY'
import os
import sys
import subprocess

tracked = subprocess.check_output(["git", "ls-files", "-z"]).decode().split("\0")
tracked = [p for p in tracked if p]
has_binary = False

for path in tracked:
    with open(path, "rb") as fh:
        chunk = fh.read(2048)
    if b"\0" in chunk:
        sys.stderr.write(f"Binary-like file detected (contains NUL bytes): {path}\n")
        has_binary = True

if has_binary:
    sys.stderr.write("\nBinary files detected. Remove them or update the allowlist if appropriate.\n")
    sys.exit(1)
else:
    print("No binary or unsupported files detected in tracked sources (heuristic scan).")
PY
fi

if [[ $has_binary -eq 1 ]]; then
  echo "\nBinary files detected. Remove them or update the allowlist if appropriate." >&2
  exit 1
else
  echo "No binary or unsupported files detected in tracked sources." >&2
fi
