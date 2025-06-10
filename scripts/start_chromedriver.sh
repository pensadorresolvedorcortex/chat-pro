#!/bin/bash
set -e
DIR="$(dirname "$0")/../bin"
CHROMEDRIVER="$DIR/chromedriver"
if [ ! -x "$CHROMEDRIVER" ]; then
  echo "chromedriver not found in $CHROMEDRIVER" >&2
  exit 1
fi
PORT=${1:-9515}
nohup "$CHROMEDRIVER" --port=$PORT >/dev/null 2>&1 &
echo $! > "$DIR/chromedriver.pid"
echo "chromedriver started on port $PORT (PID $(cat "$DIR/chromedriver.pid"))"
