#!/bin/bash
set -e
find bolao-x/bolao-x -name '*.php' -print0 | xargs -0 -n1 php -l
