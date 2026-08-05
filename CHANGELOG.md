# Smart BK - Change Log

## Version 1.4.0 (2026-08-05)

### ✅ Fitur Baru: Cetak PDF Rekap Konsultasi Siswa
- ✅ Halaman `siswa/cetak_konsultasi.php` (print view A4 + CSS @media print)
- ✅ **Kop sekolah** di bagian atas (membaca `assets/uploads/kop/kop.jpg|png|webp`); jika belum ada, fallback ke teks nama sekolah
- ✅ **Biodata siswa**: Nama, NISN (kosong → "—"), NIS/NIPD, Tempat/Tanggal Lahir **disejajarkan** (1 baris), Kelas
- ✅ **Riwayat konsultasi** lengkap: No, Tanggal, Permasalahan, Tindak Lanjut, Bukti Dukung
- ✅ **Bukti dukung**: foto tampil inline (kecil), surat/PDF tampil nama file
- ✅ **Tanda tangan konselor**: nama konselor yang mencatat (dari user) + garis tanda tangan + kota/tanggal
- ✅ Tombol "🖨 Cetak PDF" di **Detail Siswa** (khusus Admin & Guru BK)
- ✅ Cetak via browser (Print → Save as PDF), tanpa library eksternal
- ✅ Halaman cetak & tombol diblokir untuk Wali Kelas/Siswa (confidential)

### Files Baru
- `siswa/cetak_konsultasi.php`
- `assets/uploads/kop/` (folder untuk file kop sekolah)

### Files Dimodifikasi
- `siswa/detail.php` - Tombol "🖨 Cetak PDF"

### Cara Pakai Kop
1. Letakkan file kop dengan nama `kop.jpg`, `kop.png`, atau `kop.webp` di `assets/uploads/kop/`
2. Halaman cetak otomatis menampilkan kop tersebut
3. Jika belum ada file kop, tampil teks "SMK NEGERI 1 LEUWIMUNDING" sebagai pengganti

---

## Version 1.3.1 (2026-08-05)

### ✅ Penyempurnaan: Dropdown Kaskade "Kelas → Siswa" di Form Catat Konsultasi
- ✅ Pemilihan siswa dipecah menjadi 2 level: **pilih kelas** → **pilih siswa**
- ✅ Siswa dimuat via AJAX dari `/api/siswa/list.php?kelas={id}` (reuse API yang ada, tanpa endpoint baru)
- ✅ Hanya siswa di kelas terpilih yang dimuat (±30-40 siswa, bukan 1500+ sekaligus)
- ✅ Datang dari Detail Siswa (`?siswa_id=`): kelas & siswa otomatis ter-pilih
- ✅ Kelas tanpa siswa → pesan "Tidak ada siswa di kelas ini"
- ✅ Saat error validasi POST → pilihan kelas & siswa tetap bertahan
- ✅ Validasi server tetap: siswa wajib dipilih
- ✅ File diubah: hanya `konsultasi/tambah.php`

---

## Version 1.3.0 (2026-08-05)

### ✅ Fitur Baru: Konsultasi Siswa (Guru BK & Admin)
- ✅ Menu **Konsultasi Siswa** di sidebar (khusus Admin & Guru BK)
- ✅ Log konsultasi diambil dari **Master Siswa** sebagai aktor utama
- ✅ **Permasalahan** (narasi) - hanya diisi Guru BK/Admin
- ✅ **Tindak Lanjut** (narasi) - hanya diisi Guru BK/Admin
- ✅ **Bukti dukung** upload: foto (JPG/PNG) atau surat (PDF), maks 2MB, 1 file per konsultasi
- ✅ Di **Detail Siswa** ditambahkan section **"Riwayat Konsultasi"** di bawah Riwayat Pelanggaran (khusus Admin & Guru BK)
- ✅ Konsultasi bersifat **confidential** - tidak terlihat oleh Wali Kelas & Siswa
- ✅ CRUD lengkap: catat, lihat, edit (termasuk ganti lampiran), hapus
- ✅ Download lampiran via PHP dengan validasi akses role
- ✅ Search by nama siswa / permasalahan + filter rentang tanggal + pagination
- ✅ Konselor (Guru BK) auto-fill dari user login
- ✅ API endpoints: `/api/konsultasi/` (list, create, update, delete)
- ✅ Index `(siswa_id, tanggal)` untuk performa 1500+ siswa

### Files Baru
- `konsultasi/index.php` - Daftar konsultasi
- `konsultasi/tambah.php` - Form catat konsultasi + upload lampiran
- `konsultasi/edit.php` - Edit narasi + ganti lampiran
- `konsultasi/hapus.php` - Hapus konsultasi + file
- `konsultasi/hapus_lampiran.php` - Hapus lampiran saja
- `konsultasi/download.php` - Download lampiran (validasi role)
- `api/konsultasi/list.php`, `create.php`, `update.php`, `delete.php`
- `sql/migration_v1.3.0.sql`

### Files Dimodifikasi
- `includes/upload.php` - Fungsi `upload_lampiran_konsultasi()` + `hapus_lampiran_konsultasi()`
- `includes/header.php` - Menu "Konsultasi Siswa" (bk_only)
- `siswa/detail.php` - Section "Riwayat Konsultasi"
- `api/index.php` - Daftar endpoint konsultasi
- `sql/smart_bk.sql` - Tabel `konsultasi_siswa`
- `.gitignore` - Upload folder lampiran (sudah tercakup)

---

## Version 1.2.0 (2026-08-05)

### ✅ Fitur Baru: Buku Tamu
- ✅ Menu **Buku Tamu** di sidebar (hanya terlihat oleh Admin & Guru BK)
- ✅ Tabel log: No, Hari & Tanggal, Nama Tamu, Keperluan, Tindak Lanjut Guru BK, Pencatat
- ✅ Nama hari otomatis dalam Bahasa Indonesia (Senin–Minggu)
- ✅ CRUD lengkap: catat tamu, edit, hapus
- ✅ Search by nama/keperluan/tindak lanjut
- ✅ Filter rentang tanggal (dari s/d sampai)
- ✅ Pagination
- ✅ Pencatat otomatis dari user yang login
- ✅ Proteksi akses: hanya Admin & Guru BK (menu & URL)
- ✅ API endpoints: `/api/buku_tamu/` (list, create, update, delete)

### 🐛 Bug Fix: API Endpoint Tidak Berfungsi
- **Masalah:** `api_response([...])` di akhir `api/index.php` selalu dieksekusi saat file di-require oleh endpoint lain, sehingga **SEMUA API endpoint** mengembalikan halaman info listing (bukan data)
- **Fix:** Tambah guard `realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)` agar info listing hanya tampil saat `api/index.php` diakses langsung
- **Dampak:** Semua API endpoint kini berfungsi benar (siswa, user, kelas, pelanggaran, dashboard, buku tamu)

### Files Baru
- `buku_tamu/index.php` - Daftar log buku tamu
- `buku_tamu/tambah.php` - Form catat tamu
- `buku_tamu/edit.php` - Form edit
- `buku_tamu/hapus.php` - Hapus catatan
- `api/buku_tamu/list.php` - API list
- `api/buku_tamu/create.php` - API create
- `api/buku_tamu/update.php` - API update
- `api/buku_tamu/delete.php` - API delete

### Files Dimodifikasi
- `includes/header.php` - Menu Buku Tamu (bk_only)
- `api/index.php` - Fix guard akses langsung + daftar endpoint buku tamu
- `sql/smart_bk.sql` - Tabel `buku_tamu`

---

## Version 1.1.1 (2026-08-05)

### ✅ Perbaikan Import Siswa (Template Excel)
- ✅ Template import sekarang berupa **file Excel (.xls)** yang sederhana
- ✅ Setiap kolom dalam cell Excel terpisah (10 kolom)
- ✅ Template hanya berisi: header + 1 baris contoh + 1 baris catatan singkat
- ✅ Contoh kelas pada template otomatis mengikuti data kelas di database
- ✅ Parser mendukung upload file **.xls (Excel)** dan **.csv**
- ✅ Baris catatan/petunjuk di template otomatis di-skip saat upload
- ✅ Halaman import menampilkan format visual per kolom

---

## Version 1.1.0 (2026-08-05)

### ✅ New Features

#### Role-Based Access Control (Wali Kelas)
- ✅ Wali Kelas hanya melihat siswa di kelasnya sendiri (Master Siswa)
- ✅ Wali Kelas hanya melihat riwayat pelanggaran siswa di kelasnya
- ✅ Dashboard Wali Kelas di-filter berdasarkan kelasnya
- ✅ Wali Kelas tidak bisa: tambah/hapus siswa, catat/edit/hapus pelanggaran
- ✅ Menu admin-only (User, Kelas, Master Pelanggaran) disembunyikan untuk Wali Kelas
- ✅ Proteksi akses langsung via URL (bukan hanya menyembunyikan menu)
- ✅ Session menyimpan `kelas_id` user saat login

#### Import Siswa via CSV (Excel Template)
- ✅ Download template CSV dengan format standar
- ✅ Upload file CSV (maks 2MB, maks 500 siswa)
- ✅ Preview data sebelum import (validasi visual)
- ✅ Validasi lengkap: NIPD unik, JK L/P, status valid, kelas valid
- ✅ Opsi "Skip NIPD sudah ada"
- ✅ Opsi "Update data jika NIPD sudah ada"
- ✅ Report summary: total baris, valid, error, di-skip
- ✅ Detail error per baris (nomor baris + alasan)
- ✅ Warning per baris (kelas tidak ditemukan, format tanggal)
- ✅ Bulk insert/update ke database dengan transaksi

### Files Baru (5)
- `siswa/template_import.php` - Generator template CSV
- `siswa/import.php` - Form upload + petunjuk
- `siswa/import_process.php` - Parser & validasi CSV
- `siswa/import_preview.php` - Preview data sebelum import
- `siswa/import_execute.php` - Eksekusi bulk insert/update

### Files Dimodifikasi (13)
- `includes/auth.php` - Helper functions (is_wali_kelas, get_user_kelas_id, can_see_all_data)
- `login.php` - Simpan kelas_id ke session
- `dashboard.php` - Filter data untuk Wali Kelas
- `siswa/index.php` - Filter role + tombol Import
- `siswa/tambah.php` - Proteksi akses
- `siswa/edit.php` - Validasi akses per kelas
- `siswa/hapus.php` - Proteksi akses
- `siswa/detail.php` - Validasi akses per kelas
- `pelanggaran/tambah.php` - Proteksi akses + filter kelas
- `pelanggaran/riwayat.php` - Filter role + hide tombol
- `pelanggaran/edit.php` - Proteksi akses
- `pelanggaran/hapus.php` - Proteksi akses
- `pelanggaran/master.php` - Proteksi admin-only

### Proteksi Admin-Only (9)
- `user/index.php`, `user/tambah.php`, `user/edit.php`, `user/hapus.php`
- `kelas/index.php`, `kelas/tambah.php`, `kelas/edit.php`, `kelas/hapus.php`
- `pelanggaran/jenis_tambah.php`, `jenis_edit.php`, `jenis_hapus.php`

---

## Version 1.0.0 (2026-08-05)

### ✅ Features Completed

#### Backend Infrastructure
- ✅ Database schema lengkap dengan 5 tabel utama
- ✅ PHP native connection handler dengan prepared statements
- ✅ Session-based authentication system
- ✅ Helper functions (flash messages, redirects, escape, etc.)
- ✅ File upload handler dengan image resizing
- ✅ Multi-tahun ajaran support

#### REST API (27 endpoints)
- ✅ Authentication API (`/api/auth/`)
  - Login with username/password + tahun ajaran
  - Logout
  - Check authentication status
- ✅ Dashboard API (`/api/dashboard/`)
  - Real-time statistics (total siswa, kelas, pelanggaran, siswa bermasalah)
  - Top 10 siswa dengan poin tertinggi
  - Chart data 6 bulan pelanggaran
- ✅ Siswa API (`/api/siswa/`)
  - List dengan pagination, search, dan filter
  - Detail siswa + riwayat pelanggaran
  - Create, Update, Delete
- ✅ User API (`/api/user/`)
  - List dengan filter by role
  - Create dengan validasi @belajar.id untuk Guru
  - Update (termasuk reset password)
  - Delete dengan proteksi self-delete
- ✅ Kelas API (`/api/kelas/`)
  - List dengan filter tahun ajaran
  - Create, Update, Delete
- ✅ Jenis Pelanggaran API (`/api/pelanggaran/`)
  - List semua jenis pelanggaran
  - Create, Update, Delete dengan validasi poin 1-100
- ✅ Pelanggaran Siswa API (`/api/pelanggaran/`)
  - List dengan pagination dan filter by siswa
  - Create pelanggaran (role Admin & Guru BK)
  - Delete pelanggaran

#### Frontend (PHP Views)
- ✅ Login page dengan pilihan tahun ajaran
- ✅ Dashboard dengan 4 summary cards + top 10 table + chart
- ✅ Master Siswa (list, tambah, edit, detail, hapus)
  - Search by nama/NIPD
  - Filter by kelas dan tahun ajaran
  - Pagination
  - Upload foto dengan preview
  - Riwayat pelanggaran per siswa
- ✅ Master User (list, tambah, edit, hapus)
  - Filter by role (Admin, Guru BK, Wali Kelas, Siswa)
  - Validasi format email @belajar.id untuk Guru
  - Dynamic form (kelas muncul untuk Wali Kelas)
  - Reset password
- ✅ Master Kelas (list, tambah, edit, hapus)
  - Jumlah siswa per kelas
  - Link ke wali kelas
- ✅ Master Jenis Pelanggaran (list, tambah, edit, hapus)
  - Kategori: Kedisiplinan, Tata Krama, Kekerasan, Narkoba, Lainnya
  - Bobot poin 1-100
  - Deskripsi dan konsekuensi
- ✅ Catat Pelanggaran Siswa
  - Auto-fill poin berdasarkan jenis pelanggaran
  - Tanggal kejadian, lokasi, keterangan, tindakan
  - Pelapor auto-fill dari session
- ✅ Riwayat Pelanggaran
  - Tabel lengkap dengan detail siswa, jenis, poin, pelapor
  - Link ke detail siswa

#### UI/UX Design
- ✅ Sidebar navigation dengan active state
- ✅ Topbar dengan user info dan tahun ajaran
- ✅ Card-based layout
- ✅ Data table dengan hover dan stripe
- ✅ Form dengan validation states
- ✅ Badge untuk poin (warna berdasarkan threshold)
- ✅ Flash messages (success/error) dengan auto-dismiss
- ✅ Confirmation dialog untuk delete actions
- ✅ Responsive design (mobile-friendly)
- ✅ Google Fonts (Inter) untuk typography
- ✅ Chart.js untuk grafik statistik

#### Security & Validation
- ✅ Session-based authentication
- ✅ Password hashing dengan bcrypt (PASSWORD_BCRYPT)
- ✅ Prepared statements untuk SQL queries (prevent SQL injection)
- ✅ Input sanitization (htmlspecialchars)
- ✅ CSRF protection via session check
- ✅ File upload validation (type, size, MIME check)
- ✅ Role-based access control (Admin only untuk CRUD v1.0)
- ✅ Username uniqueness validation
- ✅ NIPD uniqueness validation

#### Documentation
- ✅ Product Requirement Document (PRD_SmartBK.md)
- ✅ API Documentation (api/README.md) dengan contoh cURL
- ✅ Installation Guide (README.md)
- ✅ Database schema documentation
- ✅ Code comments untuk fungsi-fungsi kompleks

### 📊 Statistics
- Total API Endpoints: 27
- Total PHP Files: 70+
- Database Tables: 5
- Seed Data: 3 users, 2 kelas, 2 siswa, 4 jenis pelanggaran, 7 pelanggaran siswa

### 🔧 Technical Stack
- **Backend:** PHP 7.4+ (Native)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Chart Library:** Chart.js 4.x
- **Web Server:** Apache (XAMPP) / Nginx
- **Architecture:** MVC-like (views, controllers, models via helper functions)

---

## Roadmap v1.1 (Future)

### User Features
- [ ] Aktivasi role Guru BK (full access input pelanggaran)
- [ ] Aktivasi role Wali Kelas (read-only dashboard per kelas)
- [ ] Aktivasi role Siswa (lihat rekap poin sendiri)
- [ ] User profile page (edit foto, ganti password sendiri)

### Notification & Integration
- [ ] WhatsApp Gateway integration (Fonnte/Wablas)
- [ ] Auto-notify orang tua ketika poin > threshold
- [ ] Email notification (optional)
- [ ] SMS Gateway (optional)

### Reports & Export
- [ ] Export siswa to Excel/CSV
- [ ] Export pelanggaran to PDF
- [ ] Generate surat panggilan orang tua otomatis
- [ ] Cetak kartu konseling siswa
- [ ] Laporan bulanan/semester per kelas

### Import & Bulk Operations
- [ ] Import siswa massal via CSV/Excel
- [ ] Bulk update status siswa (naik kelas, lulus)
- [ ] Bulk delete dengan konfirmasi

### Analytics & Reporting
- [ ] Dashboard per kelas (untuk Wali Kelas)
- [ ] Trend pelanggaran per kategori
- [ ] Komparasi antar kelas
- [ ] Prediksi siswa berisiko (ML/AI optional)

### System Enhancement
- [ ] Audit log (track semua perubahan data)
- [ ] Soft delete untuk data penting
- [ ] Backup & restore database via UI
- [ ] Settings page (logo sekolah, nama sekolah, threshold poin)
- [ ] Multi-language support (Indonesia/English)
- [ ] Dark mode toggle

### Performance & Optimization
- [ ] Caching untuk dashboard stats
- [ ] Lazy loading untuk tabel besar
- [ ] Image optimization otomatis (WebP conversion)
- [ ] Pagination API dengan cursor-based

### Mobile App (Optional)
- [ ] Progressive Web App (PWA)
- [ ] Mobile app dengan React Native / Flutter
- [ ] Push notification untuk mobile

---

## Known Issues v1.0
- None (initial release)

---

## Breaking Changes
- None (initial release)

---

## Contributors
- Backend Developer: [Your Name]
- Frontend Developer: [Your Name]
- Database Designer: [Your Name]
- UI/UX Designer: [Your Name]

---

**Last Updated:** 2026-08-05
