#!/bin/bash

set -euo pipefail

source entrypoint-utils.sh

start() {
    if isLegacy; then
        return
    fi

    # Required for web servers in FPM mode
    copyPublicFiles
    copyClientFiles

    if [ "$(bin/command config:get isInstalled)" = "true" ]; then
        actionMigrate
        return
    fi

    actionInstall
}

actionMigrate() {
    echo >&2 "info: Running \"migrate\" action."

    if ! verifyDatabaseReady ; then
        echo >&2 "error: Migration failed: database is not ready."
        exit 1
    fi

    bin/command clear-cache

    bin/command migrate || {
        local version
        version="$(bin/command config:get version)"

        echo >&2 "error: Migration failed: customizations may be incompatible with the new version."
        echo >&2 "error:   Resolve them or downgrade to version \"$version\"."
        echo >&2 "error:   See https://docs.espocrm.com/administration/docker/installation/#incompatible-customizations"
        exit 1
    }

    setPermissions
}

actionInstall() {
    echo >&2 "info: Running \"install\" action."

    rm -rf ./data/cache

    bin/command config:populate

    bin/command config:set "defaultPermissions.user" "www-data"
    bin/command config:set "defaultPermissions.group" "www-data"

    setPermissions

    bin/command config:set database.platform "${ESPOCRM_DATABASE_PLATFORM}"
    bin/command config:set database.host "${ESPOCRM_DATABASE_HOST}"
    bin/command config:set database.port "${ESPOCRM_DATABASE_PORT-}"
    bin/command config:set database.dbname "${ESPOCRM_DATABASE_NAME}"
    bin/command config:set database.user "${ESPOCRM_DATABASE_USER}"
    bin/command config:set database.password "${ESPOCRM_DATABASE_PASSWORD}"

    if ! verifyDatabaseReady ; then
        echo >&2 "error: Installation failed: database connection error. Verify the host, port, and credentials, then restart the container."
        exit 1
    fi

    bin/command rebuild >/dev/null 2>&1 || {
        echo >&2 "error: Installation failed: rebuild failed. Check data/logs/ for details, then restart the container."
        exit 1
    }

    bin/command create-admin-user "$ESPOCRM_ADMIN_USERNAME" >/dev/null 2>&1
    printf '%s\n' "$ESPOCRM_ADMIN_PASSWORD" | bin/command set-password admin >/dev/null 2>&1

    bin/command config:set "language" "$ESPOCRM_LANGUAGE"
    bin/command config:set "siteUrl" "$ESPOCRM_SITE_URL"

    [ -n "${ESPOCRM_DATE_FORMAT-}" ] && bin/command config:set "dateFormat" "$ESPOCRM_DATE_FORMAT"
    [ -n "${ESPOCRM_TIME_FORMAT-}" ] && bin/command config:set "timeFormat" "$ESPOCRM_TIME_FORMAT"
    [ -n "${ESPOCRM_TIME_ZONE-}" ] && bin/command config:set "timeZone" "$ESPOCRM_TIME_ZONE"
    [ -n "${ESPOCRM_WEEK_START-}" ] && bin/command config:set "weekStart" "$ESPOCRM_WEEK_START" --type=int
    [ -n "${ESPOCRM_DEFAULT_CURRENCY-}" ] && bin/command config:set "defaultCurrency" "$ESPOCRM_DEFAULT_CURRENCY"
    [ -n "${ESPOCRM_THOUSAND_SEPARATOR-}" ] && bin/command config:set "thousandSeparator" "$ESPOCRM_THOUSAND_SEPARATOR"
    [ -n "${ESPOCRM_DECIMAL_MARK-}" ] && bin/command config:set "decimalMark" "$ESPOCRM_DECIMAL_MARK"

    bin/command populate-scheduled-jobs
    bin/command config:set "jobRunInParallel" "true" --type=bool

    bin/command app-check || {
        echo >&2 "error: Installation failed: app-check error. Check data/logs/ for details, then restart the container."
        exit 1
    }

    bin/command config:set "isInstalled" "true" --type=bool

    echo >&2 "info: Installation completed successfully."
}

setPermissions() {
    chown -R www-data:www-data "${CUSTOM_RESOURCE_LIST[@]}"
}

setEnvironments() {
    for defaultParam in "${!DEFAULTS[@]}"
    do
        setEnvValue "${defaultParam}" "${DEFAULTS[$defaultParam]}"
    done

    for optionName in "${!OPTIONAL_PARAMS[@]}"
    do
        local varName="${OPTIONAL_PARAMS[$optionName]}"
        setEnvValue "${varName}" "${!varName-}"
    done
}

copyPublicFiles() {
    if ! awk '{print $2}' /proc/mounts | grep -qxF "/var/www/html/public"; then
        return
    fi

    echo >&2 "info: Copying public files."

    rm -rf ./public/*
    cp -a /usr/src/espocrm/public/. ./public/
}

copyClientFiles() {
    if ! awk '{print $2}' /proc/mounts | grep -qxF "/var/www/html/client"; then
        return
    fi

    echo >&2 "info: Copying client files."

    find ./client/ -mindepth 1 -maxdepth 1 ! -name 'custom' -exec rm -rf {} +
    cp -a /usr/src/espocrm/client/. ./client/
}

warnInsecureCredentials() {
    declare -a warningItems=()

    if [ "$ESPOCRM_ADMIN_PASSWORD" = "${DEFAULTS['ESPOCRM_ADMIN_PASSWORD']}" ]; then
        warningItems+=("ESPOCRM_ADMIN_PASSWORD uses the built-in default value.")
    fi

    if [ "$ESPOCRM_DATABASE_PASSWORD" = "${DEFAULTS['ESPOCRM_DATABASE_PASSWORD']}" ]; then
        warningItems+=("ESPOCRM_DATABASE_PASSWORD uses the built-in default value.")
    fi

    if [ ${#warningItems[@]} -eq 0 ]; then
        return
    fi

    echo >&2 '****************************************************'
    echo >&2 'warning: Insecure default EspoCRM credentials detected.'

    for warningItem in "${warningItems[@]}"
    do
        echo >&2 "warning: $warningItem"
    done

    echo >&2 'warning: Set strong environment variable values before using this instance in production.'
    echo >&2 '****************************************************'
}

warnLegacyInstallation() {
    if ! isLegacy; then
        return
    fi

    echo >&2 "warning: LEGACY INSTALLATION METHOD DETECTED."
    echo >&2 "warning: Do not mount /var/www/html directly. Instead, mount the following directories separately:"
    echo >&2 "warning:   /var/www/html/custom"
    echo >&2 "warning:   /var/www/html/data"
    echo >&2 "warning:   /var/www/html/client/custom"
    echo >&2 "warning: No further EspoCRM upgrades will be available."
    echo >&2 "warning: See https://docs.espocrm.com/administration/docker/installation/#migration-to-espocrm-10"

    if [ ! -f bin/command ]; then
        echo >&2 "error: Container startup aborted. Migrate to the supported volume layout shown above and restart."
        exit 1
    fi
}

# ------------------------- START -------------------------------------
# Global variables
CUSTOM_RESOURCE_LIST=(
    "./data"
    "./custom"
    "./client/custom"
)

declare -A DEFAULTS=(
    ['ESPOCRM_DATABASE_PLATFORM']='Mysql'
    ['ESPOCRM_DATABASE_HOST']='espocrm-db'
    ['ESPOCRM_DATABASE_PORT']=''
    ['ESPOCRM_DATABASE_NAME']='espocrm'
    ['ESPOCRM_DATABASE_USER']='espocrm'
    ['ESPOCRM_DATABASE_PASSWORD']='password'
    ['ESPOCRM_ADMIN_USERNAME']='admin'
    ['ESPOCRM_ADMIN_PASSWORD']='password'
    ['ESPOCRM_LANGUAGE']='en_US'
    ['ESPOCRM_SITE_URL']='http://localhost'
)

declare -A OPTIONAL_PARAMS=(
    ['language']='ESPOCRM_LANGUAGE'
    ['dateFormat']='ESPOCRM_DATE_FORMAT'
    ['timeFormat']='ESPOCRM_TIME_FORMAT'
    ['timeZone']='ESPOCRM_TIME_ZONE'
    ['weekStart']='ESPOCRM_WEEK_START'
    ['defaultCurrency']='ESPOCRM_DEFAULT_CURRENCY'
    ['thousandSeparator']='ESPOCRM_THOUSAND_SEPARATOR'
    ['decimalMark']='ESPOCRM_DECIMAL_MARK'
)

setEnvironments

if [[ "${1-}" == apache2* ]] || [ "${1-}" = 'php-fpm' ]; then
    warnInsecureCredentials
    warnLegacyInstallation

    start

    applyConfigEnv
fi
# ------------------------- END -------------------------------------

exec "$@"
