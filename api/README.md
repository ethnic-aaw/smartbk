# Smart BK API Documentation

## Base URL
```
http://localhost/smartbk/api/
```

## Authentication
Sebagian besar endpoint memerlukan autentikasi melalui PHP session. Login terlebih dahulu menggunakan endpoint `/api/auth/login.php`.

---

## Endpoints

### Authentication

#### POST /api/auth/login.php
Login ke sistem.

**Request Body:**
```json
{
  "username": "admin",
  "password": "admin123",
  "tahun_ajaran": "2024/2025"
}
```

**Response Success:**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Smart BK",
      "role": "Admin",
      "username": "admin"
    },
    "tahun_ajaran": "2024/2025"
  }
}
```

#### POST /api/auth/logout.php
Logout dari sistem.

**Response:**
```json
{
  "success": true,
  "message": "Logout berhasil."
}
```

#### GET /api/auth/check.php
Cek status autentikasi user.

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Smart BK",
      "role": "Admin",
      "username": "admin"
    },
    "tahun_ajaran": "2024/2025"
  }
}
```

---

### Dashboard

#### GET /api/dashboard/stats.php
Mendapatkan statistik dashboard.

Query param opsional:
- `periode` — periode Top 10: `harian` (hari ini) / `minggu` (7 hari) / `bulan` (bulan ini) / `tahun` (tahun ajaran, default).

Untuk role Wali Kelas, seluruh data statistik otomatis dibatasi hanya untuk kelasnya.

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_siswa": 150,
      "total_kelas": 12,
      "pelanggaran_tahun": 45,
      "siswa_bermasalah": 8
    },
    "top_siswa": [...],
    "ringkasan_komponen": [
      { "komponen": "Kehadiran", "jumlah": 18, "total_poin": 40 },
      { "komponen": "Lain-lain", "jumlah": 12, "total_poin": 55 }
    ],
    "chart": {
      "labels": ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun"],
      "data": [12, 15, 10, 20, 18, 25]
    }
  }
}
```

---

### Siswa

#### GET /api/siswa/list.php
List semua siswa dengan filter dan pagination.

**Query Parameters:**
- `q` (optional): Search by nama/NIPD
- `kelas` (optional): Filter by kelas ID
- `tahun` (optional): Filter by tahun ajaran
- `page` (optional): Halaman (default: 1)
- `per_page` (optional): Jumlah per halaman (default: 15, max: 100)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "pagination": {
      "page": 1,
      "per_page": 15,
      "total": 150,
      "total_pages": 10
    }
  }
}
```

#### GET /api/siswa/detail.php?id={id}
Detail siswa beserta riwayat pelanggaran.

**Response:**
```json
{
  "success": true,
  "data": {
    "siswa": {...},
    "riwayat_pelanggaran": [...]
  }
}
```

#### POST /api/siswa/create.php
Tambah siswa baru.

**Request Body:**
```json
{
  "nipd": "2024001",
  "nama": "Nama Siswa",
  "jenis_kelamin": "L",
  "kelas_id": 1,
  "tempat_lahir": "Jakarta",
  "tanggal_lahir": "2008-01-01",
  "nama_orang_tua": "Nama Orang Tua",
  "no_hp_orang_tua": "081234567890",
  "alamat": "Alamat lengkap",
  "status": "Aktif"
}
```

#### PUT /api/siswa/update.php?id={id}
Update data siswa.

**Request Body:** (sama dengan create, field yang tidak diisi akan menggunakan nilai lama)

#### DELETE /api/siswa/delete.php?id={id}
Hapus siswa.

---

### User

#### GET /api/user/list.php
List semua user.

**Query Parameters:**
- `role` (optional): Filter by role (Admin, Guru BK, Wali Kelas, Siswa)

#### POST /api/user/create.php
Tambah user baru.

**Request Body:**
```json
{
  "nama": "Nama Lengkap",
  "username": "username",
  "password": "password123",
  "role": "Admin",
  "kelas_id": 1,
  "status": "Aktif"
}
```

#### PUT /api/user/update.php?id={id}
Update user. Password opsional (jika tidak diisi, password tidak berubah).

#### DELETE /api/user/delete.php?id={id}
Hapus user.

---

### Kelas

#### GET /api/kelas/list.php
List semua kelas.

**Query Parameters:**
- `tahun` (optional): Filter by tahun ajaran

#### POST /api/kelas/create.php
Tambah kelas baru.

**Request Body:**
```json
{
  "nama_kelas": "X IPA 1",
  "tingkat": "X",
  "wali_kelas_id": 2,
  "tahun_ajaran": "2024/2025"
}
```

#### PUT /api/kelas/update.php?id={id}
Update kelas.

#### DELETE /api/kelas/delete.php?id={id}
Hapus kelas.

---

### Jenis Pelanggaran

#### GET /api/pelanggaran/jenis.php
List semua jenis pelanggaran.

#### POST /api/pelanggaran/jenis_create.php
Tambah jenis pelanggaran baru.

**Request Body:**
```json
{
  "kode": "PLG-001",
  "nama": "Terlambat",
  "kategori": "Kedisiplinan",
  "bobot_poin": 10,
  "deskripsi": "Datang terlambat ke sekolah",
  "konsekuensi": "Peringatan tertulis"
}
```

#### PUT /api/pelanggaran/jenis_update.php?id={id}
Update jenis pelanggaran.

#### DELETE /api/pelanggaran/jenis_delete.php?id={id}
Hapus jenis pelanggaran.

---

### Pelanggaran Siswa

#### GET /api/pelanggaran/list.php
List pelanggaran siswa.

**Query Parameters:**
- `siswa_id` (optional): Filter by siswa
- `page` (optional): Halaman
- `per_page` (optional): Jumlah per halaman

#### POST /api/pelanggaran/create.php
Catat pelanggaran baru.

**Request Body:**
```json
{
  "siswa_id": 1,
  "jenis_pelanggaran_id": 1,
  "tanggal": "2024-08-05",
  "lokasi": "Gerbang sekolah",
  "keterangan": "Terlambat 15 menit",
  "tindakan": "Peringatan lisan"
}
```

#### DELETE /api/pelanggaran/delete.php?id={id}
Hapus pelanggaran.

---

## Error Response

Semua error menggunakan format:
```json
{
  "success": false,
  "error": "Pesan error"
}
```

HTTP Status Codes:
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 405: Method Not Allowed
- 500: Internal Server Error

---

## Testing dengan cURL

### Login
```bash
curl -X POST http://localhost/smartbk/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123","tahun_ajaran":"2024/2025"}' \
  -c cookies.txt
```

### Get Dashboard Stats (dengan session)
```bash
curl -X GET http://localhost/smartbk/api/dashboard/stats.php \
  -b cookies.txt
```

### Create Siswa
```bash
curl -X POST http://localhost/smartbk/api/siswa/create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"nipd":"2024999","nama":"Test Siswa","jenis_kelamin":"L","status":"Aktif"}'
```
