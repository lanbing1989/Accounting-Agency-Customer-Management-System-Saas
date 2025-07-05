<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
function fix_date($d) {
    return preg_replace('/\./', '-', $d);
}
function count_months($start, $end) {
    $start_ts = strtotime($start);
    $end_ts = strtotime($end);
    if ($start_ts === false || $end_ts === false) return 0;
    $start_y = date('Y', $start_ts);
    $start_m = date('m', $start_ts);
    $end_y = date('Y', $end_ts);
    $end_m = date('m', $end_ts);
    return ($end_y - $start_y) * 12 + ($end_m - $start_m) + 1;
}

$package_types = [
    '小规模纳税人',
    '小规模纳税人零申报',
    '一般纳税人',
    '一般纳税人零申报'
];

$contract_id = intval($_GET['contract_id']);
$stmt_contract = $db->prepare("SELECT * FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt_contract->bindValue(':id', $contract_id, PDO::PARAM_INT);
$stmt_contract->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_contract->execute();
$contract = $stmt_contract->fetch(PDO::FETCH_ASSOC);

if (!$contract) exit('客户不存在或无权限');

$renew_period = null;
$default_month = date('Y-m');
$default_package_type = $package_types[0];
if (!empty($_GET['renew'])) {
    $renew_id = intval($_GET['renew']);
    $stmt_rn = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND contract_id=:cid AND tenant_id=:tenant_id");
    $stmt_rn->bindValue(':id', $renew_id, PDO::PARAM_INT);
    $stmt_rn->bindValue(':cid', $contract_id, PDO::PARAM_INT);
    $stmt_rn->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_rn->execute();
    $renew_period = $stmt_rn->fetch(PDO::FETCH_ASSOC);
    if ($renew_period) {
        $default_month = date('Y-m', strtotime($renew_period['service_end'] . ' +1 day'));
        $default_package_type = $renew_period['package_type'] ?? $package_types[0];
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $month = $_POST['service_month'];
    $months = intval($_POST['month_count']);
    $start = $month . '-01';
    $end = date('Y-m-d', strtotime("+$months months -1 day", strtotime($start)));
    $package_type = $_POST['package_type'];

    $sql = "SELECT * FROM service_periods WHERE contract_id=:contract_id AND tenant_id=:tenant_id AND (service_start <= :end AND service_end >= :start)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':contract_id', $contract_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_STR);
    $stmt->bindValue(':end', $end, PDO::PARAM_STR);
    $stmt->execute();
    $overlap = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($overlap) {
        $error = '该客户本时间段已存在服务期，不可重复添加！';
    } else {
        $stmt = $db->prepare("INSERT INTO service_periods (tenant_id, contract_id, service_start, service_end, month_count, package_type) VALUES (:tenant_id, :contract_id, :service_start, :service_end, :month_count, :package_type)");
        $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt->bindValue(':contract_id', $contract_id, PDO::PARAM_INT);
        $stmt->bindValue(':service_start', $start, PDO::PARAM_STR);
        $stmt->bindValue(':service_end', $end, PDO::PARAM_STR);
        $stmt->bindValue(':month_count', $months, PDO::PARAM_INT);
        $stmt->bindValue(':package_type', $package_type, PDO::PARAM_STR);
        $stmt->execute();
        $period_id = $db->lastInsertId();

        // 自动生成默认分段
        $price_per_year = floatval($_POST['price_per_year']);
        $start_fixed = fix_date($start);
        $end_fixed = fix_date($end);
        $fee = round($price_per_year * $months / 12, 2);

        $stmt2 = $db->prepare("INSERT INTO service_segments (tenant_id, service_period_id, start_date, end_date, price_per_year, segment_fee, package_type, remark) VALUES (:tenant_id, :service_period_id, :start_date, :end_date, :price_per_year, :segment_fee, :package_type, :remark)");
        $stmt2->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt2->bindValue(':service_period_id', $period_id, PDO::PARAM_INT);
        $stmt2->bindValue(':start_date', $start_fixed, PDO::PARAM_STR);
        $stmt2->bindValue(':end_date', $end_fixed, PDO::PARAM_STR);
        $stmt2->bindValue(':price_per_year', $price_per_year, PDO::PARAM_STR);
        $stmt2->bindValue(':segment_fee', $fee, PDO::PARAM_STR);
        $stmt2->bindValue(':package_type', $package_type, PDO::PARAM_STR);
        $stmt2->bindValue(':remark', '默认分段', PDO::PARAM_STR);
        $stmt2->execute();

        header('Location: contract_detail.php?id='.$contract_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新增/续费服务期</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4"><?= isset($renew_period) ? '续期服务期' : '新增/续费服务期' ?></h2>
    <div class="mb-3"><b>客户：</b><?=htmlspecialchars($contract['client_name'])?></div>
    <?php if($error): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif;?>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">起始年月</label>
            <input type="month" class="form-control" name="service_month" value="<?=$default_month?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">服务月数</label>
            <input type="number" class="form-control" name="month_count" value="12" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">套餐类型</label>
            <select name="package_type" class="form-control" required>
                <?php foreach($package_types as $type): ?>
                    <option value="<?=$type?>" <?=($type==$default_package_type)?'selected':''?>><?=$type?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">年服务费（元/年）</label>
            <input type="number" step="0.01" class="form-control" name="price_per_year" required>
        </div>
        <button type="submit" class="btn btn-success">保存</button>
        <a href="contract_detail.php?id=<?=$contract_id?>" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>