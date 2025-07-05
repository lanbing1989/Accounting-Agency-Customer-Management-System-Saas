<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$error = '';

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $client_name = trim($_POST['client_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    // 检查客户名称是否已存在（当前租户下）
    $checkStmt = $db->prepare("SELECT COUNT(*) as cnt FROM contracts WHERE client_name = :client_name AND tenant_id = :tenant_id");
    $checkStmt->bindValue(':client_name', $client_name, PDO::PARAM_STR);
    $checkStmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $checkStmt->execute();
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $exists = $row['cnt'] ?? 0;

    if ($exists) {
        $error = '客户名称已存在，请勿重复添加。';
    } else {
        $stmt = $db->prepare("INSERT INTO contracts (tenant_id, client_name, contact_person, contact_phone, contact_email, remark) VALUES (:tenant_id, :client_name, :contact_person, :contact_phone, :contact_email, :remark)");
        $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt->bindValue(':client_name', $client_name, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person', $contact_person, PDO::PARAM_STR);
        $stmt->bindValue(':contact_phone', $contact_phone, PDO::PARAM_STR);
        $stmt->bindValue(':contact_email', $contact_email, PDO::PARAM_STR);
        $stmt->bindValue(':remark', $remark, PDO::PARAM_STR);
        $stmt->execute();
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新增客户</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container mt-4">
    <h2 class="mb-4">新增客户</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label class="form-label">客户名称</label>
            <input type="text" class="form-control" name="client_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">联系人</label>
            <input type="text" class="form-control" name="contact_person">
        </div>
        <div class="mb-3">
            <label class="form-label">联系电话</label>
            <input type="text" class="form-control" name="contact_phone">
        </div>
        <div class="mb-3">
            <label class="form-label">联系邮箱</label>
            <input type="email" class="form-control" name="contact_email">
        </div>
        <div class="mb-3">
            <label class="form-label">备注</label>
            <input type="text" class="form-control" name="remark">
        </div>
        <button type="submit" class="btn btn-success">保存</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </form>
</div>
</body>
</html>