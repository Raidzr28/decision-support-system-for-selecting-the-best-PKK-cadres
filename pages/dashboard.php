<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

$jmlAlt = (int) $pdo->query("SELECT COUNT(*) FROM alternatif")->fetchColumn();
$jmlKri = (int) $pdo->query("SELECT COUNT(*) FROM kriteria")->fetchColumn();
$jmlRiwayat = (int) $pdo->query("SELECT COUNT(*) FROM riwayat")->fetchColumn();

// Hitung kader terbaik saat ini
[$alternatif, $kriteria, $matriks] = ambilDataMAUT($pdo);
$terbaik = null;
$hasil = [];
if ($alternatif && $kriteria) {
    $res = hitungMAUT($alternatif, $kriteria, $matriks, 'linear');
    $hasil = $res['hasil'];
    $terbaik = $hasil[0] ?? null;
}

$pageTitle = 'Beranda';
$pageDesc  = 'Ringkasan sistem penilaian kader PKK terbaik';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu statistik -->
<div class="grid grid-3 mb-2">
  <div class="card stat-card">
    <div class="ic" style="background:var(--grad-main)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
    </div>
    <div class="big"><?= $jmlAlt ?></div>
    <div class="lbl">Total Kader (Alternatif)</div>
    <div class="ribbon" style="background:var(--coral)"></div>
  </div>

  <div class="card stat-card">
    <div class="ic" style="background:var(--grad-amber)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="4" y1="21" x2="4" y2="14"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
    </div>
    <div class="big"><?= $jmlKri ?></div>
    <div class="lbl">Kriteria Penilaian</div>
    <div class="ribbon" style="background:var(--amber)"></div>
  </div>

  <div class="card stat-card">
    <div class="ic" style="background:var(--grad-rose)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="12" x2="15" y2="14"/></svg>
    </div>
    <div class="big"><?= $jmlRiwayat ?></div>
    <div class="lbl">Riwayat Perhitungan</div>
    <div class="ribbon" style="background:#ff6a88"></div>
  </div>
</div>

<!-- Banner kader terbaik + aksi cepat -->
<div class="grid grid-2 mb-2">
  <div class="hero-banner">
    <div class="deco"></div><div class="deco2"></div>
    <?php if ($terbaik): ?>
      <small style="opacity:.9;font-weight:700;letter-spacing:1px"><i class="fa-solid fa-trophy"></i> KADER TERBAIK SAAT INI</small>
      <h3 style="margin-top:10px"><?= e($terbaik['nama']) ?> (<?= e($terbaik['kode']) ?>)</h3>
      <p>Nilai utilitas tertinggi: <b><?= fmt($terbaik['nilai']) ?></b> &mdash; direkomendasikan sebagai kader PKK terbaik berdasarkan metode MAUT.</p>
      <a href="perhitungan.php" class="btn btn-light" style="margin-top:18px">Lihat Perhitungan</a>
    <?php else: ?>
      <h3>Belum ada data</h3>
      <p>Tambahkan data alternatif & kriteria untuk memulai perhitungan.</p>
    <?php endif; ?>
  </div>

  <div class="card pad-lg">
    <div class="section-head"><h3>Aksi Cepat</h3></div>
    <div class="flex wrap gap" style="flex-direction:column">
      <a href="perhitungan.php" class="btn btn-primary" style="justify-content:flex-start">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/></svg>
        Mulai Perhitungan MAUT
      </a>
      <a href="alternatif.php" class="btn btn-light" style="justify-content:flex-start">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Kelola Data Alternatif
      </a>
      <a href="kriteria.php" class="btn btn-light" style="justify-content:flex-start">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="4" y1="21" x2="4" y2="14"/><line x1="12" y1="21" x2="12" y2="12"/></svg>
        Atur Kriteria &amp; Bobot
      </a>
    </div>
  </div>
</div>

<!-- Ringkasan peringkat -->
<div class="card">
  <div class="section-head">
    <h3>Peringkat Sementara</h3>
    <a href="cetak_hasil.php" target="_blank" class="btn btn-ghost btn-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Cetak
    </a>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Ranking</th><th>Kode</th><th>Nama Kader</th><th class="center">Nilai Utilitas</th></tr></thead>
      <tbody>
        <?php foreach ($hasil as $h): ?>
        <tr class="<?= $h['ranking']===1?'highlight-row':'' ?>">
          <td><span class="rank-pill <?= $h['ranking']===1?'gold':'' ?>"><?= $h['ranking'] ?></span></td>
          <td><span class="badge badge-code"><?= e($h['kode']) ?></span></td>
          <td><b><?= e($h['nama']) ?></b></td>
          <td class="center"><b><?= fmt($h['nilai']) ?></b></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$hasil): ?><tr><td colspan="4" class="center muted">Belum ada data.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
