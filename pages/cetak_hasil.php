<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

$meta = [];
$backLink = 'perhitungan.php';

if (isset($_GET['hasil'])) {
    // Cetak dari riwayat tersimpan
    $stmt = $pdo->prepare("SELECT * FROM riwayat WHERE id=?");
    $stmt->execute([(int) $_GET['hasil']]);
    $row = $stmt->fetch();
    $d = $row ? json_decode($row['detail'], true) : null;
    if (is_array($d) && isset($d['res'])) {
        $alternatif = $d['alternatif'];
        $kriteria   = $d['kriteria'];
        $matriks    = $d['matriks'];
        $res        = $d['res'];
        $mode       = $d['meta']['mode'] ?? 'linear';
        $meta       = $d['meta'];
        $backLink   = 'perhitungan.php?hasil=' . (int) $row['id'];
    } else {
        $res = null;
    }
} else {
    // Cetak dari data live database (kompatibilitas lama)
    $mode = ($_GET['mode'] ?? 'linear') === 'eksponensial' ? 'eksponensial' : 'linear';
    [$alternatif, $kriteria, $matriks] = ambilDataMAUT($pdo);
    $res = ($alternatif && $kriteria) ? hitungMAUT($alternatif, $kriteria, $matriks, $mode) : null;
    $backLink = 'perhitungan.php?mode=' . $mode;
}

$docTitle = 'LAPORAN HASIL PENILAIAN KADER PKK TERBAIK';
$docSub   = 'Metode Multi Attribute Utility Theory (MAUT)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Hasil &mdash; SPK MAUT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="../assets/css/print.css?v=<?= @filemtime(__DIR__ . '/../assets/css/print.css') ?>">
</head>
<body>
<div class="print-toolbar">
  <button class="pr" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
  <a class="bk" href="<?= e($backLink) ?>"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</div>

<div class="paper">
  <?php require __DIR__ . '/../includes/kop_surat.php'; ?>

  <?php if (!$res): ?>
    <p>Data belum lengkap.</p>
  <?php else: ?>

  <div class="meta">
    <table>
      <tr><td>Tanggal Penilaian</td><td>: <?= e(isset($meta['tanggal']) ? date('d F Y', strtotime($meta['tanggal'])) : date('d F Y')) ?></td></tr>
      <?php if (!empty($meta['nama_penghitung'])): ?>
      <tr><td>Nama Penghitung</td><td>: <?= e($meta['nama_penghitung']) ?></td></tr>
      <?php endif; ?>
      <tr><td>Metode Perhitungan</td><td>: MAUT (<?= ucfirst($mode) ?>)</td></tr>
      <tr><td>Jumlah Alternatif</td><td>: <?= count($alternatif) ?> kader</td></tr>
      <tr><td>Jumlah Kriteria</td><td>: <?= count($kriteria) ?> kriteria</td></tr>
    </table>
  </div>

  <p><b>A. Matriks Keputusan</b></p>
  <table class="report">
    <tr><th>Alternatif</th><?php foreach($kriteria as $k):?><th><?=e($k['kode'])?></th><?php endforeach;?></tr>
    <?php foreach($alternatif as $a):?>
      <tr><td><?=e($a['kode'])?> - <?=e($a['nama'])?></td>
      <?php foreach($kriteria as $k):?><td class="center"><?=fmt($matriks[$a['kode']][$k['kode']]??0,0)?></td><?php endforeach;?></tr>
    <?php endforeach;?>
  </table>

  <p><b>B. Hasil Normalisasi</b></p>
  <table class="report">
    <tr><th>Alternatif</th><?php foreach($kriteria as $k):?><th><?=e($k['kode'])?></th><?php endforeach;?></tr>
    <?php foreach($alternatif as $a):?>
      <tr><td><?=e($a['kode'])?></td>
      <?php foreach($kriteria as $k):?><td class="center"><?=fmt($res['normalisasi'][$a['kode']][$k['kode']],2)?></td><?php endforeach;?></tr>
    <?php endforeach;?>
  </table>

  <p><b>C. Bobot Kriteria</b></p>
  <table class="report">
    <tr><th>Kode</th><th>Kriteria</th><th>Jenis</th><th>Bobot</th></tr>
    <?php foreach($kriteria as $k):?>
      <tr><td class="center"><?=e($k['kode'])?></td><td><?=e($k['nama'])?></td><td class="center"><?=ucfirst($k['jenis'])?></td><td class="center"><?=fmt($k['bobot'],2)?></td></tr>
    <?php endforeach;?>
  </table>

  <p><b>D. Nilai Utilitas Akhir &amp; Perangkingan</b></p>
  <table class="report">
    <tr><th>Ranking</th><th>Kode</th><th>Nama Kader</th><th>Nilai Utilitas</th></tr>
    <?php foreach($res['hasil'] as $h):?>
      <tr class="<?=$h['ranking']===1?'best':''?>">
        <td class="center"><?=$h['ranking']?></td>
        <td class="center"><?=e($h['kode'])?></td>
        <td><?=e($h['nama'])?></td>
        <td class="center"><?=fmt($h['nilai'])?></td>
      </tr>
    <?php endforeach;?>
  </table>

  <?php $best=$res['hasil'][0];?>
  <div class="summary-box">
    <b>Kesimpulan:</b> Berdasarkan hasil perhitungan metode MAUT, kader dengan nilai utilitas tertinggi adalah
    <b><?=e($best['nama'])?> (<?=e($best['kode'])?>)</b> dengan nilai <b><?=fmt($best['nilai'])?></b>,
    sehingga direkomendasikan sebagai <b>Kader PKK Terbaik</b> pada kegiatan Posyandu Lansia.
  </div>

  <div class="ttd">
    <div class="blk">
      <p>Jakarta Timur, <?= date('d F Y') ?></p>
      <p>Ketua Tim Penggerak PKK</p>
      <div class="sp"></div>
      <p class="nm">( .................................. )</p>
    </div>
  </div>
  <p class="note">*Dokumen ini dihasilkan secara otomatis oleh Sistem Pendukung Keputusan MAUT.</p>

  <?php endif; ?>
</div>
<script><?php /* auto-fokus print opsional */ ?></script>
</body>
</html>
