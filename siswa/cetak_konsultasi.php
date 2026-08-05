<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Khusus Admin & Guru BK (confidential)
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);
$siswa = db_fetch(
    'SELECT s.*, k.nama_kelas, k.tingkat, k.tahun_ajaran
     FROM siswa s
     LEFT JOIN kelas k ON k.id = s.kelas_id
     WHERE s.id = ? LIMIT 1',
    [$id],
    'row'
);

if (!$siswa) {
    set_flash('error', 'Data siswa tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

// Semua riwayat konsultasi siswa (urut tanggal)
$konsultasi = db_fetch(
    'SELECT k.*, u.nama AS konselor
     FROM konsultasi_siswa k
     LEFT JOIN users u ON u.id = k.konselor_id
     WHERE k.siswa_id = ?
     ORDER BY k.tanggal ASC, k.id ASC',
    [$id]
);
$konsultasi = $konsultasi ?: [];

// Nama konselor untuk tanda tangan (dari konsultasi terbaru, fallback ke user login)
$konselorNama = $_SESSION['user']['name'] ?? '';
if ($konsultasi) {
    $terakhir = end($konsultasi);
    if (!empty($terakhir['konselor'])) {
        $konselorNama = $terakhir['konselor'];
    }
}

// Cari file kop yang tersedia (jpg/jpeg/png/webp)
$kopDir = __DIR__ . '/../assets/uploads/kop/';
$kopFile = null;
foreach (['kop.jpg', 'kop.jpeg', 'kop.png', 'kop.webp'] as $cand) {
    if (file_exists($kopDir . $cand)) {
        $kopFile = $cand;
        break;
    }
}
$kopUrl = $kopFile ? (rtrim(APP_BASE, '/') . '/assets/uploads/kop/' . $kopFile) : null;

// Format tanggal Indonesia
function tgl_id(?string $tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($tanggal);
    return date('d', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

$ttl = trim(($siswa['tempat_lahir'] ?: '') . ($siswa['tanggal_lahir'] ? ', ' . tgl_id($siswa['tanggal_lahir']) : ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Konsultasi - <?= htmlspecialchars($siswa['nama']) ?></title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Times New Roman', Times, serif;
    color: #000;
    background: #fff;
    font-size: 12pt;
    line-height: 1.5;
}
.kop { text-align: center; }
.kop img {
    max-width: 100%;
    max-height: 150px;
    width: auto;
    height: auto;
}
.kop-teks h1 { margin: 0; font-size: 20pt; letter-spacing: 1px; }
.kop-teks .alamat { font-size: 11pt; margin-top: 2px; }
.garis-kop {
    border-bottom: 3px solid #000;
    margin: 8px 0 4px;
}
.garis-kop2 { border-bottom: 1px solid #000; margin-bottom: 18px; }
h2.judul {
    text-align: center;
    font-size: 15pt;
    margin: 16px 0 20px;
    text-decoration: underline;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.biodata {
    width: 100%;
    margin-bottom: 22px;
    font-size: 12pt;
}
.biodata td { padding: 2px 6px; vertical-align: top; }
.biodata .label { width: 210px; font-weight: bold; }
h3.seksi {
    font-size: 12pt;
    margin: 0 0 8px;
    text-decoration: underline;
}
table.riwayat {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5pt;
}
table.riwayat th, table.riwayat td {
    border: 1px solid #000;
    padding: 5px 6px;
    vertical-align: top;
    text-align: left;
}
table.riwayat th { background: #f0f0f0; font-weight: bold; text-align: center; }
.bukti-img { max-width: 90px; max-height: 70px; }
.bukti-file { font-size: 9pt; word-break: break-all; }
.tanda-tangan {
    margin-top: 40px;
    text-align: right;
}
.tt-kota { margin-bottom: 60px; }
.tt-nama { font-weight: bold; text-decoration: underline; margin-top: 6px; }
.no-data { font-style: italic; color: #444; }
/* Toolbar di layar (disembunyikan saat print) */
.toolbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #1e3a5f; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center;
    font-family: Arial, sans-serif; font-size: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,.3);
}
.toolbar button {
    background: #2563eb; color: #fff; border: 0; padding: 8px 16px;
    border-radius: 6px; cursor: pointer; font-weight: 600;
}
.toolbar button:hover { background: #1d4ed8; }
.toolbar a { color: #fff; text-decoration: none; padding: 8px 16px; border: 1px solid #fff; border-radius: 6px; }
.kertas { margin: 60px auto 0; max-width: 820px; padding: 32px; }
@media print {
    body { font-size: 12pt; }
    .toolbar { display: none !important; }
    .kertas { margin: 0; max-width: 100%; padding: 0; }
    .garis-kop, .garis-kop2 { -webkit-print-color-adjust: exact; }
    table.riwayat th { -webkit-print-color-adjust: exact; }
    @page { size: A4; margin: 20mm 18mm; }
}
</style>
</head>
<body>
<div class="toolbar">
    <strong>🖨 Preview Rekap Konsultasi</strong>
    <span style="flex:1;"></span>
    <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $siswa['id'] ?>">Kembali</a>
    <button onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</div>

<div class="kertas">
    <!-- KOP -->
    <div class="kop">
        <?php if ($kopUrl): ?>
            <img src="<?= e($kopUrl) ?>" alt="Kop Sekolah">
        <?php else: ?>
            <div class="kop-teks">
                <h1>SMK NEGERI 1 LEUWIMUNDING</h1>
                <div class="alamat">Jalan Raya Leuwimunding No. 1, Kab. Majalengka</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="garis-kop"></div>
    <div class="garis-kop2"></div>

    <h2 class="judul">Rekapitulasi Konsultasi Siswa</h2>

    <!-- BIODATA -->
    <table class="biodata">
        <tr><td class="label">Nama Siswa</td><td>: <?= e($siswa['nama']) ?></td></tr>
        <tr><td class="label">NISN</td><td>: —</td></tr>
        <tr><td class="label">NIS / NIPD</td><td>: <?= e($siswa['nipd']) ?></td></tr>
        <tr><td class="label">Tempat / Tanggal Lahir</td><td>: <?= e($ttl ?: '-') ?></td></tr>
        <tr><td class="label">Kelas</td><td>: <?= e($siswa['nama_kelas'] ? $siswa['nama_kelas'] . ' (' . $siswa['tahun_ajaran'] . ')' : '-') ?></td></tr>
    </table>

    <!-- RIWAYAT KONSULTASI -->
    <h3 class="seksi">Riwayat Konsultasi</h3>
    <table class="riwayat">
        <thead>
            <tr>
                <th style="width:32px;">No</th>
                <th style="width:90px;">Tanggal</th>
                <th>Permasalahan</th>
                <th>Tindak Lanjut</th>
                <th style="width:110px;">Bukti Dukung</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$konsultasi): ?>
                <tr><td colspan="5" class="no-data">Belum ada catatan konsultasi.</td></tr>
            <?php endif; ?>
            <?php foreach ($konsultasi as $i => $k): ?>
                <tr>
                    <td style="text-align:center;"><?= $i + 1 ?></td>
                    <td><?= e(tgl_id($k['tanggal'])) ?></td>
                    <td><?= nl2br(e($k['permasalahan'] ?: '-')) ?></td>
                    <td><?= nl2br(e($k['tindak_lanjut'] ?: '-')) ?></td>
                    <td>
                        <?php if (!empty($k['lampiran_file'])): ?>
                            <?php if (strpos($k['lampiran_type'] ?? '', 'image/') === 0): ?>
                                <img class="bukti-img" src="<?= rtrim(APP_BASE, '/') ?>/konsultasi/download.php?id=<?= (int) $k['id'] ?>" alt="<?= e($k['lampiran_original']) ?>">
                            <?php else: ?>
                                <span class="bukti-file">📄 <?= e($k['lampiran_original']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="tanda-tangan">
        <div class="tt-kota">Leuwimunding, <?= e(tgl_id(date('Y-m-d'))) ?></div>
        <div>Konselor (Guru BK)</div>
        <div style="margin-top:90px;">
            <div class="tt-nama"><?= e($konselorNama) ?></div>
            <div style="font-size:10pt;">NIP. -</div>
        </div>
    </div>
</div>
</body>
</html>
