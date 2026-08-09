<?php
function upload_foto_siswa(array $file)
{
    if (empty($file['name']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'name' => null];
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Gagal mengunggah foto. Kode error: ' . (int) $file['error']];
    }

    $maxSize = 500 * 1024;
    if ((int) $file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'Ukuran foto maksimal 500KB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Format foto harus JPG atau PNG.'];
    }

    $ext = $allowed[$mime];

    $dir = __DIR__ . '/../assets/uploads/foto_siswa';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'Folder upload tidak dapat ditulis.'];
    }

    $name = 'siswa_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $target = $dir . '/' . $name;

    $resized = resize_image($file['tmp_name'], $mime, 150, 150, $target);
    if (!$resized) {
        if (!@move_uploaded_file($file['tmp_name'], $target)) {
            return ['ok' => false, 'error' => 'Gagal menyimpan foto.'];
        }
    }

    return ['ok' => true, 'name' => $name];
}

function resize_image($source, $mime, $maxW, $maxH, $target)
{
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($source);
            break;
        case 'image/webp':
            $src = @imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    if (!$src) {
        return false;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $ratio = min($maxW / $w, $maxH / $h, 1);
    $nw = max(1, (int) round($w * $ratio));
    $nh = max(1, (int) round($h * $ratio));

    $dst = imagecreatetruecolor($nw, $nh);
    if (!$dst) {
        imagedestroy($src);
        return false;
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    switch ($mime) {
        case 'image/jpeg':
            $ok = imagejpeg($dst, $target, 85);
            break;
        case 'image/png':
            $ok = imagepng($dst, $target, 6);
            break;
        case 'image/webp':
            $ok = imagewebp($dst, $target, 85);
            break;
        default:
            $ok = false;
    }

    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}

/**
 * Upload lampiran konsultasi siswa.
 * Mendukung foto (JPG/PNG) dan surat (PDF), maksimal 2MB, 1 file.
 * Mengembalikan: ['ok'=>true,'file','original','type','size'] atau ['ok'=>false,'error'=>...]
 */
function upload_lampiran_konsultasi(array $file)
{
    if (empty($file['name']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null];
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Gagal mengunggah lampiran. Kode error: ' . (int) $file['error']];
    }

    $maxSize = 2 * 1024 * 1024;
    if ((int) $file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'Ukuran lampiran maksimal 2MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Format lampiran harus JPG, PNG, atau PDF.'];
    }

    $ext = $allowed[$mime];

    $dir = __DIR__ . '/../assets/uploads/lampiran_konsultasi';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'Folder upload tidak dapat ditulis.'];
    }

    $name = 'konsul_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $target = $dir . '/' . $name;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan lampiran.'];
    }

    return [
        'ok' => true,
        'file' => $name,
        'original' => $file['name'],
        'type' => $mime,
        'size' => (int) $file['size'],
    ];
}

/**
 * Hapus file lampiran konsultasi dari folder.
 */
function hapus_lampiran_konsultasi(?string $fileName): void
{
    if (empty($fileName)) {
        return;
    }
    $path = __DIR__ . '/../assets/uploads/lampiran_konsultasi/' . $fileName;
    if (file_exists($path)) {
        @unlink($path);
    }
}

/**
 * Upload barang bukti pelanggaran (dokumen / foto).
 * Mendukung foto (JPG/PNG/WebP) dan dokumen (PDF), maksimal 2MB, 1 file.
 * Mengembalikan: ['ok'=>true,'file','original','type','size'] atau ['ok'=>false,'error'=>...]
 */
function upload_bukti_pelanggaran(array $file)
{
    if (empty($file['name']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null];
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Gagal mengunggah bukti. Kode error: ' . (int) $file['error']];
    }

    $maxSize = 2 * 1024 * 1024;
    if ((int) $file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'Ukuran bukti maksimal 2MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Format bukti harus JPG, PNG, atau PDF.'];
    }
    $ext = $allowed[$mime];

    $dir = __DIR__ . '/../assets/uploads/bukti_pelanggaran';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    if (!is_writable($dir)) {
        return ['ok' => false, 'error' => 'Folder upload tidak dapat ditulis.'];
    }

    $name = 'bukti_' . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $target = $dir . '/' . $name;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan bukti.'];
    }

    return [
        'ok' => true,
        'file' => $name,
        'original' => $file['name'],
        'type' => $mime,
        'size' => (int) $file['size'],
    ];
}

/**
 * Hapus file bukti pelanggaran dari folder.
 */
function hapus_bukti_pelanggaran(?string $fileName): void
{
    if (empty($fileName)) {
        return;
    }
    $path = __DIR__ . '/../assets/uploads/bukti_pelanggaran/' . $fileName;
    if (file_exists($path)) {
        @unlink($path);
    }
}
