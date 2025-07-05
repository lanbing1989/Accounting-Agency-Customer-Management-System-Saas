<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');
$tenant_id = intval($_GET['id']);
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename=tenant'.$tenant_id.'_backup_'.date('Ymd_His').'.json');
$res = [];
// 导出租户、用户、合同、日志等
$tables = ['tenants', 'users', 'contracts', 'tenant_logs'];
foreach($tables as $tb) {
    $sql = "SELECT * FROM $tb WHERE tenant_id=:tid";
    if ($tb=='tenants') $sql = "SELECT * FROM tenants WHERE id=:tid";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':tid', $tenant_id, PDO::PARAM_INT);
    $stmt->execute();
    $res[$tb] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// 操作日志
@$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
    ->execute([$tenant_id, $_SESSION['user_id'], "导出数据", "导出租户ID:{$tenant_id} 的所有业务数据"]);
echo json_encode($res, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
exit;
?>