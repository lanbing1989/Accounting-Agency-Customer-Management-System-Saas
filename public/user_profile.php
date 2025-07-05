<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 查询当前用户信息（仅本租户）
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND tenant_id=:tid");
    $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 验证原密码
    if (!$user || !password_verify($old_password, $user['password'])) {
        $error = '原密码错误';
    } elseif ($new_password && $new_password !== $confirm_password) {
        $error = '新密码与确认密码不一致';
    } elseif ($new_username === '') {
        $error = '用户名不能为空';
    } else {
        // 检查用户名是否被他人占用（仅本租户）
        if ($new_username !== $username) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username AND tenant_id=:tid");
            $stmt->bindValue(':username', $new_username, PDO::PARAM_STR);
            $stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
            $stmt->execute();
            $existUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existUser && $existUser['id'] != $user_id) {
                $error = '用户名已被占用';
            }
        }
        // 修改信息
        if (!$error) {
            if ($new_password) {
                $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = :username, password = :password WHERE id = :id AND tenant_id=:tid");
                $stmt->bindValue(':password', $new_password_hashed, PDO::PARAM_STR);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = :username WHERE id = :id AND tenant_id=:tid");
            }
            $stmt->bindValue(':username', $new_username, PDO::PARAM_STR);
            $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':tid', $_SESSION['tenant_id'], PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['username'] = $new_username;
            $message = '修改成功';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>修改账户信息</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('navbar.php'); ?>
<div class="container" style="max-width:500px;margin-top:50px;">
    <div class="card">
        <div class="card-header">修改用户名和密码</div>
        <div class="card-body">
            <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
            <?php if($message): ?><div class="alert alert-success"><?=$message?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label>用户名</label>
                    <input type="text" name="username" class="form-control" required value="<?=htmlspecialchars($_SESSION['username'])?>">
                </div>
                <div class="mb-3">
                    <label>原密码</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>新密码（不修改请留空）</label>
                    <input type="password" name="new_password" class="form-control">
                </div>
                <div class="mb-3">
                    <label>确认新密码</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">保存修改</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>