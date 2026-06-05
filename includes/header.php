<?php
// Header layout — dipakai semua halaman dalam folder pages/
require_once __DIR__ . '/../includes/functions.php';
wajibLogin();
// Cegah browser meng-cache halaman dinamis (data selalu terbaru)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$nama = $_SESSION['nama'] ?? 'Admin';
$inisial = strtoupper(substr($nama, 0, 1));
$pageTitle = $pageTitle ?? 'Dashboard';
$pageDesc  = $pageDesc ?? '';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> &mdash; <?= NAMA_APP ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="../assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="app">
  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar">
    <button class="sidebar-close" onclick="toggleSidebar()" aria-label="Tutup menu" title="Tutup menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="brand">
      <div class="logo"><img src="../assets/img/logo.png" alt="Logo PKK"></div>
      <div class="title">SPK MAUT
        <span>Kader PKK Terbaik</span>
      </div>
    </div>

    <div class="profile-card">
      <div class="ava"><?= e($inisial) ?></div>
      <div class="who">
        <b><?= e($nama) ?></b>
        <small>Administrator</small>
      </div>
    </div>

    <nav class="nav">
      <a href="dashboard.php" class="<?= aktif('dashboard.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Beranda
      </a>
      <a href="perhitungan.php" class="<?= aktif('perhitungan.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="10" y2="10"/><line x1="14" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="10" y2="14"/><line x1="14" y1="14" x2="16" y2="14"/></svg>
        Perhitungan MAUT
      </a>

      <div class="nav-sep">Manajemen Data</div>
      <a href="alternatif.php" class="<?= aktif('alternatif.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Data Alternatif
      </a>
      <a href="kriteria.php" class="<?= aktif('kriteria.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
        Data Kriteria
      </a>

      <div class="nav-sep">Lainnya</div>
      <a href="historis.php" class="<?= aktif('historis.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><line x1="12" y1="7" x2="12" y2="12"/><line x1="12" y1="12" x2="15" y2="14"/></svg>
        Riwayat
      </a>
      <a href="tentang.php" class="<?= aktif('tentang.php') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Tentang
      </a>
    </nav>

    <a href="../actions/logout.php" class="logout-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="19" height="19"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Keluar
    </a>
  </aside>

  <!-- Backdrop untuk menutup sidebar saat diklik di luar -->
  <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

  <!-- ============ MAIN ============ -->
  <main class="main">
    <button class="menu-toggle" onclick="toggleSidebar()" style="margin-bottom:16px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="22" height="22"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div class="topbar">
      <div class="greet">
        <h2><?= e($pageTitle) ?></h2>
        <?php if ($pageDesc): ?><p><?= e($pageDesc) ?></p><?php endif; ?>
      </div>
      <div class="date-pill">
        <span class="dot"></span>
        <?= date('d M Y') ?>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
