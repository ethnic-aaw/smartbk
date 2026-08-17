# Panduan Setup Google OAuth 2.0 di Google Cloud Console

## Prasyarat
- Akun Google (sebaiknya akun admin sekolah / Kepala Sekolah / IT)
- Akses ke [Google Cloud Console](https://console.cloud.google.com/)

---

## Step-by-Step

### 1. Buka Google Cloud Console
1. Kunjungi: https://console.cloud.google.com/
2. Login dengan akun Google yang berwenang
3. Jika diminta, setuju dengan Terms of Service

### 2. Buat Project Baru (atau pilih existing)
1. Klik dropdown project di navbar atas (berlabel "Select a project" / "Pilih project")
2. Klik **NEW PROJECT** / **PROJECT BARU**
3. **Project name**: `Smart BK SMKN 1 Leuwimunding` (atau nama sekolah Anda)
4. **Location**: Pilih organization jika ada, atau "No organization"
5. Klik **CREATE** / **BUAT**
6. Tunggu notifikasi "Project created", lalu klik **SELECT PROJECT** / **PILIH PROJECT**

### 3. Konfigurasi OAuth Consent Screen
1. Di menu kiri: **APIs & Services** → **OAuth consent screen** (Layar persetujuan OAuth)
2. **User Type**: Pilih **External** (karena user menggunakan akun `@belajar.id` yang keluar domain Anda)
   - Catatan: External memerlukan verifikasi jika >100 user, tapi untuk development bisa pakai "Testing" dulu
3. Klik **CREATE**
4. **App information**:
   - **App name**: `Smart BK`
   - **User support email**: email admin/IT sekolah
   - **App logo**: (opsional) upload logo sekolah
5. **App domain**:
   - **Application home page**: `https://your-domain.com/smartbk` (isi nanti saat deploy)
   - **Application privacy policy link**: (opsional)
   - **Application terms of service link**: (opsional)
   - **Authorized domains**: `belajar.id` (wajib agar user @belajar.id bisa login)
6. Klik **SAVE AND CONTINUE**
7. **Scopes**: Klik **ADD OR REMOVE SCOPES**
   - Pilih: `.../auth/userinfo.email`, `.../auth/userinfo.profile`, `openid`
   - Klik **UPDATE**, lalu **SAVE AND CONTINUE**
8. **Test users** (hanya untuk External apps yang belum verified):
   - Tambahkan email admin/tester: `admin@sekolah.sch.id`, `test@belajar.id`, dll
   - Maksimal 100 user untuk testing
   - Klik **SAVE AND CONTINUE**
9. **Summary**: Review lalu **BACK TO DASHBOARD**

> **Penting**: Saat production, Anda harus **Submit for Verification** (bisa 1-4 minggu). Untuk development/testing, gunakan mode "Testing" dengan menambahkan test users manual.

### 4. Buat OAuth 2.0 Credentials
1. Di menu kiri: **APIs & Services** → **Credentials** (Kredensial)
2. Klik **+ CREATE CREDENTIALS** → **OAuth client ID**
3. **Application type**: **Web application**
4. **Name**: `Smart BK Web Client`
5. **Authorized JavaScript origins** (tambahkan semua yang berlaku):
   - `http://localhost:9000` (development XAMPP)
   - `http://localhost` (jika pakai port 80)
   - `https://your-domain.com` (production - isi nanti)
   - `https://smk1leuwimunding.sch.id` (contoh domain sekolah)
6. **Authorized redirect URIs** (WAJIB - harus persis sama dengan kode):
   - `http://localhost:9000/smartbk/auth/google_callback.php`
   - `http://localhost/smartbk/auth/google_callback.php`
   - `https://your-domain.com/smartbk/auth/google_callback.php` (production)
   - `https://smk1leuwimunding.sch.id/smartbk/auth/google_callback.php`
7. Klik **CREATE**
8. **SIMPAN KREDENSIAL**:
   - **Client ID**: `xxxxxxxxxx-xxxxxxxxxxxx.apps.googleusercontent.com`
   - **Client Secret**: `GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx`
   - Klik **DOWNLOAD JSON** (opsional, untuk backup)
9. Klik **DONE**

### 5. Enable Google People API (opsional tapi direkomendasikan)
1. Di menu kiri: **APIs & Services** → **Library**
2. Cari: **Google People API**
3. Klik **ENABLE**
   - Ini memungkinkan akses ke nama lengkap, foto profil, dll

### 6. Konfigurasi .env di Smart BK
1. Copy `.env.example` ke `.env`:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env`:
   ```env
   GOOGLE_CLIENT_ID=xxxxxxxxxx-xxxxxxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
   GOOGLE_REDIRECT_URI=http://localhost:9000/smartbk/auth/google_callback.php
   ```
   > Sesuaikan `GOOGLE_REDIRECT_URI` dengan environment (dev/prod)

### 7. Install Dependencies
```bash
composer install
```
Ini akan menginstall `google/apiclient` di folder `vendor/`.

### 8. Test Login
1. Jalankan aplikasi: `http://localhost:9000/smartbk/login.php`
2. Klik tombol **"Masuk dengan Google"**
3. Pilih akun `@belajar.id`
4. Jika berhasil → redirect ke `register.php` (jika user baru) atau `dashboard.php` (jika sudah approved)

---

## Troubleshooting

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `redirect_uri_mismatch` | URI di Cloud Console tidak cocok dengan kode | Pastikan `Authorized redirect URIs` persis sama (termasuk trailing slash) |
| `access_denied` / `unauthorized_client` | User bukan test user (app External belum verified) | Tambahkan email user ke **Test users** di OAuth consent screen |
| `invalid_client` | Client ID/Secret salah | Cek `.env`, pastikan tidak ada spasi/typo |
| `This app isn't verified` | App External belum diverifikasi Google | Klik "Advanced" → "Go to Smart BK (unsafe)" untuk testing, atau submit verification |
| `Invalid domain` | User login pakai email bukan `@belajar.id` | Validasi di `google_callback.php` akan menolak otomatis |

---

## Checklist Sebelum Production
- [ ] Domain production ditambah ke **Authorized JavaScript origins** & **Authorized redirect URIs**
- [ ] OAuth Consent Screen **verified** oleh Google (submit for verification)
- [ ] `.env` production menggunakan HTTPS redirect URI
- [ ] `GOOGLE_CLIENT_SECRET` tidak di-commit ke git (sudah di `.gitignore`)
- [ ] Test login dengan minimal 3 akun `@belajar.id` berbeda
- [ ] Approval flow berjalan: register → pending → approve → login success

---

## Referensi
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [OAuth Consent Screen Verification](https://support.google.com/cloud/answer/9110914)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)