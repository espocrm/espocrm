# Local Development Setup

## Prerequisites

- Docker and Docker Compose
- Extension ZIP file (e.g., `advanced-pack-*.zip`)

## Quick Start

```bash
# 1. Copy environment template
cp .env.example .env

# 2. Place extension ZIP in extensions/
# Download from your source and copy to extensions/

# 3. Start services
docker compose up -d --build

# 4. Access EspoCRM
open http://localhost:8080
```

## Volume Mounts

| Local Path | Container Path | Purpose |
|------------|----------------|---------|
| `./extensions/` | `/var/www/html/extensions` | Extension ZIPs (gitignored) |
| `./custom/` | `/var/www/html/custom` | Custom PHP code (git tracked) |
| `./client/custom/` | `/var/www/html/client/custom` | Custom JS/CSS (git tracked) |
| Named volume | `/var/www/html/data` | EspoCRM data (persistent) |

## Data Persistence

- **Database**: Stored in `espocrm-db-data` Docker volume
- **EspoCRM data**: Stored in `espocrm-data` Docker volume
- **Custom code**: Bind-mounted from local directories

Data persists across `docker compose down` and `docker compose up`.

To reset everything:
```bash
docker compose down -v  # -v removes volumes
```

## Adding Extensions

1. Place extension ZIP in `extensions/` directory
2. Restart: `docker compose restart espocrm`
3. Extension files are extracted and registered automatically

## Developing Custom Code

Edit files in:
- `custom/Espo/Custom/` - PHP customizations
- `client/custom/` - JavaScript/CSS customizations

Changes are reflected immediately (may require cache clear in EspoCRM).

## Useful Commands

```bash
# View logs
docker compose logs -f espocrm

# Rebuild after Dockerfile changes
docker compose up -d --build

# Clear EspoCRM cache
docker compose exec espocrm php command.php rebuild

# Access container shell
docker compose exec espocrm bash
```
