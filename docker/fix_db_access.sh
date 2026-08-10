#!/usr/bin/env bash
# Perbaiki "Access denied for user 'smartbk'" di Smart BK
#
# Pemakaian (jalankan di server, di folder project):
#   bash docker/fix_db_access.sh            # diagnosa + perbaiki (data DB aman, butuh .env)
#   bash docker/fix_db_access.sh --auto     # otomatis dari env container db (tanpa .env, data aman)
#   bash docker/fix_db_access.sh --reset    # jika masih gagal: reset volume (data DB HILANG)
#
# Alur: cek .env -> uji kredensial aplikasi -> jika ditolak, samakan password
# user MySQL via ALTER USER (root) -> uji ulang -> report.
# Foto/lampiran di assets/uploads TIDAK tersentuh.

set -uo pipefail

cd "$(dirname "$0")/.."

RESET=0
AUTO=0
case "${1:-}" in
    --reset) RESET=1 ;;
    --auto)  AUTO=1 ;;
esac

ENV_FILE=".env"
DB_NAME="smart_bk"

fail() { echo "[FAIL] $1"; exit 1; }
ok()   { echo "[OK]   $1"; }
info() { echo "[INFO] $1"; }

get_env() { sed -n "s/^$1=//p" "$ENV_FILE" | tail -n 1; }

# ---------- 1. Sumber kredensial ----------
if [ "$AUTO" -eq 1 ]; then
    echo "==> Mode --auto: ambil kredensial dari environment container db (tanpa .env)"
    if [ -z "$(docker compose ps -q db 2>/dev/null)" ]; then
        fail "Container 'db' belum berjalan. Mulai dulu: docker compose up -d --build"
    fi
    DB_USER="$(docker compose exec -T db sh -c 'echo "$MYSQL_USER"')"
    DB_PASS="$(docker compose exec -T db sh -c 'echo "$MYSQL_PASSWORD"')"
    MYSQL_ROOT_PASSWORD="$(docker compose exec -T db sh -c 'echo "$MYSQL_ROOT_PASSWORD"')"
    [ -n "$DB_USER" ] || DB_USER="smartbk"
    [ -n "$DB_PASS" ] || fail "Container db tidak punya MYSQL_PASSWORD di env. Cek: docker compose config"
    [ -n "$MYSQL_ROOT_PASSWORD" ] || fail "Container db tidak punya MYSQL_ROOT_PASSWORD di env. Cek: docker compose config"
    ok "Menggunakan kredensial container db: DB_USER=$DB_USER , DB_NAME=$DB_NAME"
else
    echo "==> Cek file $ENV_FILE"
    if [ ! -f "$ENV_FILE" ]; then
        fail "$ENV_FILE tidak ditemukan. Buat dulu: cp .env.example .env lalu isi DB_PASS dan MYSQL_ROOT_PASSWORD."
    fi

    DB_USER="$(get_env DB_USER)"
    DB_PASS="$(get_env DB_PASS)"
    MYSQL_ROOT_PASSWORD="$(get_env MYSQL_ROOT_PASSWORD)"

    [ -n "$DB_USER" ] || DB_USER="smartbk"

    if [ -z "$DB_PASS" ]; then
        fail "Variabel DB_PASS kosong di $ENV_FILE. Isi password yang akan dipakai aplikasi."
    fi
    case "$DB_PASS" in
        *"'"*) fail "DB_PASS mengandung kutip tunggal (') yang tidak didukung script ini. Ganti password di $ENV_FILE." ;;
    esac

    ok "DB_USER=$DB_USER , DB_NAME=$DB_NAME"
fi

# ---------- 2. Pastikan container db jalan ----------
echo "==> Cek container db"
if [ -z "$(docker compose ps -q db 2>/dev/null)" ]; then
    fail "Container 'db' belum berjalan. Mulai dulu: docker compose up -d --build"
fi
info "container db ditemukan"

# ---------- Fungsi verifikasi kredensial aplikasi ----------
# Nilai diambil LANGSUNG dari .env (bukan env container yang mungkin basi),
# dikirim ke dalam container db secara eksplisit.
run_verify() {
    docker compose exec -T db env TEST_U="$DB_USER" TEST_P="$DB_PASS" sh -c \
        'mysql -N -s -u"$TEST_U" -p"$TEST_P" -e "SELECT 1" smart_bk' >/dev/null 2>&1
}

echo "==> Uji kredensial aplikasi ke MySQL ..."
if run_verify; then
    ok "Kredensial $DB_USER diterima MySQL. Aplikasi seharusnya sudah bisa login."
    echo "     Bila halaman masih error, muat ulang env container app:"
    echo "         docker compose up -d --force-recreate app"
    exit 0
fi
info "Kredensial aplikasi DITOLAK (password MySQL belum cocok dengan $ENV_FILE)."

# ---------- 3. Perbaikan 1: samakan password (data aman) ----------
if [ "$RESET" -eq 0 ]; then
    echo "==> Perbaikan 1: samakan password user MySQL dengan $ENV_FILE (data aman) ..."
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        info "MYSQL_ROOT_PASSWORD kosong di $ENV_FILE - perbaikan dilewati."
    else
        ALTER_OUTPUT=$(docker compose exec -T db env NEW_U="$DB_USER" NEW_P="$DB_PASS" sh -c \
            'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "ALTER USER '\''$NEW_U'\''@'\''%'\'' IDENTIFIED BY '\''$NEW_P'\''; FLUSH PRIVILEGES;"' 2>&1)
        ALTER_RC=$?
        if [ "$ALTER_RC" -ne 0 ]; then
            info "ALTER USER gagal (kemungkinan password root container beda dengan .env)."
            info "Detail: $(echo "$ALTER_OUTPUT" | tail -n 2)"
        else
            info "ALTER USER berhasil. Verifikasi ulang ..."
            if run_verify; then
                ok "Kredensial aplikasi sudah cocok setelah ALTER USER."
                echo "     Muat ulang container app dengan .env terbaru:"
                echo "         docker compose up -d --force-recreate app"
                exit 0
            fi
            info "Masih ditolak walau ALTER USER berhasil - lanjut ke opsi reset."
        fi
    fi
else
    info "Mode --reset aktif; langsung reset volume."
fi

# ---------- 4. Perbaikan 2: reset volume (data DB hilang) ----------
if [ "$RESET" -eq 1 ]; then
    echo "==> Reset volume DB (data DB HILANG, sql/ di-import ulang) ..."
    docker compose down -v || true
    docker compose up -d --build || true

    echo "==> Menunggu DB siap ..."
    try=0
    until run_verify; do
        try=$((try + 1))
        [ "$try" -gt 90 ] && fail "DB tidak kunjung siap. Cek: docker compose logs db | tail -30"
        sleep 2
    done

    ok "Koneksi aplikasi berhasil setelah reset."
    echo "     Akses: http://IP_SERVER:9000/ (login admin/admin123 - segera ganti)."
    exit 0
fi

echo "==> Perbaikan otomatis belum tuntas. Pilihan berikutnya:"
echo "  - bash docker/fix_db_access.sh --reset  (data DB hilang, di-import ulang)"
echo "  - atau cek log: docker compose logs db | tail -30"
exit 1