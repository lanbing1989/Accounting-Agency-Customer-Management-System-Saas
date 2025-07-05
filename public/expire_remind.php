<?php
require_once 'auth.php';
require_once 'db.php';
require_once __DIR__.'/check_package.php';

// 1. 读取显示已手工结束参数
$show_closed = isset($_GET['show_closed']) && $_GET['show_closed'] == '1';

// 2. 手工结束服务期（仅本租户）
if (isset($_GET['close_id']) && is_numeric($_GET['close_id'])) {
    $close_id = intval($_GET['close_id']);
    $stmt = $db->prepare("UPDATE service_periods SET manually_closed=1 WHERE id=:id AND tenant_id=:tenant_id");
    $stmt->bindValue(':id', $close_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    // 保持当前筛选条件
    $redirect = 'expire_remind.php';
    $qs = [];
    if (isset($_GET['days'])) $qs[] = 'days=' . urlencode($_GET['days']);
    if ($show_closed) $qs[] = 'show_closed=1';
    if ($qs) $redirect .= '?' . implode('&', $qs);
    header("Location: $redirect");
    exit;
}

// 3. 恢复提醒服务期（仅本租户）
if (isset($_GET['reopen_id']) && is_numeric($_GET['reopen_id'])) {
    $reopen_id = intval($_GET['reopen_id']);
    $stmt = $db->prepare("UPDATE service_periods SET manually_closed=0 WHERE id=:id AND tenant_id=:tenant_id");
    $stmt->bindValue(':id', $reopen_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    $redirect = 'expire_remind.php';
    $qs = [];
    if (isset($_GET['days'])) $qs[] = 'days=' . urlencode($_GET['days']);
    if ($show_closed) $qs[] = 'show_closed=1';
    if ($qs) $redirect .= '?' . implode('&', $qs);
    header("Location: $redirect");
    exit;
}

// 4. 筛选参数
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$today = date('Y-m-d');
$deadline = date('Y-m-d', strtotime("+$days days"));

// 5. 查询条件（多租户限制）
$where = "sp.tenant_id = :tenant_id AND sp.service_end <= :deadline ";
if (!$show_closed) {
    $where .= "AND (sp.manually_closed IS NULL OR sp.manually_closed=0) ";
}
$where .= "AND NOT EXISTS (
    SELECT 1 FROM service_periods sp2
    WHERE sp2.contract_id = sp.contract_id AND sp2.tenant_id = sp.tenant_id AND sp2.service_start > sp.service_end
)";
$sql = "
SELECT c.client_name, c.id as contract_id, sp.*,
    (SELECT IFNULL(SUM(segment_fee),0) FROM service_segments WHERE service_period_id=sp.id AND tenant_id=sp.tenant_id) as contract_amount
FROM contracts c
JOIN service_periods sp ON sp.contract_id = c.id AND c.tenant_id = sp.tenant_id
WHERE $where
ORDER BY sp.service_end ASC
";
$stmt = $db->prepare($sql);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':deadline', $deadline, PDO::PARAM_STR);
$stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>服务期到期提醒</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">服务期到期提醒</h2>
    <form method="get" class="mb-3">
        <label>提前提醒天数：</label>
        <input type="number" name="days" value="<?=htmlspecialchars($days)?>" min="1" style="width:80px;">
        <label class="ms-2">
            <input type="checkbox" name="show_closed" value="1" <?= $show_closed ? 'checked' : '' ?> onchange="this.form.submit()"> 显示已手工结束
        </label>
        <button type="submit" class="btn btn-primary btn-sm ms-2">筛选</button>
    </form>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>客户</th>
            <th>服务期</th>
            <th>截止日期</th>
            <th>合同金额</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            $is_expired = strtotime($row['service_end']) < strtotime($today);
        ?>
        <tr>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=date('Y.m.d',strtotime($row['service_start']))?> - <?=date('Y.m.d',strtotime($row['service_end']))?></td>
            <td><?=htmlspecialchars($row['service_end'])?></td>
            <td><?=number_format($row['contract_amount'],2)?></td>
            <td>
                <?php if ($row['manually_closed']): ?>
                    <span class="badge bg-secondary">已手工结束</span>
                <?php elseif ($is_expired): ?>
                    <span class="badge bg-danger">已到期</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">即将到期</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="contract_detail.php?id=<?=urlencode($row['contract_id'])?>" class="btn btn-sm btn-outline-secondary">客户详情</a>
                <?php if ($is_expired && (!$row['manually_closed'] || !$show_closed)): ?>
                    <a href="expire_remind.php?close_id=<?=urlencode($row['id'])?><?=isset($_GET['days'])?'&days='.urlencode($_GET['days']):''?><?= $show_closed ? '&show_closed=1' : '' ?>" class="btn btn-sm btn-outline-danger ms-2">手工结束</a>
                <?php elseif ($show_closed && $row['manually_closed']): ?>
                    <a href="expire_remind.php?reopen_id=<?=urlencode($row['id'])?><?=isset($_GET['days'])?'&days='.urlencode($_GET['days']):''?>&show_closed=1" class="btn btn-sm btn-outline-success ms-2">恢复提醒</a>
                <?php endif;?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <div class="alert alert-info mt-4" style="max-width:700px;">
        <ul class="mb-0">
            <li>如某客户明确不会续费，可点“手工结束”后将其不再提醒。</li>
            <li>如需恢复提醒，可勾选“显示已手工结束”，再点击“恢复提醒”操作。</li>
        </ul>
    </div>
</div>
</body>
</html>