# Smart BK - Testing Guide

## 🧪 Manual Testing Checklist

### Prerequisites
- ✅ XAMPP/Laragon running (Apache + MySQL)
- ✅ Database `smart_bk` sudah diimport
- ✅ Akses http://localhost/smartbk/

---

## 1. Authentication & Session

### Test 1.1: Login Success
```
Steps:
1. Buka http://localhost/smartbk/
2. Input: username = admin, password = admin123, tahun = 2024/2025
3. Klik "Masuk"

Expected:
✅ Redirect ke dashboard
✅ Topbar menampilkan nama "Admin Smart BK"
✅ Tahun ajaran "2024/2025" tampil di topbar

Status: □ Pass  □ Fail
```

### Test 1.2: Login Failed
```
Steps:
1. Input: username = admin, password = wrong123
2. Klik "Masuk"

Expected:
✅ Error message "Username atau password salah"
✅ Tetap di halaman login

Status: □ Pass  □ Fail
```

### Test 1.3: Logout
```
Steps:
1. Login terlebih dahulu
2. Klik "Logout" di topbar
3. Coba akses /dashboard.php langsung

Expected:
✅ Redirect ke /login.php
✅ Session terhapus
✅ Tidak bisa akses dashboard tanpa login

Status: □ Pass  □ Fail
```

---

## 2. Dashboard

### Test 2.1: Dashboard Statistics
```
Steps:
1. Login sebagai admin
2. Perhatikan 4 card summary

Expected:
✅ Total Siswa tampil (angka benar sesuai database)
✅ Total Kelas tampil
✅ Total Pelanggaran Bulan Ini tampil
✅ Siswa Bermasalah tampil (poin > 75)

Status: □ Pass  □ Fail
```

### Test 2.2: Top 10 Siswa
```
Steps:
1. Cek tabel "Top 10 Siswa Poin Tertinggi"

Expected:
✅ Data diurutkan DESC by total poin
✅ Badge poin tampil dengan warna sesuai threshold
✅ Link ke detail siswa berfungsi

Status: □ Pass  □ Fail
```

### Test 2.3: Chart 6 Bulan
```
Steps:
1. Cek grafik bar chart di dashboard

Expected:
✅ Chart tampil dengan benar (tidak error)
✅ 6 bulan terakhir tampil
✅ Tooltip berfungsi saat hover

Status: □ Pass  □ Fail
```

---

## 3. Master Siswa

### Test 3.1: List & Filter (BUG FIXED)
```
Steps:
1. Buka /siswa/index.php
2. Pilih tahun ajaran "2024/2025" dari dropdown
3. Klik "Filter"

Expected:
✅ Data siswa terfilter tanpa error SQL
✅ Pagination berfungsi normal
✅ Total data sesuai

Status: □ Pass  □ Fail

NOTE: Bug ini SUDAH DIPERBAIKI (missing LEFT JOIN kelas)
```

### Test 3.2: Search Siswa
```
Steps:
1. Ketik "Rizki" di search box
2. Klik "Filter"

Expected:
✅ Hanya siswa dengan nama mengandung "Rizki" yang tampil
✅ Search by NIPD juga berfungsi

Status: □ Pass  □ Fail
```

### Test 3.3: Tambah Siswa (Tanpa Foto)
```
Steps:
1. Klik "+ Tambah Siswa"
2. Isi form:
   - NIPD: TEST001
   - Nama: Test Siswa
   - JK: Laki-laki
   - Kelas: X IPA 1
   - Status: Aktif
3. Klik "Simpan"

Expected:
✅ Flash message "Siswa berhasil ditambahkan"
✅ Redirect ke list siswa
✅ Data muncul di tabel

Status: □ Pass  □ Fail
```

### Test 3.4: Tambah Siswa (Dengan Foto)
```
Steps:
1. Klik "+ Tambah Siswa"
2. Isi form lengkap + upload foto JPG (< 500KB)
3. Klik "Simpan"

Expected:
✅ Foto ter-upload ke assets/uploads/foto_siswa/
✅ Foto diresize ke 150x150px
✅ Foto tampil di list dan detail

Status: □ Pass  □ Fail
```

### Test 3.5: Validation - NIPD Duplicate
```
Steps:
1. Tambah siswa dengan NIPD yang sudah ada (misal: 2024001)
2. Klik "Simpan"

Expected:
✅ Error "NIPD/NIS sudah terdaftar"
✅ Form tidak ter-submit
✅ Data lama tetap utuh

Status: □ Pass  □ Fail
```

### Test 3.6: Edit Siswa
```
Steps:
1. Klik "Edit" pada salah satu siswa
2. Ubah nama siswa
3. Klik "Simpan Perubahan"

Expected:
✅ Flash message "Data siswa berhasil diperbarui"
✅ Perubahan tersimpan di database

Status: □ Pass  □ Fail
```

### Test 3.7: Edit Foto Siswa (Replace)
```
Steps:
1. Edit siswa yang sudah punya foto
2. Upload foto baru
3. Klik "Simpan Perubahan"

Expected:
✅ Foto lama terhapus dari folder
✅ Foto baru tersimpan
✅ Foto baru tampil di detail

Status: □ Pass  □ Fail
```

### Test 3.8: Hapus Siswa
```
Steps:
1. Klik "Hapus" pada siswa test
2. Klik OK pada konfirmasi

Expected:
✅ Flash message "Siswa telah dihapus"
✅ Data terhapus dari database
✅ Foto terhapus dari folder
✅ Riwayat pelanggaran siswa ikut terhapus

Status: □ Pass  □ Fail
```

### Test 3.9: Detail Siswa
```
Steps:
1. Klik nama siswa di list
2. Cek halaman detail

Expected:
✅ Foto tampil (atau initial jika tidak ada foto)
✅ Data siswa lengkap tampil
✅ Total poin akumulasi tampil
✅ Riwayat pelanggaran tampil (jika ada)

Status: □ Pass  □ Fail
```

### Test 3.10: Pagination (BUG FIXED)
```
Steps:
1. Pastikan ada > 15 siswa
2. Klik halaman 2, 3, dst

Expected:
✅ Pagination berfungsi normal
✅ Data berubah sesuai halaman
✅ Tidak ada error SQL binding

Status: □ Pass  □ Fail

NOTE: Bug parameter binding SUDAH DIPERBAIKI (integer type)
```

---

## 4. Master User

### Test 4.1: List User & Filter
```
Steps:
1. Buka /user/index.php
2. Klik tab "Guru BK"

Expected:
✅ Hanya user dengan role Guru BK yang tampil
✅ Filter by role berfungsi untuk semua role

Status: □ Pass  □ Fail
```

### Test 4.2: Tambah Admin
```
Steps:
1. Klik "+ Tambah User"
2. Isi form:
   - Nama: Admin Test
   - Username: admintest
   - Password: admin12345
   - Role: Admin
   - Status: Aktif
3. Klik "Simpan"

Expected:
✅ User berhasil ditambahkan
✅ Password ter-hash dengan bcrypt

Status: □ Pass  □ Fail
```

### Test 4.3: Tambah Guru BK (Validation)
```
Steps:
1. Tambah user dengan role "Guru BK"
2. Username: guru (tanpa @belajar.id)
3. Klik "Simpan"

Expected:
✅ Error "Username Guru BK harus berformat nama@belajar.id"
✅ Form tidak ter-submit

Status: □ Pass  □ Fail
```

### Test 4.4: Tambah Guru BK (Valid)
```
Steps:
1. Username: guru.test@belajar.id
2. Role: Guru BK
3. Klik "Simpan"

Expected:
✅ User berhasil ditambahkan
✅ Validasi email @belajar.id lolos

Status: □ Pass  □ Fail
```

### Test 4.5: Tambah Wali Kelas
```
Steps:
1. Role: Wali Kelas
2. Kelas Diampu: X IPA 1
3. Klik "Simpan"

Expected:
✅ Field "Kelas Diampu" muncul ketika role = Wali Kelas
✅ User berhasil ditambahkan dengan kelas_id

Status: □ Pass  □ Fail
```

### Test 4.6: Edit User (Tanpa Ganti Password)
```
Steps:
1. Edit user, ubah nama saja
2. Password field kosongkan
3. Klik "Simpan Perubahan"

Expected:
✅ Nama berubah
✅ Password TIDAK berubah (tetap bisa login dengan password lama)

Status: □ Pass  □ Fail
```

### Test 4.7: Edit User (Reset Password)
```
Steps:
1. Edit user, isi password baru
2. Klik "Simpan Perubahan"
3. Logout dan login dengan password baru

Expected:
✅ Password berhasil diubah
✅ Bisa login dengan password baru
✅ Password lama tidak bisa digunakan

Status: □ Pass  □ Fail
```

### Test 4.8: Hapus User (Self-Delete Protection)
```
Steps:
1. Login sebagai admin
2. Coba hapus user admin sendiri (ID yang sedang login)

Expected:
✅ Error "Tidak bisa menghapus akun sendiri"
✅ Data tidak terhapus

Status: □ Pass  □ Fail
```

### Test 4.9: Hapus User (Valid)
```
Steps:
1. Hapus user test yang tadi dibuat

Expected:
✅ Flash message "User telah dihapus"
✅ Data terhapus dari database

Status: □ Pass  □ Fail
```

---

## 5. Master Kelas

### Test 5.1: List Kelas
```
Steps:
1. Buka /kelas/index.php

Expected:
✅ Semua kelas tampil sesuai tahun ajaran login
✅ Jumlah siswa per kelas tampil
✅ Nama wali kelas tampil

Status: □ Pass  □ Fail
```

### Test 5.2: Tambah Kelas
```
Steps:
1. Klik "+ Tambah Kelas"
2. Isi:
   - Nama Kelas: XI IPA 3
   - Tingkat: XI
   - Tahun Ajaran: 2024/2025
   - Wali Kelas: (pilih dari dropdown)
3. Klik "Simpan"

Expected:
✅ Kelas berhasil ditambahkan
✅ Wali kelas ter-assign

Status: □ Pass  □ Fail
```

### Test 5.3: Edit Kelas
```
Steps:
1. Edit kelas yang tadi dibuat
2. Ubah nama kelas
3. Klik "Simpan Perubahan"

Expected:
✅ Perubahan tersimpan

Status: □ Pass  □ Fail
```

### Test 5.4: Hapus Kelas
```
Steps:
1. Hapus kelas test (yang tidak ada siswanya)

Expected:
✅ Kelas terhapus
✅ Siswa yang di kelas tersebut menjadi kelas_id = NULL

Status: □ Pass  □ Fail
```

---

## 6. Master Jenis Pelanggaran

### Test 6.1: List Jenis Pelanggaran
```
Steps:
1. Buka /pelanggaran/master.php

Expected:
✅ Semua jenis pelanggaran tampil
✅ Diurutkan by bobot poin ASC

Status: □ Pass  □ Fail
```

### Test 6.2: Tambah Jenis Pelanggaran
```
Steps:
1. Klik "+ Tambah Pelanggaran"
2. Isi:
   - Kode: PLG-999
   - Nama: Test Pelanggaran
   - Kategori: Kedisiplinan
   - Bobot Poin: 5
   - Deskripsi: Test deskripsi
3. Klik "Simpan"

Expected:
✅ Jenis pelanggaran berhasil ditambahkan

Status: □ Pass  □ Fail
```

### Test 6.3: Validation - Kode Duplicate
```
Steps:
1. Tambah jenis pelanggaran dengan kode yang sudah ada (PLG-001)

Expected:
✅ Error "Kode sudah digunakan"

Status: □ Pass  □ Fail
```

### Test 6.4: Validation - Bobot Poin Out of Range
```
Steps:
1. Input bobot poin = 101 (di atas 100)

Expected:
✅ Error "Bobot poin harus antara 1-100"

Status: □ Pass  □ Fail
```

### Test 6.5: Edit Jenis Pelanggaran
```
Steps:
1. Edit jenis pelanggaran test
2. Ubah bobot poin jadi 10

Expected:
✅ Perubahan tersimpan

Status: □ Pass  □ Fail
```

### Test 6.6: Hapus Jenis Pelanggaran (Digunakan)
```
Steps:
1. Coba hapus jenis pelanggaran yang sudah digunakan (PLG-001)

Expected:
✅ Error "Jenis pelanggaran ini masih digunakan dan tidak dapat dihapus"

Status: □ Pass  □ Fail
```

### Test 6.7: Hapus Jenis Pelanggaran (Valid)
```
Steps:
1. Hapus jenis pelanggaran test yang tidak digunakan

Expected:
✅ Berhasil dihapus

Status: □ Pass  □ Fail
```

---

## 7. Pelanggaran Siswa

### Test 7.1: Catat Pelanggaran
```
Steps:
1. Buka /pelanggaran/tambah.php
2. Isi:
   - Siswa: Rizki Putra
   - Jenis Pelanggaran: PLG-001 Terlambat
   - Tanggal: hari ini
   - Lokasi: Gerbang sekolah
   - Keterangan: Terlambat 10 menit
3. Klik "Simpan"

Expected:
✅ Pelanggaran berhasil dicatat
✅ Poin otomatis terisi dari jenis pelanggaran
✅ Pelapor auto-fill dari session

Status: □ Pass  □ Fail
```

### Test 7.2: Riwayat Pelanggaran (BUG FIXED)
```
Steps:
1. Buka /pelanggaran/riwayat.php
2. Filter by kelas
3. Klik halaman 2 (jika ada pagination)

Expected:
✅ Data terfilter tanpa error SQL
✅ Pagination berfungsi normal

Status: □ Pass  □ Fail

NOTE: Bug LEFT JOIN kelas SUDAH DIPERBAIKI
```

### Test 7.3: Edit Pelanggaran
```
Steps:
1. Klik "Edit" pada salah satu pelanggaran
2. Ubah tanggal atau keterangan
3. Klik "Simpan Perubahan"

Expected:
✅ Perubahan tersimpan

Status: □ Pass  □ Fail
```

### Test 7.4: Hapus Pelanggaran
```
Steps:
1. Klik "Hapus" pada pelanggaran
2. Konfirmasi OK

Expected:
✅ Pelanggaran terhapus
✅ Total poin siswa berkurang

Status: □ Pass  □ Fail
```

### Test 7.5: Detail Siswa - Total Poin
```
Steps:
1. Buka detail siswa yang punya riwayat pelanggaran
2. Cek total poin di card info

Expected:
✅ Total poin = SUM(bobot_poin) dari semua pelanggaran
✅ Badge warna sesuai threshold:
   - 0-25: hijau
   - 26-50: kuning
   - 51-75: orange
   - >75: merah

Status: □ Pass  □ Fail
```

---

## 8. API Testing

### Test 8.1: API Login
```bash
curl -X POST http://localhost/smartbk/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123","tahun_ajaran":"2024/2025"}' \
  -c cookies.txt
```

Expected:
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user": {...},
    "tahun_ajaran": "2024/2025"
  }
}
```

Status: □ Pass  □ Fail

### Test 8.2: API Dashboard Stats
```bash
curl -X GET http://localhost/smartbk/api/dashboard/stats.php -b cookies.txt
```

Expected:
```json
{
  "success": true,
  "data": {
    "summary": {...},
    "top_siswa": [...],
    "chart": {...}
  }
}
```

Status: □ Pass  □ Fail

### Test 8.3: API List Siswa
```bash
curl -X GET "http://localhost/smartbk/api/siswa/list.php?page=1&per_page=10" -b cookies.txt
```

Expected:
- HTTP 200
- JSON response dengan pagination

Status: □ Pass  □ Fail

---

## 📊 Test Summary

**Total Test Cases:** 50+  
**Categories:**
- Authentication: 3 tests
- Dashboard: 3 tests
- Master Siswa: 10 tests
- Master User: 9 tests
- Master Kelas: 4 tests
- Master Jenis Pelanggaran: 7 tests
- Pelanggaran Siswa: 5 tests
- API: 3 tests

---

## 🐛 Known Issues (Fixed)

1. ✅ Parameter binding type (integer vs string) - FIXED
2. ✅ Missing LEFT JOIN kelas in COUNT query - FIXED
3. ✅ poin_badge() missing condition - FIXED

---

## 📝 Notes

- Test menggunakan data seed dari `sql/smart_bk.sql`
- Default admin: username=admin, password=admin123
- Gunakan tahun ajaran 2024/2025 untuk testing
- Backup database sebelum testing delete operations

---

**Last Updated:** 2026-08-05  
**Version:** 1.0  
**Status:** Ready for Testing
