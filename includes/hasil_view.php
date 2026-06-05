<?php
// =====================================================================
// Partial: Tampilan hasil perhitungan MAUT lengkap
// Dipakai oleh pages/perhitungan.php (setelah proses / lihat riwayat)
// Variabel yang dibutuhkan:
//   $alternatif : list [ ['kode'=>, 'nama'=>], ... ]
//   $kriteria   : list [ ['kode'=>, 'nama'=>, 'jenis'=>, 'bobot'=>], ... ]
//   $matriks    : $matriks[altKode][kriKode] = nilai
//   $res        : hasil hitungMAUT()
//   $mode       : 'linear' | 'eksponensial'
//   $meta       : (opsional) ['tanggal'=>, 'nama_penghitung'=>]
// =====================================================================
$meta = $meta ?? [];
?>
<?php if (!empty($meta)): ?>
<div class="card mb-2">
  <div class="grid grid-2 gap">
    <div>
      <p class="muted small">Metode</p>
      <p><b><?= $mode === 'eksponensial' ? 'MAUT Eksponensial' : 'MAUT Linear' ?></b></p>
    </div>
    <div>
      <p class="muted small">Tanggal Penilaian</p>
      <p><b><?= e(isset($meta['tanggal']) ? date('d F Y', strtotime($meta['tanggal'])) : date('d F Y')) ?></b></p>
    </div>
    <div>
      <p class="muted small">Nama Penghitung</p>
      <p><b><?= e($meta['nama_penghitung'] ?? '-') ?></b></p>
    </div>
    <div>
      <p class="muted small">Ukuran</p>
      <p><b><?= count($alternatif) ?></b> alternatif &times; <b><?= count($kriteria) ?></b> kriteria</p>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 1. Matriks Keputusan -->
<div class="card mb-2">
  <div class="section-head"><h3>1. Matriks Keputusan (X<sub>ij</sub>)</h3><span class="sub"><?= count($alternatif) ?> alternatif &times; <?= count($kriteria) ?> kriteria</span></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Alternatif</th>
        <?php foreach ($kriteria as $k): ?><th class="center"><?= e($k['kode']) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($alternatif as $a): ?>
        <tr><td><b><?= e($a['kode']) ?></b> &middot; <?= e($a['nama']) ?></td>
          <?php foreach ($kriteria as $k): ?><td class="center"><?= fmt($matriks[$a['kode']][$k['kode']] ?? 0, 0) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
        <tr style="background:#fffaf6">
          <td><b>MIN</b></td>
          <?php foreach ($kriteria as $k): ?><td class="center muted"><?= fmt($res['min'][$k['kode']],0) ?></td><?php endforeach; ?>
        </tr>
        <tr style="background:#fffaf6">
          <td><b>MAX</b></td>
          <?php foreach ($kriteria as $k): ?><td class="center muted"><?= fmt($res['max'][$k['kode']],0) ?></td><?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- 2. Normalisasi -->
<div class="card mb-2">
  <div class="section-head"><h3>2. Normalisasi Matriks</h3><span class="sub">r*<sub>ij</sub> = (r<sub>ij</sub> &minus; min) / (max &minus; min)</span></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Alternatif</th>
        <?php foreach ($kriteria as $k): ?><th class="center"><?= e($k['kode']) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($alternatif as $a): ?>
        <tr><td><b><?= e($a['kode']) ?></b></td>
          <?php foreach ($kriteria as $k): ?><td class="center"><?= fmt($res['normalisasi'][$a['kode']][$k['kode']], 2) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($mode === 'eksponensial'): ?>
<!-- 2b. Utilitas Marjinal -->
<div class="card mb-2">
  <div class="section-head"><h3>3. Utilitas Marjinal</h3><span class="sub">u<sub>ij</sub> = (e^(r*<sub>ij</sub>&sup2;) &minus; 1) / 1,71</span></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Alternatif</th>
        <?php foreach ($kriteria as $k): ?><th class="center"><?= e($k['kode']) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($alternatif as $a): ?>
        <tr><td><b><?= e($a['kode']) ?></b></td>
          <?php foreach ($kriteria as $k): ?><td class="center"><?= fmt($res['util_marjinal'][$a['kode']][$k['kode']], 3) ?></td><?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- 3. Bobot Kriteria -->
<div class="card mb-2">
  <div class="section-head"><h3><?= $mode==='eksponensial'?'4':'3' ?>. Bobot Kriteria (w<sub>j</sub>)</h3></div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Kode</th><th>Kriteria</th><th class="center">Jenis</th><th class="center">Bobot</th></tr></thead>
      <tbody>
      <?php $totalBobot=0; foreach ($kriteria as $k): $totalBobot+=(float)$k['bobot']; ?>
        <tr><td><span class="badge badge-code"><?= e($k['kode']) ?></span></td>
          <td><?= e($k['nama']) ?></td>
          <td class="center"><span class="badge badge-<?= $k['jenis'] ?>"><?= ucfirst($k['jenis']) ?></span></td>
          <td class="center"><b><?= fmt($k['bobot'],2) ?></b></td>
        </tr>
      <?php endforeach; ?>
        <tr style="background:#fffaf6"><td colspan="3" class="text-right"><b>Total</b></td><td class="center"><b><?= fmt($totalBobot,2) ?></b></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- 4. Utilitas Akhir & Perangkingan -->
<div class="card">
  <div class="section-head">
    <h3><?= $mode==='eksponensial'?'5':'4' ?>. Utilitas Akhir &amp; Perangkingan</h3>
    <span class="sub">U<sub>i</sub> = &Sigma; (u<sub>ij</sub> &times; w<sub>j</sub>)</span>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th class="center">Rank</th><th>Kode</th><th>Nama Kader</th>
        <?php foreach ($kriteria as $k): ?><th class="center"><?= e($k['kode']) ?></th><?php endforeach; ?>
        <th class="center">Nilai U<sub>i</sub></th></tr></thead>
      <tbody>
      <?php foreach ($res['hasil'] as $h): ?>
        <tr class="<?= $h['ranking']===1?'highlight-row':'' ?>">
          <td class="center"><span class="rank-pill <?= $h['ranking']===1?'gold':'' ?>"><?= $h['ranking'] ?></span></td>
          <td><span class="badge badge-code"><?= e($h['kode']) ?></span></td>
          <td><b><?= e($h['nama']) ?></b><?= $h['ranking']===1?' &#127942;':'' ?></td>
          <?php foreach ($kriteria as $k): ?><td class="center muted"><?= fmt($h['rincian'][$k['kode']],3) ?></td><?php endforeach; ?>
          <td class="center"><b style="color:var(--coral-deep);font-size:15px"><?= fmt($h['nilai']) ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $best=$res['hasil'][0]; ?>
  <div class="flash flash-success mt-2" style="margin-bottom:0">
    &#127942; Kader terbaik: <b><?= e($best['nama']) ?> (<?= e($best['kode']) ?>)</b> dengan nilai utilitas <b><?= fmt($best['nilai']) ?></b>.
  </div>
</div>
