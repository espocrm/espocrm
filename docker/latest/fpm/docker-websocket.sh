#!/bin/bash

set -euo pipefail

source entrypoint-utils.sh

exitIfNotReady
applyConfigEnv

exec /usr/local/bin/php /var/www/html/websocket.php
