#!/bin/bash
set -e
VERSION=${1:-$(curl -s https://chromedriver.storage.googleapis.com/LATEST_RELEASE)}
URL="https://chromedriver.storage.googleapis.com/${VERSION}/chromedriver_linux64.zip"
DIR="$(dirname "$0")/../bin"
mkdir -p "$DIR"
TMPZIP="$DIR/chromedriver.zip"
echo "Downloading Chromedriver $VERSION..."
curl -L "$URL" -o "$TMPZIP"
unzip -o "$TMPZIP" -d "$DIR"
rm "$TMPZIP"
echo "Chromedriver installed in $DIR"
