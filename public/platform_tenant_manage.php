<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');

if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 启用/禁用
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    // 获取原状态
    $stmt = $db->prepare("SELECT status FROM tenants WHERE id=?");
    $stmt->execute([$tid]);
    $old_status = $stmt->fetchColumn();
    $new_status = 1 - $old_status;
    // 执行切换
    $stmt = $db->prepare("UPDATE tenants SET status=? WHERE id=?");
    $stmt->execute([$new_status, $tid]);
    // 日志记录
    @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$tid, $_SESSION['user_id'], '切换启用/禁用', "状态由" . ($old_status ? "启用" : "禁用") . "变为" . ($new_status ? "启用" : "禁用")]);
    header("Location: platform_tenant_manage.php");
    exit;
}

// 软删除
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $tid = intval($_GET['delete']);
    // 软删除前获取租户名
    $stmt_name = $db->prepare("SELECT name FROM tenants WHERE id=?");
    $stmt_name->execute([$tid]);
    $tname = $stmt_name->fetchColumn();
    $stmt = $db->prepare("UPDATE tenants SET is_deleted=1 WHERE id=:id");
    $stmt->bindValue(':id', $tid, PDO::PARAM_INT);
    $stmt->execute();
    // 日志记录
    @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$tid, $_SESSION['user_id'], '删除租户', "租户ID:{$tid}，名称:{$tname} 被软删除"]);
    header("Location: platform_tenant_manage.php");
    exit;
}

// 套餐列表
$packages = $db->query("SELECT * FROM tenant_packages")->fetchAll(PDO::FETCH_ASSOC);

// 租户列表及用量统计
$stmt = $db->query("
SELECT t.*, 
    (SELECT COUNT(*) FROM users WHERE tenant_id=t.id) as user_count,
    (SELECT COUNT(*) FROM contracts WHERE tenant_id=t.id) as client_count,
    (SELECT COUNT(*) FROM contracts_agreement WHERE tenant_id=t.id) as agreement_count,
    tp.name as package_name, tp.max_users, tp.max_clients, tp.max_agreements
FROM tenants t
LEFT JOIN tenant_packages tp ON t.package_id=tp.id
WHERE t.is_deleted=0
ORDER BY t.id DESC
");
$tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$add_success_msg = $_SESSION['tenant_add_success_msg'] ?? '';
unset($_SESSION['tenant_add_success_msg']);
include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=htmlspecialchars($platform_name)?>-租户管理（平台）</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .over-limit { color: #c00; font-weight: bold;}
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">租户管理（平台超级管理员）</h3>
        <div>
            <a href="platform_package_manage.php" class="btn btn-outline-primary me-2">套餐维护</a>
            <a href="platform_tenant_add.php" class="btn btn-success">新增租户</a>
        </div>
    </div>
    <?php if($add_success_msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?=$add_success_msg?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>企业名称</th>
            <th>套餐</th>
            <th>套餐到期</th>
            <th>用户数/上限</th>
            <th>客户数/上限</th>
            <th>电子合同签署数/上限</th>
            <th>联系人</th>
            <th>电话</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php foreach($tenants as $t):?>
        <?php
            $user_warn = ($t['max_users'] && $t['user_count'] >= $t['max_users']);
            $client_warn = ($t['max_clients'] && $t['client_count'] >= $t['max_clients']);
            $agreement_warn = ($t['max_agreements'] && $t['agreement_count'] >= $t['max_agreements']);
        ?>
        <tr>
            <td><?=$t['id']?></td>
            <td>
                <a href="platform_tenant_detail.php?id=<?=$t['id']?>">
                    <?=htmlspecialchars($t['name'])?>
                </a>
            </td>
            <td><?=htmlspecialchars($t['package_name'] ?? '-')?></td>
            <td><?=htmlspecialchars($t['package_expire'])?></td>
            <td class="<?=$user_warn ? 'over-limit' : ''?>">
                <?=$t['user_count']?> / <?=($t['max_users']?:'-')?>
                <?php if($user_warn):?> <span title="已达套餐上限">⚠</span><?php endif;?>
            </td>
            <td class="<?=$client_warn ? 'over-limit' : ''?>">
                <?=$t['client_count']?> / <?=($t['max_clients']?:'-')?>
                <?php if($client_warn):?> <span title="已达客户上限">⚠</span><?php endif;?>
            </td>
            <td class="<?=$agreement_warn ? 'over-limit' : ''?>">
                <?=$t['agreement_count']?> / <?=($t['max_agreements']?:'-')?>
                <?php if($agreement_warn):?> <span title="已达签署上限">⚠</span><?php endif;?>
            </td>
            <td><?=htmlspecialchars($t['contact_person'])?></td>
            <td><?=htmlspecialchars($t['contact_phone'])?></td>
            <td>
                <?php if($t['status']): ?>
                    <span class="badge bg-success">正常</span>
                <?php else: ?>
                    <span class="badge bg-danger">禁用</span>
                <?php endif;?>
            </td>
            <td>
                <a href="?toggle=<?=$t['id']?>" class="btn btn-sm btn-warning" onclick="return confirm('确定切换启用/禁用状态？')">
                    <?=$t['status'] ? '禁用' : '启用'?>
                </a>
                <a href="platform_tenant_detail.php?id=<?=$t['id']?>" class="btn btn-sm btn-info">详情</a>
                <a href="?delete=<?=$t['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除此租户？')">删除</a>
            </td>
        </tr>
        <?php endforeach;?>
    </table>
</div>
</body>
</html>