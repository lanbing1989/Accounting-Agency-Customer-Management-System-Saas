<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 当前用户基础信息
define('IS_SUPERADMIN', isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin');
define('CURRENT_TENANT_ID', $_SESSION['tenant_id'] ?? 0);
define('CURRENT_TENANT_NAME', $_SESSION['tenant_name'] ?? '');
define('CURRENT_USER_ID', $_SESSION['user_id']);
define('CURRENT_USER_ROLE', $_SESSION['role']);
?>