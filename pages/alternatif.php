<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

// ---- Tambah / Edit / Hapus alternatif ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $kode = trim($_POST['kode'] ?? '');
    $nama = trim($_POST['nama'] ?? '');

    if ($aksi === 'tambah' && $kode && $nama) {
        $stmt = $pdo->prepare("INSERT INTO alternatif (kode, nama) VALUES (?,?)");
        try {
            $stmt->execute([$kode, $nama]);
            // inisialisasi nilai 0 untuk tiap kriteria
            $altId = $pdo->lastInsertId();
            $kris = $pdo->query("SELECT id FROM kriteria")->fetchAll();
            foreach ($kris as $kr) {
                $pdo->prepare("INSERT INTO nilai (alternatif_id, kriteria_id, nilai) VALUES (?,?,0)")->execute([$altId, $kr['id']]);
            }
            setFlash('success', 'Alternatif berhasil ditambahkan.');
        } catch (PDOException $e) {
            setFlash('error', 'Kode alternatif sudah digunakan.');
        }
    } elseif ($aksi === 'edit' && !empty($_POST['id'])) {
        $altId = (int) $_POST['id'];
        try {
            $pdo->prepare("UPDATE alternatif SET kode=?, nama=? WHERE id=?")->execute([$kode, $nama, $altId]);
            // perbarui nilai tiap kriteria
            if (!empty($_POST['nilai']) && is_array($_POST['nilai'])) {
                $up = $pdo->prepare("
                    INSERT INTO nilai (alternatif_id, kriteria_id, nilai) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
                ");
                foreach ($_POST['nilai'] as $kriId => $val) {
                    $v = max(0, min(10, (float) $val));
                    $up->execute([$altId, (int) $kriId, $v]);
                }
            }
            setFlash('success', 'Alternatif berhasil diperbarui.');
        } catch (PDOException $e) {
            setFlash('error', 'Kode alternatif sudah digunakan.');
        }
    }
    header('Location: alternatif.php');
    exit;
}

// ---- Hapus ----
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM alternatif WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success', 'Alternatif berhasil dihapus.');
    header('Location: alternatif.php');
    exit;
}

[$alternatif, $kriteria, $matriks] = ambilDataMAUT($pdo);

$pageTitle = 'Data Alternatif';
$pageDesc  = 'Kelola data kader PKK & nilai pada tiap kriteria';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Form tambah -->
<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="section-head"><h3>Tambah Alternatif Baru</h3></div>
    <form method="post">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label>Kode</label>
          <input type="text" name="kode" class="input" placeholder="A06" required>
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label>Nama Kader</label>
          <input type="text" name="nama" class="input" placeholder="Nama kader PKK" required>
        </div>
      </div>
      <button class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah
      </button>
    </form>
  </div>

  <div class="card" style="background:var(--grad-soft);display:flex;flex-direction:column;justify-content:center">
    <h3 style="color:#7a4220"><i class="fa-regular fa-lightbulb"></i> Tips</h3>
    <p class="mt-1" style="color:#9c6a4a;line-height:1.6;font-size:14px">Nilai pada matriks di bawah dapat diedit langsung. Perubahan tersimpan otomatis (AJAX) tanpa perlu reload. Skala penilaian <b>1&ndash;10</b>.</p>
    <a href="cetak_alternatif.php" target="_blank" class="btn btn-light mt-2" style="width:fit-content">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Cetak Data Alternatif
    </a>
  </div>
</div>

<!-- Import CSV / Excel -->
<div class="card mb-2">
  <div class="section-head" style="align-items:center">
    <h3>Import dari CSV / Excel</h3>
    <a href="../actions/template_alternatif.php" class="btn btn-ghost btn-sm" style="display:flex;align-items:center;gap:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Unduh Template
    </a>
  </div>

  <!-- Drop zone -->
  <div id="dropZone" class="drop-zone" onclick="document.getElementById('csvFile').click()"
       style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center">
    <svg class="drop-icon" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:.5rem"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
    <p class="drop-title">Seret &amp; lepas file di sini</p>
    <p class="drop-sub">atau klik untuk memilih file</p>
    <p class="drop-hint">.csv &bull; .xlsx &nbsp;&bull;&nbsp; Maks. 2 MB</p>
    <input type="file" id="csvFile" accept=".csv,.xlsx" hidden>
  </div>

  <!-- Preview (shown after file selected) -->
  <div id="importPreview" style="display:none;margin-top:1.1rem">
    <div class="file-chip" id="fileChip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <span class="file-chip-name" id="chipName">file.csv</span>
      <span class="file-chip-size" id="chipSize">—</span>
      <button type="button" class="btn btn-ghost btn-sm" id="btnGantiFile" style="margin-left:auto;flex-shrink:0">Ganti</button>
    </div>
    <div class="table-wrap mt-1" id="csvPreviewWrap" style="max-height:210px;overflow:auto"></div>
    <div class="flex gap mt-2">
      <button type="button" class="btn btn-primary" id="btnDoImport" style="display:flex;align-items:center;gap:7px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="17" height="17"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="21" x2="12" y2="9"/><path d="M20.88 18.09A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.29"/></svg>
        <span>Import Data</span>
      </button>
      <button type="button" class="btn btn-ghost" id="btnBatalImport">Batal</button>
    </div>
  </div>

  <div id="importResult" style="display:none;margin-top:1rem"></div>
</div>

<!-- Tabel alternatif + nilai (editable) -->
<div class="card">
  <div class="section-head">
    <h3>Daftar Alternatif &amp; Nilai Kriteria</h3>
    <span class="sub"><?= count($alternatif) ?> kader</span>
  </div>
  <div class="table-wrap">
    <table class="tbl">
      <thead><tr>
        <th>Kode</th><th>Nama Kader</th>
        <?php foreach ($kriteria as $k): ?><th class="center" title="<?= e($k['nama']) ?>"><?= e($k['kode']) ?></th><?php endforeach; ?>
        <th class="center">Aksi</th>
      </tr></thead>
      <tbody>
      <?php foreach ($alternatif as $a): ?>
        <tr>
          <td><span class="badge badge-code"><?= e($a['kode']) ?></span></td>
          <td><b><?= e($a['nama']) ?></b></td>
          <?php foreach ($kriteria as $k): ?>
            <td class="center">
              <input type="number" min="0" max="10" step="0.1" class="nilai-input input" style="width:64px;padding:7px;text-align:center"
                     value="<?= e((string)(float)($matriks[$a['kode']][$k['kode']] ?? 0)) ?>"
                     data-alt="<?= (int)$a['id'] ?>" data-kri="<?= (int)$k['id'] ?>">
            </td>
          <?php endforeach; ?>
          <td class="center">
            <div class="flex gap" style="justify-content:center">
              <button type="button" class="btn btn-light btn-sm btn-edit"
                      data-id="<?= (int)$a['id'] ?>" data-kode="<?= e($a['kode']) ?>" data-nama="<?= e($a['nama']) ?>">Edit</button>
              <button type="button" class="btn btn-danger btn-sm" onclick="konfirmHapus('alternatif.php?hapus=<?= $a['id'] ?>','<?= e($a['nama']) ?>')">Hapus</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal edit sederhana -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(58,44,42,.45);z-index:100;place-items:center;overflow:auto;padding:24px 0">
  <div class="card" style="width:480px;max-width:92%">
    <div class="section-head"><h3>Edit Alternatif</h3></div>
    <form method="post">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="form-row">
        <div class="form-group"><label>Kode</label><input type="text" name="kode" id="edit-kode" class="input" required></div>
        <div class="form-group" style="grid-column:span 2"><label>Nama Kader</label><input type="text" name="nama" id="edit-nama" class="input" required></div>
      </div>
      <div class="form-group">
        <label>Nilai Kriteria <span class="muted small" style="font-weight:500">(skala 1&ndash;10)</span></label>
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(86px,1fr));gap:10px">
          <?php foreach ($kriteria as $k): ?>
            <div>
              <label style="font-size:12px;color:var(--ink-soft);margin-bottom:4px" title="<?= e($k['nama']) ?>"><?= e($k['kode']) ?></label>
              <input type="number" min="0" max="10" step="0.1" name="nilai[<?= (int)$k['id'] ?>]" data-kri="<?= (int)$k['id'] ?>"
                     class="input edit-nilai" style="padding:9px;text-align:center" required>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex gap mt-1">
        <button class="btn btn-primary">Simpan</button>
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
      </div>
    </form>
  </div>
</div>
<script>
// ---- Edit alternatif (modal) ----
(function(){
  const modal = document.getElementById('editModal');
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      document.getElementById('edit-id').value   = id;
      document.getElementById('edit-kode').value = btn.dataset.kode;
      document.getElementById('edit-nama').value = btn.dataset.nama;
      // isi nilai kriteria dari nilai terkini di tabel
      modal.querySelectorAll('.edit-nilai').forEach(inp => {
        const src = document.querySelector('.nilai-input[data-alt="' + id + '"][data-kri="' + inp.dataset.kri + '"]');
        inp.value = src ? src.value : 0;
      });
      modal.style.display = 'grid';
    });
  });
  // Tutup modal saat klik backdrop
  modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
})();

// ---- Import CSV / Excel ----
(function(){
  const dropZone   = document.getElementById('dropZone');
  const csvFile    = document.getElementById('csvFile');
  const preview    = document.getElementById('importPreview');
  const previewWrap= document.getElementById('csvPreviewWrap');
  const resultBox  = document.getElementById('importResult');
  let   selFile    = null;

  // File picker change
  csvFile.addEventListener('change', e => e.target.files[0] && handleFile(e.target.files[0]));

  // Drag & drop
  ['dragenter','dragover'].forEach(ev =>
    dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('drag-over'); })
  );
  ['dragleave','dragend'].forEach(ev =>
    dropZone.addEventListener(ev, () => dropZone.classList.remove('drag-over'))
  );
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    e.dataTransfer.files[0] && handleFile(e.dataTransfer.files[0]);
  });

  function handleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['csv','xlsx'].includes(ext)) { toast('Format tidak didukung. Gunakan .csv atau .xlsx', 'error'); return; }
    if (file.size > 2*1024*1024)       { toast('Ukuran file maksimal 2 MB', 'error'); return; }
    selFile = file;
    document.getElementById('chipName').textContent = file.name;
    document.getElementById('chipSize').textContent = fmtSize(file.size);
    previewWrap.innerHTML = '';
    resultBox.style.display = 'none';
    if (ext === 'csv') {
      const r = new FileReader();
      r.onload = e => renderCsvPreview(e.target.result);
      r.readAsText(file, 'UTF-8');
    } else {
      previewWrap.innerHTML = '<p style="color:var(--ink-soft);font-size:13px;padding:6px 0">Preview tidak tersedia untuk Excel — file akan diproses saat import.</p>';
    }
    dropZone.style.display = 'none';
    preview.style.display  = 'block';
  }

  function renderCsvPreview(text) {
    if (text.charCodeAt(0) === 0xFEFF) text = text.slice(1); // strip BOM
    const allLines = text.trim().split(/\r?\n/);
    const lines    = allLines.slice(0, 6);
    const tbl = document.createElement('table');
    tbl.className = 'tbl';
    lines.forEach((line, i) => {
      const tr = document.createElement('tr');
      parseLine(line).forEach(cell => {
        const td = document.createElement(i === 0 ? 'th' : 'td');
        td.textContent = cell;
        tr.appendChild(td);
      });
      tbl.appendChild(tr);
    });
    previewWrap.appendChild(tbl);
    if (allLines.length > 6) {
      const note = document.createElement('p');
      note.style.cssText = 'font-size:12px;color:var(--ink-soft);margin-top:6px';
      note.textContent = '* Menampilkan 5 baris pertama dari ' + (allLines.length - 1) + ' baris data';
      previewWrap.appendChild(note);
    }
  }

  function parseLine(line) {
    const cols = []; let cur = '', inQ = false;
    for (let i = 0; i < line.length; i++) {
      const ch = line[i];
      if (ch === '"') inQ = !inQ;
      else if (ch === ',' && !inQ) { cols.push(cur.trim()); cur = ''; }
      else cur += ch;
    }
    cols.push(cur.trim());
    return cols;
  }

  function fmtSize(b) {
    return b < 1024 ? b+' B' : b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(1)+' MB';
  }

  function resetImport() {
    selFile = null; csvFile.value = '';
    dropZone.style.display  = '';
    preview.style.display   = 'none';
    resultBox.style.display = 'none';
  }

  document.getElementById('btnGantiFile').addEventListener('click', () => { resetImport(); csvFile.click(); });
  document.getElementById('btnBatalImport').addEventListener('click', resetImport);

  document.getElementById('btnDoImport').addEventListener('click', async () => {
    if (!selFile) return;
    const btn = document.getElementById('btnDoImport');
    btn.disabled = true; btn.querySelector('svg').style.opacity='0.4'; btn.querySelector('span').textContent = 'Mengimpor...';
    resultBox.style.display = 'none';
    const fd = new FormData();
    fd.append('file', selFile);
    try {
      const res  = await fetch('../actions/upload_alternatif.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd });
      const data = await res.json();
      resultBox.style.display = 'block';
      resultBox.innerHTML = `<div class="flash flash-${data.ok?'success':'error'}">${data.msg}</div>`;
      if (data.ok) setTimeout(() => location.reload(), 1400);
    } catch(e) {
      resultBox.style.display = 'block';
      resultBox.innerHTML = '<div class="flash flash-error">Kesalahan jaringan — coba lagi</div>';
    } finally {
      btn.disabled = false; btn.querySelector('svg').style.opacity='';
      btn.querySelector('span').textContent = 'Import Data';
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
