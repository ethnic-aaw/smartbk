<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin/Guru BK can import
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['Admin', 'Guru BK'], true)) {
    set_flash('error', 'Access denied');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

// Check if import data exists
if (!isset($_SESSION['import_data']) || !isset($_SESSION['import_data']['valid'])) {
    set_flash('error', 'Tidak ada data untuk di-import. Silakan upload file terlebih dahulu.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

$validData = $_SESSION['import_data']['valid'];

if (count($validData) === 0) {
    set_flash('error', 'Tidak ada data valid untuk di-import.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Execute bulk insert/update
$insertCount = 0;
$updateCount = 0;
$errorCount = 0;

foreach ($validData as $data) {
    if ($data['is_update']) {
        // Update existing record
        $ok = db_query(
            'UPDATE siswa SET 
                nama = ?, 
                jenis_kelamin = ?, 
                kelas_id = ?, 
                tempat_lahir = ?, 
                tanggal_lahir = ?, 
                nama_ayah = ?, 
                no_hp_ayah = ?, 
                pekerjaan_ayah = ?, 
                nama_ibu = ?, 
                no_hp_ibu = ?, 
                pekerjaan_ibu = ?, 
                nama_wali = ?, 
                alamat_orang_tua = ?, 
                alamat = ?, 
                status = ?
            WHERE id = ?',
            [
                $data['nama'],
                $data['jenis_kelamin'],
                $data['kelas_id'],
                $data['tempat_lahir'] ?: null,
                $data['tanggal_lahir'],
                $data['nama_ayah'] ?: null,
                $data['no_hp_ayah'] ?: null,
                $data['pekerjaan_ayah'] ?: null,
                $data['nama_ibu'] ?: null,
                $data['no_hp_ibu'] ?: null,
                $data['pekerjaan_ibu'] ?: null,
                $data['nama_wali'] ?: null,
                $data['alamat_orang_tua'] ?: null,
                $data['alamat'] ?: null,
                $data['status'],
                $data['siswa_id']
            ]
        );
        
        if ($ok) {
            $updateCount++;
        } else {
            $errorCount++;
        }
    } else {
        // Insert new record
        $ok = db_query(
            'INSERT INTO siswa (
                nipd, nama, jenis_kelamin, kelas_id, 
                tempat_lahir, tanggal_lahir, nama_ayah, no_hp_ayah, 
                pekerjaan_ayah, nama_ibu, no_hp_ibu, 
                pekerjaan_ibu, nama_wali, alamat_orang_tua, 
                alamat, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['nipd'],
                $data['nama'],
                $data['jenis_kelamin'],
                $data['kelas_id'],
                $data['tempat_lahir'] ?: null,
                $data['tanggal_lahir'],
                $data['nama_ayah'] ?: null,
                $data['no_hp_ayah'] ?: null,
                $data['pekerjaan_ayah'] ?: null,
                $data['nama_ibu'] ?: null,
                $data['no_hp_ibu'] ?: null,
                $data['pekerjaan_ibu'] ?: null,
                $data['nama_wali'] ?: null,
                $data['alamat_orang_tua'] ?: null,
                $data['alamat'] ?: null,
                $data['status']
            ]
        );
        
        if ($ok) {
            $insertCount++;
        } else {
            $errorCount++;
        }
    }
}

// Clear session data
unset($_SESSION['import_data']);

// Set flash message
$totalSuccess = $insertCount + $updateCount;
if ($totalSuccess > 0) {
    $message = "Import berhasil! ";
    if ($insertCount > 0 && $updateCount > 0) {
        $message .= "Ditambahkan {$insertCount} siswa baru, di-update {$updateCount} siswa.";
    } elseif ($insertCount > 0) {
        $message .= "Ditambahkan {$insertCount} siswa baru.";
    } else {
        $message .= "Di-update {$updateCount} siswa.";
    }
    
    if ($errorCount > 0) {
        $message .= " {$errorCount} data gagal diproses.";
    }
    
    set_flash('success', $message);
} else {
    set_flash('error', 'Import gagal. Tidak ada data yang berhasil diproses.');
}

redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
