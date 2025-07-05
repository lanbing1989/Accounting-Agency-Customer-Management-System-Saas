<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

function get_count($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// 统计数据
$total_tenants = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0");
$today_tenants = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND DATE(created_at)=CURDATE()");
$month_tenants = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");

$total_users = get_count($db, "SELECT COUNT(*) FROM users");
$total_admins = get_count($db, "SELECT COUNT(*) FROM users WHERE role='admin'");
$total_platform_admins = get_count($db, "SELECT COUNT(*) FROM users WHERE tenant_id=1 AND role='platform_admin'");
$total_contracts = get_count($db, "SELECT COUNT(*) FROM contracts");
$total_agreement = get_count($db, "SELECT COUNT(*) FROM contracts_agreement");

$total_expiring = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND package_expire IS NOT NULL AND package_expire<=DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND package_expire>=CURDATE()");
$total_expired = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND package_expire IS NOT NULL AND package_expire<CURDATE()");

// 新增：客户总数、合同总数
$total_clients = $total_contracts; // contracts表，每条为一个客户
$total_contracts_all = $total_agreement; // contracts_agreement表，每条为一合同签署

// 客户数TOP10
$stmt = $db->query("SELECT t.name, COUNT(c.id) as client_count 
    FROM tenants t LEFT JOIN contracts c ON t.id=c.tenant_id 
    WHERE t.is_deleted=0 
    GROUP BY t.id ORDER BY client_count DESC LIMIT 10");
$client_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 合同数TOP10
$stmt = $db->query("SELECT t.name, COUNT(a.id) as contract_count 
    FROM tenants t LEFT JOIN contracts_agreement a ON t.id=a.tenant_id 
    WHERE t.is_deleted=0 
    GROUP BY t.id ORDER BY contract_count DESC LIMIT 10");
$contract_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 套餐分布
$pkgs = $db->query("SELECT id, name FROM tenant_packages")->fetchAll(PDO::FETCH_ASSOC);
$pkg_count = [];
foreach ($pkgs as $pkg) {
    $pkg_count[$pkg['id']] = [
        'name' => $pkg['name'],
        'count' => get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND package_id=?", [$pkg['id']])
    ];
}
$pkg_count[0] = [
    'name' => '未分配',
    'count' => get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND (package_id IS NULL OR package_id=0)")
];

// 租户增长趋势（近6个月）
$trend_labels = [];
$trend_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $trend_labels[] = $month;
    $trend_data[] = get_count($db, "SELECT COUNT(*) FROM tenants WHERE is_deleted=0 AND DATE_FORMAT(created_at,'%Y-%m')=?", [$month]);
}

// 用户TOP10租户
$stmt = $db->query("SELECT t.name, COUNT(u.id) as user_count 
    FROM tenants t LEFT JOIN users u ON t.id=u.tenant_id 
    WHERE t.is_deleted=0 
    GROUP BY t.id ORDER BY user_count DESC LIMIT 10");
$user_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>平台统计报表</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-stat .card-body { font-size: 1.4rem; }
        .card-stat .card-title { font-size: 1rem; font-weight: 400; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3 class="mb-4">平台统计报表</h3>
    <div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
        <div class="col">
            <div class="card card-stat shadow-sm border-primary">
                <div class="card-body">
                    <div class="card-title text-secondary">租户总数</div>
                    <div class="fw-bold text-primary"><?=$total_tenants?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-success">
                <div class="card-body">
                    <div class="card-title text-secondary">本月新增租户</div>
                    <div class="fw-bold text-success"><?=$month_tenants?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-warning">
                <div class="card-body">
                    <div class="card-title text-secondary">今日新增租户</div>
                    <div class="fw-bold text-warning"><?=$today_tenants?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-info">
                <div class="card-body">
                    <div class="card-title text-secondary">用户总数</div>
                    <div class="fw-bold text-info"><?=$total_users?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-secondary">
                <div class="card-body">
                    <div class="card-title text-secondary">租户管理员数</div>
                    <div class="fw-bold text-secondary"><?=$total_admins?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-dark">
                <div class="card-body">
                    <div class="card-title text-secondary">平台超级管理员数</div>
                    <div class="fw-bold text-dark"><?=$total_platform_admins?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-success">
                <div class="card-body">
                    <div class="card-title text-secondary">合同总数</div>
                    <div class="fw-bold text-success"><?=$total_contracts?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-success">
                <div class="card-body">
                    <div class="card-title text-secondary">电子合同签署数</div>
                    <div class="fw-bold text-success"><?=$total_agreement?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-warning">
                <div class="card-body">
                    <div class="card-title text-secondary">30天内即将到期租户</div>
                    <div class="fw-bold text-warning"><?=$total_expiring?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-danger">
                <div class="card-body">
                    <div class="card-title text-secondary">已过期租户</div>
                    <div class="fw-bold text-danger"><?=$total_expired?></div>
                </div>
            </div>
        </div>
        <!-- 新增客户数与合同数统计 -->
        <div class="col">
            <div class="card card-stat shadow-sm border-info">
                <div class="card-body">
                    <div class="card-title text-secondary">平台客户总数</div>
                    <div class="fw-bold text-info"><?=$total_clients?></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat shadow-sm border-success">
                <div class="card-body">
                    <div class="card-title text-secondary">平台合同总数</div>
                    <div class="fw-bold text-success"><?=$total_contracts_all?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">套餐分布</div>
                <div class="card-body">
                    <canvas id="pkgPieChart" height="200"></canvas>
                    <ul class="list-group list-group-flush mt-3">
                        <?php foreach($pkg_count as $pkg): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?=htmlspecialchars($pkg['name'])?>
                                <span class="badge bg-primary rounded-pill"><?=$pkg['count']?></span>
                            </li>
                        <?php endforeach;?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">租户增长趋势（6个月）</div>
                <div class="card-body">
                    <canvas id="trendBarChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 新增：客户数、合同数TOP10 -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">客户数TOP10租户</div>
                <div class="card-body p-0">
                    <table class="table table-bordered m-0">
                        <tr>
                            <th>租户</th>
                            <th>客户数</th>
                        </tr>
                        <?php foreach($client_top as $row):?>
                        <tr>
                            <td><?=htmlspecialchars($row['name'])?></td>
                            <td><?=htmlspecialchars($row['client_count'])?></td>
                        </tr>
                        <?php endforeach;?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">合同数TOP10租户</div>
                <div class="card-body p-0">
                    <table class="table table-bordered m-0">
                        <tr>
                            <th>租户</th>
                            <th>合同数</th>
                        </tr>
                        <?php foreach($contract_top as $row):?>
                        <tr>
                            <td><?=htmlspecialchars($row['name'])?></td>
                            <td><?=htmlspecialchars($row['contract_count'])?></td>
                        </tr>
                        <?php endforeach;?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 原有TOP10用户展示 -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">用户数TOP10租户</div>
        <div class="card-body p-0">
            <table class="table table-bordered m-0">
                <tr>
                    <th>租户</th>
                    <th>用户数</th>
                </tr>
                <?php foreach($user_top as $row):?>
                <tr>
                    <td><?=htmlspecialchars($row['name'])?></td>
                    <td><?=htmlspecialchars($row['user_count'])?></td>
                </tr>
                <?php endforeach;?>
            </table>
        </div>
    </div>

</div>
<script>
document.addEventListener("DOMContentLoaded",function(){
    // 套餐分布饼图
    var pkgChart = new Chart(document.getElementById('pkgPieChart'), {
        type: 'pie',
        data: {
            labels: <?=json_encode(array_column($pkg_count,"name"))?>,
            datasets: [{
                data: <?=json_encode(array_column($pkg_count,"count"))?>,
                backgroundColor: [
                    "#007bff","#28a745","#ffc107","#17a2b8","#6c757d","#dc3545","#20c997","#fd7e14"
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 租户增长柱状图
    var trendChart = new Chart(document.getElementById('trendBarChart'), {
        type: 'bar',
        data: {
            labels: <?=json_encode($trend_labels)?>,
            datasets: [{
                label: "新增租户",
                data: <?=json_encode($trend_data)?>,
                backgroundColor: "#007bff"
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
});
</script>
</body>
</html>