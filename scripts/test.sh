#!/bin/bash
set -e
find zxtec-intranet -name '*.php' -print0 | xargs -0 -n1 php -l
