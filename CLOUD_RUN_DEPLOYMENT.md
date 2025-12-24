# EspoCRM - Google Cloud Run Deployment Guide

This guide will help you deploy EspoCRM to Google Cloud Run with proper configuration.

## Prerequisites

- Google Cloud account with billing enabled
- Google Cloud SDK (`gcloud`) installed
- Docker installed (for local testing)
- MySQL database (Cloud SQL or external)

## Step 1: Set Up Cloud SQL Database (Recommended)

```bash
# Create Cloud SQL MySQL instance
gcloud sql instances create espocrm-db \
    --database-version=MYSQL_8_0 \
    --tier=db-f1-micro \
    --region=us-central1

# Set root password
gcloud sql users set-password root \
    --host=% \
    --instance=espocrm-db \
    --password=YOUR_SECURE_PASSWORD

# Create database
gcloud sql databases create espocrm --instance=espocrm-db

# Get connection name
gcloud sql instances describe espocrm-db --format='value(connectionName)'
# Save this value - you'll need it later
```

## Step 2: Build and Push Docker Image

```bash
# Set your project ID
export PROJECT_ID=your-project-id
export REGION=us-central1

# Configure Docker for Google Container Registry
gcloud auth configure-docker

# Build the Docker image
docker build -t gcr.io/$PROJECT_ID/espocrm:latest .

# Push to Google Container Registry
docker push gcr.io/$PROJECT_ID/espocrm:latest
```

## Step 3: Deploy to Cloud Run

```bash
# Deploy with Cloud SQL connection
gcloud run deploy espocrm \
    --image gcr.io/$PROJECT_ID/espocrm:latest \
    --platform managed \
    --region $REGION \
    --allow-unauthenticated \
    --port 8080 \
    --memory 1Gi \
    --cpu 1 \
    --timeout 300 \
    --set-env-vars "DATABASE_HOST=/cloudsql/YOUR_CONNECTION_NAME" \
    --set-env-vars "DATABASE_NAME=espocrm" \
    --set-env-vars "DATABASE_USER=root" \
    --set-env-vars "DATABASE_PASSWORD=YOUR_SECURE_PASSWORD" \
    --set-env-vars "DATABASE_PORT=3306" \
    --set-env-vars "SITE_URL=https://your-cloud-run-url.run.app" \
    --add-cloudsql-instances YOUR_CONNECTION_NAME

# Replace YOUR_CONNECTION_NAME with the value from Step 1
# Replace YOUR_SECURE_PASSWORD with your actual password
```

## Step 4: Using External MySQL Database (Alternative)

If you want to use an external MySQL database instead of Cloud SQL:

```bash
gcloud run deploy espocrm \
    --image gcr.io/$PROJECT_ID/espocrm:latest \
    --platform managed \
    --region $REGION \
    --allow-unauthenticated \
    --port 8080 \
    --memory 1Gi \
    --cpu 1 \
    --timeout 300 \
    --set-env-vars "DATABASE_HOST=your-db-host.com" \
    --set-env-vars "DATABASE_NAME=espocrm" \
    --set-env-vars "DATABASE_USER=your-user" \
    --set-env-vars "DATABASE_PASSWORD=your-password" \
    --set-env-vars "DATABASE_PORT=3306" \
    --set-env-vars "SITE_URL=https://your-cloud-run-url.run.app"
```

## Step 5: Update Site URL

After deployment, Cloud Run will give you a URL. Update the SITE_URL:

```bash
gcloud run services update espocrm \
    --region $REGION \
    --update-env-vars "SITE_URL=https://your-actual-cloud-run-url.run.app"
```

## Step 6: Access EspoCRM

1. Open the Cloud Run URL in your browser
2. You should see the EspoCRM installation wizard
3. Follow the setup wizard to complete installation
4. Default admin credentials will be set during installation

## Environment Variables

Required environment variables:

- `DATABASE_HOST` - Database host (Cloud SQL socket path or hostname)
- `DATABASE_NAME` - Database name (default: espocrm)
- `DATABASE_USER` - Database user
- `DATABASE_PASSWORD` - Database password
- `DATABASE_PORT` - Database port (default: 3306)
- `SITE_URL` - Your Cloud Run service URL

## Local Testing with Docker

Test locally before deploying:

```bash
# Using docker-compose (easier)
docker-compose up

# Or using plain Docker
docker build -t espocrm:local .
docker run -p 8080:8080 \
    -e DATABASE_HOST=your-db-host \
    -e DATABASE_NAME=espocrm \
    -e DATABASE_USER=root \
    -e DATABASE_PASSWORD=password \
    -e SITE_URL=http://localhost:8080 \
    espocrm:local

# Access at http://localhost:8080
```

## Troubleshooting

### Issue: "You need to configure your webserver"

This error means the Docker container isn't running properly. Check:
- Container logs: `gcloud run services logs read espocrm --region $REGION`
- Ensure nginx and php-fpm are running
- Verify the PORT environment variable matches (should be 8080)

### Issue: Database connection failed

- Verify Cloud SQL connection name is correct
- Check database credentials
- Ensure Cloud SQL instance is running
- Verify network connectivity

### Issue: Permission errors

The entrypoint script should handle permissions, but if issues persist:
- Check container logs for permission errors
- Ensure www-data user has access to /var/www/html/data

### Issue: 500 Internal Server Error

- Check Cloud Run logs for PHP errors
- Verify all required PHP extensions are installed
- Check nginx error logs

## Scaling and Performance

Cloud Run auto-scales based on traffic. Configure scaling:

```bash
gcloud run services update espocrm \
    --region $REGION \
    --min-instances 1 \
    --max-instances 10 \
    --concurrency 80
```

## Costs

Approximate monthly costs (as of 2024):
- Cloud SQL (db-f1-micro): ~$7-15/month
- Cloud Run: Pay-per-use, typically $5-20/month for light usage
- Container Registry storage: ~$0.20/month

Total: ~$12-35/month for a small deployment

## Custom Domain

To use a custom domain:

```bash
# Map domain
gcloud run domain-mappings create \
    --service espocrm \
    --domain yourdomain.com \
    --region $REGION

# Update SITE_URL
gcloud run services update espocrm \
    --region $REGION \
    --update-env-vars "SITE_URL=https://yourdomain.com"
```

## Backup

### Database Backup (Cloud SQL)

```bash
# Create on-demand backup
gcloud sql backups create --instance=espocrm-db

# Enable automated backups
gcloud sql instances patch espocrm-db \
    --backup-start-time=02:00
```

### File Uploads Backup

Consider using Cloud Storage for file uploads by mounting a bucket.

## Security Best Practices

1. **Use Cloud SQL** instead of external databases when possible
2. **Enable HTTPS** (Cloud Run provides this automatically)
3. **Set strong database passwords**
4. **Enable VPC** for additional security
5. **Restrict access** using Cloud IAM if needed
6. **Monitor logs** regularly for suspicious activity

## Support

For issues specific to:
- EspoCRM: https://docs.espocrm.com
- Google Cloud Run: https://cloud.google.com/run/docs
- This deployment: Check container logs first

## Updating EspoCRM

To update to a new version:

```bash
# Pull latest code
git pull origin master

# Rebuild image
docker build -t gcr.io/$PROJECT_ID/espocrm:latest .

# Push new image
docker push gcr.io/$PROJECT_ID/espocrm:latest

# Redeploy
gcloud run deploy espocrm \
    --image gcr.io/$PROJECT_ID/espocrm:latest \
    --region $REGION
```

---

**Note**: The first deployment may take 2-3 minutes as EspoCRM initializes. Subsequent requests will be faster as Cloud Run keeps instances warm.
