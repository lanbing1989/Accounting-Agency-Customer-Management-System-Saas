<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="platform_tenant_manage.php"><?=htmlspecialchars($platform_name)?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#platformNavbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="platformNavbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="platform_tenant_manage.php">租户管理</a></li>
        <li class="nav-item"><a class="nav-link" href="platform_package_manage.php">套餐维护</a></li>
        <li class="nav-item"><a class="nav-link" href="platform_admin_manage.php">管理员管理</a></li>
        <li class="nav-item"><a class="nav-link" href="platform_log_all.php">日志审计</a></li>
        <li class="nav-item"><a class="nav-link" href="platform_report.php">统计报表</a></li>
        <li class="nav-item"><a class="nav-link" href="platform_settings.php">系统设置</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">退出后台</a></li>
      </ul>
    </div>
  </div>
</nav>