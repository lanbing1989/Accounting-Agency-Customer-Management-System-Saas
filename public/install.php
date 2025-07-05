<?php
// 安装锁检测
if (file_exists('install.lock')) {
    die('<h2>系统已安装！如需重新安装请先删除 install.lock 文件。</h2>');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单信息
    $host = trim($_POST['db_host']);
    $dbname = trim($_POST['db_name']);
    $user = trim($_POST['db_user']);
    $pass = trim($_POST['db_pass']);
    $charset = 'utf8mb4';

    // 管理员账号密码
    $admin_user = trim($_POST['admin_user']);
    $admin_pass_raw = trim($_POST['admin_pass']);

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // 尝试数据库连接
        $db = new PDO($dsn, $user, $pass, $options);

        // 建表SQL
        $sqls = [];
        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    contact_person VARCHAR(64),
    contact_phone VARCHAR(32),
    contact_email VARCHAR(128),
    status TINYINT(1) DEFAULT 1,
    is_deleted TINYINT(1) DEFAULT 0,
    package_id INT DEFAULT NULL,
    package_expire DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS tenant_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) NOT NULL,
    max_users INT,
    max_clients INT,
    max_agreements INT,
    price DECIMAL(10,2) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS tenant_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    user_id INT,
    action VARCHAR(64),
    detail TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    username VARCHAR(64) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    UNIQUE KEY uk_tenant_user (tenant_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(255),
    remark TEXT,
    UNIQUE KEY uk_tenant_client (tenant_id, client_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS service_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    contract_id INT NOT NULL,
    service_start DATE,
    service_end DATE,
    month_count INT,
    package_type VARCHAR(50),
    manually_closed TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS service_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    service_period_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    price_per_year DECIMAL(10,2) NOT NULL,
    segment_fee DECIMAL(10,2) NOT NULL,
    package_type VARCHAR(50),
    remark TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    contract_id INT NOT NULL,
    service_segment_id INT,
    pay_date DATE,
    amount DECIMAL(10,2),
    remark TEXT,
    is_temp TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS annual_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    contract_id INT NOT NULL,
    year INT NOT NULL,
    reported_at DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS tax_declare_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    contract_id INT NOT NULL,
    declare_period VARCHAR(20) NOT NULL,
    ele_tax_reported_at DATE,
    personal_tax_reported_at DATE,
    operator VARCHAR(255),
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS contract_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    content TEXT,
    created_at DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS seal_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255),
    image_path VARCHAR(255),
    created_at DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS contracts_agreement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    uuid VARCHAR(64) UNIQUE,
    client_id INT,
    template_id INT,
    seal_id INT,
    sign_status VARCHAR(50) DEFAULT '',
    sign_image VARCHAR(255),
    sign_time DATETIME,
    created_at DATETIME,
    sign_date DATE,
    service_period_id INT,
    service_segment_id INT,
    content_snapshot TEXT,
    contract_no VARCHAR(32),
    contract_hash VARCHAR(80),
    sign_ip VARCHAR(40),
    sign_phone VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS tenant_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    order_no VARCHAR(64) NOT NULL UNIQUE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    pay_type VARCHAR(20) DEFAULT 'wechat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        // 预留平台设置表（建议后续使用）
        $sqls[] = <<<SQL
CREATE TABLE IF NOT EXISTS platform_settings (
    s_key VARCHAR(64) PRIMARY KEY,
    s_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        // 执行所有表创建
        foreach ($sqls as $sql) {
            $db->exec($sql);
        }

        // 初始化套餐
        $db->exec("INSERT IGNORE INTO tenant_packages (id, name, max_users, max_clients, max_agreements, price) VALUES
            (1, '标准版', 10, 100, 100, 99.00),
            (2, '高级版', 50, 500, 1000, 399.00),
            (3, '旗舰版', 200, 2000, 5000, 999.00)
        ");

        // 初始化平台租户
        $db->exec("INSERT IGNORE INTO tenants (id, name, contact_person, status, is_deleted) VALUES
            (1, '平台租户', '平台管理员', 1, 0)
        ");

        // 初始化平台管理员
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE tenant_id=1 AND username=?");
        $stmt->execute([$admin_user]);
        $userCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userCheck['cnt'] == 0) {
            $admin_pass_hashed = password_hash($admin_pass_raw, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (tenant_id, username, password, role) VALUES (1, ?, ?, 'platform_admin')")
                ->execute([$admin_user, $admin_pass_hashed]);
        }

        // 写配置文件
        $dbphp = <<<PHP
<?php
\$host = '$host';
\$dbname = '$dbname';
\$user = '$user';
\$pass = '$pass';
\$charset = 'utf8mb4';
\$dsn = "mysql:host=\$host;dbname=\$dbname;charset=\$charset";
\$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    \$db = new PDO(\$dsn, \$user, \$pass, \$options);
} catch (\PDOException \$e) {
    throw new \PDOException(\$e->getMessage(), (int)\$e->getCode());
}
?>
PHP;
        file_put_contents('db.php', $dbphp);

        // 写安装锁
        file_put_contents('install.lock', 'ok');

        $success = "安装成功！平台管理员账号：<b>$admin_user</b> 平台密码：<b>$admin_pass_raw</b><br>请删除install.php并妥善保管平台账号。";
    } catch (Exception $e) {
        $error = "数据库连接或安装失败: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>系统安装</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:500px;margin-top:60px;">
    <div class="card shadow">
        <div class="card-header text-center"><h4>系统安装向导</h4></div>
        <div class="card-body">
            <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif;?>
            <?php if($success): ?>
                <div class="alert alert-success"><?=$success?></div>
            <?php else: ?>
            <form method="post">
                <div class="mb-3">
                    <label>数据库主机</label>
                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label>数据库名称</label>
                    <input type="text" class="form-control" name="db_name" required>
                </div>
                <div class="mb-3">
                    <label>数据库用户</label>
                    <input type="text" class="form-control" name="db_user" required>
                </div>
                <div class="mb-3">
                    <label>数据库密码</label>
                    <input type="password" class="form-control" name="db_pass">
                </div>
                <hr>
                <div class="mb-3">
                    <label>平台管理员账号</label>
                    <input type="text" class="form-control" name="admin_user" value="admin" required>
                </div>
                <div class="mb-3">
                    <label>平台管理员密码</label>
                    <input type="text" class="form-control" name="admin_pass" value="admin123456" required>
                </div>
                <button type="submit" class="btn btn-success w-100">开始安装</button>
            </form>
            <?php endif;?>
        </div>
    </div>
</div>
</body>
</html>