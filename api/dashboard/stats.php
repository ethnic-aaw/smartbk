<?php
/** @deprecated Tidak dipakai — dashboard React memakai api/dashboard/analytics.php. Disimpan untuk referensi. */
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

if (!db_is_ready()) {
    echo json_encode(["error" => "Database tidak tersedia"]);
    exit;
}

require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/functions.php";

$tahunAjaran = current_tahun_ajaran();
$userKelasId = current_kelas_scope();

$response = [];

// 1. Total siswa aktif
$siswaParams = ["Aktif", $tahunAjaran];
$siswaKelasSql = "";
if ($userKelasId) {
    $siswaKelasSql = " AND s.kelas_id = ?";
    $siswaParams[] = $userKelasId;
}
$response["totalSiswaAktif"] = (int) db_fetch(
    "SELECT COUNT(*) AS c FROM siswa s
     JOIN kelas k ON k.id = s.kelas_id
     WHERE s.status = ? AND k.tahun_ajaran = ?" . $siswaKelasSql,
    $siswaParams,
    "row"
)["c"] ?? 0;

// 2. Total pelanggaran bulan ini vs bulan lalu
$bulanIni = date("Y-m-01");
$bulanLalu = date("Y-m-01", strtotime("-1 month"));

$pelParams = [$tahunAjaran];
$pelKelasSql = "";
if ($userKelasId) {
    $pelKelasSql = " AND s.kelas_id = ?";
    $pelParams[] = $userKelasId;
}

$response["pelanggaran"] = [
    "bulanIni" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         WHERE k.tahun_ajaran = ? AND p.tanggal >= ?" . $pelKelasSql,
        array_merge($pelParams, [$bulanIni]),
        "row"
    )["c"] ?? 0,
    "bulanLalu" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         WHERE k.tahun_ajaran = ? AND p.tanggal >= ? AND p.tanggal < ?" . $pelKelasSql,
        array_merge($pelParams, [$bulanLalu, $bulanIni]),
        "row"
    )["c"] ?? 0,
];

// 3. Total konsultasi bulan ini vs bulan lalu
$konsParams = [$tahunAjaran];
$konsKelasSql = "";
if ($userKelasId) {
    $konsKelasSql = " AND s.kelas_id = ?";
    $konsParams[] = $userKelasId;
}

$response["konsultasi"] = [
    "bulanIni" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM konsultasi_siswa k
         JOIN siswa s ON s.id = k.siswa_id
         JOIN kelas kls ON kls.id = s.kelas_id
         WHERE kls.tahun_ajaran = ? AND k.tanggal >= ?" . $konsKelasSql,
        array_merge($konsParams, [$bulanIni]),
        "row"
    )["c"] ?? 0,
    "bulanLalu" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM konsultasi_siswa k
         JOIN siswa s ON s.id = k.siswa_id
         JOIN kelas kls ON kls.id = s.kelas_id
         WHERE kls.tahun_ajaran = ? AND k.tanggal >= ? AND k.tanggal < ?" . $konsKelasSql,
        array_merge($konsParams, [$bulanLalu, $bulanIni]),
        "row"
    )["c"] ?? 0,
];

// 4. Distribusi pelanggaran per kategori
$distParams = [$tahunAjaran];
$distKelasSql = "";
if ($userKelasId) {
    $distKelasSql = " AND s.kelas_id = ?";
    $distParams[] = $userKelasId;
}
$response["distribusiPerKategori"] = db_fetch(
    "SELECT j.kategori, COUNT(*) AS jumlah, COALESCE(SUM(j.bobot_poin), 0) AS total_poin
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?" . $distKelasSql . "
     GROUP BY j.kategori
     ORDER BY jumlah DESC",
    $distParams
);

// 5. Top 5 siswa dengan poin tertinggi (total tahun ini)
$topParams = [$tahunAjaran];
$topKelasSql = "";
if ($userKelasId) {
    $topKelasSql = " AND s.kelas_id = ?";
    $topParams[] = $userKelasId;
}
$response["topSiswa"] = db_fetch(
    "SELECT s.id, s.nama, s.nipd, k.nama_kelas,
            SUM(j.bobot_poin) AS total_poin
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?" . $topKelasSql . "
     GROUP BY s.id
     ORDER BY total_poin DESC, s.nama ASC
     LIMIT 5",
    $topParams
);

// 6. Tren pelanggaran per bulan (6 bulan terakhir)
$mon = [
    1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "Mei", 6 => "Jun",
    7 => "Jul", 8 => "Ags", 9 => "Sep", 10 => "Okt", 11 => "Nov", 12 => "Des",
];
$tahunAkhirAjaran = (int) substr($tahunAjaran, 5, 4);
$akhirPeriode = new DateTime($tahunAkhirAjaran . "-06-30");
$mulaiPeriode = (clone $akhirPeriode)->modify("-5 months")->modify("first day of this month")->setTime(0, 0);

$trenParams = [$mulaiPeriode->format("Y-m-d"), $akhirPeriode->format("Y-m-d")];
$trenKelasSql = "";
if ($userKelasId) {
    $trenKelasSql = " AND s.kelas_id = ?";
    $trenParams[] = $userKelasId;
}
$trenRows = db_fetch(
    "SELECT MONTH(p.tanggal) AS m, YEAR(p.tanggal) AS y, COUNT(*) AS c
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     WHERE p.tanggal BETWEEN ? AND ?" . $trenKelasSql . "
     GROUP BY YEAR(p.tanggal), MONTH(p.tanggal)
     ORDER BY y, m",
    $trenParams
);
$map = [];
foreach (($trenRows ?: []) as $r) {
    $map[$r["y"] . "-" . $r["m"]] = (int) $r["c"];
}
$cursor = $mulaiPeriode;
$response["trenBulanan"] = ["labels" => [], "data" => []];
for ($i = 0; $i < 6; $i++) {
    $k = $cursor->format("Y") . "-" . (int) $cursor->format("m");
    $response["trenBulanan"]["labels"][] = $mon[(int) $cursor->format("m")];
    $response["trenBulanan"]["data"][] = $map[$k] ?? 0;
    $cursor->modify("+1 month");
}

// 7. Status approval user baru
$response["approvalUsers"] = [
    "totalBaru" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM users WHERE status = ? AND created_at >= CURDATE() - INTERVAL 7 DAY",
        ["Aktif"],
        "row"
    )["c"] ?? 0,
    "menunggu" => 0,
    "disetujui" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM users WHERE status = ?",
        ["Aktif"],
        "row"
    )["c"] ?? 0,
    "ditolak" => (int) db_fetch(
        "SELECT COUNT(*) AS c FROM users WHERE status = ?",
        ["Nonaktif"],
        "row"
    )["c"] ?? 0,
];

echo json_encode($response);
