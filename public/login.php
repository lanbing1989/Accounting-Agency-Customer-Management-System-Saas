<?php
session_start();
require_once __DIR__.'/db.php';
require_once __DIR__.'/platform_utils.php';

// 获取平台名称和备案号，如果未设置则给默认值
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_icp = get_platform_setting($db, 'platform_icp', '');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // PDO参数绑定防SQL注入
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 防止会话固定攻击，登录成功重置会话ID
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id']; // 多租户：写入租户ID
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        // 建议：写入企业名称到 session，供 navbar 显示
        if (isset($user['tenant_id'])) {
            $stmt2 = $db->prepare("SELECT name FROM tenants WHERE id = ?");
            $stmt2->execute([$user['tenant_id']]);
            $tenant_name = $stmt2->fetchColumn();
            if ($tenant_name) {
                $_SESSION['tenant_name'] = $tenant_name;
            }
        }
        header('Location: index.php');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title><?=htmlspecialchars($platform_name)?> | 登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #f7fafd;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px 0 rgba(60,84,120,.13);
            margin: 80px auto 0;
            max-width: 370px;
            padding: 32px 26px 24px 26px;
        }
        .login-title {
            text-align: center;
            font-size: 1.45rem;
            font-weight: 600;
            color: #3655b3;
            margin-bottom: 28px;
            letter-spacing: 1px;
        }
        .form-control {
            border-radius: 6px;
            border-color: #e6e9ef;
            font-size: 1.06rem;
        }
        .form-control:focus {
            border-color: #3655b3;
            box-shadow: 0 0 0 2px #3655b366;
        }
        .btn-primary {
            background: #3655b3;
            border-color: #3655b3;
            font-weight: 500;
            letter-spacing: 1.5px;
            font-size: 1.08rem;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: #194089;
            border-color: #194089;
        }
        .alert-danger {
            text-align: center;
            font-size: 0.98rem;
            border-radius: 6px;
        }
        .footer {
            text-align: center;
            color: #b0b6cb;
            font-size: 0.95rem;
            margin-top: 48px;
            letter-spacing: 0.5px;
        }
        .beian {
            margin-top: 7px;
            font-size: 0.86rem;
            color: #b0b6cb;
        }
        @media (max-width: 480px) {
            .login-panel {padding: 14px 3vw;}
            .footer {font-size: 0.92rem;}
        }
    </style>
</head>
<body>
<div class="login-panel">
    <div class="login-title"><?=htmlspecialchars($platform_name)?></div>
    <?php if($error): ?>
        <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <input type="text" name="username" class="form-control" placeholder="用户名" required autofocus>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="密码" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
    </form>
</div>
<div class="footer">
    &copy; <?=date('Y')?> <?=htmlspecialchars($platform_name)?> 版权所有
    <div class="beian">
        <?php if($platform_icp): ?>
            <a href="https://beian.miit.gov.cn/" target="_blank" style="color:#b0b6cb;text-decoration:none;"><?=htmlspecialchars($platform_icp)?></a>
        <?php else: ?>
            <a href="https://beian.miit.gov.cn/" target="_blank" style="color:#b0b6cb;text-decoration:none;">填写您的备案号</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>