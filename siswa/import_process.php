<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin/Guru BK can import
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['Admin', 'Guru BK'], true)) {
    set_flash('error', 'Access denied');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Validate file upload
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    set_flash('error', 'Gagal upload file.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

$file = $_FILES['csv_file'];
$skipDuplicate = isset($_POST['skip_duplicate']);
$updateExisting = isset($_POST['update_existing']);

// Validate file extension
$fileName = $file['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($fileExt, ['csv', 'xls'], true)) {
    set_flash('error', 'File harus format Excel (.xls) atau CSV (.csv)');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    set_flash('error', 'Ukuran file maksimal 2MB.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Read file (support Excel .xls dan CSV)
$csvData = [];

if ($fileExt === 'xls') {
    // Parse SpreadsheetML 2003 XML format
    $xml = @simplexml_load_file($file['tmp_name']);
    if ($xml === false) {
        set_flash('error', 'Gagal membaca file Excel. Pastikan file adalah template yang benar.');
        redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
    }
    $ns = $xml->getNamespaces(true);
    $ssNs = $ns['ss'] ?? 'urn:schemas-microsoft-com:office:spreadsheet';
    foreach ($xml->Worksheet->Table->Row as $row) {
        $cells = [];
        foreach ($row->Cell as $cell) {
            $cells[] = trim((string) $cell->Data);
        }
        $csvData[] = $cells;
    }
} else {
    // Parse CSV file
    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $csvData[] = $row;
        }
        fclose($handle);
    } else {
        set_flash('error', 'Gagal membaca file CSV.');
        redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
    }
}

// Validate minimum data
if (count($csvData) < 2) {
    set_flash('error', 'File kosong atau hanya berisi header.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Remove header row
$header = array_shift($csvData);

// Remove empty rows and catatan/petunjuk rows
$csvData = array_filter($csvData, function($row) {
    $first = isset($row[0]) ? trim($row[0]) : '';
    if ($first === '') {
        return !empty($row[1]);
    }
    if (strpos($first, '*') === 0) {
        return false;
    }
    if (stripos($first, 'CATATAN') !== false || stripos($first, 'PETUNJUK') !== false) {
        return false;
    }
    if (stripos($first, 'TIPS:') !== false) {
        return false;
    }
    return true;
});

// Validate max rows (500)
if (count($csvData) > 500) {
    set_flash('error', 'Maksimal 500 siswa per upload. File Anda berisi ' . count($csvData) . ' baris data.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

// Get existing NIPD for duplicate check
$existingNIPD = [];
$existingSiswa = [];
$nipdRows = db_fetch('SELECT id, nipd FROM siswa');
foreach ($nipdRows as $row) {
    $existingNIPD[] = strtoupper(trim($row['nipd']));
    $existingSiswa[strtoupper(trim($row['nipd']))] = (int)$row['id'];
}

// Get kelas mapping
$kelasMap = [];
$kelasRows = db_fetch('SELECT id, nama_kelas FROM kelas');
foreach ($kelasRows as $row) {
    $kelasMap[strtolower(trim($row['nama_kelas']))] = (int)$row['id'];
}

// Parse and validate data
$validData = [];
$errors = [];
$skipped = [];
$lineNumber = 2; // Start from row 2 (after header)

foreach ($csvData as $row) {
    $error = [];
    $warning = [];
    
    // Column mapping
    $nipd = trim($row[0] ?? '');
    $nama = trim($row[1] ?? '');
    $jenisKelamin = strtoupper(trim($row[2] ?? ''));
    $kelasNama = trim($row[3] ?? '');
    $tempatLahir = trim($row[4] ?? '');
    $tanggalLahir = trim($row[5] ?? '');
    $namaAyah = trim($row[6] ?? '');
    $noHpAyah = trim($row[7] ?? '');
    $pekerjaanAyah = trim($row[8] ?? '');
    $namaIbu = trim($row[9] ?? '');
    $noHpIbu = trim($row[10] ?? '');
    $pekerjaanIbu = trim($row[11] ?? '');
    $namaWali = trim($row[12] ?? '');
    $alamatOrtu = trim($row[13] ?? '');
    $alamat = trim($row[14] ?? '');
    $status = trim($row[15] ?? 'Aktif');
    
    // Validation: NIPD
    if ($nipd === '') {
        $error[] = 'NIPD/NIS wajib diisi';
    } elseif (strlen($nipd) > 20) {
        $error[] = 'NIPD/NIS maksimal 20 karakter';
    } elseif (in_array(strtoupper($nipd), $existingNIPD)) {
        if ($skipDuplicate && !$updateExisting) {
            $skipped[] = [
                'line' => $lineNumber,
                'nipd' => $nipd,
                'nama' => $nama,
                'reason' => 'NIPD sudah ada di database'
            ];
            $lineNumber++;
            continue;
        } elseif ($updateExisting) {
            $warning[] = 'NIPD sudah ada, akan di-update';
        } else {
            $error[] = 'NIPD sudah ada di database';
        }
    }
    
    // Validation: Nama
    if ($nama === '') {
        $error[] = 'Nama lengkap wajib diisi';
    } elseif (strlen($nama) > 100) {
        $error[] = 'Nama maksimal 100 karakter';
    }
    
    // Validation: Jenis Kelamin
    if (!in_array($jenisKelamin, ['L', 'P'], true)) {
        $error[] = 'Jenis kelamin harus L atau P';
    }
    
    // Validation: Kelas (optional)
    $kelasId = null;
    if ($kelasNama !== '') {
        $kelasKey = strtolower($kelasNama);
        if (isset($kelasMap[$kelasKey])) {
            $kelasId = $kelasMap[$kelasKey];
        } else {
            $warning[] = 'Kelas "' . $kelasNama . '" tidak ditemukan, siswa akan tanpa kelas';
        }
    }
    
    // Validation: Tanggal Lahir (optional)
    if ($tanggalLahir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
        $warning[] = 'Format tanggal lahir harus YYYY-MM-DD';
        $tanggalLahir = ''; // Reset to empty
    }
    
    // Validation: Status
    $validStatus = ['Aktif', 'Tidak Aktif', 'Pindah', 'Lulus'];
    if (!in_array($status, $validStatus, true)) {
        $status = 'Aktif'; // Default
    }
    
    // If there are errors, add to error list
    if (!empty($error)) {
        $errors[] = [
            'line' => $lineNumber,
            'nipd' => $nipd,
            'nama' => $nama,
            'errors' => $error
        ];
        $lineNumber++;
        continue;
    }
    
    // Add to valid data
    $isUpdate = $updateExisting && in_array(strtoupper($nipd), $existingNIPD);
    $validData[] = [
        'line' => $lineNumber,
        'nipd' => $nipd,
        'nama' => $nama,
        'jenis_kelamin' => $jenisKelamin,
        'kelas_id' => $kelasId,
        'kelas_nama' => $kelasNama,
        'tempat_lahir' => $tempatLahir,
        'tanggal_lahir' => $tanggalLahir !== '' ? $tanggalLahir : null,
        'nama_ayah' => $namaAyah,
        'no_hp_ayah' => $noHpAyah,
        'pekerjaan_ayah' => $pekerjaanAyah,
        'nama_ibu' => $namaIbu,
        'no_hp_ibu' => $noHpIbu,
        'pekerjaan_ibu' => $pekerjaanIbu,
        'nama_wali' => $namaWali,
        'alamat_orang_tua' => $alamatOrtu,
        'alamat' => $alamat,
        'status' => $status,
        'warnings' => $warning,
        'is_update' => $isUpdate,
        'siswa_id' => $isUpdate ? $existingSiswa[strtoupper($nipd)] : null
    ];
    
    $lineNumber++;
}

// Store data in session for preview
$_SESSION['import_data'] = [
    'valid' => $validData,
    'errors' => $errors,
    'skipped' => $skipped,
    'total_rows' => count($csvData),
    'timestamp' => time()
];

// Redirect to preview page
redirect_to(rtrim(APP_BASE, '/') . '/siswa/import_preview.php');
