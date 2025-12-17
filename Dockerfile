# EspoCRM Development/Deployment Dockerfile
# Extends official image for Cloud Run deployment

FROM espocrm/espocrm:latest

# Install additional tools
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Health check with longer timeout for Cloud Run
HEALTHCHECK --interval=30s --timeout=10s --start-period=300s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

# Use the default entrypoint - it handles copying files to /var/www/html
# The entire /var/www/html is mounted as an in-memory volume in Cloud Run
