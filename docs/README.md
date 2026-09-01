# Smart BK - Kesiswaan

**Version 1.0** | Stack: PHP Native + MySQL + JavaScript

Smart BK adalah sistem informasi untuk mendigitalisasi proses pencatatan, pemantauan, dan pelaporan data bimbingan konseling di sekolah.

---

## Fitur Utama

- ✅ **Autentikasi Multi-Role** (Admin, Guru BK, Wali Kelas, Siswa)
- ✅ **Dashboard Real-time** dengan statistik dan grafik
- ✅ **Master Data Siswa** (CRUD lengkap dengan upload foto)
- ✅ **Master Data User** dengan validasi akun belajar.id
- ✅ **Master Data Kelas** dan Wali Kelas
- ✅ **Master Jenis Pelanggaran** dengan bobot poin
- ✅ **Pencatatan Pelanggaran Siswa** dengan riwayat lengkap
- ✅ **REST API** untuk integrasi frontend modern
- ✅ **Responsive Design** menggunakan CSS custom
- ✅ **Session Management** dengan PHP native

---

## Tech Stack

- **Backend:** PHP 7.4+ (Native, no framework)
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Chart:** Chart.js (via CDN)
- **Server:** Apache (XAMPP/Laragon) atau Nginx

---

## Instalasi

### 1. Requirements

- PHP >= 7.4 (dengan ekstensi: mysqli, gd, fileinfo)
- MySQL >= 5.7 atau MariaDB >= 10.3
- Apache dengan mod_rewrite (atau Nginx)
- Web Server (XAMPP, Laragon, atau sejenisnya)

### 2. Clone / Download Project

```bash
git clone <repository-url> smartbk
cd smartbk
```

Atau extract file zip ke folder `htdocs` (XAMPP) atau `www` (Laragon).

### 3. Setup Database

1. Buka **phpMyAdmin** atau MySQL client
2. Import file database:

```bash
mysql -u root -p < sql/smart_bk.sql
```

Atau melalui phpMyAdmin:
- Buat database `smart_bk`
- Import file `sql/smart_bk.sql`

### 4. Konfigurasi Database

Edit file `config/db.php` jika diperlukan (default sudah menggunakan environment variables):

```php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'smart_bk';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
```

Atau gunakan environment variables di Apache/Nginx config.

### 5. Permissions

Pastikan folder `assets/uploads/foto_siswa` memiliki permission write:

```bash
chmod -R 777 assets/uploads/foto_siswa
```

Di Windows (XAMPP), permission biasanya sudah otomatis.

### 6. Akses Aplikasi

Buka browser dan akses:

```
http://localhost/smartbk/
```

Atau jika menggunakan virtual host:

```
http://smartbk.test/
```

### 7. Login Default

**Admin:**
- Username: `admin`
- Password: `admin123`
- Tahun Ajaran: `2024/2025`

**Guru BK:**
- Username: `hana@belajar.id`
- Password: `admin123`
- Tahun Ajaran: `2024/2025`

---

## Struktur Folder

```
smartbk/
├── api/                    # REST API endpoints
│   ├── auth/              # Login, logout, check auth
│   ├── dashboard/         # Dashboard stats
│   ├── siswa/             # CRUD Siswa
│   ├── user/              # CRUD User
│   ├── kelas/             # CRUD Kelas
│   ├── pelanggaran/       # CRUD Pelanggaran & Jenis
│   ├── index.php          # API helper functions
│   └── README.md          # API documentation
├── assets/
│   ├── css/               # Style CSS
│   ├── js/                # JavaScript
│   └── uploads/           # Upload folder (foto siswa)
├── config/
│   ├── app.php            # App config (base path)
│   └── db.php             # Database connection
├── includes/
│   ├── auth.php           # Authentication middleware
│   ├── functions.php      # Helper functions
│   ├── header.php         # Header template
│   ├── footer.php         # Footer template
│   └── upload.php         # Upload handler
├── siswa/                 # CRUD Siswa (Frontend)
├── user/                  # CRUD User (Frontend)
├── kelas/                 # CRUD Kelas (Frontend)
├── pelanggaran/           # CRUD Pelanggaran (Frontend)
├── sql/
│   └── smart_bk.sql       # Database schema + seed data
├── index.php              # Entry point
├── login.php              # Login page
├── logout.php             # Logout handler
├── dashboard.php          # Dashboard
├── PRD_SmartBK.md         # Product Requirement Document
└── README.md              # This file
```

---

## API Documentation

API tersedia di endpoint `/api/`. Dokumentasi lengkap ada di [api/README.md](api/README.md).

**Base URL:**
```
http://localhost/smartbk/api/
```

**Authentication:**
Semua endpoint (kecuali login) memerlukan session PHP aktif. Login dulu via `POST /api/auth/login.php`.

**Contoh Request (Login):**

```bash
curl -X POST http://localhost/smartbk/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123",
    "tahun_ajaran": "2024/2025"
  }' \
  -c cookies.txt
```

**Contoh Request (Get Dashboard Stats):**

```bash
curl -X GET http://localhost/smartbk/api/dashboard/stats.php \
  -b cookies.txt
```

---

## User Roles & Permissions

| Role | Akses |
|---|---|
| **Admin** | Full access ke semua fitur (CRUD semua data) |
| **Guru BK** | Read-only + input pelanggaran (fase future) |
| **Wali Kelas** | Read-only data kelasnya (fase future) |
| **Siswa** | Lihat rekap poin sendiri (fase future) |

**Catatan v1.0:** Hanya Admin yang aktif penuh. Role lain sudah disiapkan struktur database-nya.

---

## Database Schema

### Tabel Utama

- **users** — Data user (Admin, Guru BK, Wali Kelas, Siswa)
- **kelas** — Data kelas/rombel
- **siswa** — Data siswa (NIPD, nama, foto, dll)
- **jenis_pelanggaran** — Master jenis pelanggaran + bobot poin
- **pelanggaran_siswa** — Riwayat pelanggaran siswa

### Relasi

```
users (wali_kelas_id) → kelas
siswa (kelas_id) → kelas
pelanggaran_siswa (siswa_id) → siswa
pelanggaran_siswa (jenis_pelanggaran_id) → jenis_pelanggaran
pelanggaran_siswa (pelapor_id) → users
```

---

## Development

### Menambahkan Fitur Baru

1. Buat file PHP baru di folder yang sesuai (misal: `siswa/export.php`)
2. Gunakan `require_once __DIR__ . '/../includes/auth.php';` untuk proteksi session
3. Gunakan helper functions dari `includes/functions.php` (flash message, redirect, dll)
4. Tambahkan menu di `includes/header.php` jika perlu

### Menambahkan API Endpoint

1. Buat file baru di `api/{module}/{action}.php`
2. Require `api/index.php` untuk akses helper functions:
   - `api_response()`, `api_error()`, `api_success()`
   - `require_auth()`, `require_role()`
   - `get_json_input()`
3. Return JSON response menggunakan helper di atas

### Database Query

Gunakan helper functions dari `config/db.php`:

```php
// SELECT all
$rows = db_fetch('SELECT * FROM siswa WHERE status = ?', ['Aktif']);

// SELECT single row
$row = db_fetch('SELECT * FROM siswa WHERE id = ? LIMIT 1', [$id], 'row');

// INSERT/UPDATE/DELETE
$stmt = db_query('INSERT INTO siswa (nama, nipd) VALUES (?, ?)', [$nama, $nipd]);
$newId = db_last_id();
```

---

## Deployment ke Dokploy / Production

### 1. Upload Files

Upload semua file ke server (via FTP, Git, atau panel hosting).

### 2. Setup Database

Import `sql/smart_bk.sql` ke database production.

### 3. Environment Variables

Set environment variables di server:

```bash
DB_HOST=localhost
DB_NAME=smart_bk_prod
DB_USER=smartbk_user
DB_PASS=secure_password_here
```

### 4. Permissions

```bash
chmod -R 755 /path/to/smartbk
chmod -R 777 /path/to/smartbk/assets/uploads
```

### 5. Apache Config (Optional)

Jika menggunakan virtual host, buat config seperti ini:

```apache
<VirtualHost *:80>
    ServerName smartbk.sekolah.sch.id
    DocumentRoot /var/www/smartbk
    
    <Directory /var/www/smartbk>
        AllowOverride All
        Require all granted
    </Directory>
    
    SetEnv DB_HOST localhost
    SetEnv DB_NAME smart_bk
    SetEnv DB_USER smartbk_user
    SetEnv DB_PASS password_here
</VirtualHost>
```

### 6. Security Checklist

- ✅ Ganti semua password default (admin, database)
- ✅ Set `DB_PASS` yang kuat
- ✅ Aktifkan HTTPS (SSL/TLS)
- ✅ Disable error reporting di production (`display_errors = Off`)
- ✅ Backup database secara berkala
- ✅ Update PHP dan MySQL/MariaDB secara rutin

---

## Troubleshooting

### 1. "Koneksi database belum tersedia"

**Solusi:**
- Cek apakah MySQL/MariaDB sudah running
- Cek kredensial database di `config/db.php`
- Pastikan database `smart_bk` sudah dibuat dan diimport

### 2. "Failed to upload foto"

**Solusi:**
- Cek permission folder `assets/uploads/foto_siswa`
- Pastikan `upload_max_filesize` dan `post_max_size` di `php.ini` cukup besar (min 2MB)
- Pastikan ekstensi `gd` dan `fileinfo` aktif di PHP

### 3. Session tidak persist / logout otomatis

**Solusi:**
- Cek `session.save_path` di `php.ini`
- Pastikan folder session writable
- Cek `session.cookie_lifetime` di `php.ini`

### 4. API CORS error (jika diakses dari frontend terpisah)

**Solusi:**
- Edit `api/.htaccess` atau `api/index.php`
- Set header CORS yang sesuai dengan domain frontend

---

## Roadmap / Future Features

Berdasarkan PRD v1.0, fitur berikutnya yang akan dikembangkan:

- [ ] Aktivasi role Guru BK (input pelanggaran)
- [ ] Aktivasi role Wali Kelas (dashboard per kelas)
- [ ] Notifikasi WhatsApp otomatis ke orang tua
- [ ] Export laporan ke PDF/Excel
- [ ] Import siswa massal via CSV
- [ ] Generate surat panggilan orang tua otomatis
- [ ] Aktivasi role Siswa (lihat rekap sendiri)
- [ ] Audit log (siapa mengubah data apa)

---

## Contributing

Jika ingin berkontribusi:

1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/nama-fitur`)
3. Commit perubahan (`git commit -m 'Menambahkan fitur X'`)
4. Push ke branch (`git push origin feature/nama-fitur`)
5. Buat Pull Request

---

## License

Project ini dibuat untuk keperluan sekolah dan bersifat open source.

---

## Kontak & Support

Untuk pertanyaan, bug report, atau feature request, silakan buka issue di repository atau hubungi tim development.

---

**Smart BK v1.0** — Sistem Kesiswaan Sekolah 🎓
