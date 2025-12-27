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

# Log to stderr for Cloud Run visibility
log() {
    echo "[EXT-INIT] $1" >&2
}

EXTENSIONS_DIR="/var/www/html/extensions"
ESPO_DIR="/var/www/html"

log "=== Extension Initialization ==="
log "Extensions dir: $EXTENSIONS_DIR"
log "EspoCRM dir: $ESPO_DIR"

# Debug: List extensions directory
log "Listing extensions directory:"
ls -la "$EXTENSIONS_DIR" >&2 2>&1 || log "Failed to list extensions directory"

# Check if extensions directory exists and has ZIP files
if [ ! -d "$EXTENSIONS_DIR" ]; then
    log "Extensions directory does not exist: $EXTENSIONS_DIR"
    exit 0
fi

zip_count=$(ls -1 "$EXTENSIONS_DIR"/*.zip 2>/dev/null | wc -l)
log "Found $zip_count ZIP file(s)"

if [ "$zip_count" -eq 0 ]; then
    log "No extension packages found in $EXTENSIONS_DIR"
    exit 0
fi

# Check if EspoCRM is installed (config.php exists with isInstalled = true)
log "Checking data/config.php..."
if [ ! -f "$ESPO_DIR/data/config.php" ]; then
    log "config.php not found at $ESPO_DIR/data/config.php"
    log "Listing data directory:"
    ls -la "$ESPO_DIR/data/" >&2 2>&1 || log "Failed to list data directory"
    log "EspoCRM not installed yet. Skipping extension initialization."
    exit 0
fi

log "config.php exists, checking isInstalled flag..."
if ! grep -q "isInstalled.*true" "$ESPO_DIR/data/config.php" 2>/dev/null; then
    log "isInstalled flag not set to true in config.php"
    log "EspoCRM installation not complete. Skipping extension initialization."
    exit 0
fi

log "EspoCRM is installed. Proceeding with extension installation."
cd "$ESPO_DIR"

# Process each extension ZIP file
for ext_file in "$EXTENSIONS_DIR"/*.zip; do
    if [ ! -f "$ext_file" ]; then
        continue
    fi

    ext_filename=$(basename "$ext_file")
    log ""
    log "Processing: $ext_filename"

    # Extract extension name from manifest.json inside ZIP
    # Try without leading slash first
    ext_name=$(unzip -p "$ext_file" "manifest.json" 2>&1 | grep -o '"name"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/' || echo "")

    # Try with leading slash (some ZIPs have this)
    if [ -z "$ext_name" ]; then
        ext_name=$(unzip -p "$ext_file" "/manifest.json" 2>&1 | grep -o '"name"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"name"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/' || echo "")
    fi

    if [ -z "$ext_name" ]; then
        log "  WARNING: Could not extract extension name from $ext_filename"
        log "  Attempting to list ZIP contents:"
        unzip -l "$ext_file" 2>&1 | head -20 >&2
        continue
    fi

    log "  Extension name: $ext_name"

    # Check if extension is already installed by querying the database via PHP
    log "  Checking if already installed..."
    is_installed=$(php -r "
        require_once 'bootstrap.php';
        \$app = new \Espo\Core\Application();
        \$em = \$app->getContainer()->getByClass(\Espo\ORM\EntityManager::class);
        \$ext = \$em->getRDBRepository('Extension')
            ->where(['name' => '$ext_name', 'isInstalled' => true])
            ->findOne();
        echo \$ext ? 'yes' : 'no';
    " 2>&1)

    log "  Installation check result: $is_installed"

    if [ "$is_installed" = "yes" ]; then
        log "  Already installed. Skipping."
        continue
    fi

    if [ "$is_installed" != "no" ]; then
        log "  WARNING: Unexpected check result. Attempting install anyway."
        log "  PHP output: $is_installed"
    fi

    log "  Installing extension via CLI..."

    # Use EspoCRM's native extension CLI command
    install_output=$(php command.php extension --file="$ext_file" 2>&1)
    install_status=$?

    log "  Install command exit code: $install_status"
    log "  Install output: $install_output"

    if [ $install_status -eq 0 ]; then
        log "  Successfully installed: $ext_name"
    else
        log "  ERROR: Failed to install $ext_name"
    fi
done

log ""
log "=== Extension Initialization Complete ==="
