#!/bin/bash
set -e
find bot wp-waba-bot -name '*.php' -not -path '*/vendor/*' -print0 | xargs -0 -n1 php -l
