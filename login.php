<?php
require_once __DIR__ . '/includes/functions.php';

// Sudah login? langsung ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        header('Location: pages/dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk &mdash; <?= NAMA_APP ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <!-- Sisi artistik -->
  <div class="auth-art">
    <div class="badge-app">Sistem Pendukung Keputusan</div>
    <h1>Penilaian<br>Kader PKK Terbaik</h1>
    <p>Menentukan kader PKK terbaik pada kegiatan Posyandu Lansia secara objektif &amp; terukur dengan metode <b>Multi Attribute Utility Theory (MAUT)</b>.</p>
    <div class="feat">
      <div><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" width="15" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Perhitungan otomatis &amp; transparan</div>
      <div><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" width="15" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> 5 kriteria penilaian terbobot</div>
      <div><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" width="15" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span> Cetak laporan ber-kop surat</div>
    </div>
  </div>

  <!-- Sisi form -->
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="brand" style="margin-bottom:24px">
        <div class="logo"><img src="assets/img/logo.png" alt="Logo PKK"></div>
        <div class="title">SPK MAUT<span>Kader PKK Terbaik</span></div>
      </div>
      <h2>Selamat datang <i class="fa-regular fa-hand" style="color:#e0a458"></i></h2>
      <p>Silakan masuk untuk melanjutkan ke dashboard.</p>

      <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" class="input" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Password</label>
          <div class="pw-field">
            <input type="password" name="password" class="input" placeholder="Masukkan password" required>
            <button type="button" class="toggle" onclick="togglePw(this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="20"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
          Masuk
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </form>
    </div>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
