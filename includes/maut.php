<?php
// =====================================================================
// FUNGSI INTI METODE MAUT (Multi Attribute Utility Theory)
// =====================================================================
// Tahapan sesuai BAB III:
//  1. Membentuk matriks keputusan
//  2. Normalisasi      : r*ij = (rij - min) / (max - min)   [benefit]
//                        r*ij = (max - rij) / (max - min)   [cost]
//  3. Utilitas marjinal: uij  = (e^(r*ij^2) - 1) / 1.71      [opsi eksponensial]
//  4. Utilitas akhir   : Ui   = Σ ( uij * wj )
//  5. Perangkingan
//
// Catatan: contoh perhitungan pada BAB IV (UA01 = 0.700) menggunakan
// utilitas LINEAR (langsung r*ij), sehingga sistem menyediakan kedua mode.
// =====================================================================

/**
 * Ambil seluruh data mentah dari database.
 * Alternatif dapat difilter berdasarkan bulan & tahun input (kolom created_at).
 *
 * @param int|null $bulan 1-12, null = semua bulan
 * @param int|null $tahun mis. 2026, null = semua tahun
 * @return array [alternatif, kriteria, matriks[altKode][kriKode] = nilai]
 */
function ambilDataMAUT(PDO $pdo, ?int $bulan = null, ?int $tahun = null): array
{
    $where  = '';
    $params = [];
    if ($bulan !== null && $bulan >= 1 && $bulan <= 12) {
        $where .= ' AND MONTH(created_at) = ?';
        $params[] = $bulan;
    }
    if ($tahun !== null && $tahun > 0) {
        $where .= ' AND YEAR(created_at) = ?';
        $params[] = $tahun;
    }
    $stmt = $pdo->prepare("SELECT * FROM alternatif WHERE 1=1 $where ORDER BY kode");
    $stmt->execute($params);
    $alternatif = $stmt->fetchAll();

    $kriteria   = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();

    $rows = $pdo->query("
        SELECT a.kode AS alt, k.kode AS kri, n.nilai
        FROM nilai n
        JOIN alternatif a ON a.id = n.alternatif_id
        JOIN kriteria  k ON k.id = n.kriteria_id
    ")->fetchAll();

    $matriks = [];
    foreach ($rows as $r) {
        $matriks[$r['alt']][$r['kri']] = (float) $r['nilai'];
    }

    return [$alternatif, $kriteria, $matriks];
}

/**
 * Hitung MAUT lengkap.
 * @param string $mode 'linear' (sesuai contoh BAB IV) atau 'eksponensial' (rumus BAB III)
 * @return array hasil lengkap untuk ditampilkan / dicetak
 */
function hitungMAUT(array $alternatif, array $kriteria, array $matriks, string $mode = 'linear'): array
{
    // --- Min & Max tiap kriteria ---
    $minK = [];
    $maxK = [];
    foreach ($kriteria as $k) {
        $kode = $k['kode'];
        $kolom = [];
        foreach ($alternatif as $a) {
            $kolom[] = $matriks[$a['kode']][$kode] ?? 0;
        }
        $minK[$kode] = min($kolom);
        $maxK[$kode] = max($kolom);
    }

    // --- Normalisasi & Utilitas marjinal ---
    $normalisasi = [];
    $utilMarjinal = [];
    foreach ($alternatif as $a) {
        $ak = $a['kode'];
        foreach ($kriteria as $k) {
            $kk    = $k['kode'];
            $x     = $matriks[$ak][$kk] ?? 0;
            $min   = $minK[$kk];
            $max   = $maxK[$kk];
            $range = ($max - $min);

            if ($range == 0) {
                $r = 0.0; // hindari pembagian nol
            } elseif ($k['jenis'] === 'cost') {
                $r = ($max - $x) / $range;
            } else { // benefit
                $r = ($x - $min) / $range;
            }
            $normalisasi[$ak][$kk] = $r;

            // Utilitas marjinal
            if ($mode === 'eksponensial') {
                $u = (exp(pow($r, 2)) - 1) / 1.71;
            } else {
                $u = $r; // linear (default, sesuai contoh paper)
            }
            $utilMarjinal[$ak][$kk] = $u;
        }
    }

    // --- Utilitas akhir : Ui = Σ (uij * wj) ---
    $hasil = [];
    foreach ($alternatif as $a) {
        $ak = $a['kode'];
        $total = 0.0;
        $rincian = [];
        foreach ($kriteria as $k) {
            $kk    = $k['kode'];
            $w     = (float) $k['bobot'];
            $u     = $utilMarjinal[$ak][$kk];
            $kontrib = $u * $w;
            $total  += $kontrib;
            $rincian[$kk] = $kontrib;
        }
        $hasil[] = [
            'kode'    => $ak,
            'nama'    => $a['nama'],
            'rincian' => $rincian,
            'nilai'   => $total,
        ];
    }

    // --- Perangkingan ---
    usort($hasil, fn($x, $y) => $y['nilai'] <=> $x['nilai']);
    foreach ($hasil as $i => &$h) {
        $h['ranking'] = $i + 1;
    }
    unset($h);

    return [
        'min'          => $minK,
        'max'          => $maxK,
        'normalisasi'  => $normalisasi,
        'util_marjinal'=> $utilMarjinal,
        'hasil'        => $hasil,
        'mode'         => $mode,
    ];
}

/** Format angka untuk tampilan (3 desimal, koma) */
function fmt($n, int $dec = 3): string
{
    return number_format((float) $n, $dec, ',', '.');
}

/**
 * Pastikan kolom nama_penghitung tersedia di tabel riwayat.
 * Idempoten — aman dipanggil berkali-kali (untuk instalasi lama).
 */
function pastikanKolomRiwayat(PDO $pdo): void
{
    $ada = $pdo->query("SHOW COLUMNS FROM riwayat LIKE 'nama_penghitung'")->fetch();
    if (!$ada) {
        $pdo->exec("ALTER TABLE riwayat ADD COLUMN nama_penghitung VARCHAR(120) NULL AFTER metode");
    }
}

/**
 * Simpan satu perhitungan MAUT ke tabel riwayat.
 * Menyimpan seluruh data input + hasil pada kolom detail (JSON) agar
 * perhitungan lengkap bisa ditampilkan / dicetak ulang dari riwayat.
 * @return int id riwayat yang baru dibuat
 */
function simpanRiwayat(
    PDO $pdo,
    array $alternatif,
    array $kriteria,
    array $matriks,
    array $res,
    string $mode,
    string $tanggal,
    string $namaPenghitung,
    int $userId
): int {
    pastikanKolomRiwayat($pdo);

    $terbaik = $res['hasil'][0];
    $detail = [
        'meta'       => [
            'mode'            => $mode,
            'tanggal'         => $tanggal,
            'nama_penghitung' => $namaPenghitung,
        ],
        'alternatif' => array_map(fn($a) => ['kode' => $a['kode'], 'nama' => $a['nama']], $alternatif),
        'kriteria'   => array_map(fn($k) => [
            'kode'  => $k['kode'],
            'nama'  => $k['nama'],
            'bobot' => (float) $k['bobot'],
            'jenis' => $k['jenis'],
        ], $kriteria),
        'matriks'    => $matriks,
        'res'        => $res,
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO riwayat
         (tanggal, metode, nama_penghitung, total_alternatif, total_kriteria, kader_terbaik, nilai_terbaik, detail, user_id)
         VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $tanggal . ' ' . date('H:i:s'),
        $mode === 'eksponensial' ? 'MAUT Eksponensial' : 'MAUT Linear',
        $namaPenghitung,
        count($alternatif),
        count($kriteria),
        $terbaik['nama'] . ' (' . $terbaik['kode'] . ')',
        $terbaik['nilai'],
        json_encode($detail, JSON_UNESCAPED_UNICODE),
        $userId,
    ]);

    return (int) $pdo->lastInsertId();
}
