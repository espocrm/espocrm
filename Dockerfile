# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:9.2.5

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Find and copy EspoCRM source files to /var/www/html at build time
# The official image stores files in /usr/src/espocrm
RUN if [ -d /usr/src/espocrm ]; then \
        cp -a /usr/src/espocrm/. /var/www/html/; \
    elif [ -d /var/www/espocrm ]; then \
        cp -a /var/www/espocrm/. /var/www/html/; \
    fi && \
    chown -R www-data:www-data /var/www/html

# Create custom entrypoint wrapper (skip the original entrypoint copy step)
RUN printf '#!/bin/bash\n\
set -e\n\
\n\
echo "Starting EspoCRM Cloud Run entrypoint..."\n\
\n\
# Sync installation state: if data/config.php shows installed, ensure install/config.php matches\n\
# This prevents the installation wizard from appearing after container restarts\n\
if [ -f /var/www/html/data/config.php ]; then\n\
    if grep -q "isInstalled.*true" /var/www/html/data/config.php 2>/dev/null; then\n\
        echo "EspoCRM already installed, syncing install config..."\n\
        mkdir -p /var/www/html/install\n\
        echo "<?php return [\\\"isInstalled\\\" => true];" > /var/www/html/install/config.php\n\
        chown www-data:www-data /var/www/html/install/config.php\n\
    fi\n\
fi\n\
\n\
# Fix permissions for GCS mounted directories\n\
echo "Fixing permissions for mounted directories..."\n\
chown -R www-data:www-data /var/www/html/data 2>/dev/null || true\n\
chown -R www-data:www-data /var/www/html/custom 2>/dev/null || true\n\
chown -R www-data:www-data /var/www/html/client/custom 2>/dev/null || true\n\
chmod -R 775 /var/www/html/data 2>/dev/null || true\n\
chmod -R 775 /var/www/html/custom 2>/dev/null || true\n\
chmod -R 775 /var/www/html/client/custom 2>/dev/null || true\n\
echo "Permissions fixed"\n\
\n\
# Start Apache in foreground\n\
exec apache2-foreground\n\
' > /usr/local/bin/custom-entrypoint.sh && chmod +x /usr/local/bin/custom-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=300s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/custom-entrypoint.sh"]
