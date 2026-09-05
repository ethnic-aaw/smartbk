<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

try {
    if (!db_is_ready()) {
        api_error('Database tidak tersedia', 503);
    }

    $tahunAjaran = current_tahun_ajaran();
    $userKelasId = current_kelas_scope();
    $response = [];

    // 1. Total siswa aktif
    $siswaParams = ["Aktif", $tahunAjaran];
    if ($userKelasId) { $siswaParams[] = $userKelasId; }
    $response["totalSiswa"] = (int) ((db_fetch(
        "SELECT COUNT(*) AS c FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.status = ? AND k.tahun_ajaran = ?" . ($userKelasId ? " AND s.kelas_id = ?" : ""),
        $siswaParams, "row"
    ) ?: [])["c"] ?? 0);

    // 2. Total kelas
    $kelasParams = [$tahunAjaran];
    if ($userKelasId) { $kelasParams[] = $userKelasId; }
    $response["totalKelas"] = (int) ((db_fetch(
        "SELECT COUNT(*) AS c FROM kelas WHERE tahun_ajaran = ?" . ($userKelasId ? " AND id = ?" : ""),
        $kelasParams, "row"
    ) ?: [])["c"] ?? 0);

    // 3. Siswa bermasalah (poin > 75)
    $probParams = ["Aktif", $tahunAjaran];
    $probKelasSql = "";
    if ($userKelasId) { $probKelasSql = " AND s.kelas_id = ?"; $probParams[] = $userKelasId; }
    $response["siswaBermasalah"] = (int) ((db_fetch(
        "SELECT COUNT(*) AS c FROM (
            SELECT s.id FROM pelanggaran_siswa p
            JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
            JOIN siswa s ON s.id = p.siswa_id
            JOIN kelas k ON k.id = s.kelas_id
            WHERE s.status = ? AND k.tahun_ajaran = ?$probKelasSql
            GROUP BY s.id HAVING SUM(j.bobot_poin) > 75
        ) t", $probParams, "row"
    ) ?: [])["c"] ?? 0);

    // 4. Pelanggaran hari ini
    $todayParams = [$tahunAjaran];
    $todayKelasSql = "";
    if ($userKelasId) { $todayKelasSql = " AND s.kelas_id = ?"; $todayParams[] = $userKelasId; }
    $response["pelanggaranHariIni"] = (int) ((db_fetch(
        "SELECT COUNT(*) AS c FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         WHERE k.tahun_ajaran = ? AND p.tanggal = CURDATE()$todayKelasSql",
        $todayParams, "row"
    ) ?: [])["c"] ?? 0);

    // 5. Kategori dominan
    $catParams = [$tahunAjaran];
    $catKelasSql = "";
    if ($userKelasId) { $catKelasSql = " AND s.kelas_id = ?"; $catParams[] = $userKelasId; }
    $kategoriRows = db_fetch(
        "SELECT j.kategori, COUNT(*) AS jumlah
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
         WHERE k.tahun_ajaran = ?$catKelasSql
         GROUP BY j.kategori ORDER BY jumlah DESC",
        $catParams
    ) ?: [];

    $totalKat = array_sum(array_column($kategoriRows, 'jumlah'));
    $katColors = [
        "Kedisiplinan" => "#2563EB", "Tata Krama" => "#10B981",
        "Kekerasan" => "#EF4444", "Narkoba" => "#F59E0B", "Lainnya" => "#8B5CF6",
    ];
    $response["kategoriDominan"] = array_map(function ($r) use ($totalKat, $katColors) {
        $j = (int) $r['jumlah'];
        return [
            "name"  => $r['kategori'] ?: "Lainnya",
            "value" => $totalKat > 0 ? round(($j / $totalKat) * 100) : 0,
            "count" => $j,
            "color" => $katColors[$r['kategori']] ?? "#A78BFA",
        ];
    }, $kategoriRows);

    // 6. Tren pelanggaran (timeline)
    $period = trim($_GET['period'] ?? '1Y');
    if (!in_array($period, ['7D', '30D', '90D', '1Y'], true)) $period = '1Y';
    $days = ['7D' => 7, '30D' => 30, '90D' => 90, '1Y' => 365][$period];
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

    $trenParams = [$tahunAjaran, $dateFrom];
    $trenKelasSql = "";
    if ($userKelasId) { $trenKelasSql = " AND s.kelas_id = ?"; $trenParams[] = $userKelasId; }
    $trenRows = db_fetch(
        "SELECT p.tanggal AS tgl, COUNT(*) AS jumlah
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         WHERE k.tahun_ajaran = ? AND p.tanggal >= ?$trenKelasSql
         GROUP BY p.tanggal ORDER BY p.tanggal ASC",
        $trenParams
    ) ?: [];
    $response["trenPelanggaran"] = array_map(fn($r) => ["date" => $r['tgl'], "jumlah" => (int) $r['jumlah']], $trenRows);

    // 7. Tren per kategori
    $katTrenParams = [$tahunAjaran, $dateFrom];
    $katTrenKelasSql = "";
    if ($userKelasId) { $katTrenKelasSql = " AND s.kelas_id = ?"; $katTrenParams[] = $userKelasId; }
    $katTrenRows = db_fetch(
        "SELECT p.tanggal AS tgl, j.kategori, COUNT(*) AS jumlah
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
         WHERE k.tahun_ajaran = ? AND p.tanggal >= ?$katTrenKelasSql
         GROUP BY p.tanggal, j.kategori ORDER BY p.tanggal ASC",
        $katTrenParams
    ) ?: [];
    $katMap = [];
    foreach ($katTrenRows as $r) {
        $tgl = $r['tgl'];
        $kat = $r['kategori'] ?: "Lainnya";
        if (!isset($katMap[$tgl])) $katMap[$tgl] = ["date" => $tgl];
        $katMap[$tgl][$kat] = (int) $r['jumlah'];
    }
    $response["trenPerKategori"] = array_values($katMap);

    // 8. Top 10 siswa
    $topParams = [$tahunAjaran];
    $topKelasSql = "";
    if ($userKelasId) { $topKelasSql = " AND s.kelas_id = ?"; $topParams[] = $userKelasId; }
    $response["topSiswa"] = db_fetch(
        "SELECT s.id, s.nama, s.nipd, k.nama_kelas, SUM(j.bobot_poin) AS total_poin
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
         WHERE k.tahun_ajaran = ?$topKelasSql
         GROUP BY s.id ORDER BY total_poin DESC, s.nama ASC LIMIT 10",
        $topParams
    ) ?: [];

    // 9. Sparkline 6 bulan
    $sparkCursor = new DateTime("first day of this month");
    $sparkCursor->modify("-5 months");
    $sparkEnd = new DateTime("last day of this month");
    $sparkParams = [$sparkCursor->format("Y-m-d"), $sparkEnd->format("Y-m-d")];
    $sparkKelasSql = "";
    if ($userKelasId) { $sparkKelasSql = " AND s.kelas_id = ?"; $sparkParams[] = $userKelasId; }
    $sparkRows = db_fetch(
        "SELECT YEAR(p.tanggal) AS y, MONTH(p.tanggal) AS m, COUNT(*) AS c
         FROM pelanggaran_siswa p JOIN siswa s ON s.id = p.siswa_id
         WHERE p.tanggal BETWEEN ? AND ?$sparkKelasSql
         GROUP BY YEAR(p.tanggal), MONTH(p.tanggal) ORDER BY y, m",
        $sparkParams
    ) ?: [];
    $sparkMap = [];
    foreach ($sparkRows as $r) $sparkMap[$r['y'] . '-' . $r['m']] = (int) $r['c'];
    $sc = clone $sparkCursor;
    $response["sparkPelanggaran"] = [];
    for ($i = 0; $i < 6; $i++) {
        $k = $sc->format("Y") . "-" . (int) $sc->format("m");
        $response["sparkPelanggaran"][] = ["value" => $sparkMap[$k] ?? 0];
        $sc->modify("+1 month");
    }

    $response["tahunAjaran"] = $tahunAjaran;
    $response["period"] = $period;

    api_success($response);

} catch (\Throwable $e) {
    error_log("analytics.php error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    api_error('Terjadi kesalahan server. Silakan coba lagi.', 500);
}
