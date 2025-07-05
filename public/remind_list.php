<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$sql = "
SELECT c.client_name, c.id as contract_id, sp.*, 
    (SELECT IFNULL(SUM(segment_fee),0) FROM service_segments WHERE service_period_id=sp.id AND tenant_id=:tenant_id1) as contract_amount,
    (SELECT IFNULL(SUM(amount),0) FROM payments WHERE is_temp=0 AND tenant_id=:tenant_id2 AND service_segment_id IN (SELECT id FROM service_segments WHERE service_period_id=sp.id AND tenant_id=:tenant_id3)) as paid_amount
FROM contracts c
JOIN service_periods sp ON sp.contract_id = c.id AND c.tenant_id = :tenant_id4 AND sp.tenant_id = :tenant_id5
ORDER BY sp.service_end ASC
";
$stmt = $db->prepare($sql);
$stmt->bindValue(':tenant_id1', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id3', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id4', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->bindValue(':tenant_id5', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>催收提醒</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">催收提醒（所有未收款服务期）</h2>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>客户</th>
            <th>服务期</th>
            <th>截止日期</th>
            <th>合同金额</th>
            <th>已收金额</th>
            <th>待收金额</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            $wait = $row['contract_amount'] - $row['paid_amount'];
            if($wait <= 0) continue; // 已收全不提醒
        ?>
        <tr>
            <td><?=htmlspecialchars($row['client_name'])?></td>
            <td><?=date('Y.m.d',strtotime($row['service_start']))?> - <?=date('Y.m.d',strtotime($row['service_end']))?></td>
            <td><?=htmlspecialchars($row['service_end'])?></td>
            <td><?=number_format($row['contract_amount'],2)?></td>
            <td><?=number_format($row['paid_amount'],2)?></td>
            <td class="text-danger"><?=number_format($wait,2)?></td>
            <td>
                <a href="contract_detail.php?id=<?=urlencode($row['contract_id'])?>" class="btn btn-sm btn-outline-secondary">客户详情</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>