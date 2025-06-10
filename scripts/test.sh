#!/bin/bash
set -e
if ! command -v php >/dev/null 2>&1; then
  echo "php not installed; skipping lint" >&2
  exit 0
fi
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
