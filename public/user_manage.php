<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

if ($_SESSION['role'] !== 'admin') die('仅管理员可用！');

// 新增用户（仅本租户）
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add_user'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    if ($u && $p) {
        $stmt = $db->prepare("INSERT INTO users (tenant_id, username, password) VALUES (:tid, :u, :p)");
        $stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $stmt->bindValue(':u', $u, PDO::PARAM_STR);
        $stmt->bindValue(':p', password_hash($p, PASSWORD_DEFAULT), PDO::PARAM_STR);
        @$stmt->execute();
    }
}
// 删除用户（仅本租户，不允许删admin）
if (isset($_GET['del']) && $_GET['del']!=='admin') {
    $stmt = $db->prepare("DELETE FROM users WHERE username=:u AND tenant_id=:tid");
    $stmt->bindValue(':u', $_GET['del'], PDO::PARAM_STR);
    $stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
}
$users = [];
$stmt = $db->prepare("SELECT username FROM users WHERE tenant_id=:tid");
$stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $users[] = $row['username'];
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>用户管理</title>
<link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php');?>
<div class="container mt-4">
<h3>用户管理</h3>
<form method="post" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="username" class="form-control" placeholder="用户名" required></div>
    <div class="col-auto"><input type="password" name="password" class="form-control" placeholder="密码" required></div>
    <div class="col-auto"><button type="submit" name="add_user" class="btn btn-success">新增用户</button></div>
</form>
<table class="table table-bordered">
    <tr><th>用户名</th><th>操作</th></tr>
    <?php foreach($users as $u):?>
    <tr>
        <td><?=htmlspecialchars($u)?></td>
        <td>
            <?php if($u!=='admin'):?>
            <a href="?del=<?=urlencode($u)?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除?')">删除</a>
            <?php else:?>
            <span class="text-muted">系统管理员</span>
            <?php endif;?>
        </td>
    </tr>
    <?php endforeach;?>
</table>
</div>
</body>
</html>