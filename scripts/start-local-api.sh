#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

export HJ_DB_HOST="${HJ_DB_HOST:-127.0.0.1}"
export HJ_DB_PORT="${HJ_DB_PORT:-3307}"
export HJ_DB_USERNAME="${HJ_DB_USERNAME:-root}"
export HJ_DB_PASSWORD="${HJ_DB_PASSWORD:-huajian-root}"
export HJ_DB_MAIN="${HJ_DB_MAIN:-huajian_main}"
export HJ_DB_SITE="${HJ_DB_SITE:-huajian_site_10001}"
export HJ_ADMIN_USERNAME="${HJ_ADMIN_USERNAME:-admin}"
export HJ_ADMIN_PASSWORD="${HJ_ADMIN_PASSWORD:-admin123456}"

exec php -d max_execution_time="${HJ_API_MAX_EXECUTION_TIME:-180}" -S "${HJ_API_HOST:-127.0.0.1}:${HJ_API_PORT:-8000}" -t server/public server/public/index.php
