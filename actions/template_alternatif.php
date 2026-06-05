<?php
require_once __DIR__ . '/../includes/functions.php';
wajibLogin();

$kris = $pdo->query("SELECT kode FROM kriteria ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="template_alternatif.csv"');
header('Cache-Control: no-cache');

// UTF-8 BOM so Excel opens it correctly
echo "\xEF\xBB\xBF";

$cols = array_merge(['Kode', 'Nama'], $kris);
echo implode(',', $cols) . "\r\n";

// Two example rows
$n = count($kris);
echo 'A01,Contoh Kader 1,' . implode(',', array_fill(0, $n, '7')) . "\r\n";
echo 'A02,Contoh Kader 2,' . implode(',', array_fill(0, $n, '8')) . "\r\n";
