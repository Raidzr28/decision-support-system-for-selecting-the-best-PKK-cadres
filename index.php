<?php
require_once __DIR__ . '/includes/functions.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
