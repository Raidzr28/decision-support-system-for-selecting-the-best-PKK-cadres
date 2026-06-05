<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

[$alternatif, $kriteria, $matriks] = ambilDataMAUT($pdo);

$docTitle = 'DAFTAR DATA ALTERNATIF (KADER PKK)';
$docSub   = 'Data Penilaian Kinerja Kader pada Kegiatan Posyandu Lansia';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Data Alternatif &mdash; SPK MAUT</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="../assets/css/print.css?v=<?= @filemtime(__DIR__ . '/../assets/css/print.css') ?>">
</head>
<body>
<div class="print-toolbar">
  <button class="pr" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
  <a class="bk" href="alternatif.php"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</div>

<div class="paper">
  <?php require __DIR__ . '/../includes/kop_surat.php'; ?>

  <div class="meta">
    <table>
      <tr><td>Tanggal Cetak</td><td>: <?= date('d F Y') ?></td></tr>
      <tr><td>Jumlah Alternatif</td><td>: <?= count($alternatif) ?> kader</td></tr>
      <tr><td>Skala Penilaian</td><td>: 1 &ndash; 10</td></tr>
    </table>
  </div>

  <table class="report">
    <tr>
      <th>No</th><th>Kode</th><th>Nama Kader</th>
      <?php foreach($kriteria as $k):?><th title="<?=e($k['nama'])?>"><?=e($k['kode'])?></th><?php endforeach;?>
    </tr>
    <?php $no=1; foreach($alternatif as $a):?>
      <tr>
        <td class="center"><?=$no++?></td>
        <td class="center"><?=e($a['kode'])?></td>
        <td><?=e($a['nama'])?></td>
        <?php foreach($kriteria as $k):?><td class="center"><?=fmt($matriks[$a['kode']][$k['kode']]??0,0)?></td><?php endforeach;?>
      </tr>
    <?php endforeach;?>
  </table>

  <p class="note">Keterangan kriteria:
    <?php foreach($kriteria as $i=>$k): echo ($i?'; ':'').e($k['kode']).' = '.e($k['nama']); endforeach; ?>.
  </p>

  <div class="ttd">
    <div class="blk">
      <p>Jakarta Timur, <?= date('d F Y') ?></p>
      <p>Pengelola Posyandu Lansia</p>
      <div class="sp"></div>
      <p class="nm">( .................................. )</p>
    </div>
  </div>
  <p class="note">*Dokumen ini dihasilkan secara otomatis oleh Sistem Pendukung Keputusan MAUT.</p>
</div>
</body>
</html>
