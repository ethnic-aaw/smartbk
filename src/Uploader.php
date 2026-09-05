<?php
declare(strict_types=1);

namespace SmartBK;

/**
 * Centralized upload + deletion helper for Smart BK.
 *
 * Every physical upload path is derived from a single base constant
 * (UPLOAD_BASE) so the location is never re-derived (and mistyped) in each
 * caller. Validation (error / size / MIME), directory creation, date-based
 * naming and move_uploaded_file all live here.
 *
 * foto_siswa additionally goes through GD (150x150). lampiran_konsultasi and
 * bukti_pelanggaran only validate size/MIME and move the file as-is.
 */
final class Uploader
{
    /** Physical root of every uploaded asset. */
    public const UPLOAD_BASE = __DIR__ . '/../assets/uploads';

    public const DIR_FOTO_SISWA = self::UPLOAD_BASE . '/foto_siswa';
    public const DIR_LAMPIRAN_KONSULTASI = self::UPLOAD_BASE . '/lampiran_konsultasi';
    public const DIR_BUKTI_PELANGGARAN = self::UPLOAD_BASE . '/bukti_pelanggaran';

    public const MAX_FOTO_SISWA = 500 * 1024;       // 500 KB
    public const MAX_DOKUMEN = 2 * 1024 * 1024;      // 2 MB

    public const FOTO_SISWA_SIZE = 150;              // 150x150

    /** MIME -> extension map, images only. */
    public const IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** MIME -> extension map, images + PDF. */
    public const DOKUMEN_MIME = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    private function __construct()
    {
    }

    // ---- Public upload entry points ---------------------------------------

    /**
     * foto_siswa: JPG/PNG/WebP, max 500KB, resized to FOTO_SISWA_SIZE x
     * FOTO_SISWA_SIZE (GD, aspect ratio preserved, never upscaled).
     *
     * Returns ['ok'=>true,'name'=>'<file>|null'] or ['ok'=>false,'error'=>str].
     */
    public static function uploadFotoSiswa(array $file): array
    {
        $res = self::store(
            $file,
            self::DIR_FOTO_SISWA,
            self::IMAGE_MIME,
            self::MAX_FOTO_SISWA,
            'siswa_',
            true
        );

        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error']];
        }
        return ['ok' => true, 'name' => $res['file']];
    }

    /**
     * lampiran_konsultasi: JPG/PNG/Webp/PDF, max 2MB, stored as-is.
     *
     * Returns ['ok'=>true,'file'|'original'|'type'|'size'] or
     * ['ok'=>false,'error'=>str] or ['ok'=>true,'file'=>null] when no file.
     */
    public static function uploadLampiranKonsultasi(array $file): array
    {
        return self::store(
            $file,
            self::DIR_LAMPIRAN_KONSULTASI,
            self::DOKUMEN_MIME,
            self::MAX_DOKUMEN,
            'konsul_',
            false
        );
    }

    /**
     * bukti_pelanggaran: JPG/PNG/Webp/PDF, max 2MB, stored as-is.
     */
    public static function uploadBuktiPelanggaran(array $file): array
    {
        return self::store(
            $file,
            self::DIR_BUKTI_PELANGGARAN,
            self::DOKUMEN_MIME,
            self::MAX_DOKUMEN,
            'bukti_',
            false
        );
    }

    // ---- Public delete entry points ---------------------------------------

    public static function hapusFotoSiswa(?string $fileName): void
    {
        self::delete(self::DIR_FOTO_SISWA, $fileName);
    }

    public static function hapusLampiranKonsultasi(?string $fileName): void
    {
        self::delete(self::DIR_LAMPIRAN_KONSULTASI, $fileName);
    }

    public static function hapusBuktiPelanggaran(?string $fileName): void
    {
        self::delete(self::DIR_BUKTI_PELANGGARAN, $fileName);
    }

    // ---- GD resize --------------------------------------------------------

    /**
     * Resize $source to fit within $maxW x $maxH (aspect ratio preserved,
     * never upscaled) and write it to $target via the matching GD output
     * function. Returns true on success, false on failure.
     */
    public static function resizeImage(string $source, string $mime, int $maxW, int $maxH, string $target): bool
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

    // ---- Internal helpers -------------------------------------------------

    /**
     * Generic store: validate $file and move it into $dir (optionally
     * resized for the foto_siswa case).
     *
     * Success: ['ok'=>true,'file'=>name,'original'=>orig,'type'=>mime,'size'=>size]
     * No file: ['ok'=>true,'file'=>null]
     * Error:   ['ok'=>false,'error'=>message]
     */
    private static function store(array $file, string $dir, array $allowedMime, int $maxSize, string $prefix, bool $resize): array
    {
        if (empty($file['name']) && (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'file' => null];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Gagal mengunggah. Kode error: ' . (int) $file['error']];
        }

        if ((int) $file['size'] > $maxSize) {
            return ['ok' => false, 'error' => 'Ukuran file melebihi batas maksimal yang ditentukan.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);
        if (!isset($allowedMime[$mime])) {
            return ['ok' => false, 'error' => 'Format file tidak didukung.'];
        }
        $ext = $allowedMime[$mime];

        $dirError = self::ensureDir($dir);
        if ($dirError !== null) {
            return ['ok' => false, 'error' => $dirError];
        }

        $name = $prefix . date('Ymd_His') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
        $target = $dir . '/' . $name;

        if ($resize) {
            $saved = self::resizeImage((string) $file['tmp_name'], $mime, self::FOTO_SISWA_SIZE, self::FOTO_SISWA_SIZE, $target);
            if (!$saved && !@move_uploaded_file((string) $file['tmp_name'], $target)) {
                return ['ok' => false, 'error' => 'Gagal menyimpan file.'];
            }
        } else {
            if (!@move_uploaded_file((string) $file['tmp_name'], $target)) {
                return ['ok' => false, 'error' => 'Gagal menyimpan file.'];
            }
        }

        return [
            'ok' => true,
            'file' => $name,
            'original' => (string) $file['name'],
            'type' => $mime,
            'size' => (int) $file['size'],
        ];
    }

    private static function ensureDir(string $dir): ?string
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_writable($dir)) {
            return 'Folder upload tidak dapat ditulis.';
        }
        return null;
    }

    /**
     * Delete a stored file from $dir. basename() guards against any
     * path-traversal payload that may have slipped into $fileName.
     */
    private static function delete(string $dir, ?string $fileName): void
    {
        if (empty($fileName)) {
            return;
        }
        $path = $dir . '/' . basename($fileName);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
