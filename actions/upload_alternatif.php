<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesi berakhir']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['ok' => false, 'msg' => 'Tidak ada file yang diunggah']); exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'msg' => 'Gagal mengunggah file (kode: ' . $file['error'] . ')']); exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'xlsx'])) {
    echo json_encode(['ok' => false, 'msg' => 'Format tidak didukung. Gunakan .csv atau .xlsx']); exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'msg' => 'Ukuran file maksimal 2 MB']); exit;
}

// Load kriteria
$kris = $pdo->query("SELECT id, kode FROM kriteria ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$kriMap = [];
foreach ($kris as $k) {
    $kriMap[strtoupper(trim($k['kode']))] = (int)$k['id'];
}

// Parse
$rows = ($ext === 'csv') ? parseCsvFile($file['tmp_name']) : parseXlsxFile($file['tmp_name']);

if (empty($rows)) {
    echo json_encode(['ok' => false, 'msg' => 'File kosong atau tidak dapat dibaca']); exit;
}

// Validate header
$header = array_map(fn($h) => strtoupper(trim($h)), $rows[0]);
$kodeIdx = array_search('KODE', $header);
$namaIdx = array_search('NAMA', $header);

if ($kodeIdx === false || $namaIdx === false) {
    echo json_encode(['ok' => false, 'msg' => 'Header wajib tidak ditemukan. Kolom pertama: Kode, kedua: Nama']); exit;
}

// Map kriteria columns by header name
$kriCols = [];
foreach ($header as $i => $h) {
    if ($i === $kodeIdx || $i === $namaIdx) continue;
    if (isset($kriMap[$h])) $kriCols[$i] = $kriMap[$h];
}

try {
    $pdo->beginTransaction();

    $stmtAlt = $pdo->prepare(
        "INSERT INTO alternatif (kode, nama) VALUES (?,?)
         ON DUPLICATE KEY UPDATE nama=VALUES(nama), id=LAST_INSERT_ID(id)"
    );
    $stmtInit = $pdo->prepare(
        "INSERT IGNORE INTO nilai (alternatif_id, kriteria_id, nilai) VALUES (?,?,0)"
    );
    $stmtNilai = $pdo->prepare(
        "INSERT INTO nilai (alternatif_id, kriteria_id, nilai) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)"
    );

    $count = 0;
    for ($r = 1; $r < count($rows); $r++) {
        $row  = $rows[$r];
        $kode = trim($row[$kodeIdx] ?? '');
        $nama = trim($row[$namaIdx] ?? '');
        if ($kode === '' || $nama === '') continue;

        $stmtAlt->execute([$kode, $nama]);
        $altId = (int)$pdo->lastInsertId();
        if (!$altId) continue;

        // Ensure nilai rows exist for every kriteria
        foreach ($kris as $kr) {
            $stmtInit->execute([$altId, $kr['id']]);
        }

        // Write values from mapped columns
        foreach ($kriCols as $colIdx => $kriId) {
            $val = isset($row[$colIdx]) && $row[$colIdx] !== '' ? (float)$row[$colIdx] : 0.0;
            $val = max(0.0, min(10.0, $val));
            $stmtNilai->execute([$altId, $kriId, $val]);
        }

        $count++;
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => "$count baris berhasil diimpor", 'count' => $count]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Gagal mengimpor: ' . $e->getMessage()]);
}

// ---- helpers ----

function parseCsvFile(string $path): array {
    $rows = [];
    if (($fh = fopen($path, 'r')) !== false) {
        $first = true;
        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            if ($first) {
                // Strip UTF-8 BOM
                if (isset($row[0])) $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                $first = false;
            }
            $rows[] = $row;
        }
        fclose($fh);
    }
    return $rows;
}

function parseXlsxFile(string $path): array {
    if (!class_exists('ZipArchive')) return [];

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return [];

    // Shared strings
    $shared = [];
    $ssXml  = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ss = simplexml_load_string($ssXml);
        if ($ss) {
            foreach ($ss->si as $si) {
                if (count($si->r)) {
                    $t = '';
                    foreach ($si->r as $r) $t .= (string)$r->t;
                    $shared[] = $t;
                } else {
                    $shared[] = (string)$si->t;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetXml) return [];

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) return [];

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        $max   = 0;
        foreach ($row->c as $cell) {
            preg_match('/^([A-Z]+)/', (string)$cell['r'], $m);
            $col = xlsxColIdx($m[1]);
            $t   = (string)$cell['t'];
            $v   = (string)$cell->v;
            if ($t === 's')         $v = $shared[(int)$v] ?? '';
            elseif ($t === 'inlineStr') $v = (string)$cell->is->t;
            $cells[$col] = $v;
            if ($col > $max) $max = $col;
        }
        $arr = [];
        for ($i = 0; $i <= $max; $i++) $arr[] = $cells[$i] ?? '';
        if (count(array_filter($arr, fn($v) => $v !== '')) > 0) $rows[] = $arr;
    }
    return $rows;
}

function xlsxColIdx(string $col): int {
    $idx = 0;
    for ($i = 0; $i < strlen($col); $i++) $idx = $idx * 26 + (ord($col[$i]) - 64);
    return $idx - 1;
}
