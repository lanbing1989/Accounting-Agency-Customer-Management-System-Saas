<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$id = intval($_GET['id']);

// 查询合同，仅本租户
$stmt = $db->prepare("SELECT * FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

// 查询所有服务期
$periods = [];
$stmt1 = $db->prepare("SELECT * FROM service_periods WHERE contract_id=:id AND tenant_id=:tenant_id ORDER BY service_start DESC");
$stmt1->bindValue(':id', $id, PDO::PARAM_INT);
$stmt1->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt1->execute();
while($row = $stmt1->fetch(PDO::FETCH_ASSOC)) $periods[] = $row;

// 查询所有分段
$segments_by_period = [];
if ($periods) {
    $period_ids = implode(',', array_map('intval', array_column($periods, 'id')));
    if ($period_ids) {
        $sql = "SELECT * FROM service_segments WHERE service_period_id IN ($period_ids) AND tenant_id=:tenant_id";
        $stmt2 = $db->prepare($sql);
        $stmt2->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt2->execute();
        while($seg = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $segments_by_period[$seg['service_period_id']][] = $seg;
        }
    }
}

// 查询临时收费
$stmt3 = $db->prepare("SELECT * FROM payments WHERE contract_id=:id AND is_temp=1 AND tenant_id=:tenant_id ORDER BY pay_date DESC, id DESC");
$stmt3->bindValue(':id', $id, PDO::PARAM_INT);
$stmt3->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt3->execute();
$temp_payments = $stmt3;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>客户详情</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2>客户：<?=htmlspecialchars($contract['client_name']??'')?></h2>
    <div class="mb-2"><b>联系人：</b><?=htmlspecialchars($contract['contact_person']??'')?></div>
    <div class="mb-2"><b>联系电话：</b><?=htmlspecialchars($contract['contact_phone']??'')?></div>
    <div class="mb-2"><b>联系邮箱：</b><?=htmlspecialchars($contract['contact_email']??'')?></div>
    <div class="mb-3"><b>备注：</b><?=htmlspecialchars($contract['remark']??'')?></div>
    <a href="contract_edit.php?id=<?=urlencode($id)?>" class="btn btn-outline-primary btn-sm">编辑客户</a>
    <a href="service_period_add.php?contract_id=<?=urlencode($id)?>" class="btn btn-success btn-sm">新增服务期/续费</a>
    <a href="contract_delete.php?id=<?=urlencode($id)?>" class="btn btn-danger btn-sm" onclick="return confirm('确定要彻底删除该客户及其所有服务期和记录吗？')">删除客户</a>
    <a href="index.php" class="btn btn-secondary btn-sm">返回列表</a>
    <hr>
    <h4>周期性收费</h4>
    <div class="table-responsive">
    <table class="table table-bordered table-hover bg-white mb-4">
        <thead class="table-light">
            <tr>
                <th>服务期</th>
                <th>月数</th>
                <th>套餐类型</th>
                <th>分段详情</th>
                <th>分段调整</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($periods as $p): ?>
            <tr>
                <td><?=date('Y.m.d',strtotime($p['service_start']))?> - <?=date('Y.m.d',strtotime($p['service_end']))?></td>
                <td><?=htmlspecialchars($p['month_count'])?></td>
                <td><?=htmlspecialchars($p['package_type'])?></td>
                <td style="padding:0;">
                    <?php if(!empty($segments_by_period[$p['id']])): ?>
                    <table class="table table-sm m-0">
                        <thead>
                        <tr>
                            <th>分段期间</th>
                            <th>年费</th>
                            <th>分段费用</th>
                            <th>套餐</th>
                            <th>备注</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($segments_by_period[$p['id']] as $seg): ?>
                            <tr>
                                <td><?=date('Y.m.d',strtotime($seg['start_date']))?> - <?=date('Y.m.d',strtotime($seg['end_date']))?></td>
                                <td><?=htmlspecialchars($seg['price_per_year'])?></td>
                                <td><?=htmlspecialchars($seg['segment_fee'])?></td>
                                <td><?=htmlspecialchars($seg['package_type'])?></td>
                                <td><?=htmlspecialchars($seg['remark'])?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <span class="text-muted">无分段</span>
                    <?php endif; ?>
                </td>
                <td>
                  <a href="segment_add.php?period_id=<?=urlencode($p['id'])?>" class="btn btn-sm btn-outline-warning">调整价格/补差</a>
                </td>
                <td>
                    <a href="payment_list.php?period_id=<?=urlencode($p['id'])?>" class="btn btn-sm btn-outline-secondary">收费记录</a>
                    <a href="service_period_add.php?contract_id=<?=urlencode($id)?>&renew=<?=urlencode($p['id'])?>" class="btn btn-sm btn-outline-success">续期</a>
                    <a href="service_period_delete.php?id=<?=urlencode($p['id'])?>&contract_id=<?=urlencode($id)?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除本服务期吗？')">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">临时收费记录</h4>
        <a href="temp_payment.php?contract_id=<?=urlencode($id)?>" class="btn btn-sm btn-success">新增临时收费</a>
    </div>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>收费日期</th>
                <th>金额</th>
                <th>备注</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php while($tp = $temp_payments->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?=htmlspecialchars($tp['id'])?></td>
                <td><?=htmlspecialchars($tp['pay_date'])?></td>
                <td><?=htmlspecialchars($tp['amount'])?></td>
                <td><?=htmlspecialchars($tp['remark'])?></td>
                <td>
                    <a href="temp_payment.php?del=<?=urlencode($tp['id'])?>&contract_id=<?=urlencode($id)?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>