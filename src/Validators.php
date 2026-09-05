<?php
declare(strict_types=1);

namespace SmartBK;

/**
 * Centralized server-side validators for Smart BK.
 *
 * Each validate*() accepts a normalized input array and returns a
 * [field => message] error map. An empty array means the input is valid.
 *
 * Uniqueness (nipd, kode) is intentionally NOT checked here — those are
 * enforced by UNIQUE constraints in the database schema and the calling
 * controller is responsible for surfacing a friendly duplicate-key error.
 */
final class Validators
{
    /** @var array<string,string> */
    public const STATUS_SISWA = ['Aktif', 'Tidak Aktif', 'Pindah', 'Lulus'];

    /** @var array<string,string> */
    public const JENIS_KELAMIN = ['L', 'P'];

    /** @var array<string,string> */
    public const KATEGORI_PELANGGARAN = ['Kedisiplinan', 'Tata Krama', 'Kekerasan', 'Narkoba', 'Lainnya'];

    /** @var array<int,string> */
    public const KOMPONEN_PELANGGARAN = [
        'Kehadiran',
        'Kegiatan Belajar Mengajar',
        'Pakaian Seragam',
        'Makan dan Minum',
        'Izin Meninggalkan Sekolah',
        'Perkelahian',
        'Praktik Kerja Lapangan (PKL)',
        'Kebersihan Lingkungan',
        'Lain-lain',
    ];

    /**
     * Validate siswa create/update input.
     *
     * @param array $input Normalized input (values may be raw, they are trimmed here).
     * @return array<string,string> Field-level errors (empty = valid).
     */
    public static function validateSiswa(array $input): array
    {
        $errors = [];

        $nipd = trim((string) ($input['nipd'] ?? ''));
        $nama = trim((string) ($input['nama'] ?? ''));
        $jenis_kelamin = trim((string) ($input['jenis_kelamin'] ?? ''));
        $kelas_id = (int) ($input['kelas_id'] ?? 0);
        $status = trim((string) ($input['status'] ?? ''));

        // NIPD / NIS — required, max 20 (column is VARCHAR(20) UNIQUE).
        if ($nipd === '') {
            $errors['nipd'] = 'NIPD/NIS wajib diisi.';
        } elseif (strlen($nipd) > 20) {
            $errors['nipd'] = 'NIPD/NIS maksimal 20 karakter.';
        }

        // Nama lengkap — required, max 100 (column is VARCHAR(100)).
        if ($nama === '') {
            $errors['nama'] = 'Nama lengkap wajib diisi.';
        } elseif (strlen($nama) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter.';
        }

        // Jenis kelamin — must be L or P (column is ENUM('L','P')).
        if (!in_array($jenis_kelamin, self::JENIS_KELAMIN, true)) {
            $errors['jenis_kelamin'] = 'Jenis kelamin harus L atau P.';
        }

        // Kelas ID — optional, but if supplied must be a non-negative integer.
        if ($kelas_id < 0) {
            $errors['kelas_id'] = 'Kelas tidak valid.';
        }

        // Status — must match the allowed enum (callers default missing → 'Aktif'
        // before invoking the validator, so an empty value here is invalid).
        if (!in_array($status, self::STATUS_SISWA, true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        return $errors;
    }

    /**
     * Validate jenis pelanggaran create/update input.
     *
     * @param array $input Normalized input (values may be raw, they are trimmed here).
     * @return array<string,string> Field-level errors (empty = valid).
     */
    public static function validateJenisPelanggaran(array $input): array
    {
        $errors = [];

        $kode = trim((string) ($input['kode'] ?? ''));
        $nama = trim((string) ($input['nama'] ?? ''));
        $komponen = trim((string) ($input['komponen'] ?? ''));
        $kategori = trim((string) ($input['kategori'] ?? ''));
        $bobot_poin = (int) ($input['bobot_poin'] ?? 0);

        // Kode — required, max 20 (column is VARCHAR(20) UNIQUE).
        if ($kode === '') {
            $errors['kode'] = 'Kode pelanggaran wajib diisi.';
        } elseif (strlen($kode) > 20) {
            $errors['kode'] = 'Kode pelanggaran maksimal 20 karakter.';
        }

        // Nama — required, max 150 (column is VARCHAR(150)).
        if ($nama === '') {
            $errors['nama'] = 'Nama pelanggaran wajib diisi.';
        } elseif (strlen($nama) > 150) {
            $errors['nama'] = 'Nama maksimal 150 karakter.';
        }

        // Komponen — must be one of the allowed values.
        if (!in_array($komponen, self::KOMPONEN_PELANGGARAN, true)) {
            $errors['komponen'] = 'Komponen tidak valid.';
        }

        // Kategori — must be one of the allowed values (column ENUM).
        if (!in_array($kategori, self::KATEGORI_PELANGGARAN, true)) {
            $errors['kategori'] = 'Kategori tidak valid.';
        }

        // Bobot poin — must be an integer in the range 1–100.
        if ($bobot_poin < 1 || $bobot_poin > 100) {
            $errors['bobot_poin'] = 'Bobot poin harus antara 1–100.';
        }

        return $errors;
    }
}
