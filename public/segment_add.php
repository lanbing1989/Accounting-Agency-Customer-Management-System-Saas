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

$period_id = intval($_GET['period_id']);
$stmt_period = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
$stmt_period->bindValue(':id', $period_id, PDO::PARAM_INT);
$stmt_period->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_period->execute();
$period = $stmt_period->fetch(PDO::FETCH_ASSOC);

if (!$period) exit('服务期不存在或无权限');

// 删除分段
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = intval($_GET['del']);
    $stmt_del = $db->prepare("DELETE FROM service_segments WHERE id=:id AND service_period_id=:period_id AND tenant_id=:tenant_id");
    $stmt_del->bindValue(':id', $del_id, PDO::PARAM_INT);
    $stmt_del->bindValue(':period_id', $period_id, PDO::PARAM_INT);
    $stmt_del->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_del->execute();
    // 删除后同步套餐类型为最新分段的类型（如果还有分段）
    $stmt_latest = $db->prepare("SELECT package_type FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id ORDER BY end_date DESC LIMIT 1");
    $stmt_latest->bindValue(':period_id', $period_id, PDO::PARAM_INT);
    $stmt_latest->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_latest->execute();
    $latest_seg = $stmt_latest->fetch(PDO::FETCH_ASSOC);
    if ($latest_seg) {
        $stmt_update = $db->prepare("UPDATE service_periods SET package_type = :pt WHERE id = :period_id AND tenant_id=:tenant_id");
        $stmt_update->bindValue(':pt', $latest_seg['package_type'], PDO::PARAM_STR);
        $stmt_update->bindValue(':period_id', $period_id, PDO::PARAM_INT);
        $stmt_update->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt_update->execute();
    }
    header("Location: segment_add.php?period_id=$period_id");
    exit;
}

// 编辑分段
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_seg = null;
if ($edit_id) {
    $stmt_edit = $db->prepare("SELECT * FROM service_segments WHERE id=:id AND service_period_id=:period_id AND tenant_id=:tenant_id");
    $stmt_edit->bindValue(':id', $edit_id, PDO::PARAM_INT);
    $stmt_edit->bindValue(':period_id', $period_id, PDO::PARAM_INT);
    $stmt_edit->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_edit->execute();
    $edit_seg = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if (!$edit_seg) $edit_id = 0;
}

// 获取所有分段
$segments = [];
$stmt_segs = $db->prepare("SELECT * FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id ORDER BY start_date");
$stmt_segs->bindValue(':period_id', $period_id, PDO::PARAM_INT);
$stmt_segs->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_segs->execute();
while ($row = $stmt_segs->fetch(PDO::FETCH_ASSOC)) $segments[] = $row;

// 新分段起始
$new_start = $period['service_start'];
if (count($segments) > 0) {
    $last = end($segments);
    $new_start = date('Y-m-d', strtotime($last['end_date'].' +1 day'));
}
$can_add = strtotime($new_start) <= strtotime($period['service_end']);

// 提交新增分段
if ($_SERVER['REQUEST_METHOD']=='POST' && !$edit_id && $can_add) {
    $price_per_year = floatval($_POST['price_per_year']);
    $remark = $_POST['remark'];
    $to_date = $_POST['segment_end_date'];
    $package_type = $_POST['package_type'];
    $start = fix_date(trim($new_start));
    $end = fix_date(trim($to_date));
    $months = count_months($start, $end);
    $fee = round($price_per_year * $months / 12, 2);

    $stmt = $db->prepare("INSERT INTO service_segments (tenant_id, service_period_id, start_date, end_date, price_per_year, segment_fee, package_type, remark) VALUES (:tenant_id, :service_period_id, :start_date, :end_date, :price_per_year, :segment_fee, :package_type, :remark)");
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':service_period_id', $period_id, PDO::PARAM_INT);
    $stmt->bindValue(':start_date', $start, PDO::PARAM_STR);
    $stmt->bindValue(':end_date', $end, PDO::PARAM_STR);
    $stmt->bindValue(':price_per_year', $price_per_year, PDO::PARAM_STR);
    $stmt->bindValue(':segment_fee', $fee, PDO::PARAM_STR);
    $stmt->bindValue(':package_type', $package_type, PDO::PARAM_STR);
    $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
    $stmt->execute();
    // 新增分段后，同步套餐类型为最新分段的类型
    $stmt_latest = $db->prepare("SELECT package_type FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id ORDER BY end_date DESC LIMIT 1");
    $stmt_latest->bindValue(':period_id', $period_id, PDO::PARAM_INT);
    $stmt_latest->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_latest->execute();
    $latest_seg = $stmt_latest->fetch(PDO::FETCH_ASSOC);
    if ($latest_seg) {
        $stmt_update = $db->prepare("UPDATE service_periods SET package_type = :pt WHERE id = :period_id AND tenant_id=:tenant_id");
        $stmt_update->bindValue(':pt', $latest_seg['package_type'], PDO::PARAM_STR);
        $stmt_update->bindValue(':period_id', $period_id, PDO::PARAM_INT);
        $stmt_update->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt_update->execute();
    }
    header('Location: segment_add.php?period_id='.$period_id);
    exit;
}

// 提交编辑分段
if ($_SERVER['REQUEST_METHOD']=='POST' && $edit_id) {
    $price_per_year = floatval($_POST['price_per_year']);
    $remark = $_POST['remark'];
    $to_date = $_POST['segment_end_date'];
    $package_type = $_POST['package_type'];
    $start = fix_date(trim($edit_seg['start_date']));
    $end = fix_date(trim($to_date));
    $months = count_months($start, $end);
    $fee = round($price_per_year * $months / 12, 2);

    $stmt = $db->prepare("UPDATE service_segments SET end_date=:end_date, price_per_year=:price_per_year, segment_fee=:segment_fee, package_type=:package_type, remark=:remark WHERE id=:id AND tenant_id=:tenant_id");
    $stmt->bindValue(':end_date', $end, PDO::PARAM_STR);
    $stmt->bindValue(':price_per_year', $price_per_year, PDO::PARAM_STR);
    $stmt->bindValue(':segment_fee', $fee, PDO::PARAM_STR);
    $stmt->bindValue(':package_type', $package_type, PDO::PARAM_STR);
    $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
    $stmt->bindValue(':id', $edit_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    // 编辑分段后，同步套餐类型为最新分段的类型
    $stmt_latest = $db->prepare("SELECT package_type FROM service_segments WHERE service_period_id=:period_id AND tenant_id=:tenant_id ORDER BY end_date DESC LIMIT 1");
    $stmt_latest->bindValue(':period_id', $period_id, PDO::PARAM_INT);
    $stmt_latest->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_latest->execute();
    $latest_seg = $stmt_latest->fetch(PDO::FETCH_ASSOC);
    if ($latest_seg) {
        $stmt_update = $db->prepare("UPDATE service_periods SET package_type = :pt WHERE id = :period_id AND tenant_id=:tenant_id");
        $stmt_update->bindValue(':pt', $latest_seg['package_type'], PDO::PARAM_STR);
        $stmt_update->bindValue(':period_id', $period_id, PDO::PARAM_INT);
        $stmt_update->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt_update->execute();
    }
    header('Location: segment_add.php?period_id='.$period_id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>服务期价格分段/调整</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    .btn-sm { margin-right: 4px;}
    </style>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2>服务期价格分段/调整</h2>
    <div class="mb-3">
        <b>服务期：</b><?=date('Y.m.d',strtotime($period['service_start']))?> - <?=date('Y.m.d',strtotime($period['service_end']))?> (<?=$period['month_count']?>个月)
    </div>
    <table class="table table-bordered">
        <thead>
            <tr><th>分段期间</th><th>年费</th><th>分段费用</th><th>套餐</th><th>备注</th><th>操作</th></tr>
        </thead>
        <tbody>
        <?php foreach($segments as $seg): ?>
            <tr>
                <td><?=date('Y.m.d',strtotime($seg['start_date']))?> - <?=date('Y.m.d',strtotime($seg['end_date']))?></td>
                <td><?=$seg['price_per_year']?></td>
                <td><?=$seg['segment_fee']?></td>
                <td><?=$seg['package_type']?></td>
                <td><?=htmlspecialchars($seg['remark'])?></td>
                <td>
                    <a href="segment_add.php?period_id=<?=$period_id?>&edit=<?=$seg['id']?>" class="btn btn-sm btn-outline-primary">编辑</a>
                    <a href="segment_add.php?period_id=<?=$period_id?>&del=<?=$seg['id']?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确认删除本分段？')">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($edit_id): // 编辑表单 ?>
    <form method="post" class="bg-white p-4 rounded shadow-sm mb-3">
        <div class="mb-3">
            <label class="form-label">分段起始日期</label>
            <input type="date" class="form-control" name="segment_start_date" value="<?=$edit_seg['start_date']?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">本分段截止日期</label>
            <input type="date" class="form-control" name="segment_end_date" value="<?=$edit_seg['end_date']?>" min="<?=$edit_seg['start_date']?>" max="<?=$period['service_end']?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">年服务费（元/年）</label>
            <input type="number" class="form-control" name="price_per_year" step="0.01" value="<?=$edit_seg['price_per_year']?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">套餐类型</label>
            <select name="package_type" class="form-control" required>
                <?php foreach($package_types as $type): ?>
                    <option value="<?=$type?>" <?=$edit_seg['package_type']==$type?'selected':''?>><?=$type?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark" value="<?=htmlspecialchars($edit_seg['remark'])?>">
        </div>
        <button type="submit" class="btn btn-success">保存修改</button>
        <a href="segment_add.php?period_id=<?=$period_id?>" class="btn btn-secondary">取消</a>
    </form>
    <?php elseif ($can_add): // 新增表单 ?>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">本分段起始日期</label>
            <input type="date" class="form-control" name="segment_start_date" value="<?=$new_start?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">本分段截止日期</label>
            <input type="date" class="form-control" name="segment_end_date" value="<?=$period['service_end']?>" min="<?=$new_start?>" max="<?=$period['service_end']?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">年服务费（元/年）</label>
            <input type="number" class="form-control" name="price_per_year" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">套餐类型</label>
            <select name="package_type" class="form-control" required>
                <?php foreach($package_types as $type): ?>
                    <option value="<?=$type?>"><?=$type?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark">
        </div>
        <button type="submit" class="btn btn-success">添加分段</button>
    </form>
    <?php else: ?>
        <div class="alert alert-info mt-3">已覆盖整个服务期，不可再新增分段。如需调整，请先删除后面的分段。</div>
    <?php endif; ?>
    <a href="contract_detail.php?id=<?=$period['contract_id']?>" class="btn btn-secondary mt-3">返回</a>
</div>
</body>
</html>