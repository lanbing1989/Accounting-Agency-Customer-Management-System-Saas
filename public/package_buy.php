<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';

$tenant_id = $_SESSION['tenant_id'] ?? 0;
if (!$tenant_id) {
    header("Location: login.php");
    exit;
}

// 当前租户信息
$stmt = $db->prepare("SELECT * FROM tenants WHERE id=?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);

// 当前套餐&到期日
$current_package_id = $tenant['package_id'] ?? null;
$current_expire = $tenant['package_expire'] ?? null;

// 全部套餐
$packages = $db->query("SELECT * FROM tenant_packages ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
    $package_id = intval($_POST['package_id']);
    $package_stmt = $db->prepare("SELECT * FROM tenant_packages WHERE id=?");
    $package_stmt->execute([$package_id]);
    $pkg = $package_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pkg) die('套餐不存在');

    // 判断是否为升级：新套餐价格高于当前套餐，并且当前套餐未到期
    $is_upgrade = false;
    $diff = $pkg['price'];
    $upgrade_detail = '';
    if ($current_package_id && $current_package_id != $package_id && $current_expire && strtotime($current_expire) > time()) {
        // 查询当前套餐
        $old_pkg_stmt = $db->prepare("SELECT * FROM tenant_packages WHERE id=?");
        $old_pkg_stmt->execute([$current_package_id]);
        $old_pkg = $old_pkg_stmt->fetch(PDO::FETCH_ASSOC);
        if ($old_pkg && $pkg['price'] > $old_pkg['price']) {
            // 剩余天数
            $today = date('Y-m-d');
            $remain_days = ceil((strtotime($current_expire) - strtotime($today)) / 86400);
            if ($remain_days < 1) $remain_days = 0;
            // 补差价
            $old_day_price = $old_pkg['price'] / 365;
            $new_day_price = $pkg['price'] / 365;
            $diff = $remain_days * ($new_day_price - $old_day_price);
            $diff = max(0, round($diff, 2));
            $is_upgrade = true;
            $upgrade_detail = "（升级补差价，剩余{$remain_days}天）";
        }
    }

    // 生成订单号、下单
    $order_no = 'WX'.date('YmdHis').rand(1000,9999);
    $db->prepare("INSERT INTO tenant_orders (tenant_id, package_id, amount, order_no, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())")
        ->execute([$tenant_id, $pkg['id'], $diff, $order_no]);

    // 记录日志
    $log_action = $is_upgrade ? '套餐升级下单' : '套餐下单';
    $log_detail = $is_upgrade
        ? "由【{$old_pkg['name']}】升级为【{$pkg['name']}】，原到期日：{$current_expire}，补差价：{$diff}元，订单号：{$order_no}"
        : "购买套餐【{$pkg['name']}】，金额：{$diff}元，订单号：{$order_no}";
    $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$tenant_id, $_SESSION['user_id'] ?? null, $log_action, $log_detail]);

    header("Location: pay_order_wechat.php?order_no=$order_no");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>套餐购买</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container" style="max-width:700px;margin-top:40px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fs-5">选择套餐</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <?php foreach($packages as $pkg): ?>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?=htmlspecialchars($pkg['name'])?></h5>
                            <p>用户数：<?=is_null($pkg['max_users'])?'不限':$pkg['max_users']?></p>
                            <p>客户数：<?=is_null($pkg['max_clients'])?'不限':$pkg['max_clients']?></p>
                            <p>合同数：<?=is_null($pkg['max_agreements'])?'不限':$pkg['max_agreements']?></p>
                            <p>价格：<b><?=$pkg['price']?>元/年</b></p>
                            <?php
                                // 显示升级补差价提示
                                $upgrade_tip = '';
                                if ($current_package_id && $current_package_id != $pkg['id'] && $current_expire && strtotime($current_expire) > time()) {
                                    // 查当前套餐
                                    foreach($packages as $old_pkg) {
                                        if ($old_pkg['id'] == $current_package_id) break;
                                    }
                                    if (isset($old_pkg) && $pkg['price'] > $old_pkg['price']) {
                                        $today = date('Y-m-d');
                                        $remain_days = ceil((strtotime($current_expire) - strtotime($today)) / 86400);
                                        if ($remain_days < 1) $remain_days = 0;
                                        $old_day_price = $old_pkg['price'] / 365;
                                        $new_day_price = $pkg['price'] / 365;
                                        $diff = $remain_days * ($new_day_price - $old_day_price);
                                        $diff = max(0, round($diff, 2));
                                        $upgrade_tip = "升级补差价：{$diff}元（剩余{$remain_days}天）";
                                    }
                                }
                            ?>
                            <?php if($upgrade_tip): ?>
                                <div class="alert alert-info p-1 text-center" style="font-size:13px;"><?=$upgrade_tip?></div>
                            <?php endif; ?>
                            <button type="submit" name="package_id" value="<?=$pkg['id']?>" class="btn btn-success w-100">立即购买</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</div>
</body>
</html>