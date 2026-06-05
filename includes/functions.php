<?php
// =====================================================================
// Fungsi Bantu Umum + Auth Guard
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/** Cek apakah user sudah login, jika belum redirect ke login */
function wajibLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/** Escape output HTML */
function e($str): string
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/** Halaman aktif untuk highlight menu */
function aktif(string $file): string
{
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $file ? 'active' : '';
}

/** Flash message helper */
function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
