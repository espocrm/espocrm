# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:latest

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Create startup script that initializes directories at runtime
# Both data/ and client/custom/ are volume-mounted (empty), so create at runtime
RUN printf '#!/bin/bash\n\
set -e\n\
echo "Initializing EspoCRM directories..."\n\
\n\
# Create data directories in the mounted volume\n\
mkdir -p /var/www/html/data/cache\n\
mkdir -p /var/www/html/data/logs\n\
mkdir -p /var/www/html/data/upload\n\
mkdir -p /var/www/html/data/tmp\n\
mkdir -p /var/www/html/data/export\n\
mkdir -p /var/www/html/data/import\n\
mkdir -p /var/www/html/data/.backup\n\
\n\
# Create client/custom placeholder (also volume-mounted)\n\
mkdir -p /var/www/html/client/custom\n\
\n\
# Set world-writable permissions for both mounted volumes\n\
chmod -R 777 /var/www/html/data\n\
chmod -R 777 /var/www/html/client/custom\n\
\n\
echo "Directories initialized successfully"\n\
\n\
# Execute the original entrypoint\n\
exec docker-entrypoint.sh apache2-foreground\n\
' > /usr/local/bin/startup.sh && chmod +x /usr/local/bin/startup.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/startup.sh"]
