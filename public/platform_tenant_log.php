<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台管理员可用！');
$tenant_id = intval($_GET['id']);

// 支持按用户ID/操作类型筛选
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';

$where = 'tenant_id=:tid';
$params = [':tid' => $tenant_id];
if ($filter_user) {
    $where .= ' AND user_id=:uid';
    $params[':uid'] = $filter_user;
}
if ($filter_action) {
    $where .= ' AND action LIKE :act';
    $params[':act'] = '%' . $filter_action . '%';
}

$sql = "SELECT * FROM tenant_logs WHERE $where ORDER BY created_at DESC LIMIT 100";
$stmt = $db->prepare($sql);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 批量获取用户ID对应用户名映射表（便于显示用户名）
$user_ids = [];
foreach ($logs as $log) {
    if ($log['user_id']) $user_ids[] = $log['user_id'];
}
$user_map = [];
if ($user_ids) {
    $in = implode(',', array_map('intval', array_unique($user_ids)));
    $stmt_u = $db->query("SELECT id, username FROM users WHERE id IN ($in)");
    foreach ($stmt_u->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $user_map[$row['id']] = $row['username'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>租户操作日志</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('platform_navbar.php');?>
<div class="container mt-4">
    <h3>租户日志</h3>
    <form class="row g-2 mb-3">
        <div class="col-auto">
            <input type="number" class="form-control" name="user_id" placeholder="用户ID" value="<?=htmlspecialchars($filter_user)?>">
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="action" placeholder="操作类型" value="<?=htmlspecialchars($filter_action)?>">
        </div>
        <input type="hidden" name="id" value="<?=$tenant_id?>">
        <div class="col-auto">
            <button class="btn btn-outline-primary">筛选</button>
            <a href="platform_tenant_log.php?id=<?=$tenant_id?>" class="btn btn-outline-secondary">重置</a>
        </div>
    </form>
    <table class="table table-bordered">
        <tr>
            <th>时间</th>
            <th>用户ID</th>
            <th>用户名</th>
            <th>操作</th>
            <th>详情</th>
        </tr>
        <?php foreach($logs as $log):?>
        <tr<?=stripos($log['action'],'删除')!==false||stripos($log['action'],'重置')!==false?' style="background:#fff2f2"':''?>>
            <td><?=$log['created_at']?></td>
            <td><?=$log['user_id']?></td>
            <td><?=isset($user_map[$log['user_id']]) ? htmlspecialchars($user_map[$log['user_id']]) : '--'?></td>
            <td><?=htmlspecialchars($log['action'])?></td>
            <td style="max-width:350px;word-break:break-all"><?=htmlspecialchars($log['detail'])?></td>
        </tr>
        <?php endforeach;?>
    </table>
    <a href="platform_tenant_detail.php?id=<?=$tenant_id?>" class="btn btn-secondary">返回详情</a>
</div>
</body>
</html>