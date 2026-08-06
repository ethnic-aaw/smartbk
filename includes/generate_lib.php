<?php
/**
 * Library untuk Generate & Undo Tahun Ajaran.
 * Membutuhkan includes/auth.php, includes/functions.php, config/db.php.
 */

function generate_tahun_ajaran(string $tahunLama, string $tahunBaru, bool $buatKelasX): array
{
    global $mysqli;

    if (!db_is_ready()) {
        return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
    }

    $kelasRows = db_fetch(
        'SELECT * FROM kelas WHERE tahun_ajaran = ? ORDER BY tingkat ASC, nama_kelas ASC',
        [$tahunLama]
    );
    if (!$kelasRows) {
        return ['success' => false, 'message' => 'Tidak ada data kelas pada tahun ajaran ' . $tahunLama . '.'];
    }

    $exists = db_fetch('SELECT COUNT(*) AS c FROM kelas WHERE tahun_ajaran = ?', [$tahunBaru], 'row');
    if ((int) ($exists['c'] ?? 0) > 0) {
        return ['success' => false, 'message' => 'Tahun ajaran "' . $tahunBaru . '" sudah terdaftar. Batalkan generate sebelumnya terlebih dahulu.'];
    }

    $logAktif = db_fetch(
        'SELECT id FROM log_generate WHERE tahun_ajaran_lama = ? AND status = ? LIMIT 1',
        [$tahunLama, 'Aktif'],
        'row'
    );
    if ($logAktif) {
        return ['success' => false, 'message' => 'Masih ada generate aktif untuk tahun ajaran ini. Batalkan (Undo) terlebih dahulu.'];
    }

    $mysqli->begin_transaction();

    try {
        $snapshot = ['siswa' => [], 'kelas_baru' => [], 'users' => []];
        $kelasMap = [];

        // Snapshot siswa aktif yang terdampak (sebelum diubah)
        $siswaAffected = db_fetch(
            'SELECT s.id, s.kelas_id, s.status, k.tingkat
             FROM siswa s
             JOIN kelas k ON k.id = s.kelas_id
             WHERE k.tahun_ajaran = ? AND s.status = ?',
            [$tahunLama, 'Aktif']
        );
        foreach (($siswaAffected ?: []) as $sw) {
            $snapshot['siswa'][] = [
                'id' => (int) $sw['id'],
                'kelas_id' => (int) $sw['kelas_id'],
                'status' => $sw['status'],
            ];
        }

        // Snapshot wali kelas yang akan dipindah
        $waliIds = [];
        foreach ($kelasRows as $k) {
            if (naik_tingkat($k['tingkat']) !== null && !empty($k['wali_kelas_id'])) {
                $waliIds[] = (int) $k['wali_kelas_id'];
            }
        }
        if ($waliIds) {
            $placeholders = implode(',', array_fill(0, count($waliIds), '?'));
            $usersRows = db_fetch('SELECT id, kelas_id FROM users WHERE id IN (' . $placeholders . ')', $waliIds);
            foreach (($usersRows ?: []) as $r) {
                $snapshot['users'][] = [
                    'id' => (int) $r['id'],
                    'kelas_id_asal' => (int) $r['kelas_id'],
                ];
            }
        }

        // 1) Buat kelas baru hasil kenaikan (X→XI, XI→XII, VII→VIII, VIII→IX)
        foreach ($kelasRows as $k) {
            $tingkatBaru = naik_tingkat($k['tingkat']);
            if ($tingkatBaru === null) {
                continue;
            }
            $namaBaru = nama_kelas_naik($k['nama_kelas'], $k['tingkat'], $tingkatBaru);
            $ok = db_query(
                'INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES (?, ?, ?, ?)',
                [$namaBaru, $tingkatBaru, !empty($k['wali_kelas_id']) ? (int) $k['wali_kelas_id'] : null, $tahunBaru]
            );
            if (!$ok) {
                throw new RuntimeException('Gagal membuat kelas "' . $namaBaru . '".');
            }
            $newId = db_last_id();
            $kelasMap[(int) $k['id']] = $newId;
            $snapshot['kelas_baru'][] = ['id' => $newId, 'nama_kelas' => $namaBaru];
        }

        // 2) Buat kelas X (atau VII) kosong untuk tahun baru
        if ($buatKelasX) {
            foreach ($kelasRows as $k) {
                if (!in_array($k['tingkat'], ['X', 'VII'], true)) {
                    continue;
                }
                $ok = db_query(
                    'INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES (?, ?, NULL, ?)',
                    [$k['nama_kelas'], $k['tingkat'], $tahunBaru]
                );
                if (!$ok) {
                    throw new RuntimeException('Gagal membuat kelas "' . $k['nama_kelas'] . '" untuk tahun baru.');
                }
                $snapshot['kelas_baru'][] = ['id' => db_last_id(), 'nama_kelas' => $k['nama_kelas']];
            }
        }

        // 3) Pindahkan akun wali kelas ke kelas barunya
        foreach ($kelasRows as $k) {
            if (naik_tingkat($k['tingkat']) !== null && !empty($k['wali_kelas_id']) && isset($kelasMap[(int) $k['id']])) {
                db_query('UPDATE users SET kelas_id = ? WHERE id = ?', [$kelasMap[(int) $k['id']], (int) $k['wali_kelas_id']]);
            }
        }

        // 4) Naikkan siswa (status Aktif) ke kelas baru
        foreach ($kelasMap as $oldId => $newId) {
            db_query('UPDATE siswa SET kelas_id = ? WHERE kelas_id = ? AND status = ?', [$newId, $oldId, 'Aktif']);
        }

        // 5) Siswa tingkat akhir (XII / IX) dijadikan lulus/alumni, tetap di kelas lama (tahun lama)
        foreach ($kelasRows as $k) {
            if (tingkat_lulus($k['tingkat'])) {
                db_query('UPDATE siswa SET status = ? WHERE kelas_id = ? AND status = ?', ['Lulus', (int) $k['id'], 'Aktif']);
            }
        }

        // 6) Simpan log untuk keperluan undo
        $ok = db_query(
            'INSERT INTO log_generate (tahun_ajaran_lama, tahun_ajaran_baru, snapshot_json, status, created_by) VALUES (?, ?, ?, ?, ?)',
            [$tahunLama, $tahunBaru, json_encode($snapshot), 'Aktif', (int) ($_SESSION['user']['id'] ?? 0)]
        );
        if (!$ok) {
            throw new RuntimeException('Gagal menyimpan log generate.');
        }

        $mysqli->commit();

        $naik = 0;
        $lulus = 0;
        foreach (($siswaAffected ?: []) as $sw) {
            if (naik_tingkat($sw['tingkat']) !== null) {
                $naik++;
            } elseif (tingkat_lulus($sw['tingkat'])) {
                $lulus++;
            }
        }

        $message = 'Generate tahun ajaran "' . $tahunBaru . '" berhasil. '
            . $naik . ' siswa naik kelas, ' . $lulus . ' siswa lulus/alumni, '
            . count($snapshot['kelas_baru']) . ' kelas baru dibuat.';

        return ['success' => true, 'message' => $message];
    } catch (Throwable $e) {
        if ($mysqli->inTransaction()) {
            $mysqli->rollback();
        }
        return ['success' => false, 'message' => 'Generate gagal: ' . $e->getMessage()];
    }
}

function undo_tahun_ajaran(int $logId): array
{
    global $mysqli;

    if (!db_is_ready()) {
        return ['success' => false, 'message' => 'Koneksi database tidak tersedia.'];
    }

    $log = db_fetch('SELECT * FROM log_generate WHERE id = ? LIMIT 1', [$logId], 'row');
    if (!$log) {
        return ['success' => false, 'message' => 'Log generate tidak ditemukan.'];
    }
    if ($log['status'] !== 'Aktif') {
        return ['success' => false, 'message' => 'Generate ini sudah dibatalkan sebelumnya.'];
    }

    $snapshot = json_decode($log['snapshot_json'], true);
    if (!is_array($snapshot)) {
        return ['success' => false, 'message' => 'Data snapshot tidak valid.'];
    }

    $mysqli->begin_transaction();

    try {
        // 1) Kembalikan data siswa ke kondisi sebelum generate
        foreach (($snapshot['siswa'] ?? []) as $sw) {
            db_query('UPDATE siswa SET kelas_id = ?, status = ? WHERE id = ?', [(int) $sw['kelas_id'], $sw['status'], (int) $sw['id']]);
        }

        // 2) Kembalikan akun wali kelas
        foreach (($snapshot['users'] ?? []) as $u) {
            db_query('UPDATE users SET kelas_id = ? WHERE id = ?', [(int) $u['kelas_id_asal'], (int) $u['id']]);
        }

        // 3) Hapus kelas baru yang masih kosong (kelas yang sudah berisi siswa dipertahankan)
        $dihapus = 0;
        $dilewati = 0;
        foreach (($snapshot['kelas_baru'] ?? []) as $kb) {
            $row = db_fetch('SELECT COUNT(*) AS c FROM siswa WHERE kelas_id = ?', [(int) $kb['id']], 'row');
            if ((int) ($row['c'] ?? 0) === 0) {
                db_query('DELETE FROM kelas WHERE id = ?', [(int) $kb['id']]);
                $dihapus++;
            } else {
                $dilewati++;
            }
        }

        // 4) Tandai log sebagai dibatalkan
        db_query('UPDATE log_generate SET status = ? WHERE id = ?', ['Dibatalkan', $logId]);

        $mysqli->commit();

        $message = 'Generate tahun ajaran "' . $log['tahun_ajaran_baru'] . '" berhasil dibatalkan. Data siswa dikembalikan, '
            . $dihapus . ' kelas baru dihapus.';
        if ($dilewati > 0) {
            $message .= ' ' . $dilewati . ' kelas baru dipertahankan karena sudah berisi siswa.';
        }

        return ['success' => true, 'message' => $message];
    } catch (Throwable $e) {
        if ($mysqli->inTransaction()) {
            $mysqli->rollback();
        }
        return ['success' => false, 'message' => 'Undo gagal: ' . $e->getMessage()];
    }
}
