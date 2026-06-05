<?php
// Komponen Kop Surat — di-include oleh halaman cetak
// Variabel opsional: $docTitle, $docSub
$docTitle = $docTitle ?? 'LAPORAN';
$docSub   = $docSub ?? '';
?>
<div class="kop">
  <div class="logo">
    <img src="../assets/img/logo.png" alt="Logo PKK">
  </div>
  <div class="teks">
    <h1><?= NAMA_INSTANSI ?></h1>
    <h2><?= SUB_INSTANSI ?></h2>
    <p>Tim Penggerak PKK &mdash; Kegiatan Posyandu Lansia</p>
    <p class="web"><?= ALAMAT_INSTANSI ?></p>
  </div>
</div>

<div class="doc-title">
  <h3><?= e($docTitle) ?></h3>
  <?php if ($docSub): ?><p><?= e($docSub) ?></p><?php endif; ?>
</div>
