<?php
// AJAX endpoint: simpan nilai matriks keputusan
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Sesi berakhir']); exit;
}

$altId = (int)($_POST['alternatif_id'] ?? 0);
$kriId = (int)($_POST['kriteria_id'] ?? 0);
$nilai = (float)($_POST['nilai'] ?? 0);

if ($altId <= 0 || $kriId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Parameter tidak valid']); exit;
}

try {
    // Upsert
    $stmt = $pdo->prepare("
        INSERT INTO nilai (alternatif_id, kriteria_id, nilai) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
    ");
    $stmt->execute([$altId, $kriId, $nilai]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan']);
}
