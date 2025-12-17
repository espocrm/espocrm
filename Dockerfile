# EspoCRM Development/Deployment Dockerfile
# Extends official image for custom configurations

FROM espocrm/espocrm:latest

# Install additional development tools if needed
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/data/cache \
    /var/www/html/data/logs \
    /var/www/html/data/upload \
    /var/www/html/data/tmp \
    /var/www/html/data/export \
    /var/www/html/data/import \
    /var/www/html/custom/Espo/Custom \
    /var/www/html/custom/Espo/Modules \
    /var/www/html/client/custom

# Set proper permissions for all directories
RUN chmod -R 775 /var/www/html/data && \
    chmod -R 775 /var/www/html/custom && \
    chmod -R 775 /var/www/html/client/custom && \
    chown -R www-data:www-data /var/www/html

# Create startup script to handle runtime permissions
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Ensure data directories exist and are writable\n\
mkdir -p /var/www/html/data/cache\n\
mkdir -p /var/www/html/data/logs\n\
mkdir -p /var/www/html/data/upload\n\
mkdir -p /var/www/html/data/tmp\n\
\n\
# Set permissions\n\
chmod -R 775 /var/www/html/data 2>/dev/null || true\n\
chown -R www-data:www-data /var/www/html/data 2>/dev/null || true\n\
\n\
# Execute the original entrypoint\n\
exec docker-entrypoint.sh apache2-foreground\n\
' > /usr/local/bin/startup.sh && chmod +x /usr/local/bin/startup.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/startup.sh"]
