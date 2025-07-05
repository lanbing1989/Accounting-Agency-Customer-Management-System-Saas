<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php'; // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 筛选参数（修复筛选BUG，避免intval('')===0判断为有效条件）
$filter_tenant = $_GET['tenant_id'] ?? '';
$filter_user = $_GET['user_id'] ?? '';
$filter_action = trim($_GET['action'] ?? '');
$filter_keyword = trim($_GET['keyword'] ?? '');
$filter_date1 = trim($_GET['date1'] ?? '');
$filter_date2 = trim($_GET['date2'] ?? '');

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page-1)*$page_size;

// 构造where条件
$where = '1=1';
$params = [];
if ($filter_tenant !== '') {
    $where .= ' AND tenant_id=:tid';
    $params[':tid'] = intval($filter_tenant);
}
if ($filter_user !== '') {
    $where .= ' AND user_id=:uid';
    $params[':uid'] = intval($filter_user);
}
if ($filter_action !== '') {
    $where .= ' AND action LIKE :act';
    $params[':act'] = '%' . $filter_action . '%';
}
if ($filter_keyword !== '') {
    $where .= ' AND detail LIKE :kwd';
    $params[':kwd'] = '%' . $filter_keyword . '%';
}
if ($filter_date1 !== '') {
    $where .= ' AND created_at >= :d1';
    $params[':d1'] = $filter_date1 . ' 00:00:00';
}
if ($filter_date2 !== '') {
    $where .= ' AND created_at <= :d2';
    $params[':d2'] = $filter_date2 . ' 23:59:59';
}

// 获取总数
$sql_count = "SELECT COUNT(*) FROM tenant_logs WHERE $where";
$stmt = $db->prepare($sql_count);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$total = $stmt->fetchColumn();
$total_pages = max(1, ceil($total/$page_size));

// 获取日志数据
$sql = "SELECT * FROM tenant_logs WHERE $where ORDER BY created_at DESC LIMIT $offset, $page_size";
$stmt = $db->prepare($sql);
foreach($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 批量获取租户名和用户名
$tenant_ids = [];
$user_ids = [];
foreach ($logs as $log) {
    if ($log['tenant_id']) $tenant_ids[$log['tenant_id']] = true;
    if ($log['user_id']) $user_ids[$log['user_id']] = true;
}
$tenant_map = [];
if ($tenant_ids) {
    $in = implode(',', array_map('intval', array_keys($tenant_ids)));
    $stmt_t = $db->query("SELECT id, name FROM tenants WHERE id IN ($in)");
    foreach ($stmt_t->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tenant_map[$row['id']] = $row['name'];
    }
}
$user_map = [];
if ($user_ids) {
    $in = implode(',', array_map('intval', array_keys($user_ids)));
    $stmt_u = $db->query("SELECT id, username FROM users WHERE id IN ($in)");
    foreach ($stmt_u->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $user_map[$row['id']] = $row['username'];
    }
}

// 所有租户选项（下拉用）
$tenant_options = [];
$stmt = $db->query("SELECT id, name FROM tenants");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $tenant_options[] = $row;
}

// 所有用户选项（下拉用, 非常多时建议不做）
$user_options = [];
$stmt = $db->query("SELECT id, username FROM users");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $user_options[] = $row;
}

include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>平台操作日志</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        .log-danger { background: #fff3f3;}
        .log-highlight { background: #f2f9ff;}
        .table-log th, .table-log td { font-size: 15px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3>平台操作日志</h3>
    <form class="row g-2 mb-3">
        <div class="col-auto">
            <select class="form-select" name="tenant_id">
                <option value="">全部租户</option>
                <?php foreach($tenant_options as $t): ?>
                    <option value="<?=$t['id']?>" <?=$filter_tenant == $t['id'] ? 'selected' : ''?>><?=htmlspecialchars($t['name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <select class="form-select" name="user_id">
                <option value="">全部用户</option>
                <?php foreach($user_options as $u): ?>
                    <option value="<?=$u['id']?>" <?=$filter_user == $u['id'] ? 'selected' : ''?>><?=htmlspecialchars($u['username'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="action" placeholder="操作类型" value="<?=htmlspecialchars($filter_action)?>">
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="keyword" placeholder="详情关键词" value="<?=htmlspecialchars($filter_keyword)?>">
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" name="date1" value="<?=htmlspecialchars($filter_date1)?>">
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" name="date2" value="<?=htmlspecialchars($filter_date2)?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary">筛选</button>
            <a href="platform_log_all.php" class="btn btn-outline-secondary">重置</a>
        </div>
    </form>
    <div class="mb-2 text-secondary small">共 <b><?=$total?></b> 条记录</div>
    <div class="table-responsive">
    <table class="table table-bordered table-log">
        <tr>
            <th>时间</th>
            <th>租户</th>
            <th>用户</th>
            <th>操作</th>
            <th>详情</th>
        </tr>
        <?php foreach($logs as $log):?>
        <tr class="<?php
            if(stripos($log['action'],'删除')!==false||stripos($log['action'],'重置')!==false) echo 'log-danger';
            elseif(stripos($log['action'],'新增')!==false) echo 'log-highlight';
        ?>">
            <td><?=htmlspecialchars($log['created_at'])?></td>
            <td><?=isset($tenant_map[$log['tenant_id']]) ? htmlspecialchars($tenant_map[$log['tenant_id']]) : $log['tenant_id']?></td>
            <td><?=isset($user_map[$log['user_id']]) ? htmlspecialchars($user_map[$log['user_id']]) : $log['user_id']?></td>
            <td><?=htmlspecialchars($log['action'])?></td>
            <td style="max-width:420px;word-break:break-all"><?=htmlspecialchars($log['detail'])?></td>
        </tr>
        <?php endforeach;?>
        <?php if(!$logs): ?>
        <tr><td colspan="5" class="text-center text-muted">暂无日志</td></tr>
        <?php endif;?>
    </table>
    </div>
    <nav aria-label="Page navigation">
      <ul class="pagination">
        <li class="page-item<?=$page<=1?' disabled':''?>">
          <a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>1]))?>">首页</a>
        </li>
        <li class="page-item<?=$page<=1?' disabled':''?>">
          <a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>max(1,$page-1)]))?>">&laquo;</a>
        </li>
        <li class="page-item disabled"><span class="page-link"><?=$page?>/<?=$total_pages?></span></li>
        <li class="page-item<?=$page>=$total_pages?' disabled':''?>">
          <a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>min($total_pages,$page+1)]))?>">&raquo;</a>
        </li>
        <li class="page-item<?=$page>=$total_pages?' disabled':''?>">
          <a class="page-link" href="?<?=http_build_query(array_merge($_GET,['page'=>$total_pages]))?>">末页</a>
        </li>
      </ul>
    </nav>
</div>
</body>
</html>