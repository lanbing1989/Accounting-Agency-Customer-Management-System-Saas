<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$contract_id = intval($_GET['contract_id']);

// 合同仅本租户
$stmt = $db->prepare("SELECT * FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt->bindValue(':id', $contract_id, PDO::PARAM_INT);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare("SELECT * FROM service_periods WHERE contract_id=:id AND tenant_id=:tenant_id ORDER BY service_end DESC LIMIT 1");
$stmt2->bindValue(':id', $contract_id, PDO::PARAM_INT);
$stmt2->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt2->execute();
$last = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$contract || !$last) exit("无权限或无历史服务期");

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $start = date('Y-m-d', strtotime($last['service_end'] . ' +1 day'));
    $end = date('Y-m-d', strtotime($start . ' +1 year -1 day'));
    $stmt = $db->prepare("INSERT INTO service_periods (tenant_id, contract_id, service_start, service_end, total_fee) VALUES (:tenant_id, :contract_id, :service_start, :service_end, :total_fee)");
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':contract_id', $contract_id, PDO::PARAM_INT);
    $stmt->bindValue(':service_start', $start, PDO::PARAM_STR);
    $stmt->bindValue(':service_end', $end, PDO::PARAM_STR);
    $stmt->bindValue(':total_fee', $_POST['total_fee'], PDO::PARAM_STR);
    $stmt->execute();
    header('Location: contract_detail.php?id='.urlencode($contract_id));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>续费</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">续费（新服务期）</h2>
    <div class="mb-3">
        <strong>客户：</strong><?=htmlspecialchars($contract['client_name'])?>
    </div>
    <div class="mb-3">
        <strong>上一个服务期：</strong>
        <?=date('Y.m.d',strtotime($last['service_start']))?> - <?=date('Y.m.d',strtotime($last['service_end']))?>
    </div>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">新服务期起止日期</label>
            <input type="text" class="form-control" value="<?=date('Y.m.d',strtotime($last['service_end'].' +1 day'))?> - <?=date('Y.m.d',strtotime($last['service_end'].' +1 year'))?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">新服务期总金额</label>
            <input type="number" step="0.01" class="form-control" name="total_fee" required>
        </div>
        <button type="submit" class="btn btn-success">续费</button>
        <a href="contract_detail.php?id=<?=urlencode($contract_id)?>" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>