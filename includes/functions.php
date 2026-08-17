<?php
function set_flash(string $type, string $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect_to(string $url)
{
    header('Location: ' . $url);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_tahun_ajaran(): ?string
{
    return $_SESSION['tahun_ajaran'] ?? null;
}

function current_kelas_scope(): ?int
{
    $role = $_SESSION['user']['role'] ?? '';
    if ($role !== 'Wali Kelas') {
        return null;
    }
    $kelasId = $_SESSION['user']['kelas_id'] ?? null;
    return $kelasId ? (int) $kelasId : null;
}

function poin_badge(int $poin): string
{
    if ($poin > 75) {
        return '<span class="badge badge-danger">' . $poin . '</span>';
    }
    if ($poin >= 51) {
        return '<span class="badge badge-warning">' . $poin . '</span>';
    }
    if ($poin >= 26) {
        return '<span class="badge badge-warning">' . $poin . '</span>';
    }
    return '<span class="badge badge-good">' . $poin . '</span>';
}

function fase_pelanggaran(int $poin): ?array
{
    if ($poin < 1) {
        return null;
    }
    return db_fetch(
        'SELECT kategori, min_skor, max_skor, tindak_lanjut, administrasi
         FROM fase_pelanggaran
         WHERE min_skor <= ? AND (max_skor IS NULL OR max_skor >= ?)
         ORDER BY min_skor DESC
         LIMIT 1',
        [$poin, $poin],
        'row'
    ) ?: null;
}

function fase_badge(int $poin): string
{
    $f = fase_pelanggaran($poin);
    if (!$f) {
        return '';
    }
    $cls = match ($f['kategori']) {
        'Pelanggaran Ringan' => 'badge-good',
        'Pelanggaran Sedang' => 'badge-warning',
        default => 'badge-danger',
    };
    $rentang = (int) $f['max_skor'] ? ((int) $f['min_skor'] . '–' . (int) $f['max_skor']) : ((int) $f['min_skor'] . '+');
    $title = $f['kategori'] . ' (poin ' . $rentang . ') — ' . ($f['tindak_lanjut'] ?? '');
    if (!empty($f['administrasi'])) {
        $title .= '. ' . $f['administrasi'];
    }
    return '<span class="badge ' . $cls . '" title="' . e($title) . '">' . e($f['kategori']) . '</span>';
}

function naik_tingkat(?string $tingkat): ?string
{
    $map = [
        'X' => 'XI',
        'XI' => 'XII',
        'VII' => 'VIII',
        'VIII' => 'IX',
    ];
    return $map[$tingkat] ?? null;
}

function tingkat_lulus(?string $tingkat): bool
{
    return in_array($tingkat, ['XII', 'IX'], true);
}

function nama_kelas_naik(string $nama, string $tingkat, string $tingkatBaru): string
{
    $prefix = $tingkat . ' ';
    if (strpos($nama, $prefix) === 0) {
        return $tingkatBaru . substr($nama, strlen($prefix) - 1);
    }
    return $nama;
}

/**
 * Badge untuk approval status
 */
function approval_badge(string $status): string
{
    $class = match ($status) {
        'pending' => 'badge-warning',
        'approved' => 'badge-good',
        'rejected' => 'badge-danger',
        default => 'badge-good',
    };
    $label = match ($status) {
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => ucfirst($status),
    };
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}
