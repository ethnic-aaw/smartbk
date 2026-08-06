# Smart BK - Deployment Docker (Arch Linux Server)

Panduan langkah demi langkah untuk menjalankan Smart BK di server Linux (Arch) menggunakan Docker.

## 1. Persiapan di Komputer Anda (Windows)

Zip seluruh folder project **termasuk** `assets/uploads` (agar kop sekolah & foto lama ikut terkirim):

```powershell
# di dalam folder smartbk
Compress-Archive -Path .\* -DestinationPath .\..\smartbk-deploy.zip
```

> JANGAN lupa: isi dulu `.env` sebelum di-zip, atau isi setelah di server (lihat langkah 4).

---

## 2. Install Docker di Server Arch Linux

```bash
# Update sistem & install docker
sudo pacman -Syu docker docker-compose

# Aktifkan & jalankan daemon Docker (otomatis saat boot)
sudo systemctl enable --now docker

# (Opsional) Izinkan user login memakai docker tanpa sudo
sudo usermod -aG docker $USER
# logout lalu login ulang agar berlaku

# Cek versi
docker --version
docker compose version
```

---

## 3. Upload Project ke Server

```bash
# dari komputer Anda (bukan di server)
scp smartbk-deploy.zip user@IP_SERVER:/tmp/

# di server: letakkan di /opt (atau lokasi lain)
sudo mkdir -p /opt
sudo mv /tmp/smartbk-deploy.zip /opt/
cd /opt
sudo unzip smartbk-deploy.zip -d smartbk
cd smartbk
```

---

## 4. Konfigurasi `.env`

```bash
# buat .env dari template
cp .env.example .env

# isi password kuat (nano .env)
nano .env
```

Isi minimal:

```ini
DB_USER=smartbk
DB_PASS=GANTI_DENGAN_PASSWORD_KUAT
MYSQL_ROOT_PASSWORD=GANTI_DENGAN_ROOT_PASSWORD_KUAT
```

> `.env` tidak ikut git (aman). Password di sini dipakai container app & db.

---

## 5. Izin Folder Upload

Uplaod folder di-`bind mount` ke container sebagai user `www-data` (uid 33). Beri izin tulis agar upload foto/lampiran berfungsi:

```bash
sudo chown -R 33:33 assets/uploads
# atau cara paling sederhana:
sudo chmod -R 777 assets/uploads
```

---

## 6. Build & Jalankan Container

```bash
# build image app + jalankan kedua container (app + mysql)
docker compose up -d --build

# lihat status
docker compose ps

# lihat log app (jika ada error)
docker compose logs -f app
```

Saat pertama kali `db` dibuat, `sql/smart_bk.sql` **otomatis di-import** oleh MySQL (via `/docker-entrypoint-initdb.d`). Database & tabel + data awal langsung jadi.

---

## 7. Akses Aplikasi

Buka browser:

```
http://IP_SERVER/
```

Login default:
- **Admin:** `admin` / `admin123`
- **Guru BK:** `hana@belajar.id` / `admin123`

> Setelah berhasil login, **segera ganti password admin** di Master User.

---

## 8. Firewall

Buka port 80 agar bisa diakses dari luar:

```bash
# jika memakai ufw
sudo ufw allow 80/tcp
sudo ufw enable

# jika memakai nftables, tambahkan rule:
# tcp dport 80 accept
```

---

## 9. Perintah Harian

```bash
# lihat status
docker compose ps

# log aplikasi
docker compose logs -f app

# restart aplikasi (tanpa rebuild)
docker compose restart app

# update kode terbaru (setelah upload file baru / git pull)
docker compose up -d --build

# stop semua
docker compose down

# stop + hapus data DB (HATI-HATI: data hilang)
docker compose down -v
```

---

## 10. Backup & Restore Database

```bash
# Backup (keluar file backup_smartbk.sql)
docker compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" smart_bk' > backup_smartbk.sql

# Restore
cat backup_smartbk.sql | docker compose exec -T db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" smart_bk'
```

> File upload (foto/lampiran) ada di folder `assets/uploads` — cukup backup folder itu.

---

## Troubleshooting

**Akses menampilkan "Koneksi database belum tersedia"**
```bash
# pastikan db container sehat
docker compose ps
docker compose logs db | tail -50
```

**Upload foto gagal / permission denied**
```bash
# perbaiki izin folder upload
sudo chmod -R 777 assets/uploads
```

**Port 80 sudah terpakai service lain**
Ubah `80:80` menjadi `8080:80` di `docker-compose.yml`, lalu akses `http://IP_SERVER:8080/`.

**Container db error password**
Volume DB lama menyimpan kredensial sebelumnya. Hapus volume lalu ulangi:
```bash
docker compose down -v
# isi ulang .env lalu
docker compose up -d --build
```
