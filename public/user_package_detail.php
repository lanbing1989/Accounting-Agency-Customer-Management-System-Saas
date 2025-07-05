<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

// 获取当前租户ID
$tenant_id = $_SESSION['tenant_id'] ?? 0;
if (!$tenant_id) {
    header("Location: login.php");
    exit;
}

// 查询租户及套餐信息（参数字段全部同步新版）
$stmt = $db->prepare("SELECT 
        t.name AS tenant_name, 
        t.package_id, 
        t.package_expire, 
        t.created_at, 
        p.name AS package_name, 
        p.max_users, 
        p.max_clients, 
        p.max_agreements, 
        p.price
    FROM tenants t 
    LEFT JOIN tenant_packages p ON t.package_id = p.id 
    WHERE t.id = ?");
$stmt->execute([$tenant_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// 若未设置套餐信息，做基础处理
$packageNames = [
    1 => '标准版',
    2 => '高级版',
    3 => '旗舰版'
];
if (!$info['package_name']) {
    $info['package_name'] = $packageNames[$info['package_id']] ?? '未知套餐';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>套餐详情</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container" style="max-width:680px;margin-top:40px;">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fs-5">
                    套餐详情
                </div>
                <div class="card-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-4">租户名称</dt>
                        <dd class="col-sm-8 text-primary"><?=htmlspecialchars($info['tenant_name'])?></dd>

                        <dt class="col-sm-4">套餐名称</dt>
                        <dd class="col-sm-8"><?=htmlspecialchars($info['package_name'])?></dd>

                        <dt class="col-sm-4">到期时间</dt>
                        <dd class="col-sm-8">
                            <span class="<?=strtotime($info['package_expire']) < time() ? 'text-danger' : 'text-success'?>">
                                <?=htmlspecialchars($info['package_expire'])?>
                            </span>
                        </dd>
                    </dl>
                    <hr>
                    <div class="mb-3">
                        <b>套餐参数：</b>
                        <ul class="list-group my-2">
                            <li class="list-group-item">
                                最多用户数：<?=is_null($info['max_users']) ? '不限' : $info['max_users']?>
                            </li>
                            <li class="list-group-item">
                                最多客户数：<?=is_null($info['max_clients']) ? '不限' : $info['max_clients']?>
                            </li>
                            <li class="list-group-item">
                                最多电子合同签署数：<?=is_null($info['max_agreements']) ? '不限' : $info['max_agreements']?>
                            </li>
                            <li class="list-group-item">
                                套餐价格：<?=isset($info['price']) ? $info['price'].' 元/年' : '面议'?>
                            </li>
                        </ul>
                    </div>
                    <div class="mb-3 text-end">
                        <a href="package_buy.php" class="btn btn-warning">立即续费/升级套餐</a>
                        <a href="index.php" class="btn btn-secondary">返回首页</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>