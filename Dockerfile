# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:latest

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Set permissions for directories that won't be volume-mounted
RUN chmod -R 775 /var/www/html/custom && \
    chmod -R 775 /var/www/html/client/custom && \
    chown -R www-data:www-data /var/www/html

# Create startup script that initializes the data directory at runtime
# This is needed because the in-memory volume mounts EMPTY over /var/www/html/data
COPY <<'EOF' /usr/local/bin/startup.sh
#!/bin/bash
set -e

echo "Initializing EspoCRM data directory..."

# Create required directories in the mounted volume
mkdir -p /var/www/html/data/cache
mkdir -p /var/www/html/data/logs
mkdir -p /var/www/html/data/upload
mkdir -p /var/www/html/data/tmp
mkdir -p /var/www/html/data/export
mkdir -p /var/www/html/data/import
mkdir -p /var/www/html/data/.backup

# Set permissions (www-data is uid 33)
chown -R www-data:www-data /var/www/html/data
chmod -R 775 /var/www/html/data

echo "Data directory initialized successfully"

# Execute the original entrypoint
exec docker-entrypoint.sh apache2-foreground
EOF

RUN chmod +x /usr/local/bin/startup.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/startup.sh"]
