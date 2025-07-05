<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__.'/platform_utils.php';

// 获取平台系统名字
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');

// 显示套餐相关信息（需要已在 session 中存储套餐名和到期时间，否则可从数据库查）
$package_name = $_SESSION['package_name'] ?? '';
$package_expire = $_SESSION['package_expire'] ?? '';

// 若未设置套餐名，可根据 package_id 转换为中文名（演示用）
if (!$package_name && isset($_SESSION['package_id'])) {
  switch ($_SESSION['package_id']) {
    case 1: $package_name = '标准版'; break;
    case 2: $package_name = '高级版'; break;
    case 3: $package_name = '旗舰版'; break;
    default: $package_name = '未知套餐'; break;
  }
}

// 平台超级管理员不显示套餐信息
$is_platform_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'platform_admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php"><?=htmlspecialchars($platform_name)?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <!-- 显示租户名称/企业名称 -->
        <?php if (isset($_SESSION['tenant_name']) && $_SESSION['tenant_name']): ?>
          <li class="nav-item">
            <span class="nav-link disabled" style="color:#fff;font-weight:bold;">
              <?php echo htmlspecialchars($_SESSION['tenant_name']); ?>
            </span>
          </li>
        <?php endif; ?>

        <!-- 套餐信息作为超链接跳转到详情页面（平台超管不显示） -->
        <?php if (!$is_platform_admin && $package_name && $package_expire): ?>
          <li class="nav-item">
            <a class="nav-link" href="user_package_detail.php" title="查看套餐详情" style="color:#ffe28a;">
              <?php echo htmlspecialchars($package_name); ?>（到期：<?php echo htmlspecialchars($package_expire); ?>）
            </a>
          </li>
        <?php endif; ?>

        <!-- 仅平台超级管理员显示后台管理入口 -->
        <?php if ($is_platform_admin): ?>
          <li class="nav-item">
            <a class="nav-link" href="/platform_tenant_manage.php" target="_blank">后台管理</a>
          </li>
        <?php endif; ?>

        <!-- 客户管理 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            客户管理
          </a>
          <ul class="dropdown-menu" aria-labelledby="customerDropdown">
            <li><a class="dropdown-item" href="index.php">客户列表</a></li>
            <li><a class="dropdown-item" href="contract_add.php">新增客户</a></li>
            <li><a class="dropdown-item" href="temp_payment.php">临时收费</a></li>
          </ul>
        </li>
        <!-- 提醒通知 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="remindDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            提醒通知
          </a>
          <ul class="dropdown-menu" aria-labelledby="remindDropdown">
            <li><a class="dropdown-item" href="expire_remind.php">到期提醒</a></li>
            <li><a class="dropdown-item" href="remind_list.php">催收提醒</a></li>
          </ul>
        </li>
        <!-- 申报标记 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            申报标记
          </a>
          <ul class="dropdown-menu" aria-labelledby="reportDropdown">
            <li><a class="dropdown-item" href="tax_report.php">报税登记</a></li>
            <li><a class="dropdown-item" href="annual_report.php">年报登记</a></li>
          </ul>
        </li>
        <!-- 电子合同 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="contractDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            电子合同
          </a>
          <ul class="dropdown-menu" aria-labelledby="contractDropdown">
            <li><a class="dropdown-item" href="ht_agreements.php">合同管理</a></li>
            <li><a class="dropdown-item" href="ht_contract_templates.php">合同模板</a></li>
            <li><a class="dropdown-item" href="ht_seal_templates.php">签章管理</a></li>
          </ul>
        </li>
        <!-- 其它 -->
              <!-- 电子合同 -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="contractDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            其它
          </a>
          <ul class="dropdown-menu" aria-labelledby="contractDropdown">
            <li><a class="dropdown-item" href="user_profile.php">修改密码</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a class="dropdown-item" href="user_manage.php">用户管理</a></li>
            <li><a class="dropdown-item" href="export_all_data.php">导出数据</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="logout.php">退出</a></li>
      </ul>
    </div>
  </div>
</nav>