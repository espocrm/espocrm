# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:8.4

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Create custom entrypoint wrapper
RUN printf '#!/bin/bash\n\
set -e\n\
\n\
# Call the original docker-entrypoint.sh with a dummy command to do the setup\n\
# Then fix permissions and start apache ourselves\n\
\n\
# Run entrypoint setup phase (it copies files when /var/www/html is empty)\n\
/usr/local/bin/docker-entrypoint.sh true || true\n\
\n\
# Fix permissions for all writable directories\n\
echo "Fixing permissions..."\n\
chown -R www-data:www-data /var/www/html 2>/dev/null || true\n\
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
