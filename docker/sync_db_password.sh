#!/usr/bin/env bash
# Samakan password user MySQL (smartbk) dengan DB_PASS di file .env
# tanpa menghapus volume/data. Jalankan di server Linux, di folder project.
#
#   bash docker/sync_db_password.sh
#
# Setelah selesai, restart app:  docker compose restart app

set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE=".env"
if [ ! -f "$ENV_FILE" ]; then
    echo "Error: file '$ENV_FILE' tidak ditemukan."
    echo "Buat dulu: cp .env.example .env  lalu isi password di dalamnya."
    exit 1
fi

get_env() {
    sed -n "s/^$1=//p" "$ENV_FILE" | tail -n 1
}

DB_USER="$(get_env DB_USER)"
DB_PASS="$(get_env DB_PASS)"
MYSQL_ROOT_PASSWORD="$(get_env MYSQL_ROOT_PASSWORD)"

[ -n "$DB_USER" ] || DB_USER="smartbk"

if [ -z "$DB_PASS" ]; then
    echo "Error: DB_PASS belum diisi di '$ENV_FILE'."
    exit 1
fi
if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
    echo "Error: MYSQL_ROOT_PASSWORD belum diisi di '$ENV_FILE'."
    exit 1
fi

case "$DB_PASS" in
    *"'"*) echo "Error: DB_PASS tidak boleh mengandung karakter kutip tunggal (') di shell ini."; exit 1 ;;
esac

if [ -z "$(docker compose ps -q db 2>/dev/null)" ]; then
    echo "Error: container 'db' belum berjalan. Mulai dulu: docker compose up -d"
    exit 1
fi

echo "Mengubah password user MySQL '$DB_USER' sesuai DB_PASS di $ENV_FILE ..."

docker compose exec -T db env NEW_DB_USER="$DB_USER" NEW_DB_PASS="$DB_PASS" sh -c '
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" \
        -e "ALTER USER '\''$NEW_DB_USER'\''@'\''%'\'' IDENTIFIED BY '\''$NEW_DB_PASS'\''; FLUSH PRIVILEGES;"
'

echo "Selesai. Password user '$DB_USER' sudah disamakan dengan DB_PASS di .env."
echo "Restart app agar memakai kredensial baru: docker compose restart app"