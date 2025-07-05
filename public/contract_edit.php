<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$id = intval($_GET['id']);

// 用参数绑定方式获取客户（仅本租户）
$stmt_row = $db->prepare("SELECT * FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt_row->bindValue(':id', $id, PDO::PARAM_INT);
$stmt_row->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_row->execute();
$row = $stmt_row->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    exit('客户不存在或无权限');
}

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    $stmt = $db->prepare("UPDATE contracts SET client_name=:client_name, contact_person=:contact_person, contact_phone=:contact_phone, contact_email=:contact_email, remark=:remark WHERE id=:id AND tenant_id=:tenant_id");
    $stmt->bindValue(':client_name', $client_name, PDO::PARAM_STR);
    $stmt->bindValue(':contact_person', $contact_person, PDO::PARAM_STR);
    $stmt->bindValue(':contact_phone', $contact_phone, PDO::PARAM_STR);
    $stmt->bindValue(':contact_email', $contact_email, PDO::PARAM_STR);
    $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>编辑客户</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">编辑客户</h2>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">客户名称</label>
            <input type="text" class="form-control" name="client_name" value="<?=htmlspecialchars($row['client_name'])?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">联系人</label>
            <input type="text" class="form-control" name="contact_person" value="<?=htmlspecialchars($row['contact_person'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系电话</label>
            <input type="text" class="form-control" name="contact_phone" value="<?=htmlspecialchars($row['contact_phone'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系邮箱</label>
            <input type="email" class="form-control" name="contact_email" value="<?=htmlspecialchars($row['contact_email'])?>">
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark" value="<?=htmlspecialchars($row['remark'])?>">
        </div>
        <button type="submit" class="btn btn-primary">保存</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>