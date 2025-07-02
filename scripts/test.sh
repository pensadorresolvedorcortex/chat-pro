#!/bin/bash
set -e
if ! command -v php >/dev/null; then
  echo "php not found"
  exit 1
fi
find bolao-x -name '*.php' -print0 | xargs -0 -n1 php -l
