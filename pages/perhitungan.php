<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

$res = null;            // hasil perhitungan untuk ditampilkan
$mode = 'linear';
$meta = [];            // info tanggal & nama penghitung
$alternatif = [];
$kriteria = [];
$matriks = [];
$savedId = null;

// =====================================================================
// 1) Lihat hasil tersimpan dari riwayat (?hasil=id) — PRG target
// =====================================================================
if (isset($_GET['hasil'])) {
    $stmt = $pdo->prepare("SELECT * FROM riwayat WHERE id=?");
    $stmt->execute([(int) $_GET['hasil']]);
    $row = $stmt->fetch();
    if ($row) {
        $d = json_decode($row['detail'], true);
        if (is_array($d) && isset($d['res'])) {
            $alternatif = $d['alternatif'];
            $kriteria   = $d['kriteria'];
            $matriks    = $d['matriks'];
            $res        = $d['res'];
            $mode       = $d['meta']['mode'] ?? 'linear';
            $meta       = $d['meta'];
            $savedId    = (int) $row['id'];
        }
    }
    if (!$res) {
        setFlash('error', 'Data riwayat tidak ditemukan atau formatnya lama.');
        header('Location: historis.php');
        exit;
    }
}

// =====================================================================
// 2) Proses form perhitungan baru
// =====================================================================
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['proses'] ?? '') === '1') {
    $mode    = ($_POST['mode'] ?? 'linear') === 'eksponensial' ? 'eksponensial' : 'linear';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $namaPng = trim($_POST['nama_penghitung'] ?? '');

    // ---- Kumpulkan kriteria dari form ----
    $kriteria = [];
    foreach (($_POST['kri_kode'] ?? []) as $i => $kode) {
        $kode = trim($kode);
        $nama = trim($_POST['kri_nama'][$i] ?? '');
        if ($kode === '' && $nama === '') continue;       // baris kosong → lewati
        $kriteria[] = [
            'kode'  => $kode !== '' ? $kode : 'C' . ($i + 1),
            'nama'  => $nama !== '' ? $nama : ('Kriteria ' . ($i + 1)),
            'bobot' => (float) str_replace(',', '.', (string) ($_POST['kri_bobot'][$i] ?? 0)),
            'jenis' => ($_POST['kri_jenis'][$i] ?? 'benefit') === 'cost' ? 'cost' : 'benefit',
            '_idx'  => $i,
        ];
    }

    // ---- Kumpulkan alternatif + matriks nilai dari form ----
    $alternatif = [];
    $matriks = [];
    foreach (($_POST['alt_kode'] ?? []) as $a => $akode) {
        $akode = trim($akode);
        $anama = trim($_POST['alt_nama'][$a] ?? '');
        if ($akode === '' && $anama === '') continue;
        $akode = $akode !== '' ? $akode : 'A' . ($a + 1);
        $alternatif[] = ['kode' => $akode, 'nama' => $anama !== '' ? $anama : ('Alternatif ' . ($a + 1))];
        foreach ($kriteria as $k) {
            $val = $_POST['nilai'][$a][$k['_idx']] ?? 0;
            $matriks[$akode][$k['kode']] = (float) str_replace(',', '.', (string) $val);
        }
    }

    // ---- Validasi ----
    if (count($kriteria) < 1)        $errors[] = 'Minimal harus ada 1 kriteria.';
    if (count($alternatif) < 2)      $errors[] = 'Minimal harus ada 2 alternatif untuk diperingkat.';
    if ($namaPng === '')             $errors[] = 'Nama penghitung wajib diisi.';
    $kodeKri = array_column($kriteria, 'kode');
    if (count($kodeKri) !== count(array_unique($kodeKri))) $errors[] = 'Kode kriteria tidak boleh sama.';
    $kodeAlt = array_column($alternatif, 'kode');
    if (count($kodeAlt) !== count(array_unique($kodeAlt))) $errors[] = 'Kode alternatif tidak boleh sama.';

    if (!$errors) {
        $res = hitungMAUT($alternatif, $kriteria, $matriks, $mode);
        $newId = simpanRiwayat($pdo, $alternatif, $kriteria, $matriks, $res, $mode, $tanggal, $namaPng, (int) $_SESSION['user_id']);
        setFlash('success', 'Perhitungan selesai dan otomatis tersimpan ke riwayat.');
        header('Location: perhitungan.php?hasil=' . $newId);
        exit;
    }
}

// =====================================================================
// 3) Data awal untuk mengisi form (prefill dari database, bisa diubah)
// =====================================================================
$showForm = ($res === null);
if ($showForm) {
    // ---- Filter alternatif berdasarkan bulan & tahun input (created_at) ----
    $fBulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int) $_GET['bulan'] : null;
    $fTahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int) $_GET['tahun'] : null;

    // Daftar tahun yang tersedia untuk dropdown filter
    $tahunTersedia = $pdo->query(
        "SELECT DISTINCT YEAR(created_at) AS th FROM alternatif ORDER BY th DESC"
    )->fetchAll(PDO::FETCH_COLUMN);

    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    [$dbAlt, $dbKri, $dbMatriks] = ambilDataMAUT($pdo, $fBulan, $fTahun);
    $prefillKriteria = array_map(fn($k) => [
        'kode'  => $k['kode'],
        'nama'  => $k['nama'],
        'bobot' => (float) $k['bobot'],
        'jenis' => $k['jenis'],
    ], $dbKri);
    $prefillAlternatif = array_map(function ($a) use ($dbKri, $dbMatriks) {
        $nilai = [];
        foreach ($dbKri as $idx => $k) {
            $nilai[$idx] = (float) ($dbMatriks[$a['kode']][$k['kode']] ?? 0);
        }
        return ['kode' => $a['kode'], 'nama' => $a['nama'], 'nilai' => $nilai];
    }, $dbAlt);
}

$pageTitle = 'Perhitungan MAUT';
$pageDesc  = 'Isi data alternatif & kriteria, lalu proses untuk menghitung dan menyimpan ke riwayat';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!$showForm): ?>
  <!-- ===================== TAMPILAN HASIL ===================== -->
  <div class="card mb-2">
    <div class="flex between center-y wrap gap">
      <div>
        <h3 style="font-size:16px">Hasil Perhitungan</h3>
        <p class="muted small mt-1">Perhitungan ini sudah tersimpan di riwayat.</p>
      </div>
      <div class="flex gap wrap">
        <a href="perhitungan.php" class="btn btn-ghost btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Perhitungan Baru
        </a>
        <a href="historis.php" class="btn btn-light btn-sm">Lihat Riwayat</a>
        <?php if ($savedId): ?>
        <a href="cetak_hasil.php?hasil=<?= $savedId ?>" target="_blank" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Cetak Hasil
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/../includes/hasil_view.php'; ?>

<?php else: ?>
  <!-- ===================== FORM INPUT ===================== -->
  <?php if ($errors): ?>
    <div class="flash flash-error mb-2">
      <b>Periksa kembali:</b>
      <ul style="margin:.4rem 0 0 1.1rem">
        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- ---- Filter Data Alternatif (form terpisah, GET) ---- -->
  <div class="card mb-2">
    <div class="section-head">
      <h3>Filter Data Alternatif</h3>
      <span class="sub">Berdasarkan bulan &amp; tahun data diinput</span>
    </div>
    <form method="get" class="form-row" style="align-items:flex-end">
      <div class="form-group">
        <label>Bulan</label>
        <select name="bulan" class="input">
          <option value="">Semua Bulan</option>
          <?php foreach ($namaBulan as $bln => $lbl): ?>
            <option value="<?= $bln ?>"<?= $fBulan === $bln ? ' selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Tahun</label>
        <select name="tahun" class="input">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunTersedia as $th): ?>
            <option value="<?= (int) $th ?>"<?= $fTahun === (int) $th ? ' selected' : '' ?>><?= (int) $th ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <div class="flex gap">
          <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Terapkan
          </button>
          <?php if ($fBulan !== null || $fTahun !== null): ?>
            <a href="perhitungan.php" class="btn btn-ghost">Reset</a>
          <?php endif; ?>
        </div>
      </div>
    </form>
    <?php if ($fBulan !== null || $fTahun !== null): ?>
      <p class="muted small mt-1">
        Menampilkan <b><?= count($dbAlt) ?></b> alternatif
        yang diinput pada
        <?= $fBulan !== null ? e($namaBulan[$fBulan]) : 'semua bulan' ?><?= $fTahun !== null ? ' ' . (int) $fTahun : '' ?>.
        <?php if (count($dbAlt) === 0): ?>
          <br>Tidak ada data alternatif pada periode tersebut &mdash; sesuaikan filter atau pilih <i>Reset</i>.
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <form method="post" id="formMAUT">
    <input type="hidden" name="proses" value="1">

    <!-- ---- Kriteria ---- -->
    <div class="card mb-2">
      <div class="section-head">
        <h3>1. Kriteria Penilaian</h3>
        <span class="sub">Total bobot sebaiknya = 1,00</span>
      </div>
      <div class="table-wrap">
        <table class="tbl" id="tblKriteria">
          <thead><tr>
            <th style="width:120px">Kode</th><th>Nama Kriteria</th>
            <th class="center" style="width:120px">Bobot</th>
            <th class="center" style="width:150px">Jenis</th>
            <th class="center" style="width:70px">Aksi</th>
          </tr></thead>
          <tbody></tbody>
          <tfoot><tr style="background:#fffaf6">
            <td colspan="2" class="text-right"><b>Total Bobot</b></td>
            <td class="center"><b id="totalBobot">0,00</b></td>
            <td colspan="2"></td>
          </tr></tfoot>
        </table>
      </div>
      <button type="button" class="btn btn-light btn-sm mt-2" id="addKriteria">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Kriteria
      </button>
    </div>

    <!-- ---- Alternatif ---- -->
    <div class="card mb-2">
      <div class="section-head">
        <h3>2. Alternatif &amp; Nilai</h3>
        <span class="sub">Skala nilai 1&ndash;10</span>
      </div>
      <div class="table-wrap">
        <table class="tbl" id="tblAlternatif">
          <thead><tr id="altHead">
            <th style="width:110px">Kode</th><th>Nama Alternatif</th>
            <!-- kolom kriteria diisi via JS -->
            <th class="center" style="width:70px">Aksi</th>
          </tr></thead>
          <tbody></tbody>
        </table>
      </div>
      <button type="button" class="btn btn-light btn-sm mt-2" id="addAlternatif">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Alternatif
      </button>
    </div>

    <!-- ---- Pengaturan & Proses ---- -->
    <div class="card">
      <div class="section-head"><h3>3. Pengaturan Perhitungan</h3></div>
      <div class="form-row">
        <div class="form-group">
          <label>Metode Utilitas</label>
          <select name="mode" class="input">
            <option value="linear">Linear</option>
            <option value="eksponensial">Eksponensial</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Penilaian</label>
          <input type="date" name="tanggal" class="input" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Nama Penghitung</label>
          <input type="text" name="nama_penghitung" class="input" placeholder="Nama petugas" value="<?= e($_SESSION['nama'] ?? '') ?>" required>
        </div>
      </div>
      <button class="btn btn-primary mt-1" type="submit">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
        Proses &amp; Simpan ke Riwayat
      </button>
    </div>
  </form>

  <script>
  (function () {
    const prefillKri = <?= json_encode($prefillKriteria, JSON_UNESCAPED_UNICODE) ?>;
    const prefillAlt = <?= json_encode($prefillAlternatif, JSON_UNESCAPED_UNICODE) ?>;

    const state = {
      kriteria: prefillKri.length ? prefillKri.map(k => ({...k})) : [{kode:'C1', nama:'', bobot:0, jenis:'benefit'}],
      alternatif: prefillAlt.length
        ? prefillAlt.map(a => ({kode:a.kode, nama:a.nama, nilai:{...a.nilai}}))
        : [{kode:'A1', nama:'', nilai:{}}, {kode:'A2', nama:'', nilai:{}}],
    };

    const kriBody  = document.querySelector('#tblKriteria tbody');
    const altHead  = document.getElementById('altHead');
    const altBody  = document.querySelector('#tblAlternatif tbody');
    const totalEl  = document.getElementById('totalBobot');

    // Baca nilai DOM saat ini ke state agar tidak hilang ketika render ulang
    function sync() {
      kriBody.querySelectorAll('tr').forEach((tr, i) => {
        if (!state.kriteria[i]) return;
        state.kriteria[i].kode  = tr.querySelector('.k-kode').value;
        state.kriteria[i].nama  = tr.querySelector('.k-nama').value;
        state.kriteria[i].bobot = tr.querySelector('.k-bobot').value;
        state.kriteria[i].jenis = tr.querySelector('.k-jenis').value;
      });
      altBody.querySelectorAll('tr').forEach((tr, i) => {
        if (!state.alternatif[i]) return;
        state.alternatif[i].kode = tr.querySelector('.a-kode').value;
        state.alternatif[i].nama = tr.querySelector('.a-nama').value;
        tr.querySelectorAll('.a-nilai').forEach(inp => {
          state.alternatif[i].nilai[inp.dataset.ki] = inp.value;
        });
      });
    }

    function esc(s){ return (s==null?'':String(s)); }

    function render() {
      // ---- Kriteria ----
      kriBody.innerHTML = '';
      let total = 0;
      state.kriteria.forEach((k, i) => {
        total += parseFloat(String(k.bobot).replace(',', '.')) || 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><input type="text" class="input k-kode" name="kri_kode[${i}]" value="${esc(k.kode)}" placeholder="C${i+1}"></td>
          <td><input type="text" class="input k-nama" name="kri_nama[${i}]" value="${esc(k.nama)}" placeholder="Nama kriteria"></td>
          <td><input type="number" step="0.01" min="0" max="1" class="input k-bobot" name="kri_bobot[${i}]" value="${esc(k.bobot)}" style="text-align:center"></td>
          <td>
            <select class="input k-jenis" name="kri_jenis[${i}]">
              <option value="benefit"${k.jenis==='benefit'?' selected':''}>Benefit</option>
              <option value="cost"${k.jenis==='cost'?' selected':''}>Cost</option>
            </select>
          </td>
          <td class="center"><button type="button" class="btn btn-danger btn-sm del-kri" data-i="${i}" title="Hapus"><i class="fa-solid fa-trash-can"></i></button></td>`;
        kriBody.appendChild(tr);
      });
      totalEl.textContent = total.toFixed(2).replace('.', ',');

      // ---- Header alternatif (kolom per kriteria) ----
      altHead.querySelectorAll('.kri-col').forEach(el => el.remove());
      const aksiTh = altHead.lastElementChild;
      state.kriteria.forEach((k, ki) => {
        const th = document.createElement('th');
        th.className = 'center kri-col';
        th.textContent = k.kode || ('C' + (ki + 1));
        altHead.insertBefore(th, aksiTh);
      });

      // ---- Baris alternatif ----
      altBody.innerHTML = '';
      state.alternatif.forEach((a, i) => {
        let cells = '';
        state.kriteria.forEach((k, ki) => {
          const v = (a.nilai && a.nilai[ki] != null) ? a.nilai[ki] : '';
          cells += `<td class="center"><input type="number" step="0.1" min="0" max="10" class="input a-nilai" name="nilai[${i}][${ki}]" data-ki="${ki}" value="${esc(v)}" style="width:64px;text-align:center"></td>`;
        });
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><input type="text" class="input a-kode" name="alt_kode[${i}]" value="${esc(a.kode)}" placeholder="A${i+1}"></td>
          <td><input type="text" class="input a-nama" name="alt_nama[${i}]" value="${esc(a.nama)}" placeholder="Nama alternatif"></td>
          ${cells}
          <td class="center"><button type="button" class="btn btn-danger btn-sm del-alt" data-i="${i}" title="Hapus"><i class="fa-solid fa-trash-can"></i></button></td>`;
        altBody.appendChild(tr);
      });
    }

    // ---- Aksi ----
    document.getElementById('addKriteria').addEventListener('click', () => {
      sync();
      state.kriteria.push({kode:'C'+(state.kriteria.length+1), nama:'', bobot:0, jenis:'benefit'});
      render();
    });
    document.getElementById('addAlternatif').addEventListener('click', () => {
      sync();
      state.alternatif.push({kode:'A'+(state.alternatif.length+1), nama:'', nilai:{}});
      render();
    });
    kriBody.addEventListener('click', e => {
      const btn = e.target.closest('.del-kri'); if (!btn) return;
      sync();
      if (state.kriteria.length <= 1) return;
      const i = +btn.dataset.i;
      state.kriteria.splice(i, 1);
      // geser indeks nilai pada tiap alternatif
      state.alternatif.forEach(a => {
        const nn = {};
        Object.keys(a.nilai).map(Number).sort((x,y)=>x-y).forEach(k => {
          if (k < i) nn[k] = a.nilai[k];
          else if (k > i) nn[k-1] = a.nilai[k];
        });
        a.nilai = nn;
      });
      render();
    });
    altBody.addEventListener('click', e => {
      const btn = e.target.closest('.del-alt'); if (!btn) return;
      sync();
      if (state.alternatif.length <= 1) return;
      state.alternatif.splice(+btn.dataset.i, 1);
      render();
    });
    // Update total bobot saat mengetik
    kriBody.addEventListener('input', e => {
      if (e.target.classList.contains('k-bobot')) {
        let total = 0;
        kriBody.querySelectorAll('.k-bobot').forEach(b => total += parseFloat(String(b.value).replace(',','.'))||0);
        totalEl.textContent = total.toFixed(2).replace('.', ',');
      }
      // sinkron kode kriteria ke header kolom alternatif
      if (e.target.classList.contains('k-kode')) {
        const i = [...kriBody.querySelectorAll('.k-kode')].indexOf(e.target);
        const th = altHead.querySelectorAll('.kri-col')[i];
        if (th) th.textContent = e.target.value || ('C'+(i+1));
      }
    });

    render();
  })();
  </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
