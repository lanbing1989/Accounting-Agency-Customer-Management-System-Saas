<?php
session_start();
// 清理所有会话数据
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>已退出登录</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:400px;margin-top:80px;">
    <div class="card shadow">
        <div class="card-body text-center">
            <h3 class="mb-4 text-success">您已成功退出</h3>
            <a href="login.php" class="btn btn-primary">重新登录</a>
        </div>
    </div>
</div>
</body>
</html>