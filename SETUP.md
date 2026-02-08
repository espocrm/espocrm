# EspoCRM Local Development Setup

This guide will help you set up EspoCRM on your local machine with a MySQL Docker container.

## Quick Start

### 1. Configure Your Environment

Edit the `.env.install` file with your MySQL Docker credentials:

```bash
# Database Configuration (MySQL Docker Container)
DB_PLATFORM=Mysql
DB_HOST=localhost      # or your Docker container IP
DB_PORT=3306          # your MySQL Docker port
DB_NAME=espocrm
DB_USER=root
DB_PASSWORD=password

# Admin User Configuration
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
```

**Important**: Update `DB_HOST` and `DB_PORT` to match your MySQL Docker container settings.

### 2. Run the Setup Script

```bash
./setup.sh
```

This single command will:
- ✅ Install PHP dependencies (composer install)
- ✅ Install Node.js dependencies (npm install)
- ✅ Build frontend assets
- ✅ Create the database
- ✅ Run the CLI installation
- ✅ Create admin user
- ✅ Set proper file permissions

### 3. Start the Development Server

```bash
php -S localhost:8080 -t public
```

Then open http://localhost:8080 in your browser.

---

## Manual Installation (Step-by-Step)

If you prefer to run each step manually:

### 1. Install Dependencies

```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install
```

### 2. Build Frontend

```bash
npm run build-dev
```

### 3. Create Database

Connect to your MySQL Docker container and create the database:

```bash
mysql -hlocalhost -P3306 -uroot -ppassword

# In MySQL:
CREATE DATABASE espocrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 4. CLI Installation

Run the installation steps one by one:

```bash
# Accept license
php install/cli.php -a step1 -d "user-lang=en_US"

# Configure database
php install/cli.php -a step2 -d "db-platform=Mysql&db-name=espocrm&host-name=localhost&db-user-name=root&db-user-password=password"

# Confirm setup
php install/cli.php -a setupConfirmation

# Check permissions
php install/cli.php -a checkPermission

# Save settings
php install/cli.php -a saveSettings

# Build system
php install/cli.php -a buildSystem

# Create admin user
php install/cli.php -a step3 -d "user-name=admin&user-pass=admin123&user-confirm-pass=admin123"

# Finalize user creation
php install/cli.php -a createUser

# Finish installation
php install/cli.php -a finish
```

### 5. Set Permissions

```bash
chmod -R 755 data custom client
find . -type d -exec chmod 755 {} +
find . -type f -exec chmod 644 {} +
chmod +x bin/*
```

---

## Connecting to MySQL Docker Container

If your MySQL is in Docker, you might need to:

### Option 1: Use Docker Host Network

```bash
# In .env.install, set:
DB_HOST=localhost
DB_PORT=3306  # or your mapped port
```

### Option 2: Use Docker Container IP

Find your container IP:
```bash
docker inspect <container_name> | grep IPAddress
```

Update `.env.install`:
```bash
DB_HOST=172.17.0.2  # use the IP from above
DB_PORT=3306
```

### Option 3: Connect via Docker Network

If your app will run in Docker too:
```bash
DB_HOST=mysql  # container name
DB_PORT=3306
```

---

## Troubleshooting

### Database Connection Failed

Check your MySQL Docker container is running:
```bash
docker ps | grep mysql
```

Test connection:
```bash
mysql -h<host> -P<port> -u<user> -p<password> -e "SHOW DATABASES;"
```

### Permission Denied Errors

```bash
sudo chown -R $USER:$USER /home/user/espocrm
chmod +x setup.sh
```

### Installation Already Exists

To reinstall, clear the data directory:
```bash
rm -rf data/config.php data/cache/* data/.installed
```

Then run `./setup.sh` again.

---

## Development Commands

```bash
# Clear cache
php clear_cache.php

# Rebuild
php rebuild.php

# Run unit tests
npm run unit-tests

# Run integration tests
npm run integration-tests

# Static analysis (PHPStan)
npm run sa

# Build frontend (production)
npm run build

# Build frontend (development)
npm run build-dev
```

---

## Default Credentials

After installation:
- **URL**: http://localhost:8080
- **Username**: admin (or what you set in .env.install)
- **Password**: admin123 (or what you set in .env.install)

**Important**: Change the admin password after first login!

---

## Next Steps

1. Read the [development documentation](https://docs.espocrm.com/development/)
2. Set up a cron job for scheduled tasks:
   ```bash
   * * * * * cd /home/user/espocrm && php cron.php > /dev/null 2>&1
   ```
3. Configure your IDE for PHP development with PHPStan support

---

## Support

- **Documentation**: https://docs.espocrm.com
- **Forum**: https://forum.espocrm.com
- **GitHub Issues**: https://github.com/espocrm/espocrm/issues
