<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$now = new DateTime();
$report_year = $now->format('Y') - 1;
$deadline = new DateTime(($report_year + 1) . '-06-30');
$days_left = (int)$now->diff($deadline)->format('%r%a');

$page = max(1, intval($_GET['page'] ?? 1));
$page_size = 50;
$offset = ($page - 1) * $page_size;

// 标记已申报/反标记操作
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contract_id'])) {
    $cid = intval($_POST['contract_id']);
    $action = $_POST['action'] ?? 'mark'; // 新增：标识是“mark”还是“unmark”
    // 只允许操作本租户的数据
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM annual_reports WHERE contract_id = :cid AND year = :year AND tenant_id = :tenant_id');
    $stmt->bindValue(':cid', $cid, PDO::PARAM_INT);
    $stmt->bindValue(':year', $report_year, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($action === 'mark') {
        if (!$row['cnt']) {
            $stmt = $db->prepare('INSERT INTO annual_reports (contract_id, year, reported_at, tenant_id) VALUES (:cid, :year, :at, :tenant_id)');
            $stmt->bindValue(':cid', $cid, PDO::PARAM_INT);
            $stmt->bindValue(':year', $report_year, PDO::PARAM_INT);
            $stmt->bindValue(':at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    // 新增反标记功能
    elseif ($action === 'unmark') {
        $stmt = $db->prepare('DELETE FROM annual_reports WHERE contract_id = :cid AND year = :year AND tenant_id = :tenant_id');
        $stmt->bindValue(':cid', $cid, PDO::PARAM_INT);
        $stmt->bindValue(':year', $report_year, PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt->execute();
    }
    header("Location: annual_report.php?page=$page");
    exit;
}

// 统计总数（仅本租户）
$count_query = "
    SELECT COUNT(DISTINCT c.id) AS total
    FROM contracts c
    JOIN service_periods sp ON sp.contract_id = c.id
    WHERE c.tenant_id = :tenant_id1 AND sp.tenant_id = :tenant_id2 AND sp.service_end >= CURDATE()
";
$stmt = $db->prepare($count_query);
$stmt->bindValue(':tenant_id1', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, ceil($total / $page_size));

// 查询分页客户（仅本租户）
$clients = [];
$q = "
    SELECT c.*, MAX(sp.service_end) as service_end
    FROM contracts c
    JOIN service_periods sp ON sp.contract_id = c.id
    WHERE c.tenant_id = :tenant_id1 AND sp.tenant_id = :tenant_id2 AND sp.service_end >= CURDATE()
    GROUP BY c.id
    ORDER BY service_end DESC, c.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($q);
$stmt->bindValue(':tenant_id1', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $page_size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stmt2 = $db->prepare('SELECT * FROM annual_reports WHERE contract_id = :cid AND year = :year AND tenant_id = :tenant_id');
    $stmt2->bindValue(':cid', $row['id'], PDO::PARAM_INT);
    $stmt2->bindValue(':year', $report_year, PDO::PARAM_INT);
    $stmt2->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt2->execute();
    $ar = $stmt2->fetch(PDO::FETCH_ASSOC);
    $row['annual_report'] = $ar;
    $clients[] = $row;
}

$month = intval($now->format('m'));
$is_last_month = ($month == 6);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>工商年报登记</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .deadline-alert {font-weight: bold;}
    .deadline-critical {color: #fff; background: #d9534f; padding: 0.3em 0.7em; border-radius: 6px;}
    .fixed-height-cell { min-height:48px; }
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">工商年报登记</h2>
    <div class="mb-3">
        <span class="deadline-alert <?=$is_last_month?'deadline-critical':'text-danger'?>">
            申报年度：<?=$report_year?> 年度，截止日期：<?=$deadline->format('Y年m月d日')?>
            ，剩余
            <?php if ($days_left >= 0): ?>
                <b><?=$days_left?></b> 天
                <?php if ($is_last_month): ?>
                     <span class="ms-2">（重点提醒：本月为最后申报月！）</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-danger">已过截止日！</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户名称</th>
            <th>联系人</th>
            <th>联系电话</th>
            <th>服务期截止</th>
            <th><?=$report_year?>年度年报</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $row): ?>
        <tr>
            <td><?=htmlspecialchars($row['id'])?></td>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=htmlspecialchars($row['contact_person'])?></td>
            <td><?=htmlspecialchars($row['contact_phone'])?></td>
            <td><?=htmlspecialchars($row['service_end'])?></td>
            <td class="fixed-height-cell">
                <?php if ($row['annual_report']): ?>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-success">已申报（<?=htmlspecialchars($row['annual_report']['reported_at'])?>）</span>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="contract_id" value="<?=htmlspecialchars($row['id'])?>">
                            <input type="hidden" name="action" value="unmark">
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要反标记为未申报吗？')">反标记未申报</button>
                        </form>
                    </div>
                <?php elseif ($days_left < 0): ?>
                    <span class="text-danger">已过截止日未申报</span>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span style="color:transparent;">已申报（0000-00-00 00:00:00）</span>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="contract_id" value="<?=htmlspecialchars($row['id'])?>">
                            <input type="hidden" name="action" value="mark">
                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('确定要标记为已申报吗？')">标记已申报</button>
                        </form>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
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
        共 <?=htmlspecialchars($total)?> 条，每页 <?=htmlspecialchars($page_size)?> 条
    </div>
</div>
</body>
</html>