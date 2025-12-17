# EspoCRM Development/Deployment Dockerfile
# Extends official image for custom configurations

FROM espocrm/espocrm:latest

# Install additional development tools if needed
RUN apt-get update && apt-get install -y \
    vim \
    git \
    && rm -rf /var/lib/apt/lists/*

# Copy custom configurations if any
# COPY ./custom /var/www/html/custom

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80
