<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/maut.php';
wajibLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $kode = trim($_POST['kode']); $nama = trim($_POST['nama']);
        $bobot = (float)str_replace(',', '.', $_POST['bobot']); $jenis = $_POST['jenis'];
        try {
            $pdo->prepare("INSERT INTO kriteria (kode,nama,bobot,jenis) VALUES (?,?,?,?)")->execute([$kode,$nama,$bobot,$jenis]);
            // inisialisasi nilai 0
            $kid = $pdo->lastInsertId();
            foreach ($pdo->query("SELECT id FROM alternatif")->fetchAll() as $a) {
                $pdo->prepare("INSERT INTO nilai (alternatif_id,kriteria_id,nilai) VALUES (?,?,0)")->execute([$a['id'],$kid]);
            }
            setFlash('success','Kriteria berhasil ditambahkan.');
        } catch (PDOException $e) { setFlash('error','Kode kriteria sudah ada.'); }
    } elseif ($aksi === 'update_bobot') {
        foreach ($_POST['bobot'] as $id => $b) {
            $pdo->prepare("UPDATE kriteria SET bobot=? WHERE id=?")->execute([(float)str_replace(',','.',$b), (int)$id]);
        }
        foreach ($_POST['jenis'] as $id => $j) {
            $pdo->prepare("UPDATE kriteria SET jenis=? WHERE id=?")->execute([$j, (int)$id]);
        }
        setFlash('success','Bobot & jenis kriteria berhasil diperbarui.');
    }
    header('Location: kriteria.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM kriteria WHERE id=?")->execute([(int)$_GET['hapus']]);
    setFlash('success','Kriteria berhasil dihapus.');
    header('Location: kriteria.php'); exit;
}

$kriteria = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();
$totalBobot = array_sum(array_column($kriteria, 'bobot'));

$pageTitle = 'Data Kriteria';
$pageDesc  = 'Kelola kriteria penilaian beserta bobotnya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-2 mb-2">
  <div class="card">
    <div class="section-head"><h3>Tambah Kriteria</h3></div>
    <form method="post">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group"><label>Kode</label><input type="text" name="kode" class="input" placeholder="C6" required></div>
        <div class="form-group" style="grid-column:span 2"><label>Nama Kriteria</label><input type="text" name="nama" class="input" placeholder="Nama kriteria" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Bobot (0&ndash;1)</label><input type="number" name="bobot" class="input" step="0.01" min="0" max="1" placeholder="0.10" required></div>
        <div class="form-group"><label>Jenis</label>
          <select name="jenis" class="input"><option value="benefit">Benefit (max)</option><option value="cost">Cost (min)</option></select>
        </div>
      </div>
      <button class="btn btn-primary">Tambah Kriteria</button>
    </form>
  </div>

  <div class="card" style="background:var(--grad-soft);display:flex;flex-direction:column;justify-content:center">
    <h3 style="color:#7a4220"><i class="fa-solid fa-scale-balanced"></i> Total Bobot</h3>
    <p style="font-size:46px;font-weight:800;font-family:'Sora';color:#7a4220;margin-top:6px"><?= fmt($totalBobot,2) ?></p>
    <p style="color:#9c6a4a;font-size:14px;line-height:1.6">Total bobot seluruh kriteria sebaiknya berjumlah <b>1,00</b> agar hasil utilitas akurat.</p>
  </div>
</div>

<div class="card">
  <div class="section-head"><h3>Daftar Kriteria</h3><span class="sub">Ubah bobot/jenis lalu simpan</span></div>
  <form method="post">
    <input type="hidden" name="aksi" value="update_bobot">
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr><th>Kode</th><th>Nama Kriteria</th><th class="center">Jenis</th><th class="center">Bobot</th><th class="center">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($kriteria as $k): ?>
          <tr>
            <td><span class="badge badge-code"><?= e($k['kode']) ?></span></td>
            <td><b><?= e($k['nama']) ?></b></td>
            <td class="center">
              <select name="jenis[<?= $k['id'] ?>]" class="input" style="width:130px;padding:7px">
                <option value="benefit" <?= $k['jenis']==='benefit'?'selected':'' ?>>Benefit</option>
                <option value="cost" <?= $k['jenis']==='cost'?'selected':'' ?>>Cost</option>
              </select>
            </td>
            <td class="center">
              <input type="number" name="bobot[<?= $k['id'] ?>]" class="input bobot-check" style="width:90px;padding:7px;text-align:center" step="0.01" min="0" max="1" value="<?= number_format($k['bobot'],2,'.','') ?>">
            </td>
            <td class="center">
              <button type="button" class="btn btn-danger btn-sm" onclick="konfirmHapus('kriteria.php?hapus=<?= $k['id'] ?>','<?= e($k['nama']) ?>')">Hapus</button>
            </td>
          </tr>
        <?php endforeach; ?>
          <tr style="background:#fffaf6"><td colspan="3" class="text-right"><b>Total Bobot</b></td><td class="center"><b id="total-bobot"><?= fmt($totalBobot,2) ?></b></td><td></td></tr>
        </tbody>
      </table>
    </div>
    <button class="btn btn-primary mt-2">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
      Simpan Perubahan
    </button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
