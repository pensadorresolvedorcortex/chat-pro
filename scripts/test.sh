#!/bin/bash
set -e
if ! command -v php >/dev/null 2>&1; then
  echo "php: command not found"
  exit 1
fi
find studio-privilege-seo -name '*.php' -print0 | xargs -0 -n1 php -l
