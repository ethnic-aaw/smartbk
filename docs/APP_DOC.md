# Smart BK — Dokumentasi Aplikasi

Sistem Kesiswaan berbasis web untuk sekolah menengah. Mengelola data siswa, pelanggaran, konsultasi, dan buku tamu dengan dashboard analytics modern.

---

## 1. Ikhtisar

| Aspek | Detail |
|-------|--------|
| Nama | Smart BK |
| Versi | 1.6.0 |
| Stack | PHP 8.2 + Apache + MySQL 8.0 + React 19 (dashboard) |
| Frontend | Server-rendered PHP + React SPA (Orbit dashboard) |
| Auth | Session-based + Google OAuth 2.0 |
| Deployment | XAMPP (lokal) / Docker (server) |
| Port | 9000 (Docker), default Apache (XAMPP) |

---

## 2. Struktur Direktori

```
smartbk/
├── api/                    # REST API endpoints (JSON)
│   ├── auth/               #   login, logout, check
│   ├── dashboard/          #   stats, analytics
│   ├── siswa/              #   CRUD siswa
│   ├── user/               #   CRUD user
│   ├── kelas/              #   CRUD kelas
│   ├── pelanggaran/        #   CRUD pelanggaran + jenis
│   ├── buku_tamu/          #   CRUD buku tamu
│   └── konsultasi/         #   CRUD konsultasi
├── auth/                   # OAuth callback handler
├── assets/
│   ├── css/                #   Global stylesheet
│   ├── js/                 #   Client-side JS
│   ├── icons/              #   SVG icons
│   └── uploads/            #   User uploads (foto, bukti, lampiran)
├── config/
│   ├── app.php             #   .env loader, APP_BASE, OAUTH_REDIRECT_URI
│   └── db.php              #   Database connection (mysqli)
├── docs/                   #   Dokumentasi
├── includes/
│   ├── auth.php            #   Auth guard + role helpers
│   ├── csrf.php            #   CSRF token generation & validation
│   ├── session.php         #   Session init, login lockout
│   ├── functions.php       #   Helper functions (flash, badge, fase)
│   ├── generate_lib.php    #   Generate & undo tahun ajaran
│   ├── google_oauth.php    #   Google OAuth 2.0 wrapper
│   ├── header.php          #   HTML header template
│   ├── footer.php          #   HTML footer template
│   └── upload.php          #   File upload + image resize
├── orbit/                  # React dashboard (Orbit template)
│   ├── src/                #   TypeScript source
│   ├── dist/               #   Built assets
│   └── package.json
├── sql/
│   ├── smart_bk.sql        #   Schema + seed data (fresh install)
│   └── migration_v1.6.0.sql #  OAuth & approval columns
├── docker/
│   ├── 000-default.conf    #   Apache vhost config
│   ├── ports.conf          #   Apache port config
│   └── php.ini             #   PHP production settings
├── Dockerfile              #   PHP 8.2 Apache image
├── docker-compose.yml      #   App + MySQL services
├── .htaccess               #   URL rewrite, security headers
├── composer.json           #   PHP dependencies
├── sw.js                   #   Service worker (PWA cache)
├── manifest.webmanifest    #   PWA manifest
├── index.php               #   Entry redirect
├── login.php               #   Login page
├── register.php            #   Registration page
├── logout.php              #   Logout handler
├── dashboard.php           #   Dashboard (React mount)
├── pending_approval.php    #   Waiting approval page
│
│── siswa/                  #   CRUD siswa (PHP pages)
│   ├── index.php           #     List siswa
│   ├── tambah.php          #     Add siswa
│   ├── edit.php            #     Edit siswa
│   ├── detail.php          #     Detail siswa + riwayat
│   ├── hapus.php           #     Delete siswa
│   ├── import.php          #     Import Excel/CSV
│   ├── import_preview.php  #     Preview import data
│   ├── import_execute.php  #     Execute import
│   ├── import_process.php  #     Process import file
│   ├── template_import.php #     Download import template
│   └── cetak_konsultasi.php #    Print konsultasi report
│
│── pelanggaran/            #   CRUD pelanggaran (PHP pages)
│   ├── master.php          #     List jenis pelanggaran
│   ├── riwayat.php         #     Riwayat pelanggaran siswa
│   ├── tambah.php          #     Catat pelanggaran
│   ├── edit.php            #     Edit pelanggaran
│   ├── hapus.php           #     Delete pelanggaran
│   ├── jenis_tambah.php    #     Tambah jenis pelanggaran
│   ├── jenis_edit.php      #     Edit jenis pelanggaran
│   ├── jenis_hapus.php     #     Hapus jenis pelanggaran
│   ├── bukti.php           #     Lihat bukti
│   └── download.php        #     Download bukti
│
│── kelas/                  #   CRUD kelas (PHP pages)
│   ├── index.php           #     List kelas
│   ├── tambah.php          #     Tambah kelas
│   ├── edit.php            #     Edit kelas
│   ├── hapus.php           #     Hapus kelas
│   ├── generate.php        #     Generate tahun ajaran baru
│   └── generate_undo.php   #     Undo generate
│
│── user/                   #   CRUD user (PHP pages)
│   ├── index.php           #     List user
│   ├── tambah.php          #     Tambah user
│   ├── edit.php            #     Edit user
│   ├── hapus.php           #     Hapus user
│   └── approval.php        #     Approve/reject user registration
│
│── buku_tamu/              #   CRUD buku tamu (PHP pages)
│   ├── index.php           #     List buku tamu
│   ├── tambah.php          #     Tambah tamu
│   ├── edit.php            #     Edit tamu
│   └── hapus.php           #     Hapus tamu
│
│── konsultasi/             #   CRUD konsultasi (PHP pages)
│   ├── index.php           #     List konsultasi
│   ├── tambah.php          #     Tambah konsultasi
│   ├── edit.php            #     Edit konsultasi
│   ├── hapus.php           #     Hapus konsultasi
│   ├── hapus_lampiran.php  #     Hapus lampiran
│   └── download.php        #     Download lampiran
│
└── vendor/                 #   Composer dependencies (gitignored)
```

---

## 3. Database Schema

Database: `smart_bk` (MySQL 8.0)

### Tabel `users`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| nama | VARCHAR(100) | Nama lengkap |
| username | VARCHAR(100) UNIQUE | Login username |
| password_hash | VARCHAR(255) | bcrypt hash |
| google_id | VARCHAR(100) UNIQUE | Google OAuth ID |
| email | VARCHAR(150) UNIQUE | Email |
| email_verified_at | TIMESTAMP | Waktu verifikasi email |
| role | ENUM | Admin, Guru BK, Wali Kelas, Siswa |
| kelas_id | INT FK | Kelas (untuk Wali Kelas/Siswa) |
| status | ENUM | Aktif, Nonaktif |
| approval_status | ENUM | pending, approved, rejected |
| approved_by | INT FK | ID admin yang approve |
| approved_at | TIMESTAMP | Waktu approval |
| registration_token | VARCHAR(64) | Token registrasi |
| last_login_at | TIMESTAMP | Login terakhir |
| siswa_id | INT FK | Mapping ke tabel siswa |
| created_at | TIMESTAMP | |

### Tabel `kelas`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| nama_kelas | VARCHAR(100) | Contoh: X IPA 1 |
| tingkat | VARCHAR(20) | X, XI, XII |
| wali_kelas_id | INT FK | ID user Wali Kelas |
| tahun_ajaran | VARCHAR(20) | Contoh: 2024/2025 |
| created_at | TIMESTAMP | |

### Tabel `siswa`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| nipd | VARCHAR(20) UNIQUE | NIS/NISN |
| email | VARCHAR(150) UNIQUE | Email siswa |
| nama | VARCHAR(100) | Nama lengkap |
| jenis_kelamin | ENUM('L','P') | |
| kelas_id | INT FK | |
| tempat_lahir | VARCHAR(100) | |
| tanggal_lahir | DATE | |
| nama_orang_tua | VARCHAR(100) | |
| no_hp_orang_tua | VARCHAR(20) | |
| nama_ayah | VARCHAR(100) | Biodata ayah |
| no_hp_ayah | VARCHAR(20) | |
| pekerjaan_ayah | VARCHAR(100) | |
| nama_ibu | VARCHAR(100) | Biodata ibu |
| no_hp_ibu | VARCHAR(20) | |
| pekerjaan_ibu | VARCHAR(100) | |
| nama_wali | VARCHAR(100) | Wali (jika ada) |
| alamat_orang_tua | TEXT | |
| foto | VARCHAR(255) | Nama file foto |
| alamat | TEXT | Alamat siswa |
| status | ENUM | Aktif, Tidak Aktif, Pindah, Lulus |
| google_id | VARCHAR(100) UNIQUE | Google OAuth ID |
| created_at | TIMESTAMP | |

### Tabel `jenis_pelanggaran`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| kode | VARCHAR(20) UNIQUE | Contoh: PLG-001 |
| nama | VARCHAR(150) | Contoh: Terlambat |
| komponen | VARCHAR(100) | |
| kategori | ENUM | Kedisiplinan, Tata Krama, Kekerasan, Narkoba, Lainnya |
| bobot_poin | INT | Skor poin pelanggaran |
| deskripsi | TEXT | |
| konsekuensi | TEXT | |
| created_at | TIMESTAMP | |

### Tabel `pelanggaran_siswa`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| siswa_id | INT FK | |
| jenis_pelanggaran_id | INT FK | |
| tanggal | DATE | |
| lokasi | TEXT | |
| keterangan | TEXT | |
| tindakan | TEXT | |
| pelapor_id | INT FK | ID user pelapor |
| bukti_file | VARCHAR(255) | Nama file bukti |
| bukti_original | VARCHAR(255) | Nama file asli |
| bukti_type | VARCHAR(50) | MIME type |
| bukti_size | INT | Ukuran file |
| created_at | TIMESTAMP | |

### Tabel `buku_tamu`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| tanggal | DATE | |
| nama_tamu | VARCHAR(150) | |
| keperluan | TEXT | |
| tindak_lanjut | TEXT | |
| pencatat_id | INT FK | ID user pencatat |
| created_at | TIMESTAMP | |

### Tabel `konsultasi_siswa`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| siswa_id | INT FK | |
| tanggal | DATE | |
| permasalahan | TEXT | |
| tindak_lanjut | TEXT | |
| konselor_id | INT FK | ID user konselor |
| lampiran_file | VARCHAR(255) | |
| lampiran_original | VARCHAR(255) | |
| lampiran_type | VARCHAR(50) | |
| lampiran_size | INT | |
| created_at | TIMESTAMP | |
| | | INDEX (siswa_id, tanggal) |

### Tabel `fase_pelanggaran`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| kategori | VARCHAR(50) | Ringan, Sedang, Berat |
| min_skor | INT | |
| max_skor | INT NULL | NULL = unlimited |
| tindak_lanjut | VARCHAR(255) | |
| administrasi | VARCHAR(255) | |

**Threshold fase:**
- Ringan: 1-29 poin → Peringatan 1 & 2
- Sedang: 30-74 poin → Panggilan orang tua 1-3
- Berat: 75-100+ poin → Skorsing & Dikembalikan ke orang tua

### Tabel `log_generate`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| tahun_ajaran_lama | VARCHAR(20) | |
| tahun_ajaran_baru | VARCHAR(20) | |
| snapshot_json | LONGTEXT | Backup data sebelum generate |
| status | ENUM | Aktif, Dibatalkan |
| created_by | INT FK | |
| created_at | TIMESTAMP | |

### Tabel `user_approvals`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | INT AI PK | |
| user_id | INT FK | |
| approver_id | INT FK | |
| action | ENUM | approved, rejected |
| note | TEXT | |
| created_at | TIMESTAMP | |

---

## 4. Autentikasi & Otorisasi

### Role & Akses

| Role | Akses |
|------|-------|
| Admin | Full access — semua menu, CRUD, approval user |
| Guru BK | Lihat semua data siswa, catat pelanggaran/konsultasi, approve user |
| Wali Kelas | Hanya lihat/input data kelas sendiri |
| Siswa | Lihat data sendiri (via dashboard React) |

### Login Flow

1. **Form login** → `login.php` → validasi username/password (bcrypt)
2. **Google OAuth** → redirect ke Google → callback ke `auth/google_callback.php` → mapping `google_id` ke user
3. **Rate limiting** → 5 percobaan gagal → lockout 15 menit (`session.php:26`)
4. **Registration** → `register.php` → status `pending` → menunggu approval Admin/Guru BK
5. **CSRF** → token di-set di session, di-verify di setiap POST/PUT/DELETE (`csrf.php`)

### Session Config

- `httponly: true`, `samesite: Lax`
- `secure: false` (untuk localhost)
- Session regenerate setelah login berhasil

---

## 5. API Endpoints

Base URL: `/api/` (JSON, same-origin CORS)

### Auth
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| POST | `/api/auth/login.php` | Login |
| POST | `/api/auth/logout.php` | Logout |
| GET | `/api/auth/check.php` | Cek status login |

### Dashboard
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/dashboard/stats.php` | Statistik ringkas |
| GET | `/api/dashboard/analytics.php?period=7D/30D/90D/1Y` | Data analytics lengkap |

### Siswa
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/siswa/list.php` | List siswa (filter by kelas_id, search) |
| GET | `/api/siswa/detail.php?id={id}` | Detail siswa + riwayat |
| POST | `/api/siswa/create.php` | Tambah siswa |
| PUT | `/api/siswa/update.php?id={id}` | Edit siswa |
| DELETE | `/api/siswa/delete.php?id={id}` | Hapus siswa |

### User
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/user/list.php` | List user |
| POST | `/api/user/create.php` | Tambah user |
| PUT | `/api/user/update.php?id={id}` | Edit user |
| DELETE | `/api/user/delete.php?id={id}` | Hapus user |

### Kelas
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/kelas/list.php` | List kelas |
| POST | `/api/kelas/create.php` | Tambah kelas |
| PUT | `/api/kelas/update.php?id={id}` | Edit kelas |
| DELETE | `/api/kelas/delete.php?id={id}` | Hapus kelas |

### Pelanggaran
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/pelanggaran/jenis.php` | List jenis pelanggaran |
| POST | `/api/pelanggaran/jenis_create.php` | Tambah jenis |
| PUT | `/api/pelanggaran/jenis_update.php?id={id}` | Edit jenis |
| DELETE | `/api/pelanggaran/jenis_delete.php?id={id}` | Hapus jenis |
| GET | `/api/pelanggaran/list.php` | List pelanggaran siswa |
| POST | `/api/pelanggaran/create.php` | Catat pelanggaran |
| DELETE | `/api/pelanggaran/delete.php?id={id}` | Hapus pelanggaran |

### Buku Tamu
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/buku_tamu/list.php` | List buku tamu |
| POST | `/api/buku_tamu/create.php` | Tambah tamu |
| PUT | `/api/buku_tamu/update.php?id={id}` | Edit tamu |
| DELETE | `/api/buku_tamu/delete.php?id={id}` | Hapus tamu |

### Konsultasi
| Method | Endpoint | Fungsi |
|--------|----------|--------|
| GET | `/api/konsultasi/list.php` | List konsultasi |
| POST | `/api/konsultasi/create.php` | Tambah konsultasi |
| PUT | `/api/konsultasi/update.php?id={id}` | Edit konsultasi |
| DELETE | `/api/konsultasi/delete.php?id={id}` | Hapus konsultasi |

### API Response Format

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

Error:
```json
{
  "success": false,
  "error": "Error message"
}
```

---

## 6. Dashboard (React / Orbit)

Dashboard menggunakan React SPA yang di-build ke `orbit/dist/`. File built di-mount ke `dashboard.php`.

### Tech Stack
- React 19 + TypeScript
- Tailwind CSS v4
- Recharts (charts)
- Framer Motion (animations)
- Lucide React (icons)
- React Router v7

### Halaman
| Route | Komponen | Fungsi |
|-------|----------|--------|
| `/dashboard` | DashboardPage | Stats cards, charts, top siswa |
| `/billing` | BillingPage | (Placeholder) |
| `/crm/contacts` | ContactsPage | (Placeholder) |
| `/ai/chat` | ChatPage | (Placeholder) |
| `/settings` | SettingsPage | (Placeholder) |
| `/help` | HelpPage | (Placeholder) |

### Build Commands

```bash
cd orbit
npm install
npm run build        # Full build
npm run build:dash   # Dashboard-only build
```

---

## 7. Fitur Utama

### 7.1 Manajemen Siswa
- CRUD lengkap (tambah, edit, hapus, detail)
- Import siswa dari file Excel/CSV
- Download template import
- Biodata lengkap: data pribadi, orang tua, wali
- Upload foto siswa (auto-resize ke 150x150)
- Status: Aktif, Tidak Aktif, Pindah, Lulus
- Cetak laporan konsultasi per siswa

### 7.2 Pelanggaran
- Master jenis pelanggaran (kode, nama, kategori, bobot poin)
- Catat pelanggaran siswa dengan bukti foto/dokumen
- Riwayat pelanggaran per siswa
- Sistem fase berdasarkan akumulasi poin:
  - Ringan (1-29): Peringatan
  - Sedang (30-74): Panggilan orang tua
  - Berat (75+): Skorsing / Dikembalikan ke orang tua
- Download bukti pelanggaran

### 7.3 Konsultasi
- Catat sesi konseling siswa
- Upload lampiran (dokumen/foto)
- Filter by siswa dan tanggal

### 7.4 Buku Tamu
- Catat kunjungan tamu ke BK
- Tindak lanjut per kunjungan

### 7.5 Generate Tahun Ajaran
- Copy kelas + siswa aktif ke tahun ajaran baru
- Auto-rotate tingkat (X→XI, XI→XII, XII→Lulus)
- Snapshot data sebelum generate (untuk undo)
- Undo/rollback ke keadaan sebelumnya

### 7.6 User Management
- CRUD user dengan role-based access
- Approval system untuk registrasi baru
- Google OAuth 2.0 login
- Login lockout (5 percobaan → 15 menit)

### 7.7 Dashboard Analytics
- Total siswa aktif, total kelas
- Siswa bermasalah (poin > 75)
- Pelanggaran hari ini
- Kategori dominan (pie chart)
- Tren pelanggaran (line chart, filterable: 7D/30D/90D/1Y)
- Top 10 siswa dengan poin tertinggi
- Sparkline 6 bulan

---

## 8. Keamanan

| Layer | Implementasi |
|-------|-------------|
| CSRF | Token-based, di-verify di semua state-changing requests |
| XSS | `htmlspecialchars()` via helper `e()` di semua output |
| SQL Injection | Prepared statements (mysqli) |
| Auth Guard | Session check di `includes/auth.php`, redirect ke login |
| Rate Limiting | Login lockout 5 percobaan / 15 menit |
| CORS | Same-origin only (no wildcard) |
| Security Headers | X-Frame-Options DENY, X-Content-Type-Options nosniff, CSP, Referrer-Policy, Permissions-Policy |
| Upload Validation | MIME check (finfo), size limit 500KB, whitelist ext |
| Session | httponly, samesite=Lax, regenerate on login |
| .htaccess | Options -Indexes (disable directory listing) |

---

## 9. Deployment

### 9.1 XAMPP (Localhost)

1. Copy project ke `C:\xampp\htdocs\smartbk\`
2. Start Apache + MySQL dari XAMPP Control Panel
3. Buka phpMyAdmin, import `sql/smart_bk.sql`
4. Import `sql/migration_v1.6.0.sql` (jika fresh install)
5. Copy `.env.example` ke `.env`, isi konfigurasi
6. Akses `http://localhost/smartbk/`

### 9.2 Docker (Server)

```bash
# Build & start
docker compose up -d --build

# Import DB (first time)
docker exec -i smartbk_db mysql -u root -psmartbk_Root_ChangeMe_2025 smart_bk < sql/smart_bk.sql

# Akses
http://IP_SERVER:9000/smartbk/
```

### 9.3 Default Credentials

| User | Username | Password | Role |
|------|----------|----------|------|
| Admin Smart BK | admin | (default hash) | Admin |
| Hana Fitri | hana@belajar.id | (default hash) | Guru BK |
| Rina Lestari | rina@belajar.id | (default hash) | Wali Kelas |

> **Penting**: Ganti password default setelah deploy ke produksi!

### 9.4 Environment Variables (.env)

```env
DB_USER=smartbk
DB_PASS=ganti_dengan_password_kuat
MYSQL_ROOT_PASSWORD=ganti_dengan_root_password_kuat
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:9000/smartbk/auth/google_callback.php
```

---

## 10. Upload Paths

| Jenis | Folder | Maks Size |
|-------|--------|-----------|
| Foto siswa | `assets/uploads/foto_siswa/` | 500KB |
| Bukti pelanggaran | `assets/uploads/bukti_pelanggaran/` | - |
| Lampiran konsultasi | `assets/uploads/lampiran_konsultasi/` | - |
| Kop surat | `assets/uploads/kop/` | - |

Upload path di-share via Docker volume: `./assets/uploads:/var/www/html/assets/uploads`

---

## 11. Dependencies

### PHP (Composer)
- `google/apiclient` ^2.16 — Google OAuth
- PHP >= 8.1

### Frontend (npm)
- react ^19, react-dom ^19
- react-router-dom ^7
- recharts ^2.12
- framer-motion ^11
- lucide-react ^0.400
- tailwindcss ^4
- typescript ^5.6
- vite ^6

### System
- MySQL 8.0
- Apache 2.4 (with mod_rewrite, mod_headers)
- PHP extensions: gd (jpeg/png/webp), mysqli, mbstring, fileinfo

---

## 12. URL Rewriting

File `.htaccess` melakukan:
1. Strip `.php` extension dari URL (301 redirect)
2. Friendly URL: `/siswa` → `/siswa/index.php`
3. Security headers (X-Frame-Options, CSP, dll)
4. Skip rewrite untuk `/api/` paths

Contoh:
- `/login` → `login.php`
- `/siswa/tambah` → `siswa/tambah.php`
- `/pelanggaran/riwayat` → `pelanggaran/riwayat.php`

---

## 13. PWA (Progressive Web App)

- `manifest.webmanifest` — App name: "Smart BK", standalone display
- `sw.js` — Service worker cache aset statis (CSS, JS, icons)
- Cache strategy: Cache-first untuk static assets, network-first untuk halaman

---

## 14. Migration

Jika sudah punya database lama, jalankan file SQL secara berurutan:

```bash
# 1. Fresh install — import schema + seed
mysql -u root smart_bk < sql/smart_bk.sql

# 2. Update ke v1.6.0 — tambah kolom OAuth & approval
mysql -u root smart_bk < sql/migration_v1.6.0.sql
```

---

## 15. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| "Koneksi database belum tersedia" | Pastikan MySQL running, cek `.env` atau default credentials |
| Import siswa gagal | Cek format file (Excel/CSV), pastikan header kolom sesuai template |
| Upload foto error | Cek izin folder `assets/uploads/`, pastikan PHP gd extension aktif |
| Login ditolak | Cek role user, pastikan `approval_status = 'approved'` |
| Google OAuth error | Cek `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET` di `.env`, pastikan redirect URI cocok |
| Dashboard kosong | Jalankan `npm run build:dash` di folder `orbit/`, cek browser console |
| Container port conflict | Ganti port di `docker-compose.yml` (default: 9000) |
