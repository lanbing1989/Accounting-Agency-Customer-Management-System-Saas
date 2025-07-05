<?php
// session_start(); // 如未统一入口请加上

// 1. 平台超级管理员账号不受套餐/到期/用量限制
$role = $_SESSION['role'] ?? '';
if ($role === 'platform_admin') {
    return; // 超管直接通过
}

// 2. 其他租户账号做套餐/到期/用量校验
$tenant_id = $_SESSION['tenant_id'] ?? 0;
if ($tenant_id) {
    require_once __DIR__.'/db.php';
    $stmt = $db->prepare("SELECT package_id, package_expire FROM tenants WHERE id=?");
    $stmt->execute([$tenant_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if (strtotime($row['package_expire']) < time()) {
            header("Location: /pay.php?expired=1");
            exit;
        }
        $_SESSION['package_id'] = $row['package_id'];
        $_SESSION['package_expire'] = $row['package_expire'];
        // ...其余套餐用量校验逻辑...
    }
}
?>