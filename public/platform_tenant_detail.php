<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

$tenant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$tenant_id) die('参数错误，缺少租户ID');

// 处理信息编辑保存（带详细日志）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tenant'])) {
    $tenant_name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $contact_phone = trim($_POST['contact_phone']);
    $contact_email = trim($_POST['contact_email']);
    // 取原值
    $stmt0 = $db->prepare("SELECT name,contact_person,contact_phone,contact_email FROM tenants WHERE id=?");
    $stmt0->execute([$tenant_id]);
    $old = $stmt0->fetch(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("UPDATE tenants SET name=?, contact_person=?, contact_phone=?, contact_email=? WHERE id=?");
    $stmt->execute([$tenant_name, $contact_person, $contact_phone, $contact_email, $tenant_id]);
    // 日志（详细变更前后）
    $detail = "原名称：{$old['name']}→{$tenant_name}；原联系人：{$old['contact_person']}→{$contact_person}；原电话：{$old['contact_phone']}→{$contact_phone}；原邮箱：{$old['contact_email']}→{$contact_email}";
    @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$tenant_id, $_SESSION['user_id'], "编辑租户信息", $detail]);
    header("Location: platform_tenant_detail.php?id=$tenant_id&msg=edit_success");
    exit;
}

// 处理重置租户管理员密码（含日志）
$reset_pass_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pass'])) {
    $admin_user_id = intval($_POST['admin_user_id']);
    $reset_password = trim($_POST['reset_password']);
    if (!$reset_password) {
        $reset_pass_msg = "密码不能为空！";
    } else {
        $reset_password_hash = password_hash($reset_password, PASSWORD_DEFAULT);
        // 取用户名
        $stmtu = $db->prepare("SELECT username FROM users WHERE id=? AND tenant_id=? AND role='admin'");
        $stmtu->execute([$admin_user_id, $tenant_id]);
        $admin_username = $stmtu->fetchColumn();
        $stmt = $db->prepare("UPDATE users SET password=? WHERE id=? AND tenant_id=? AND role='admin'");
        $stmt->execute([$reset_password_hash, $admin_user_id, $tenant_id]);
        // 日志
        @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
            ->execute([$tenant_id, $_SESSION['user_id'], "重置管理员密码", "管理员ID：{$admin_user_id}（账号：{$admin_username}）被重置密码"]);
        header("Location: platform_tenant_detail.php?id=$tenant_id&msg=reset_pass_success");
        exit;
    }
}

$stmt = $db->prepare("SELECT t.*, tp.name as package_name, tp.max_users, tp.max_clients, tp.max_agreements 
    FROM tenants t 
    LEFT JOIN tenant_packages tp ON t.package_id=tp.id
    WHERE t.id=:id");
$stmt->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tenant || !isset($tenant['id'])) die('租户不存在');

// 用户统计
$stmt = $db->prepare("SELECT id, username, role FROM users WHERE tenant_id=:id");
$stmt->bindValue(':id', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查找管理员账号（以role=admin为主，取第一个）
$admin_user = null;
foreach ($users as $u) {
    if ($u['role'] === 'admin') {
        $admin_user = $u;
        break;
    }
}

// 客户数统计
$stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE tenant_id=:tid");
$stmt->bindValue(':tid', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$client_count = $stmt->fetchColumn();

// 用户数统计
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE tenant_id=:tid");
$stmt->bindValue(':tid', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$user_count = $stmt->fetchColumn();

// 电子合同签署数统计
$stmt = $db->prepare("SELECT COUNT(*) FROM contracts_agreement WHERE tenant_id=:tid");
$stmt->bindValue(':tid', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$agreement_count = $stmt->fetchColumn();

// 获取套餐列表
$pkglist = [];
try {
    $pkglist = $db->query("SELECT * FROM tenant_packages")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $pkglist = [];
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>租户详情</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('platform_navbar.php');?>
<div class="container mt-4">
    <h3>租户详情：<?=htmlspecialchars($tenant['name'])?></h3>
    <?php if($msg==='edit_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            信息已保存！
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
        </div>
    <?php elseif($msg==='reset_pass_success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            管理员密码已重置！
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
        </div>
    <?php endif; ?>
    <?php if($reset_pass_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?=$reset_pass_msg?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['edit']) && $_GET['edit']==='1'): ?>
    <!-- 编辑表单模式 -->
    <form method="post" class="mb-4 bg-white p-3 rounded shadow-sm">
        <input type="hidden" name="edit_tenant" value="1">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">租户名称 <span class="text-danger">*</span></label>
                <input class="form-control" name="name" value="<?=htmlspecialchars($tenant['name'])?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">联系人</label>
                <input class="form-control" name="contact_person" value="<?=htmlspecialchars($tenant['contact_person'])?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">电话</label>
                <input class="form-control" name="contact_phone" value="<?=htmlspecialchars($tenant['contact_phone'])?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">邮箱</label>
                <input class="form-control" name="contact_email" value="<?=htmlspecialchars($tenant['contact_email'])?>">
            </div>
            <div class="col-md-12 mt-2">
                <button type="submit" class="btn btn-primary">保存</button>
                <a href="platform_tenant_detail.php?id=<?=$tenant_id?>" class="btn btn-secondary">取消</a>
            </div>
        </div>
    </form>
    <?php else: ?>
    <ul class="list-group mb-4">
        <li class="list-group-item"><b>ID：</b><?=htmlspecialchars($tenant['id'])?></li>
        <li class="list-group-item"><b>租户名称：</b><?=htmlspecialchars($tenant['name'])?></li>
        <li class="list-group-item"><b>套餐：</b><?=htmlspecialchars($tenant['package_name'] ?? '-')?></li>
        <li class="list-group-item"><b>套餐到期：</b><?=htmlspecialchars($tenant['package_expire'] ?? '-')?></li>
        <li class="list-group-item"><b>用户数/上限：</b><?=htmlspecialchars($user_count)?> / <?=isset($tenant['max_users']) && $tenant['max_users'] !== null ? $tenant['max_users'] : '-'?></li>
        <li class="list-group-item"><b>客户数/上限：</b><?=htmlspecialchars($client_count)?> / <?=isset($tenant['max_clients']) && $tenant['max_clients'] !== null ? $tenant['max_clients'] : '-'?></li>
        <li class="list-group-item"><b>电子合同签署数/上限：</b><?=htmlspecialchars($agreement_count)?> / <?=isset($tenant['max_agreements']) && $tenant['max_agreements'] !== null ? $tenant['max_agreements'] : '-'?></li>
        <li class="list-group-item"><b>联系人：</b><?=htmlspecialchars($tenant['contact_person'])?></li>
        <li class="list-group-item"><b>电话：</b><?=htmlspecialchars($tenant['contact_phone'])?></li>
        <li class="list-group-item"><b>邮箱：</b><?=htmlspecialchars($tenant['contact_email'])?></li>
        <li class="list-group-item"><b>状态：</b>
            <?php if(!empty($tenant['status'])): ?>
                <span class="badge bg-success">正常</span>
            <?php else: ?>
                <span class="badge bg-danger">禁用</span>
            <?php endif;?>
        </li>
        <li class="list-group-item"><b>创建时间：</b><?=htmlspecialchars($tenant['created_at'])?></li>
    </ul>
    <div class="mb-3">
        <a href="platform_tenant_detail.php?id=<?=$tenant_id?>&edit=1" class="btn btn-primary btn-sm">编辑租户信息</a>
    </div>
    <?php endif; ?>

    <?php if($admin_user): ?>
    <form method="post" class="mb-4 bg-white p-3 rounded shadow-sm" style="max-width:400px;">
        <input type="hidden" name="reset_pass" value="1">
        <input type="hidden" name="admin_user_id" value="<?=htmlspecialchars($admin_user['id'])?>">
        <div class="mb-2"><b>重置管理员密码（账号：<?=htmlspecialchars($admin_user['username'])?>）：</b></div>
        <div class="input-group">
            <input class="form-control" name="reset_password" type="password" required placeholder="新密码">
            <button class="btn btn-warning" type="submit" onclick="return confirm('确定要重置该管理员密码？');">重置密码</button>
        </div>
    </form>
    <?php endif; ?>

    <form method="post" class="mb-4" action="platform_tenant_package.php">
        <input type="hidden" name="tenant_id" value="<?=htmlspecialchars($tenant['id'])?>">
        <label>分配套餐：</label>
        <select name="package_id" class="form-select d-inline w-auto">
            <option value="0">未分配</option>
            <?php if(!empty($pkglist)): ?>
                <?php foreach($pkglist as $pkg): ?>
                <option value="<?=htmlspecialchars($pkg['id'])?>" <?=($tenant['package_id']==$pkg['id']?'selected':'')?>>
                    <?=htmlspecialchars($pkg['name'])?>
                    （用户上限<?=htmlspecialchars($pkg['max_users'])?>，客户上限<?=htmlspecialchars($pkg['max_clients'])?>，电子合同签署上限<?=htmlspecialchars($pkg['max_agreements'])?>）
                </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <label>到期：</label>
        <input type="date" name="package_expire" value="<?=htmlspecialchars($tenant['package_expire'] ?? '')?>">
        <button type="submit" class="btn btn-primary btn-sm">保存</button>
    </form>
    <h5>该租户下用户列表：</h5>
    <table class="table table-bordered mb-4">
        <tr><th>ID</th><th>用户名</th><th>角色</th></tr>
        <?php if(!empty($users)): ?>
            <?php foreach($users as $u):?>
            <tr>
                <td><?=htmlspecialchars($u['id'])?></td>
                <td><?=htmlspecialchars($u['username'])?></td>
                <td><?=htmlspecialchars($u['role'])?></td>
            </tr>
            <?php endforeach;?>
        <?php else: ?>
            <tr><td colspan="3" class="text-muted text-center">暂无用户</td></tr>
        <?php endif;?>
    </table>
    <div class="mb-4">
        <a href="platform_tenant_manage.php" class="btn btn-secondary btn-sm me-2">返回租户列表</a>
        <a href="platform_tenant_log.php?id=<?=htmlspecialchars($tenant['id'])?>" class="btn btn-info btn-sm me-2">查看日志</a>
        <a href="platform_tenant_export.php?id=<?=htmlspecialchars($tenant['id'])?>" class="btn btn-secondary btn-sm">导出数据</a>
    </div>
</div>
</body>
</html>