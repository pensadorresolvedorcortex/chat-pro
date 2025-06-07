#!/bin/bash
set -e
find kalil -name '*.php' -print0 | xargs -0 -n1 php -l
