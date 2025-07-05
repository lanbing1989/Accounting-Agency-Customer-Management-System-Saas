<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 处理套餐新增
$add_success_msg = '';
$add_error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package'])) {
    $name = trim($_POST['name']);
    $max_users = intval($_POST['max_users']);
    $max_clients = intval($_POST['max_clients']);
    $max_agreements = intval($_POST['max_agreements']);
    $price = trim($_POST['price']);
    if (!$name) {
        $add_error_msg = '套餐名称不能为空！';
    } elseif ($price !== '' && !is_numeric($price)) {
        $add_error_msg = '价格必须为数字！';
    } else {
        $stmt = $db->prepare("INSERT INTO tenant_packages (name, max_users, max_clients, max_agreements, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $max_users, $max_clients, $max_agreements, ($price === '' ? null : $price)]);
        $add_success_msg = "套餐“{$name}”添加成功！";
    }
}

// 处理套餐编辑
$edit_success_msg = '';
$edit_error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_package'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $max_users = intval($_POST['max_users']);
    $max_clients = intval($_POST['max_clients']);
    $max_agreements = intval($_POST['max_agreements']);
    $price = trim($_POST['price']);
    if (!$name) {
        $edit_error_msg = '套餐名称不能为空！';
    } elseif ($price !== '' && !is_numeric($price)) {
        $edit_error_msg = '价格必须为数字！';
    } else {
        $stmt = $db->prepare("UPDATE tenant_packages SET name=?, max_users=?, max_clients=?, max_agreements=?, price=? WHERE id=?");
        $stmt->execute([$name, $max_users, $max_clients, $max_agreements, ($price === '' ? null : $price), $id]);
        $edit_success_msg = "套餐“{$name}”修改成功！";
    }
}

// 处理套餐删除
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    $db->prepare("DELETE FROM tenant_packages WHERE id=?")->execute([$pid]);
    header("Location: platform_package_manage.php");
    exit;
}

// 查询套餐列表
$packages = $db->query("SELECT * FROM tenant_packages ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// 编辑套餐时获取旧数据
$edit_package = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    foreach ($packages as $p) {
        if ($p['id'] == intval($_GET['edit'])) {
            $edit_package = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>套餐管理</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<?php include('platform_navbar.php'); ?>
<div class="container mt-4">
    <h3>套餐管理</h3>
    <?php if($add_success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?=$add_success_msg?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button></div>
    <?php endif; ?>
    <?php if($add_error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?=$add_error_msg?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button></div>
    <?php endif; ?>
    <?php if($edit_success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?=$edit_success_msg?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button></div>
    <?php endif; ?>
    <?php if($edit_error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?=$edit_error_msg?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button></div>
    <?php endif; ?>

    <?php if($edit_package): ?>
    <!-- 套餐编辑表单 -->
    <form method="post" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm">
        <input type="hidden" name="edit_package" value="1">
        <input type="hidden" name="id" value="<?=htmlspecialchars($edit_package['id'])?>">
        <div class="col-auto"><input class="form-control" name="name" placeholder="套餐名称" value="<?=htmlspecialchars($edit_package['name'])?>" required></div>
        <div class="col-auto"><input class="form-control" name="max_users" placeholder="最大用户数" type="number" min="0" value="<?=htmlspecialchars($edit_package['max_users'])?>"></div>
        <div class="col-auto"><input class="form-control" name="max_clients" placeholder="最大客户数" type="number" min="0" value="<?=htmlspecialchars($edit_package['max_clients'])?>"></div>
        <div class="col-auto"><input class="form-control" name="max_agreements" placeholder="最大合同数" type="number" min="0" value="<?=htmlspecialchars($edit_package['max_agreements'])?>"></div>
        <div class="col-auto"><input class="form-control" name="price" placeholder="价格（元/年）" type="number" min="0" step="0.01" value="<?=htmlspecialchars($edit_package['price'] ?? '')?>"></div>
        <div class="col-auto"><button class="btn btn-primary">保存修改</button></div>
        <div class="col-auto"><a href="platform_package_manage.php" class="btn btn-secondary">取消</a></div>
    </form>
    <?php else: ?>
    <!-- 套餐新增表单 -->
    <form method="post" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm">
        <input type="hidden" name="add_package" value="1">
        <div class="col-auto"><input class="form-control" name="name" placeholder="套餐名称" required></div>
        <div class="col-auto"><input class="form-control" name="max_users" placeholder="最大用户数" type="number" min="0"></div>
        <div class="col-auto"><input class="form-control" name="max_clients" placeholder="最大客户数" type="number" min="0"></div>
        <div class="col-auto"><input class="form-control" name="max_agreements" placeholder="最大合同数" type="number" min="0"></div>
        <div class="col-auto"><input class="form-control" name="price" placeholder="价格（元/年）" type="number" min="0" step="0.01"></div>
        <div class="col-auto"><button class="btn btn-success">新增套餐</button></div>
    </form>
    <?php endif; ?>

    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>套餐名称</th>
            <th>最大用户数</th>
            <th>最大客户数</th>
            <th>最大合同数</th>
            <th>价格（元/年）</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($packages as $p): ?>
        <tr>
            <td><?=$p['id']?></td>
            <td><?=htmlspecialchars($p['name'])?></td>
            <td><?=$p['max_users']?></td>
            <td><?=$p['max_clients']?></td>
            <td><?=$p['max_agreements']?></td>
            <td><?=is_null($p['price']) ? '面议' : htmlspecialchars($p['price'])?></td>
            <td>
                <a href="?edit=<?=$p['id']?>" class="btn btn-sm btn-primary">编辑</a>
                <a href="?delete=<?=$p['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除此套餐？')">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>