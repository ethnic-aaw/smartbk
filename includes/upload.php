<?php
/**
 * Thin procedural facades over SmartBK\Uploader.
 *
 * All real upload / resize / delete logic lives in src/Uploader.php so the
 * physical upload base and the GD handling exist in exactly one place. These
 * wrappers only exist so the existing page scripts (siswa/, konsultasi/,
 * pelanggaran/) keep their `require_once __DIR__.'/../includes/upload.php'`
 * and function-name contracts unchanged.
 */
require_once __DIR__ . '/../src/Uploader.php';

use SmartBK\Uploader;

/**
 * foto_siswa: JPG/PNG/Webp, max 500KB, resized to 150x150 (GD).
 * Returns ['ok'=>true,'name'=>?] or ['ok'=>false,'error'=>str].
 */
function upload_foto_siswa(array $file)
{
    return Uploader::uploadFotoSiswa($file);
}

/**
 * lampiran_konsultasi: JPG/PNG/Webp/PDF, max 2MB, stored as-is.
 */
function upload_lampiran_konsultasi(array $file)
{
    return Uploader::uploadLampiranKonsultasi($file);
}

/**
 * bukti_pelanggaran: JPG/PNG/Webp/PDF, max 2MB, stored as-is.
 */
function upload_bukti_pelanggaran(array $file)
{
    return Uploader::uploadBuktiPelanggaran($file);
}

/**
 * Hapus file lampiran konsultasi dari folder.
 */
function hapus_lampiran_konsultasi(?string $fileName): void
{
    Uploader::hapusLampiranKonsultasi($fileName);
}

/**
 * Hapus file bukti pelanggaran dari folder.
 */
function hapus_bukti_pelanggaran(?string $fileName): void
{
    Uploader::hapusBuktiPelanggaran($fileName);
}

/**
 * Backward-compat wrapper for the GD resize helper.
 */
function resize_image($source, $mime, $maxW, $maxH, $target)
{
    return Uploader::resizeImage((string) $source, (string) $mime, (int) $maxW, (int) $maxH, (string) $target);
}
