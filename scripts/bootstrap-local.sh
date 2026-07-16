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

docker compose up -d mysql

echo "Waiting for MySQL ${HJ_DB_HOST}:${HJ_DB_PORT}..."
for attempt in $(seq 1 60); do
  if docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -uroot -p"${HJ_DB_PASSWORD}" --silent; then
    php scripts/db_bootstrap.php
    echo "Local databases are ready."
    exit 0
  fi
  sleep 2
done

echo "MySQL did not become ready in time." >&2
docker compose logs --tail=80 mysql >&2
exit 1
