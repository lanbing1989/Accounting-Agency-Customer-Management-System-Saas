<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

// 客户列表（仅本租户）
$customers = [];
$stmt_cust = $db->prepare("SELECT id, client_name FROM contracts WHERE tenant_id=:tenant_id ORDER BY client_name");
$stmt_cust->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_cust->execute();
while($row = $stmt_cust->fetch(PDO::FETCH_ASSOC)) $customers[] = $row;

$contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;

// 新增临时收费处理
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $contract_id_post = intval($_POST['contract_id']);
    $pay_date = $_POST['pay_date'];
    $amount = floatval($_POST['amount']);
    $remark = $_POST['remark'];

    // 合法性校验，防止无效客户（仅本租户）
    $stmt_check = $db->prepare("SELECT COUNT(*) FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_check->bindValue(':id', $contract_id_post, PDO::PARAM_INT);
    $stmt_check->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_check->execute();
    if (!$stmt_check->fetchColumn()) {
        die('客户不存在或无权限');
    }

    // 日期及金额的简单校验
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pay_date)) {
        die('收费日期格式错误');
    }
    if ($amount <= 0) {
        die('金额必须大于0');
    }

    // 安全插入（带tenant_id）
    $stmt = $db->prepare("INSERT INTO payments (tenant_id, contract_id, pay_date, amount, remark, is_temp) VALUES (:tenant_id, :contract_id, :pay_date, :amount, :remark, 1)");
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':contract_id', $contract_id_post, PDO::PARAM_INT);
    $stmt->bindValue(':pay_date', $pay_date, PDO::PARAM_STR);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
    $stmt->execute();
    header('Location: temp_payment.php'.($contract_id_post ? '?contract_id='.urlencode($contract_id_post) : ''));
    exit;
}

// 删除临时收费处理
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $del_id = intval($_GET['del']);
    $del_contract_id = isset($_GET['contract_id']) ? intval($_GET['contract_id']) : 0;
    // 只删 is_temp=1 且本租户的记录
    $stmt = $db->prepare("DELETE FROM payments WHERE id=:id AND is_temp=1 AND tenant_id=:tenant_id");
    $stmt->bindValue(':id', $del_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    header('Location: temp_payment.php'.($del_contract_id ? '?contract_id='.urlencode($del_contract_id) : ''));
    exit;
}

// 临时收费列表
if ($contract_id) {
    $stmt = $db->prepare("
        SELECT p.*, c.client_name 
        FROM payments p 
        JOIN contracts c ON c.id=p.contract_id 
        WHERE p.is_temp=1 AND p.tenant_id=:tenant_id AND c.tenant_id=:tenant_id2 AND p.contract_id=:cid
        ORDER BY p.pay_date DESC, p.id DESC
    ");
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':cid', $contract_id, PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt;
} else {
    $stmt = $db->prepare("
        SELECT p.*, c.client_name 
        FROM payments p 
        JOIN contracts c ON c.id=p.contract_id 
        WHERE p.is_temp=1 AND p.tenant_id=:tenant_id AND c.tenant_id=:tenant_id2
        ORDER BY p.pay_date DESC, p.id DESC
    ");
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id2', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt;
}

// 客户名->id映射js
$customer_json = json_encode($customers, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>临时收费管理</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">临时收费管理</h2>
        <?php if($contract_id): ?>
            <a href="contract_detail.php?id=<?=urlencode($contract_id)?>" class="btn btn-sm btn-secondary">返回客户详情</a>
        <?php endif; ?>
    </div>
    <form method="post" class="bg-white p-4 rounded shadow-sm mb-4" id="form-temp-pay" autocomplete="off">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">客户</label>
                <input class="form-control" list="customer_list" id="customer_input" autocomplete="off" placeholder="输入客户名搜索" value="<?php
                    if($contract_id){
                        foreach($customers as $c){
                            if($c['id']==$contract_id) echo htmlspecialchars($c['client_name']);
                        }
                    }
                ?>" <?= $contract_id ? 'readonly' : '' ?>>
                <datalist id="customer_list">
                    <?php foreach($customers as $c): ?>
                        <option value="<?=htmlspecialchars($c['client_name'])?>">
                    <?php endforeach;?>
                </datalist>
                <input type="hidden" name="contract_id" id="contract_id" value="<?=htmlspecialchars($contract_id)?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">收费日期</label>
                <input type="date" class="form-control" name="pay_date" value="<?=htmlspecialchars(date('Y-m-d'))?>" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">金额</label>
                <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">备注</label>
                <input type="text" class="form-control" name="remark" maxlength="100">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success">新增临时收费</button>
            </div>
        </div>
    </form>
    <h4>历史临时收费</h4>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>客户</th>
            <th>收费日期</th>
            <th>金额</th>
            <th>备注</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php while($p = $payments->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><?=htmlspecialchars($p['id'])?></td>
            <td><?=htmlspecialchars($p['client_name'])?></td>
            <td><?=htmlspecialchars($p['pay_date'])?></td>
            <td><?=htmlspecialchars($p['amount'])?></td>
            <td><?=htmlspecialchars($p['remark'])?></td>
            <td>
                <a href="temp_payment.php?del=<?=urlencode($p['id'])?><?=($contract_id?'&contract_id='.urlencode($contract_id):'')?>" class="btn btn-sm btn-danger" onclick="return confirm('确认删除？')">删除</a>
            </td>
        </tr>
        <?php endwhile;?>
        </tbody>
    </table>
</div>
<script>
const customers = <?=$customer_json?>;
document.getElementById('form-temp-pay').addEventListener('submit', function(e) {
    <?php if(!$contract_id): ?>
    // 将输入的客户名转为id
    const inputName = document.getElementById('customer_input').value.trim();
    const found = customers.find(c => c.client_name === inputName);
    if (!found) {
        alert('客户不存在，请选择正确的客户');
        e.preventDefault();
        return false;
    }
    document.getElementById('contract_id').value = found.id;
    <?php endif; ?>
});
</script>
</body>
</html>