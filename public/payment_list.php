<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$period_id = intval($_GET['period_id']);

// 查询服务期和所属合同（仅本租户）
$stmt_period = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
$stmt_period->bindValue(':id', $period_id, PDO::PARAM_INT);
$stmt_period->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_period->execute();
$period = $stmt_period->fetch(PDO::FETCH_ASSOC);

if (!$period) exit('服务期不存在或无权限');

$stmt_contract = $db->prepare("SELECT * FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt_contract->bindValue(':id', intval($period['contract_id']), PDO::PARAM_INT);
$stmt_contract->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_contract->execute();
$contract = $stmt_contract->fetch(PDO::FETCH_ASSOC);

if (!$contract) exit('合同不存在或无权限');

// 查询所有分段（仅本租户）
$segments = [];
$stmt_seg = $db->prepare("SELECT * FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id");
$stmt_seg->bindValue(':period_id', $period_id, PDO::PARAM_INT);
$stmt_seg->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_seg->execute();
while($row = $stmt_seg->fetch(PDO::FETCH_ASSOC)) $segments[$row['id']] = $row;

// 查询所有收费记录（仅本租户）
$stmt_payments = $db->prepare("
    SELECT p.*, s.start_date, s.end_date 
    FROM payments p 
    LEFT JOIN service_segments s ON p.service_segment_id=s.id 
    WHERE p.tenant_id=:tenant_id 
      AND p.service_segment_id IN (
        SELECT id FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id2
      )
    ORDER BY p.pay_date DESC, p.id DESC
");
// 注意：需要 tenant_id 和 tenant_id2 两个参数，分别绑定！
$stmt_payments->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_payments->bindValue(':period_id', $period_id, PDO::PARAM_INT);
$stmt_payments->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_payments->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>收费记录</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h4>客户：<?=htmlspecialchars($contract['client_name'])?></h4>
    <div class="mb-2">
        <b>服务期：</b><?=date('Y.m.d',strtotime($period['service_start']))?> - <?=date('Y.m.d',strtotime($period['service_end']))?>
    </div>
    <a href="payment_add.php?period_id=<?=$period_id?>" class="btn btn-success btn-sm mb-2">新增收费</a>
    <a href="contract_detail.php?id=<?=$period['contract_id']?>" class="btn btn-secondary btn-sm mb-2">返回</a>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>分段期间</th>
                <th>收费日期</th>
                <th>金额</th>
                <th>备注</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php while($p = $stmt_payments->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?=$p['id']?></td>
                <td>
                    <?php
                    if ($p['start_date'] && $p['end_date']) {
                        echo date('Y.m.d', strtotime($p['start_date'])) . " - " . date('Y.m.d', strtotime($p['end_date']));
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                <td><?=htmlspecialchars($p['pay_date'])?></td>
                <td><?=$p['amount']?></td>
                <td><?=htmlspecialchars($p['remark'])?></td>
                <td>
                    <a href="payment_delete.php?id=<?=$p['id']?>&period_id=<?=$period_id?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除此收费记录？')">删除</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>