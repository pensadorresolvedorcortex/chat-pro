#!/bin/sh
set -e

PLUGIN_DIR="ultimate-dashboard-pro"
LANG_DIR="$PLUGIN_DIR/languages"

# Compile .po files to .mo
for po in "$LANG_DIR"/*.po; do
    [ -f "$po" ] || continue
    mo="${po%.po}.mo"
    echo "Compiling $po -> $mo"
    msgfmt "$po" -o "$mo"

done

ZIP_FILE="${PLUGIN_DIR}.zip"
# Remove existing zip if any
rm -f "$ZIP_FILE"

# Create zip archive excluding version control files and build script
zip -r "$ZIP_FILE" "$PLUGIN_DIR" -x '*.git*' -x '*/.gitignore' -x "$ZIP_FILE" -x 'build.sh'

# Cleanup generated .mo files so they are not left in the working tree
find "$LANG_DIR" -name '*.mo' -delete

echo "Created $ZIP_FILE"
