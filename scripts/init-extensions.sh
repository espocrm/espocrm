#!/bin/bash
#
# Extension Initialization Script
#
# This script installs baked-in extensions using EspoCRM's native CLI.
# It checks the database to avoid reinstalling already-installed extensions.
#
# Extensions are tracked in the 'extension' table by name, so this script
# will only run installation for new extensions.
#
# Usage: Called automatically from container entrypoint
#
# To add a new extension:
#   1. Place the extension ZIP in /var/www/html/extensions/
#   2. Redeploy - the extension will be installed on startup
#

set -e

EXTENSIONS_DIR="/var/www/html/extensions"
ESPO_DIR="/var/www/html"

echo "=== Extension Initialization ==="

# Check if extensions directory exists and has ZIP files
if [ ! -d "$EXTENSIONS_DIR" ] || [ -z "$(ls -A $EXTENSIONS_DIR/*.zip 2>/dev/null)" ]; then
    echo "No extension packages found in $EXTENSIONS_DIR"
    exit 0
fi

# Check if EspoCRM is installed (config.php exists with isInstalled = true)
if [ ! -f "$ESPO_DIR/data/config.php" ]; then
    echo "EspoCRM not installed yet. Skipping extension initialization."
    exit 0
fi

if ! grep -q "isInstalled.*true" "$ESPO_DIR/data/config.php" 2>/dev/null; then
    echo "EspoCRM installation not complete. Skipping extension initialization."
    exit 0
fi

cd "$ESPO_DIR"

# Process each extension ZIP file
for ext_file in "$EXTENSIONS_DIR"/*.zip; do
    if [ ! -f "$ext_file" ]; then
        continue
    fi

    ext_filename=$(basename "$ext_file")
    echo ""
    echo "Processing: $ext_filename"

    # Extract extension name from manifest.json inside ZIP
    ext_name=$(unzip -p "$ext_file" "manifest.json" 2>/dev/null | grep -o '"name"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/' || echo "")

    # Try alternate path with leading slash (some ZIPs have this)
    if [ -z "$ext_name" ]; then
        ext_name=$(unzip -p "$ext_file" "/manifest.json" 2>/dev/null | grep -o '"name"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/' || echo "")
    fi

    if [ -z "$ext_name" ]; then
        echo "  WARNING: Could not extract extension name from $ext_filename. Skipping."
        continue
    fi

    echo "  Extension name: $ext_name"

    # Check if extension is already installed by querying the database via PHP
    is_installed=$(php -r "
        require_once 'bootstrap.php';
        \$app = new \Espo\Core\Application();
        \$em = \$app->getContainer()->getByClass(\Espo\ORM\EntityManager::class);
        \$ext = \$em->getRDBRepository('Extension')
            ->where(['name' => '$ext_name', 'isInstalled' => true])
            ->findOne();
        echo \$ext ? 'yes' : 'no';
    " 2>/dev/null || echo "error")

    if [ "$is_installed" = "yes" ]; then
        echo "  Already installed. Skipping."
        continue
    fi

    if [ "$is_installed" = "error" ]; then
        echo "  WARNING: Could not check installation status. Attempting install anyway."
    fi

    echo "  Installing extension..."

    # Use EspoCRM's native extension CLI command
    if php command.php extension --file="$ext_file" 2>&1; then
        echo "  Successfully installed: $ext_name"
    else
        echo "  ERROR: Failed to install $ext_name"
        # Don't exit - continue with other extensions
    fi
done

echo ""
echo "=== Extension Initialization Complete ==="
