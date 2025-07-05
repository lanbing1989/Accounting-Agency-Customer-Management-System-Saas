<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// 搜索过滤
$where = ' AND c.tenant_id = :tenant_id';
$params = [':tenant_id' => $_SESSION['tenant_id']];
if (!empty($_GET['client_name'])) {
    $where .= ' AND c.client_name LIKE :client_name';
    $params[':client_name'] = '%' . $_GET['client_name'] . '%';
}

// 统计总数
$count_query = "SELECT COUNT(*) FROM contracts c WHERE 1 $where";
$count_stmt = $db->prepare($count_query);
foreach ($params as $k => $v) $count_stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
$count_stmt->execute();
$total = $count_stmt->fetch(PDO::FETCH_NUM)[0];
$total_pages = max(1, ceil($total / $page_size));

// 客户列表
$query = "SELECT c.*, 
    (SELECT MAX(service_end) FROM service_periods sp WHERE sp.contract_id = c.id AND sp.tenant_id = c.tenant_id) AS latest_end,
    (
        SELECT sg.package_type
        FROM service_periods sp
        JOIN service_segments sg ON sg.service_period_id = sp.id AND sg.tenant_id = sp.tenant_id
        WHERE sp.contract_id = c.id AND sp.tenant_id = c.tenant_id
        ORDER BY sg.end_date DESC LIMIT 1
    ) AS latest_package_type
    FROM contracts c
    WHERE 1 $where
    ORDER BY latest_end DESC, c.id DESC
    LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>客户列表</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .client-name-cell {
        max-width: 180px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .operation-cell {
        white-space: nowrap;
        min-width: 220px;
    }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container">
    <form class="row g-2 mb-3" method="get">
        <div class="col-auto">
            <input type="text" class="form-control" name="client_name" placeholder="客户名称" value="<?=htmlspecialchars($_GET['client_name']??'')?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">搜索</button>
        </div>
        <div class="col-auto">
            <a href="contract_add.php" class="btn btn-success">新增客户</a>
        </div>
        <div class="col-auto">
            <a href="contract_import.php" class="btn btn-warning">导入客户</a>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th style="max-width: 180px;">客户名称</th>
            <th>联系人</th>
            <th>联系电话</th>
            <th>联系邮箱</th>
            <th>最近服务期截止</th>
            <th>最新套餐类型</th>
            <th>备注</th>
            <th class="operation-cell">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><?=$row['id']?></td>
            <td class="client-name-cell" title="<?=htmlspecialchars($row['client_name'])?>">
                <?=htmlspecialchars($row['client_name'])?>
            </td>
            <td><?=htmlspecialchars($row['contact_person'])?></td>
            <td><?=htmlspecialchars($row['contact_phone'])?></td>
            <td><?=htmlspecialchars($row['contact_email'])?></td>
            <td><?=$row['latest_end'] ? $row['latest_end'] : '-'?></td>
            <td><?=$row['latest_package_type'] ? htmlspecialchars($row['latest_package_type']) : '-'?></td>
            <td><?=htmlspecialchars($row['remark'])?></td>
            <td class="operation-cell">
                <a href="contract_edit.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline-primary">编辑</a>
                <a href="contract_detail.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline-secondary">详情/服务期</a>
                <a href="contract_delete.php?id=<?=$row['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要彻底删除该客户及其所有服务期和记录吗？')">删除</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <!-- 分页导航 -->
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>1]))?>">首页</a>
            </li>
            <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>max(1,$page-1)]))?>">&laquo;</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link">第 <?=$page?> / <?=$total_pages?> 页</span>
            </li>
            <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>min($total_pages,$page+1)]))?>">&raquo;</a>
            </li>
            <li class="page-item<?= $page >= $total_pages ? ' disabled' : '' ?>">
                <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page'=>$total_pages]))?>">尾页</a>
            </li>
        </ul>
    </nav>
    <div class="mb-3 text-center text-muted">
        共 <?=$total?> 条，每页 <?=$page_size?> 条
    </div>
</div>
</body>
</html>