<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';               // 【必须放在前面，确保 $db 已定义】
require_once __DIR__.'/platform_utils.php';

$platform_settings = get_platform_settings($db);
$platform_name = get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
$platform_logo = get_platform_setting($db, 'platform_logo');
if ($_SESSION['role'] !== 'platform_admin') die('仅平台超级管理员可用！');

// 1. 定义所有可配置项（可扩展，部分预留接口参数）
$setting_keys = [
    'platform_name'        => '平台名称',
    'platform_logo'        => '平台LOGO文件',
    'platform_icp'         => 'ICP备案号',
    'platform_tel'         => '客服电话',
    'platform_email'       => '联系邮箱',
    'allow_register'       => '允许注册',
    'default_package'      => '新租户默认套餐ID',
    'max_tenant_count'     => '最大租户数',
    'max_user_count'       => '最大用户数',
    'login_ip_whitelist'   => '登录IP白名单(逗号分隔)',
    'platform_announce'    => '平台公告',
    // SMTP 邮件相关
    'smtp_host'            => 'SMTP服务器',
    'smtp_port'            => 'SMTP端口',
    'smtp_user'            => 'SMTP用户名',
    'smtp_pass'            => 'SMTP密码',
    'smtp_secure'          => 'SMTP安全协议',
    'smtp_from_email'      => '发件邮箱',
    'smtp_from_name'       => '发件名称',
    // 预留短信、支付、OSS、API
    'sms_provider'         => '短信服务商',
    'sms_appid'            => '短信AppID',
    'sms_key'              => '短信Key',
    'sms_sign'             => '短信签名',
    'oss_provider'         => '对象存储服务商',
    'oss_key'              => 'OSS Key',
    'oss_secret'           => 'OSS Secret',
    'oss_bucket'           => 'OSS Bucket',
    'oss_endpoint'         => 'OSS Endpoint',
    'payment_provider'     => '支付服务商',
    'payment_appid'        => '支付AppID',
    'payment_key'          => '支付Key',
    'api_access_key'       => '平台API密钥',
];

// 2. 读取当前设置
$stmt = $db->query("SELECT s_key, s_value FROM platform_settings");
$settings = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $settings[$row['s_key']] = $row['s_value'];
}

// 3. 处理LOGO上传
if (!empty($_FILES['platform_logo_file']['name'])) {
    $file = $_FILES['platform_logo_file'];
    if ($file['error'] === 0 && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file['name'])) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $target = '/upload/logo_' . date('Ymd_His') . '.' . $ext;
        $fullpath = $_SERVER['DOCUMENT_ROOT'] . $target;
        if (move_uploaded_file($file['tmp_name'], $fullpath)) {
            $settings['platform_logo'] = $target;
            $stmt = $db->prepare("REPLACE INTO platform_settings (s_key, s_value) VALUES (?, ?)");
            $stmt->execute(['platform_logo', $target]);
        }
    }
}

// 4. 保存设置
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($setting_keys as $k=>$v) {
        // 跳过文件上传项
        if ($k === 'platform_logo') continue;
        $val = isset($_POST[$k]) ? trim($_POST[$k]) : '';
        // 处理checkbox
        if ($k === 'allow_register') $val = isset($_POST[$k]) ? '1' : '0';
        $stmt = $db->prepare("REPLACE INTO platform_settings (s_key, s_value) VALUES (?, ?)");
        $stmt->execute([$k, $val]);
        $settings[$k] = $val;
    }
    $msg = "设置已保存！";
    // 可记录日志
    @$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (1, ?, '修改平台设置', '', NOW())")
        ->execute([$_SESSION['user_id']]);
}

// 5. 获取套餐（用于下拉）
$pkg_list = $db->query("SELECT id,name FROM tenant_packages")->fetchAll(PDO::FETCH_ASSOC);

include('platform_navbar.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>平台设置</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-label { font-weight: 500;}
        .form-check-label { font-weight: 400;}
        .section-title { font-weight:600;font-size:1.1rem;margin-top:38px;}
    </style>
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width:800px;">
    <h3 class="mb-4">平台设置 <small class="text-secondary" style="font-size:1rem;">Platform Settings</small></h3>
    <?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif;?>
    <form method="post" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">

        <div class="section-title">基础信息</div>
        <div class="mb-3">
            <label class="form-label">平台名称</label>
            <input type="text" class="form-control" name="platform_name" value="<?=htmlspecialchars($settings['platform_name']??'')?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">平台LOGO上传</label>
            <input type="file" class="form-control" name="platform_logo_file" accept=".jpg,.jpeg,.png,.gif">
            <?php if(!empty($settings['platform_logo'])):?>
                <div class="mt-2">
                    <img src="<?=htmlspecialchars($settings['platform_logo'])?>" alt="logo" style="max-height:48px;">
                </div>
            <?php endif;?>
            <div class="form-text">支持jpg/png/gif，推荐尺寸不超过300x300像素。</div>
        </div>
        <div class="mb-3">
            <label class="form-label">ICP备案号</label>
            <input type="text" class="form-control" name="platform_icp" value="<?=htmlspecialchars($settings['platform_icp']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">客服电话</label>
            <input type="text" class="form-control" name="platform_tel" value="<?=htmlspecialchars($settings['platform_tel']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系邮箱</label>
            <input type="email" class="form-control" name="platform_email" value="<?=htmlspecialchars($settings['platform_email']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">新用户注册</label><br>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="allow_register" value="1" id="chkReg" <?=!empty($settings['allow_register']) && $settings['allow_register']==='1'?'checked':''?>>
                <label class="form-check-label" for="chkReg">允许新用户注册</label>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">新租户默认套餐</label>
            <select class="form-select" name="default_package">
                <option value="">请选择</option>
                <?php foreach($pkg_list as $pkg):?>
                  <option value="<?=$pkg['id']?>" <?=(isset($settings['default_package']) && $settings['default_package']==$pkg['id'])?'selected':''?>><?=htmlspecialchars($pkg['name'])?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">最大租户数</label>
            <input type="number" class="form-control" name="max_tenant_count" min="0" value="<?=htmlspecialchars($settings['max_tenant_count']??'')?>" placeholder="0表示不限制">
        </div>
        <div class="mb-3">
            <label class="form-label">最大用户数</label>
            <input type="number" class="form-control" name="max_user_count" min="0" value="<?=htmlspecialchars($settings['max_user_count']??'')?>" placeholder="0表示不限制">
        </div>
        <div class="mb-3">
            <label class="form-label">登录IP白名单（多个用英文逗号分隔）</label>
            <input type="text" class="form-control" name="login_ip_whitelist" value="<?=htmlspecialchars($settings['login_ip_whitelist']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">平台公告</label>
            <textarea class="form-control" name="platform_announce" rows="3"><?=htmlspecialchars($settings['platform_announce']??'')?></textarea>
        </div>

        <div class="section-title">SMTP 邮件设置</div>
        <div class="mb-3">
            <label class="form-label">SMTP服务器</label>
            <input type="text" class="form-control" name="smtp_host" value="<?=htmlspecialchars($settings['smtp_host']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">SMTP端口</label>
            <input type="number" class="form-control" name="smtp_port" value="<?=htmlspecialchars($settings['smtp_port']??'')?>" placeholder="如 465/587">
        </div>
        <div class="mb-3">
            <label class="form-label">SMTP用户名</label>
            <input type="text" class="form-control" name="smtp_user" value="<?=htmlspecialchars($settings['smtp_user']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">SMTP密码</label>
            <input type="password" class="form-control" name="smtp_pass" value="<?=htmlspecialchars($settings['smtp_pass']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">SMTP安全协议</label>
            <select class="form-select" name="smtp_secure">
                <option value="">无</option>
                <option value="ssl" <?=$settings['smtp_secure']==='ssl'?'selected':''?>>SSL</option>
                <option value="tls" <?=$settings['smtp_secure']==='tls'?'selected':''?>>TLS</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">发件邮箱</label>
            <input type="email" class="form-control" name="smtp_from_email" value="<?=htmlspecialchars($settings['smtp_from_email']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">发件名称</label>
            <input type="text" class="form-control" name="smtp_from_name" value="<?=htmlspecialchars($settings['smtp_from_name']??'')?>">
        </div>

        <div class="section-title">短信接口（预留）</div>
        <div class="mb-3">
            <label class="form-label">短信服务商</label>
            <input type="text" class="form-control" name="sms_provider" value="<?=htmlspecialchars($settings['sms_provider']??'')?>" placeholder="如阿里云/腾讯云/华为云">
        </div>
        <div class="mb-3">
            <label class="form-label">短信AppID</label>
            <input type="text" class="form-control" name="sms_appid" value="<?=htmlspecialchars($settings['sms_appid']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">短信Key</label>
            <input type="text" class="form-control" name="sms_key" value="<?=htmlspecialchars($settings['sms_key']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">短信签名</label>
            <input type="text" class="form-control" name="sms_sign" value="<?=htmlspecialchars($settings['sms_sign']??'')?>">
        </div>

        <div class="section-title">对象存储/OSS（预留）</div>
        <div class="mb-3">
            <label class="form-label">服务商</label>
            <input type="text" class="form-control" name="oss_provider" value="<?=htmlspecialchars($settings['oss_provider']??'')?>" placeholder="如阿里云/腾讯云/七牛云">
        </div>
        <div class="mb-3">
            <label class="form-label">OSS Key</label>
            <input type="text" class="form-control" name="oss_key" value="<?=htmlspecialchars($settings['oss_key']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">OSS Secret</label>
            <input type="text" class="form-control" name="oss_secret" value="<?=htmlspecialchars($settings['oss_secret']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Bucket</label>
            <input type="text" class="form-control" name="oss_bucket" value="<?=htmlspecialchars($settings['oss_bucket']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Endpoint</label>
            <input type="text" class="form-control" name="oss_endpoint" value="<?=htmlspecialchars($settings['oss_endpoint']??'')?>">
        </div>

        <div class="section-title">支付接口（预留）</div>
        <div class="mb-3">
            <label class="form-label">支付服务商</label>
            <input type="text" class="form-control" name="payment_provider" value="<?=htmlspecialchars($settings['payment_provider']??'')?>" placeholder="如支付宝/微信/Stripe">
        </div>
        <div class="mb-3">
            <label class="form-label">支付AppID</label>
            <input type="text" class="form-control" name="payment_appid" value="<?=htmlspecialchars($settings['payment_appid']??'')?>">
        </div>
        <div class="mb-3">
            <label class="form-label">支付Key</label>
            <input type="text" class="form-control" name="payment_key" value="<?=htmlspecialchars($settings['payment_key']??'')?>">
        </div>

        <div class="section-title">API密钥（预留）</div>
        <div class="mb-3">
            <label class="form-label">平台API密钥</label>
            <input type="text" class="form-control" name="api_access_key" value="<?=htmlspecialchars($settings['api_access_key']??'')?>">
            <div class="form-text">可用于OpenAPI、Webhook等平台接入</div>
        </div>

        <button class="btn btn-primary px-5">保存设置</button>
    </form>
</div>
</body>
</html>