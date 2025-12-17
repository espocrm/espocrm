# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:latest

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Create startup script that initializes the data directory at runtime
# This is needed because the in-memory volume mounts EMPTY over /var/www/html/data
RUN printf '#!/bin/bash\n\
set -e\n\
echo "Initializing EspoCRM data directory..."\n\
mkdir -p /var/www/html/data/cache\n\
mkdir -p /var/www/html/data/logs\n\
mkdir -p /var/www/html/data/upload\n\
mkdir -p /var/www/html/data/tmp\n\
mkdir -p /var/www/html/data/export\n\
mkdir -p /var/www/html/data/import\n\
mkdir -p /var/www/html/data/.backup\n\
chown -R www-data:www-data /var/www/html/data\n\
chmod -R 775 /var/www/html/data\n\
echo "Data directory initialized successfully"\n\
exec docker-entrypoint.sh apache2-foreground\n\
' > /usr/local/bin/startup.sh && chmod +x /usr/local/bin/startup.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/startup.sh"]
