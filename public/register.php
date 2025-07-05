<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$success = '';

// 获取标准版套餐ID和信息
$stmt_pkg = $db->prepare("SELECT id, name, price FROM tenant_packages WHERE name=? LIMIT 1");
$stmt_pkg->execute(['标准版']);
$pkg_row = $stmt_pkg->fetch(PDO::FETCH_ASSOC);
$package_id = $pkg_row ? intval($pkg_row['id']) : 1;
$package_name = $pkg_row ? $pkg_row['name'] : '标准版';
$package_price = $pkg_row ? $pkg_row['price'] : 0;

// 试用期3天
$package_expire = date('Y-m-d', strtotime('+3 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_name = trim($_POST['tenant_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    // 验证码校验
    if (!$captcha || !isset($_SESSION['captcha_code']) || strtolower($captcha) !== strtolower($_SESSION['captcha_code'])) {
        $error = "验证码错误，请重新输入！";
    // 基本校验
    } elseif (!$tenant_name || !$contact_person || !$contact_phone || !$contact_email || !$admin_username || !$admin_password) {
        $error = "所有字段均为必填项！";
    } else {
        // 检查租户名是否重复
        $stmt = $db->prepare("SELECT COUNT(*) FROM tenants WHERE name=?");
        $stmt->execute([$tenant_name]);
        if ($stmt->fetchColumn() > 0) {
            $error = "租户名称已存在，请更换。";
        } else {
            // 检查管理员账号是否重复
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
            $stmt->execute([$admin_username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "管理员账号已存在，请更换。";
            } else {
                // 创建租户
                $stmt = $db->prepare("INSERT INTO tenants (name, contact_person, contact_phone, contact_email, status, package_id, package_expire, created_at) VALUES (?, ?, ?, ?, 1, ?, ?, NOW())");
                $stmt->execute([$tenant_name, $contact_person, $contact_phone, $contact_email, $package_id, $package_expire]);
                $tenant_id = $db->lastInsertId();

                // 创建租户管理员账号
                $admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (tenant_id, username, password, role) VALUES (?, ?, ?, 'tenant_admin')");
                $stmt->execute([$tenant_id, $admin_username, $admin_password_hash]);

                // 系统日志
                $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, NULL, ?, ?, NOW())")
                  ->execute([$tenant_id, '租户注册', "注册成功，联系人：{$contact_person}，手机号：{$contact_phone}"]);

                $success = "注册成功，您的租户账号已创建！已获得 <b>标准版</b> <b>3天试用期</b>，试用结束后请购买套餐以继续使用。<br><br><a href='login.php' class='btn btn-success mt-2'>去登录</a>";
            }
        }
    }
    // 防止验证码复用
    unset($_SESSION['captcha_code']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>租户注册 - SaaS平台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
            min-height: 100vh;
        }
        .register-card {
            max-width: 680px;
            margin: 60px auto 0 auto;
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(60,120,240,0.08), 0 1.5px 3px rgba(60,120,240,0.07);
            overflow: hidden;
            background: #fff;
        }
        .register-header {
            background: linear-gradient(90deg, #3183f4 0%, #6dd5ed 100%);
            color: #fff;
            text-align: center;
            padding: 32px 10px 18px 10px;
        }
        .register-header h3 {
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .register-header .subtitle {
            font-weight: 400;
            font-size: 1.13rem;
            letter-spacing: 1px;
        }
        .card-body {
            padding: 2.2rem 3.5rem 1.7rem 3.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #3183f4;
        }
        .form-control:focus {
            border-color: #3183f4;
            box-shadow: 0 0 0 2px #3183f420;
        }
        .alert-info {
            background: #e8f5fe;
            color: #1e88e5;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(90deg, #3183f4 0%, #6dd5ed 100%);
            border: none;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .btn-primary:hover, .btn-success:hover {
            background: #3183f4 !important;
        }
        .btn-success {
            background: linear-gradient(90deg, #21c98f 0%, #31b4f4 100%);
            border: none;
            font-weight: 600;
            letter-spacing: 1px;
        }
        @media (max-width: 900px) {
            .register-card {max-width: 98vw;}
            .card-body {padding: 1.1rem 0.8rem;}
        }
        @media (max-width: 600px) {
            .register-card {margin: 18px 0;}
            .register-header {padding: 20px 0 10px 0;}
            .card-body {padding: 0.8rem;}
        }
        .captcha-img {
            cursor: pointer;
            border-radius: 7px;
            border: 1px solid #e0eaf3;
            margin-left: 8px;
            vertical-align: middle;
            box-shadow: 0 1px 3px rgba(49,131,244,0.07);
        }
    </style>
</head>
<body>
<div class="register-card shadow">
    <div class="register-header">
        <h3>租户注册</h3>
        <div class="subtitle">
            注册即送 <b>标准版</b> <b>3天免费试用</b>
        </div>
    </div>
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger text-center"><?=$error?></div>
        <?php endif;?>
        <?php if($success): ?>
            <div class="alert alert-success text-center"><?=$success?></div>
        <?php else: ?>
        <div class="alert alert-info mb-3 text-center">
            标准版套餐试用期为 <b>3天</b>，试用结束后请购买套餐以继续使用。
        </div>
        <form method="post" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">租户名称</label>
                    <input type="text" class="form-control" name="tenant_name" maxlength="32" required placeholder="公司/团队名称">
                </div>
                <div class="col-md-6">
                    <label class="form-label">联系人</label>
                    <input type="text" class="form-control" name="contact_person" maxlength="16" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">联系电话</label>
                    <input type="text" class="form-control" name="contact_phone" maxlength="20" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">联系邮箱</label>
                    <input type="email" class="form-control" name="contact_email" maxlength="50" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">管理员账号</label>
                    <input type="text" class="form-control" name="admin_username" maxlength="20" required placeholder="建议用手机或邮箱">
                </div>
                <div class="col-md-6">
                    <label class="form-label">管理员密码</label>
                    <input type="password" class="form-control" name="admin_password" maxlength="32" required autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label class="form-label">验证码</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="captcha" maxlength="6" required placeholder="输入图片验证码">
                        <img src="captcha.php?<?=time()?>" class="captcha-img" alt="验证码" title="点击刷新" onclick="this.src='captcha.php?'+Math.random()">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">试用套餐</label>
                    <input type="text" class="form-control" value="<?=$package_name?>（<?=$package_price?>元/年）" disabled>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fs-5 mt-4">注册并开启试用</button>
        </form>
        <?php endif;?>
    </div>
</div>
</body>
</html>