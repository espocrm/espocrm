# EspoCRM Deployment Guide

## Overview

This guide covers two deployment scenarios:
1. **Local Development** - Quick start with Docker Compose
2. **GCP Cloud Run** - Automated deployment via GitHub Actions

---

## Part 1: Local Development with Docker Compose

### Prerequisites

- Docker Engine 20.10+
- Docker Compose v2.0+
- 4GB RAM minimum (8GB recommended)
- 10GB disk space

### Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/your-org/espocrm.git
cd espocrm

# 2. Create environment file
cp .env.example .env

# 3. (Optional) Customize .env values
nano .env

# 4. Start all services
docker compose up -d

# 5. Check status
docker compose ps

# 6. View logs
docker compose logs -f espocrm
```

### Access the Application

- **Web UI**: http://localhost:8080
- **Default Credentials**: admin / admin123 (change immediately!)
- **WebSocket**: ws://localhost:8081

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Network                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  espocrm-db  │  │   espocrm    │  │ espocrm-websocket│  │
│  │  (MariaDB)   │◄─┤   (Apache)   │  │    (Port 8081)   │  │
│  │  Port: 3306  │  │  Port: 8080  │  └──────────────────┘  │
│  └──────────────┘  └──────────────┘                        │
│                           ▲                                 │
│                    ┌──────┴───────┐                        │
│                    │espocrm-daemon│                        │
│                    │(Background)  │                        │
│                    └──────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

### Services

| Service | Purpose | Port |
|---------|---------|------|
| espocrm-db | MariaDB database | 3306 (internal) |
| espocrm | Main web application | 8080 |
| espocrm-daemon | Background job processor | - |
| espocrm-websocket | Real-time updates | 8081 |

### Common Commands

```bash
# Start services
docker compose up -d

# Stop services (preserves data)
docker compose down

# Stop and remove all data
docker compose down --volumes

# View logs
docker compose logs -f [service-name]

# Shell access
docker exec -it espocrm bash

# Restart a service
docker compose restart espocrm

# Update to latest version
docker compose pull && docker compose up -d
```

### Development Workflow

1. **Making changes**: Edit files in the `custom/` directory
2. **Clear cache**: Admin > Clear Cache, or via CLI:
   ```bash
   docker exec -it espocrm php command.php clear-cache
   ```
3. **Rebuild**: Run `grunt dev` in development container

### Troubleshooting

**Database connection issues:**
```bash
# Check database container
docker compose logs espocrm-db

# Verify database is healthy
docker compose ps
```

**Permission errors:**
```bash
# Fix permissions inside container
docker exec -it espocrm chown -R www-data:www-data /var/www/html
```

**Reset installation:**
```bash
docker compose down --volumes
docker compose up -d
```

---

## Part 2: GCP Cloud Run Deployment (via GitHub Actions)

### Prerequisites

1. **GCP Account** with billing enabled
2. **GCP Project** created
3. **APIs Enabled**:
   - Cloud Run API
   - Cloud SQL Admin API
   - Artifact Registry API
   - Secret Manager API

### GCP Setup

#### 1. Create Service Account

```bash
# Set project
export PROJECT_ID=your-project-id
gcloud config set project $PROJECT_ID

# Create service account
gcloud iam service-accounts create github-actions \
    --display-name="GitHub Actions Deployer"

# Grant roles
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:github-actions@$PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/run.admin"

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:github-actions@$PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/storage.admin"

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:github-actions@$PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/artifactregistry.admin"

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:github-actions@$PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/iam.serviceAccountUser"

# Create and download key
gcloud iam service-accounts keys create key.json \
    --iam-account=github-actions@$PROJECT_ID.iam.gserviceaccount.com
```

#### 2. Create Cloud SQL Instance

```bash
# Create MySQL instance
gcloud sql instances create espocrm-dev \
    --database-version=MYSQL_8_0 \
    --tier=db-f1-micro \
    --region=us-central1 \
    --root-password=YOUR_ROOT_PASSWORD

# Create database
gcloud sql databases create espocrm --instance=espocrm-dev

# Create user
gcloud sql users create espocrm \
    --instance=espocrm-dev \
    --password=YOUR_DB_PASSWORD
```

#### 3. Create Artifact Registry

```bash
gcloud artifacts repositories create espocrm-repo \
    --repository-format=docker \
    --location=us-central1 \
    --description="EspoCRM Docker images"
```

#### 4. Create Extensions Storage Bucket

Extensions (like Advanced Pack) are stored securely in a private GCS bucket and downloaded during build.

```bash
# Create a private bucket for extensions
gcloud storage buckets create gs://espocrm-extensions-${PROJECT_ID} \
    --location=us-central1 \
    --uniform-bucket-level-access

# Upload extensions to the bucket
gsutil cp /path/to/advanced-pack-3.11.12.zip gs://espocrm-extensions-${PROJECT_ID}/extensions/

# Verify upload
gsutil ls gs://espocrm-extensions-${PROJECT_ID}/extensions/
```

**Adding new extensions:**
```bash
# Simply upload the ZIP file to the extensions folder
gsutil cp /path/to/new-extension.zip gs://espocrm-extensions-${PROJECT_ID}/extensions/

# Trigger a new deployment - extension will be installed automatically
```

**How it works:**
1. During CI/CD build, extensions are downloaded from the GCS bucket
2. Extensions are baked into the Docker image
3. On container startup, new extensions are installed via EspoCRM CLI
4. Already-installed extensions are skipped (tracked in database)

### GitHub Secrets Configuration

Add these secrets to your GitHub repository (Settings > Secrets > Actions):

| Secret Name | Description |
|-------------|-------------|
| `GCP_PROJECT_ID` | Your GCP project ID |
| `GCP_SA_KEY` | Service account JSON key (base64 encoded) |
| `GCP_REGION` | Deployment region (e.g., us-central1) |
| `GCS_EXTENSIONS_BUCKET` | GCS bucket name for extensions (e.g., espocrm-extensions-myproject) |
| `DB_INSTANCE_CONNECTION` | Cloud SQL connection string |
| `DB_NAME` | Database name |
| `DB_USER` | Database username |
| `DB_PASSWORD` | Database password |
| `ADMIN_USERNAME` | EspoCRM admin username |
| `ADMIN_PASSWORD` | EspoCRM admin password |

### Deployment

Push to the `dev` branch to trigger automatic deployment:

```bash
git checkout -b dev
git push origin dev
```

### Accessing Dev Environment

After deployment, the GitHub Action will output the Cloud Run URL:
```
https://espocrm-dev-HASH-uc.a.run.app
```

---

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `ESPOCRM_DATABASE_PLATFORM` | Mysql or Postgresql | Mysql |
| `ESPOCRM_DATABASE_HOST` | Database hostname | espocrm-db |
| `ESPOCRM_DATABASE_NAME` | Database name | espocrm |
| `ESPOCRM_DATABASE_USER` | Database user | espocrm |
| `ESPOCRM_DATABASE_PASSWORD` | Database password | - |
| `ESPOCRM_ADMIN_USERNAME` | Admin username | admin |
| `ESPOCRM_ADMIN_PASSWORD` | Admin password | - |
| `ESPOCRM_SITE_URL` | Full site URL | - |
| `ESPOCRM_LANGUAGE` | Default language | en_US |
| `ESPOCRM_TIME_ZONE` | Timezone | UTC |

---

## Security Considerations

1. **Never commit `.env` files** - They're in `.gitignore`
2. **Change default passwords** immediately after setup
3. **Use HTTPS in production** - Configure SSL/TLS
4. **Restrict database access** - Use Cloud SQL Proxy or VPC
5. **Enable Cloud Run authentication** for sensitive environments
