<?php
// =====================================================================
// Konfigurasi Koneksi Database (PDO MySQL) — TEMPLATE
// -------------------------------------------------------------------
// 1. Salin file ini menjadi: config/database.php
// 2. Sesuaikan kredensial (DB_USER / DB_PASS) sesuai server Anda.
// File config/database.php sengaja TIDAK di-commit (lihat .gitignore)
// agar kredensial tidak ikut ter-upload ke repository publik.
// =====================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'spk_maut');
define('DB_USER', 'root');
define('DB_PASS', ''); // <-- isi password database Anda

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

// Identitas instansi untuk kop surat
define('NAMA_INSTANSI', 'PEMERINTAH KOTA JAKARTA TIMUR');
define('SUB_INSTANSI',  'KELURAHAN PULOGEBANG &mdash; KECAMATAN CAKUNG');
define('ALAMAT_INSTANSI', 'Posyandu Lansia Kelurahan Pulogebang, Jakarta Timur, DKI Jakarta');
define('NAMA_APP', 'SPK Kader PKK Terbaik');
