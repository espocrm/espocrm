CONFIG_PREFIX="ESPOCRM_CONFIG_"

# This allows containers with /var/www/html directory mounted directly to keep running.
# To be removed in future releases.
isLegacy() {
    awk '$2 == "/var/www/html" { found = 1; exit 0 } END { if (!found) { exit 1 } }' /proc/mounts
}

exitIfNotReady() {
    if isLegacy; then
        return
    fi

    bin/command app-check >/dev/null 2>&1 || {
        echo >&2 "Waiting for the main container to be ready..."
        exit 0
    }
}

applyConfigEnv() {
    if isLegacy; then
        return
    fi

    local name
    local value

    compgen -v | while read -r name; do
        if [[ $name != "$CONFIG_PREFIX"* ]]; then
            continue
        fi

        value="${!name}"
        saveConfigValue "$name" "$value"
    done
}

verifyDatabaseReady() {
    for i in {1..20}; do
        [ "$(bin/command db:check 2>/dev/null)" = "OK" ] && return 0

        echo >&2 "Waiting for database connection (attempt $i/20)..."
        sleep 3
    done

    echo >&2 "error: Database connection failed."
    return 1
}

normalizeConfigParamName() {
    local value="$1"

    if [[ "${value^^}" == "${CONFIG_PREFIX^^}"* ]]; then
        value="${value:${#CONFIG_PREFIX}}"
    fi

    value="${value,,}"
    value="${value//__/.}"

    local result="" i=0 next
    while [[ $i -lt ${#value} ]]; do
        if [[ "${value:$i:1}" == "_" ]]; then
            next="${value:$((i+1)):1}"
            if [[ "$next" =~ [a-zA-Z] ]]; then
                result+="${next^^}"
                i=$(( i + 2 ))
                continue
            fi
        fi
        result+="${value:$i:1}"
        i=$(( i + 1 ))
    done

    echo "$result"
}

normalizeConfigParamValue() {
    local value="$1"
    local trimmed="$value"

    trimmed="${trimmed#"${trimmed%%[![:space:]]*}"}"
    trimmed="${trimmed%"${trimmed##*[![:space:]]}"}"

    if [[ "${trimmed:0:1}" == "'" && "${trimmed: -1:1}" == "'" ]]; then
        local inner="${trimmed:1:${#trimmed}-2}"
        local pat="\\\\"
        pat+="'"
        local repl="'"
        echo "${inner//$pat/$repl}"
        return
    fi

    echo "$value"
}

saveConfigValue() {
    local name="$1"
    local value="$2"

    local normalizedName
    normalizedName=$(normalizeConfigParamName "$name")

    local normalizedValue
    normalizedValue=$(normalizeConfigParamValue "$value")

    bin/command config:set "$normalizedName" "$normalizedValue" --type=auto
}

setEnvValue() {
    local var="$1"
    local fileVar="${var}_FILE"
    local def="${2:-}"
    if [ "${!var:-}" ] && [ "${!fileVar:-}" ]; then
        printf >&2 "error: Both $var and $fileVar are set"
        exit 1
    fi
    local val="$def"
    if [ "${!var:-}" ]; then
        val="${!var}"
    elif [ "${!fileVar:-}" ]; then
        val="$(< "${!fileVar}")"
    fi
    export "$var"="$val"
    unset "$fileVar"
}
