# PRD — Smart BK
**Sistem Informasi Bimbingan & Konseling Sekolah**
Version 1.0 · Stack: PHP Native + MySQL · Deploy: Dokploy

---

## 1. Tujuan Aplikasi

Smart BK hadir untuk mendigitalisasi proses pencatatan, pemantauan, dan pelaporan data bimbingan konseling di sekolah. Aplikasi ini menjadi jembatan antara Guru BK, Wali Kelas, dan Siswa dalam satu platform terpusat — menggantikan pencatatan manual yang rawan kehilangan data dan sulit dipantau secara real-time.

**Manfaat utama:**
- Guru BK dapat memantau akumulasi poin pelanggaran siswa secara live
- Wali Kelas mendapat ringkasan kondisi siswa di kelasnya
- Admin memiliki kontrol penuh atas seluruh data master
- Data siswa tersimpan aman dan terstruktur berbasis NIPD/NIS

---

## 2. Pengguna & Hak Akses

| Role | Deskripsi | Hak Akses (v1.0) |
|---|---|---|
| **Admin** | Operator sistem / staf TU | **Full Access** — semua fitur, CRUD semua data |
| Guru BK | Guru Bimbingan Konseling | Read-only + input pelanggaran *(fase berikutnya)* |
| Wali Kelas | Wali kelas per rombel | Read-only data kelasnya *(fase berikutnya)* |
| Siswa | Peserta didik | Lihat rekap poin diri sendiri *(fase berikutnya)* |

> **Catatan v1.0:** Seluruh hak akses dipegang Admin. Role lain disiapkan strukturnya di database untuk ekspansi berikutnya.

---

## 3. Design & Frontend

### 3.1 Design System

**Palet Warna**
```
Primary     : #2563EB  (Biru Pendidikan)
Primary Dark: #1D4ED8
Accent      : #10B981  (Hijau — status baik)
Warning     : #F59E0B  (Kuning — perlu perhatian)
Danger      : #EF4444  (Merah — pelanggaran berat)
Background  : #F8FAFC
Surface     : #FFFFFF
Border      : #E2E8F0
Text Primary: #0F172A
Text Muted  : #64748B
```

**Tipografi**
- Font: `Inter` (Google Fonts) — weight 400 / 500 / 600 / 700
- Base size: 14px
- Heading h1: 24px · h2: 20px · h3: 16px

**Border radius:** 8px (card), 6px (input/button), 4px (badge)
**Shadow card:** `0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06)`

---

### 3.2 Layout Utama

```
┌─────────────────────────────────────────────────────────┐
│  SIDEBAR (240px fixed)     │  MAIN CONTENT AREA          │
│                            │                             │
│  [Logo Smart BK]           │  [Topbar: judul + user]     │
│  ──────────────            │  ─────────────────────────  │
│  • Dashboard               │                             │
│  • Master Siswa            │  [Konten halaman aktif]     │
│  • Master User             │                             │
│  • Master Kelas            │                             │
│  • Poin Pelanggaran        │                             │
│  ──────────────            │                             │
│  • Logout                  │                             │
└─────────────────────────────────────────────────────────┘
```

**Sidebar:**
- Background `#1E3A5F` (navy gelap)
- Item aktif: background `#2563EB`, teks putih
- Item hover: background `rgba(255,255,255,0.08)`
- Icon + label setiap menu item

**Topbar:**
- Background putih, border-bottom `#E2E8F0`
- Kiri: Judul halaman aktif (bold)
- Kanan: avatar + nama user + dropdown logout

---

### 3.3 Komponen UI Reusable

**Card Stats (Dashboard)**
```
┌─────────────────────┐
│  [Ikon]  Label      │
│  Angka besar (32px) │
│  Sub-label kecil    │
└─────────────────────┘
```

**Data Table**
- Header: background `#F1F5F9`, teks uppercase kecil, font-weight 600
- Row hover: `#F8FAFC`
- Row stripe: ringan setiap baris genap
- Kolom aksi: tombol Edit (biru outline) + Hapus (merah outline), ukuran kecil
- Pagination: prev/next + info "Menampilkan X–Y dari Z data"

**Form Input**
- Label di atas input, bukan placeholder saja
- Border `#CBD5E1` → focus border `#2563EB` + shadow biru tipis
- Error state: border merah + pesan error di bawah field
- Upload foto: drag-and-drop area atau klik, preview thumbnail 60×60px

**Badge / Chip Poin**
- Rendah (0–25): `bg #DCFCE7` teks `#16A34A`
- Sedang (26–50): `bg #FEF9C3` teks `#CA8A04`
- Tinggi (51–75): `bg #FED7AA` teks `#EA580C`
- Kritis (>75): `bg #FEE2E2` teks `#DC2626`

**Alert / Notifikasi**
- Success: border-left 4px hijau, bg `#F0FDF4`
- Error: border-left 4px merah, bg `#FFF1F2`
- Warning: border-left 4px kuning, bg `#FFFBEB`
- Auto-dismiss 3 detik atau klik ×

---

## 4. Core Features (v1.0)

### 4.1 Autentikasi

- Halaman login full-page: logo Smart BK, form username + password
- Guru menggunakan **akun belajar.id** sebagai username (format: `nama@belajar.id`)
- Admin menggunakan username lokal (bebas format)
- Session PHP native, remember-me opsional
- Redirect ke dashboard setelah login berhasil
- Proteksi: semua halaman require session aktif

---

### 4.2 Dashboard

**Summary Cards (baris atas):**
| Card | Isi |
|---|---|
| Total Siswa | Jumlah siswa aktif seluruh angkatan |
| Total Kelas | Jumlah rombongan belajar |
| Total Pelanggaran | Total kejadian tercatat bulan ini |
| Siswa Bermasalah | Siswa dengan poin > threshold kritis |

**Top 10 Siswa Poin Tertinggi (tabel utama dashboard):**
Kolom: Rank · Foto (thumbnail 36px) · Nama Siswa · NIPD/NIS · Kelas · Total Poin · Status Badge · Tanggal Pelanggaran Terakhir

- Diurutkan DESC berdasarkan total poin akumulasi
- Baris rank 1–3 mendapat highlight subtle (background lebih terang)
- Klik nama siswa → link ke detail siswa

**Grafik Pelanggaran per Bulan:**
- Bar chart sederhana 6 bulan terakhir
- Implementasi dengan `Chart.js` via CDN (ringan, tanpa dependensi berat)

---

### 4.3 Master Data Siswa

**Tabel Daftar Siswa:**
Kolom: Foto · NIPD/NIS · Nama Lengkap · Kelas · Jenis Kelamin · Total Poin · Aksi

**Form Tambah/Edit Siswa:**

| Field | Tipe | Validasi |
|---|---|---|
| NIPD / NIS | Text | Required, unik, max 20 karakter |
| Nama Lengkap | Text | Required, max 100 karakter |
| Jenis Kelamin | Select | L / P |
| Kelas | Select | FK ke master kelas |
| Tempat Lahir | Text | Opsional |
| Tanggal Lahir | Date | Opsional |
| Nama Orang Tua | Text | Opsional |
| No. HP Orang Tua | Text | Opsional |
| Foto | File Upload | JPG/PNG, max 500KB, diresize ke 150×150px |
| Alamat | Textarea | Opsional |
| Status | Select | Aktif / Tidak Aktif / Pindah / Lulus |

**Fitur Pendukung:**
- Search real-time by nama / NIPD
- Filter by kelas dan status
- Import siswa via CSV (kolom: NIPD, Nama, Kelas, JK) — ekspansi selanjutnya
- Export ke PDF / Excel — ekspansi selanjutnya

---

### 4.4 Master Data User

**Sub-menu User dibagi berdasarkan role:**

**Tab / Filter: Admin | Guru BK | Wali Kelas | Siswa**

**Form Tambah User:**

| Field | Tipe | Keterangan |
|---|---|---|
| Nama Lengkap | Text | Required |
| Username | Text | Untuk admin: bebas; untuk guru: format `nama@belajar.id` |
| Password | Password | Min 8 karakter; hidden by default |
| Role | Select | Admin / Guru BK / Wali Kelas / Siswa |
| Kelas Diampu | Select | Muncul jika role = Wali Kelas |
| NIP (guru) | Text | Opsional, untuk Guru BK |
| Status | Select | Aktif / Nonaktif |

**Catatan Akun Guru:**
- Username wajib format `@belajar.id` jika role Guru BK
- Validasi format email `belajar.id` di sisi server (PHP)

**Tabel User:**
Kolom: Nama · Username · Role Badge · Kelas (jika wali kelas) · Status · Aksi

---

### 4.5 Master Kelas

**Tabel Kelas:**
Kolom: Nama Kelas · Tingkat · Wali Kelas · Jumlah Siswa · Aksi

**Form Tambah/Edit Kelas:**

| Field | Tipe | Keterangan |
|---|---|---|
| Nama Kelas | Text | Contoh: X IPA 1, XI IPS 2 |
| Tingkat | Select | X / XI / XII (SMA) atau VII/VIII/IX (SMP) |
| Wali Kelas | Select | FK ke tabel user (role: Wali Kelas) |
| Tahun Ajaran | Text | Contoh: 2024/2025 |

---

### 4.6 Master Poin Pelanggaran

**Tabel Jenis Pelanggaran:**
Kolom: Kode · Nama Pelanggaran · Kategori · Bobot Poin · Deskripsi · Aksi

**Form Tambah/Edit Pelanggaran:**

| Field | Tipe | Keterangan |
|---|---|---|
| Kode | Text | Auto-generate atau manual, contoh: PLG-001 |
| Nama Pelanggaran | Text | Required, max 150 karakter |
| Kategori | Select | Kedisiplinan / Tata Krama / Kekerasan / Narkoba / Lainnya |
| Bobot Poin | Number | 1–100, required |
| Deskripsi | Textarea | Penjelasan detail pelanggaran |
| Konsekuensi | Textarea | Tindakan yang diambil sekolah |

**Skala Poin (referensi):**
- 1–10 poin: Pelanggaran ringan (terlambat, seragam tidak lengkap)
- 11–25 poin: Pelanggaran sedang (membolos, bertengkar)
- 26–50 poin: Pelanggaran berat (merusak fasilitas, intimidasi)
- 51–100 poin: Pelanggaran sangat berat (kekerasan fisik, narkoba)

---

### 4.7 Pencatatan Pelanggaran Siswa

**Form Input Pelanggaran:**

| Field | Tipe | Keterangan |
|---|---|---|
| Siswa | Select / Search | Cari by nama atau NIPD |
| Jenis Pelanggaran | Select | FK ke master pelanggaran (poin auto-fill) |
| Tanggal Kejadian | Date | Default: hari ini |
| Lokasi Kejadian | Text | Opsional |
| Keterangan | Textarea | Detail kejadian |
| Tindakan Diambil | Textarea | Tindak lanjut Guru BK |
| Pelapor | Text | Auto-fill: nama user login |

**Riwayat Pelanggaran Siswa:**
- Diakses dari detail halaman siswa
- Tabel: Tanggal · Pelanggaran · Poin · Pelapor · Keterangan
- Total poin akumulasi ditampilkan di header

---

## 5. Struktur Database (Ringkasan)

```sql
-- Tabel utama
users          (id, nama, username, password_hash, role, kelas_id, status)
kelas          (id, nama_kelas, tingkat, wali_kelas_id, tahun_ajaran)
siswa          (id, nipd, nama, jenis_kelamin, kelas_id, foto, status, ...)
jenis_pelanggaran  (id, kode, nama, kategori, bobot_poin, deskripsi, konsekuensi)
pelanggaran_siswa  (id, siswa_id, jenis_pelanggaran_id, tanggal, keterangan,
                    tindakan, pelapor_id, created_at)
```

---

## 6. User Flow — Admin

### 6.1 Login
```
Buka /login
  → Isi username + password
  → [PHP session check]
  → Berhasil: redirect /dashboard
  → Gagal: pesan error, form tetap tampil
```

### 6.2 Dashboard
```
/dashboard
  → Tampil 4 summary card (query COUNT)
  → Tampil tabel Top 10 siswa poin tertinggi
  → Tampil bar chart 6 bulan (data dari query GROUP BY MONTH)
```

### 6.3 Kelola Master Siswa
```
/siswa (daftar)
  → Search / filter kelas
  → Klik [+ Tambah Siswa] → /siswa/tambah
      → Isi form → Upload foto → Submit
      → Validasi server (NIPD unik, foto size)
      → Berhasil: redirect /siswa + flash "Siswa berhasil ditambahkan"
      → Gagal: kembali ke form + highlight error
  → Klik [Edit] → /siswa/edit/{id} → proses sama
  → Klik [Hapus] → konfirmasi modal → DELETE → reload tabel
  → Klik nama siswa → /siswa/detail/{id}
      → Info lengkap + foto
      → Riwayat pelanggaran (tabel)
      → Total poin akumulasi
      → Tombol [+ Catat Pelanggaran]
```

### 6.4 Kelola Master User
```
/user (daftar, default tab: semua)
  → Filter by role (tab)
  → Klik [+ Tambah User] → /user/tambah
      → Pilih role → field relevan muncul dinamis
      → Jika role Guru: validasi username format @belajar.id
      → Submit → validasi → redirect + flash
  → Klik [Edit] → /user/edit/{id}
  → Klik [Hapus] → konfirmasi → DELETE
  → Reset password: ikon kunci → set password baru
```

### 6.5 Kelola Kelas
```
/kelas (daftar)
  → CRUD kelas (tambah, edit, hapus)
  → Klik nama kelas → daftar siswa di kelas tersebut
```

### 6.6 Kelola Poin Pelanggaran
```
/pelanggaran-master (daftar jenis)
  → CRUD jenis pelanggaran
  → Tabel sortable by bobot poin
```

### 6.7 Catat Pelanggaran Siswa
```
/pelanggaran/tambah
  → Search siswa (AJAX / form biasa)
  → Pilih jenis pelanggaran → poin auto-tampil
  → Isi detail → Submit
  → Redirect ke detail siswa + flash sukses
```

### 6.8 Logout
```
Klik avatar → dropdown → [Logout]
  → session_destroy()
  → redirect /login
```

---

## 7. Halaman & Route (PHP Native)

| Route | File PHP | Deskripsi |
|---|---|---|
| `/login` | `login.php` | Halaman autentikasi |
| `/dashboard` | `dashboard.php` | Dashboard utama |
| `/siswa` | `siswa/index.php` | Daftar siswa |
| `/siswa/tambah` | `siswa/tambah.php` | Form tambah siswa |
| `/siswa/edit/{id}` | `siswa/edit.php?id=` | Form edit siswa |
| `/siswa/detail/{id}` | `siswa/detail.php?id=` | Detail + riwayat pelanggaran |
| `/user` | `user/index.php` | Daftar user |
| `/user/tambah` | `user/tambah.php` | Form tambah user |
| `/user/edit/{id}` | `user/edit.php?id=` | Form edit user |
| `/kelas` | `kelas/index.php` | Master kelas |
| `/pelanggaran-master` | `pelanggaran/master.php` | Jenis pelanggaran |
| `/pelanggaran/tambah` | `pelanggaran/tambah.php` | Input pelanggaran siswa |
| `/logout` | `logout.php` | Destroy session |

---

## 8. Struktur Folder PHP

```
smart-bk/
├── index.php               ← redirect ke /login atau /dashboard
├── login.php
├── logout.php
├── dashboard.php
├── config/
│   └── db.php              ← koneksi MySQLi / PDO
├── includes/
│   ├── header.php          ← sidebar + topbar HTML
│   ├── footer.php          ← closing tags + scripts
│   └── auth.php            ← session check, middleware
├── siswa/
│   ├── index.php
│   ├── tambah.php
│   ├── edit.php
│   ├── detail.php
│   └── hapus.php
├── user/
│   ├── index.php
│   ├── tambah.php
│   ├── edit.php
│   └── hapus.php
├── kelas/
│   ├── index.php
│   ├── tambah.php
│   └── edit.php
├── pelanggaran/
│   ├── master.php
│   └── tambah.php
├── assets/
│   ├── css/
│   │   └── style.css       ← custom CSS di atas Bootstrap/Tailwind CDN
│   ├── js/
│   │   └── main.js         ← confirm dialog, alert dismiss, dsb.
│   └── uploads/
│       └── foto_siswa/     ← foto siswa (writable, gitignore)
└── sql/
    └── smart_bk.sql        ← schema + data seed awal
```

---

## 9. Apa yang Dikembangkan Selanjutnya

Berdasarkan struktur v1.0, urutan pengembangan yang disarankan:

1. **Aktivasi role Guru BK** — beri akses input pelanggaran & lihat data kelasnya
2. **Aktivasi role Wali Kelas** — dashboard ringkas per kelas, notifikasi siswa bermasalah
3. **Notifikasi otomatis** — WhatsApp Gateway (Fonnte/Wablas) ke orang tua saat poin melewati threshold
4. **Laporan & Ekspor** — cetak PDF kartu konseling, ekspor Excel rekap bulanan
5. **Import CSV massal** — upload data siswa dari file Excel/CSV
6. **Surat Panggilan Orang Tua** — generate surat otomatis dari template, unduh PDF
7. **Aktivasi role Siswa** — login siswa lihat rekap poin & riwayat sendiri
8. **Audit log** — rekam siapa mengubah data apa dan kapan

---

*Dokumen ini merupakan PRD v1.0 untuk fase perancangan frontend & arsitektur Smart BK. Revisi dilakukan seiring kebutuhan stakeholder berkembang.*
