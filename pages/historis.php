<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();
pastikanKolomRiwayat($pdo);

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM riwayat WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success','Riwayat berhasil dihapus.');
    header('Location: historis.php'); exit;
}

$riwayat = $pdo->query("SELECT * FROM riwayat ORDER BY tanggal DESC")->fetchAll();
$detailId = (int)($_GET['detail'] ?? 0);
$detail = null;
if ($detailId) {
    $stmt = $pdo->prepare("SELECT * FROM riwayat WHERE id=?"); $stmt->execute([$detailId]);
    $row = $stmt->fetch();
    if ($row) {
        $d = json_decode($row['detail'], true);
        // Format baru: {meta, res:{hasil}}; format lama: list hasil langsung
        $hasil = isset($d['res']['hasil']) ? $d['res']['hasil'] : (is_array($d) ? $d : []);
        $detail = ['row' => $row, 'hasil' => $hasil];
    }
}

$pageTitle = 'Riwayat Perhitungan';
$pageDesc  = 'Catatan historis hasil perhitungan MAUT yang tersimpan';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($detail): ?>
<div class="card mb-2">
  <div class="section-head">
    <h3>Detail Riwayat #<?= $detail['row']['id'] ?></h3>
    <div class="flex gap">
      <a href="perhitungan.php?hasil=<?= $detail['row']['id'] ?>" class="btn btn-light btn-sm">Perhitungan Lengkap</a>
      <a href="historis.php" class="btn btn-ghost btn-sm">&larr; Kembali</a>
    </div>
  </div>
  <p class="muted small mb-2">
    <?= date('d F Y, H:i', strtotime($detail['row']['tanggal'])) ?> &middot; Metode <b><?= e($detail['row']['metode']) ?></b>
    <?php if (!empty($detail['row']['nama_penghitung'])): ?> &middot; Penghitung <b><?= e($detail['row']['nama_penghitung']) ?></b><?php endif; ?>
  </p>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th class="center">Rank</th><th>Kode</th><th>Nama</th><th class="center">Nilai Utilitas</th></tr></thead>
      <tbody>
      <?php foreach ($detail['hasil'] as $h): ?>
        <tr class="<?= $h['ranking']===1?'highlight-row':'' ?>">
          <td class="center"><span class="rank-pill <?= $h['ranking']===1?'gold':'' ?>"><?= $h['ranking'] ?></span></td>
          <td><span class="badge badge-code"><?= e($h['kode']) ?></span></td>
          <td><b><?= e($h['nama']) ?></b></td>
          <td class="center"><b><?= fmt($h['nilai']) ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="section-head"><h3>Daftar Riwayat</h3><span class="sub"><?= count($riwayat) ?> catatan</span></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Tanggal</th><th>Metode</th><th>Penghitung</th><th class="center">Alt</th><th class="center">Kriteria</th><th>Kader Terbaik</th><th class="center">Nilai</th><th class="center">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($riwayat as $r): ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
          <td><span class="badge badge-benefit"><?= e($r['metode']) ?></span></td>
          <td><?= e($r['nama_penghitung'] ?? '-') ?></td>
          <td class="center"><?= $r['total_alternatif'] ?></td>
          <td class="center"><?= $r['total_kriteria'] ?></td>
          <td><b><?= e($r['kader_terbaik']) ?></b> <i class="fa-solid fa-trophy" style="color:#e0a458"></i></td>
          <td class="center"><b><?= fmt($r['nilai_terbaik']) ?></b></td>
          <td class="center">
            <div class="flex gap" style="justify-content:center">
              <a href="historis.php?detail=<?= $r['id'] ?>" class="btn btn-light btn-sm">Detail</a>
              <button class="btn btn-danger btn-sm" onclick="konfirmHapus('historis.php?hapus=<?= $r['id'] ?>','riwayat ini')">Hapus</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$riwayat): ?><tr><td colspan="8" class="center muted">Belum ada riwayat. Lakukan perhitungan dari halaman Perhitungan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
