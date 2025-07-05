<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = intval($_POST['tenant_id']);
    $package_id = intval($_POST['package_id']);
    $expire = $_POST['package_expire'] ?: null;

    // 获取原套餐和到期日
    $stmt0 = $db->prepare("SELECT package_id, package_expire FROM tenants WHERE id=?");
    $stmt0->execute([$tenant_id]);
    $old = $stmt0->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("UPDATE tenants SET package_id=:pid, package_expire=:expire WHERE id=:tid");
    $stmt->bindValue(':pid', $package_id);
    $stmt->bindValue(':expire', $expire);
    $stmt->bindValue(':tid', $tenant_id);
    $stmt->execute();

    // 日志记录
    $detail = "原套餐ID：{$old['package_id']}→{$package_id}；原到期：{$old['package_expire']}→{$expire}";
    @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$tenant_id, $_SESSION['user_id'], '分配/变更套餐', $detail]);
}
header("Location: platform_tenant_detail.php?id=$tenant_id");
exit;
?>