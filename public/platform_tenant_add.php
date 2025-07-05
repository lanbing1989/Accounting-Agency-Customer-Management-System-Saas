<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 获取套餐列表
$packages = $db->query("SELECT * FROM tenant_packages ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$add_success_msg = '';
$add_error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tenant'])) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_phone = trim($_POST['contact_phone']);
    $contact_email = trim($_POST['contact_email']);
    $package_id = intval($_POST['package_id']);
    $package_expire = trim($_POST['package_expire']);
    $admin_username = trim($_POST['admin_username']);
    $admin_password_raw = trim($_POST['admin_password']);

    if (!$name || !$admin_username || !$admin_password_raw) {
        $add_error_msg = "租户名称、管理员账号、管理员密码均不能为空！";
    } elseif (!$package_expire || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $package_expire)) {
        $add_error_msg = "请填写有效的套餐到期日期（格式：YYYY-MM-DD）！";
    } else {
        // 校验用户名唯一性
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $stmt_check->execute([$admin_username]);
        $user_exist = $stmt_check->fetchColumn();
        if ($user_exist) {
            $add_error_msg = "管理员账号 '{$admin_username}' 已被占用，请更换！";
        } else {
            // 新增租户
            $stmt = $db->prepare("INSERT INTO tenants (name, contact_person, contact_phone, contact_email, created_at, status, is_deleted, package_id, package_expire) VALUES (:name,:person,:phone,:email, NOW(), 1, 0, :pkg, :expire)");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':person', $contact_person, PDO::PARAM_STR);
            $stmt->bindValue(':phone', $contact_phone, PDO::PARAM_STR);
            $stmt->bindValue(':email', $contact_email, PDO::PARAM_STR);
            $stmt->bindValue(':pkg', $package_id, PDO::PARAM_INT);
            $stmt->bindValue(':expire', $package_expire, PDO::PARAM_STR);
            $stmt->execute();
            $tenant_id = $db->lastInsertId();

            // 创建管理员账号
            $admin_password_hash = password_hash($admin_password_raw, PASSWORD_DEFAULT);
            $stmt_user = $db->prepare("INSERT INTO users (tenant_id, username, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt_user->execute([$tenant_id, $admin_username, $admin_password_hash]);

            // 写入操作日志
            @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
                ->execute([
                    $tenant_id,
                    $_SESSION['user_id'],
                    '新增租户',
                    "租户名称：{$name}，联系人：{$contact_person}，电话：{$contact_phone}，邮箱：{$contact_email}，分配套餐ID：{$package_id}，到期：{$package_expire}，初始管理员：{$admin_username}"
                ]);

            $add_success_msg = "租户“{$name}”添加成功，初始管理员账号：{$admin_username}，密码：{$admin_password_raw}";

            // 跳转到租户管理页并闪回提示
            $_SESSION['tenant_add_success_msg'] = $add_success_msg;
            header("Location: platform_tenant_manage.php");
            exit;
        }
    }
}

include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>新增租户</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        .form-section-title {font-size:1.15rem;font-weight:bold;margin-top:2rem;margin-bottom:1rem;}
        .package-desc {font-size:0.95em;color:#666;}
    </style>
</head>
<body class="bg-light">
<div class="container" style="max-width:700px;margin-top:40px;">
    <h3 class="mb-4">新增租户</h3>
    <?php if($add_error_msg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?=$add_error_msg?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="post" class="bg-white p-4 rounded shadow-sm">
        <input type="hidden" name="add_tenant" value="1">

        <div class="form-section-title">基本信息</div>
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label">企业/租户名称 <span class="text-danger">*</span></label>
                <input class="form-control" name="name" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">套餐分配 <span class="text-danger">*</span></label>
                <select name="package_id" class="form-select" required>
                    <option value="0">无套餐</option>
                    <?php foreach($packages as $pkg):?>
                    <option value="<?=$pkg['id']?>">
                        <?=htmlspecialchars($pkg['name'])?>
                        <?php
                            $desc = [];
                            if ($pkg['max_users']) $desc[] = "用户上限{$pkg['max_users']}";
                            if ($pkg['max_clients']) $desc[] = "客户上限{$pkg['max_clients']}";
                            if ($pkg['max_agreements']) $desc[] = "合同上限{$pkg['max_agreements']}";
                            if (isset($pkg['price']) && $pkg['price'] !== null && $pkg['price'] !== '') $desc[] = "￥{$pkg['price']}元/年";
                            if ($desc) echo '（'.implode('，', $desc).'）';
                        ?>
                    </option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label">套餐到期日 <span class="text-danger">*</span></label>
                <input class="form-control" name="package_expire" type="date" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">联系人</label>
                <input class="form-control" name="contact_person">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label">联系电话</label>
                <input class="form-control" name="contact_phone">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">联系邮箱</label>
                <input class="form-control" name="contact_email" type="email">
            </div>
        </div>

        <div class="form-section-title">管理员账号</div>
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label">管理员账号 <span class="text-danger">*</span></label>
                <input class="form-control" name="admin_username" required autocomplete="new-username">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">管理员密码 <span class="text-danger">*</span></label>
                <input class="form-control" name="admin_password" required type="password" autocomplete="new-password">
            </div>
        </div>
        <div class="mb-3 text-end">
            <button class="btn btn-success px-4">提交新增</button>
            <a href="platform_tenant_manage.php" class="btn btn-secondary ms-2">返回</a>
        </div>
    </form>
</div>
</body>
</html>