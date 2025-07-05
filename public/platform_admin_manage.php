<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 新增管理员
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password_raw = trim($_POST['password']);
    if (!$username || !$password_raw) {
        $err = "账号和密码不能为空！";
    } else {
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM users WHERE username=? AND tenant_id=1");
        $stmt_check->execute([$username]);
        if ($stmt_check->fetchColumn()) {
            $err = "账号已存在！";
        } else {
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (tenant_id, username, password, role) VALUES (1, ?, ?, 'platform_admin')");
            $stmt->execute([$username, $password_hash]);
            $msg = "平台管理员账号“{$username}”添加成功。";
            // 日志
            @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
                ->execute([1, $_SESSION['user_id'], '新增平台管理员', "账号：{$username}"]);
        }
    }
}

// 禁用/启用管理员（用role=disabled禁用）
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $admin_id = intval($_GET['toggle']);
    // 不能操作自己
    if ($admin_id != $_SESSION['user_id']) {
        $stmt0 = $db->prepare("SELECT role,username FROM users WHERE id=? AND tenant_id=1");
        $stmt0->execute([$admin_id]);
        $admin_info = $stmt0->fetch(PDO::FETCH_ASSOC);
        if ($admin_info && $admin_info['role']==='platform_admin') {
            $new_role = 'disabled';
            $action_txt = '禁用';
        } elseif ($admin_info && $admin_info['role']==='disabled') {
            $new_role = 'platform_admin';
            $action_txt = '启用';
        } else {
            $new_role = null;
        }
        if ($new_role) {
            $stmt = $db->prepare("UPDATE users SET role=? WHERE id=? AND tenant_id=1");
            $stmt->execute([$new_role, $admin_id]);
            @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
                ->execute([1, $_SESSION['user_id'], '平台管理员状态变更', "账号：{$admin_info['username']}，状态变更为{$action_txt}"]);
        }
    }
    header("Location: platform_admin_manage.php");
    exit;
}

// 重置管理员密码
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pass'])) {
    $admin_id = intval($_POST['admin_id']);
    $new_pass = trim($_POST['new_password']);
    if ($new_pass) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmtu = $db->prepare("SELECT username FROM users WHERE id=? AND tenant_id=1");
        $stmtu->execute([$admin_id]);
        $admin_username = $stmtu->fetchColumn();
        $stmt = $db->prepare("UPDATE users SET password=? WHERE id=? AND tenant_id=1");
        $stmt->execute([$new_hash, $admin_id]);
        $msg = "管理员 {$admin_username} 密码已重置。";
        @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
            ->execute([1, $_SESSION['user_id'], '重置平台管理员密码', "账号：{$admin_username}"]);
    } else {
        $err = "新密码不能为空！";
    }
}

// 删除管理员（不能删自己）
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = intval($_GET['delete']);
    if ($admin_id != $_SESSION['user_id']) {
        $stmtu = $db->prepare("SELECT username FROM users WHERE id=? AND tenant_id=1");
        $stmtu->execute([$admin_id]);
        $admin_username = $stmtu->fetchColumn();
        $stmt = $db->prepare("DELETE FROM users WHERE id=? AND tenant_id=1 AND role='platform_admin'");
        $stmt->execute([$admin_id]);
        $msg = "管理员 {$admin_username} 已删除。";
        @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
            ->execute([1, $_SESSION['user_id'], '删除平台管理员', "账号：{$admin_username}"]);
    }
    header("Location: platform_admin_manage.php");
    exit;
}

// 管理员列表
$stmt = $db->prepare("SELECT id, username, role FROM users WHERE tenant_id=1");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>平台管理员管理</title>
  <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width:700px;">
  <h3 class="mb-3">平台管理员管理</h3>
  <?php if($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?=$msg?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if($err): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?=$err?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white p-3 rounded shadow-sm mb-4">
    <input type="hidden" name="add_admin" value="1">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <input class="form-control" name="username" required placeholder="管理员账号">
      </div>
      <div class="col-auto">
        <input class="form-control" name="password" type="password" required placeholder="初始密码">
      </div>
      <div class="col-auto">
        <button class="btn btn-success">新增管理员</button>
      </div>
    </div>
  </form>

  <table class="table table-bordered align-middle">
    <tr>
      <th>ID</th>
      <th>账号</th>
      <th>状态</th>
      <th>操作</th>
    </tr>
    <?php foreach ($admins as $a): ?>
    <tr>
      <td><?=htmlspecialchars($a['id'])?></td>
      <td><?=htmlspecialchars($a['username'])?></td>
      <td>
        <?php if($a['role']==='platform_admin'): ?>
          <span class="badge bg-success">启用</span>
        <?php elseif($a['role']==='disabled'): ?>
          <span class="badge bg-danger">禁用</span>
        <?php else: ?>
          <span class="badge bg-secondary"><?=$a['role']?></span>
        <?php endif;?>
      </td>
      <td>
        <?php if($a['role']==='platform_admin'||$a['role']==='disabled'): ?>
          <?php if($a['id']!=$_SESSION['user_id']): ?>
            <a href="?toggle=<?=$a['id']?>" class="btn btn-sm btn-warning" onclick="return confirm('确定切换该管理员状态？')">
              <?=$a['role']==='platform_admin'?'禁用':'启用'?>
            </a>
            <a href="?delete=<?=$a['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除该管理员？')">删除</a>
          <?php endif;?>
          <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#resetModal<?=$a['id']?>">重置密码</button>
          <!-- 重置密码弹窗 -->
          <div class="modal fade" id="resetModal<?=$a['id']?>" tabindex="-1">
            <div class="modal-dialog">
              <form method="post" class="modal-content">
                <input type="hidden" name="reset_pass" value="1">
                <input type="hidden" name="admin_id" value="<?=$a['id']?>">
                <div class="modal-header">
                  <h5 class="modal-title">重置密码：<?=htmlspecialchars($a['username'])?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input class="form-control" name="new_password" type="password" required placeholder="新密码">
                </div>
                <div class="modal-footer">
                  <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">取消</button>
                  <button class="btn btn-warning">确认重置</button>
                </div>
              </form>
            </div>
          </div>
        <?php else: ?>
          <span class="text-muted">-</span>
        <?php endif;?>
      </td>
    </tr>
    <?php endforeach;?>
  </table>
</div>
</body>
</html>