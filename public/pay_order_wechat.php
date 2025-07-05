<?php
require_once __DIR__.'/db.php';
$config = require __DIR__ . '/config_wechat_pay.php';

$order_no = $_GET['order_no'] ?? '';
if (!$order_no) die('订单号缺失');

$stmt = $db->prepare("SELECT * FROM tenant_orders WHERE order_no=?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('订单不存在');
if ($order['status'] === 'paid') die('订单已支付');

$package = $db->prepare("SELECT * FROM tenant_packages WHERE id=?");
$package->execute([$order['package_id']]);
$pkg = $package->fetch(PDO::FETCH_ASSOC);
if (!$pkg) die('套餐不存在');

// 微信金额必须大于等于1分
$total_fee = intval(round($order['amount'] * 100));
if ($total_fee < 1) die('订单金额必须大于等于0.01元');

$nonce_str = strtoupper(md5(uniqid(mt_rand(), true)));
$body = 'SaaS平台套餐购买-'.$pkg['name'];
$notify_url = $config['notify_url'];

$params = [
    'appid'            => $config['appid'],
    'mch_id'           => $config['mch_id'],
    'nonce_str'        => $nonce_str,
    'body'             => $body,
    'out_trade_no'     => $order_no,
    'total_fee'        => $total_fee,
    'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'notify_url'       => $notify_url,
    'trade_type'       => 'NATIVE',
];

// 生成签名
ksort($params);
$stringA = '';
foreach ($params as $k => $v) {
    if ($v !== '' && !is_null($v)) {
        $stringA .= "$k=$v&";
    }
}
$stringSignTemp = $stringA . "key=" . $config['api_key'];
$params['sign'] = strtoupper(md5($stringSignTemp));

// 转为XML
$xml = "<xml>";
foreach ($params as $k => $v) {
    $xml .= "<$k><![CDATA[$v]]></$k>";
}
$xml .= "</xml>";

// 发起请求
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/pay/unifiedorder");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    // 写日志
    $log_action = '微信下单失败';
    $log_detail = '订单号：'.$order_no.'，错误信息：Curl错误: '.$curlErr;
    $stmt_log = $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt_log->execute([
        $order['tenant_id'],
        $order['user_id'] ?? null,
        $log_action,
        $log_detail
    ]);
    die('微信下单失败：Curl错误: ' . $curlErr);
}

$xml_res = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

if ($xml_res === false) {
    // 写日志
    $log_action = '微信下单失败';
    $log_detail = '订单号：'.$order_no.'，错误信息：接口响应格式错误';
    $stmt_log = $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt_log->execute([
        $order['tenant_id'],
        $order['user_id'] ?? null,
        $log_action,
        $log_detail
    ]);
    die('微信下单失败：接口响应格式错误');
}

// 错误诊断输出
if ($xml_res->return_code != 'SUCCESS' || $xml_res->result_code != 'SUCCESS') {
    $msg = '微信下单失败：';
    $msg .= isset($xml_res->return_msg) ? (string)$xml_res->return_msg : '';
    if (isset($xml_res->err_code) && isset($xml_res->err_code_des)) {
        $msg .= ' [' . (string)$xml_res->err_code . '] ' . (string)$xml_res->err_code_des;
    }
    // 写日志
    $log_action = '微信下单失败';
    $log_detail = '订单号：'.$order_no.'，错误信息：'.$msg;
    $stmt_log = $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt_log->execute([
        $order['tenant_id'],
        $order['user_id'] ?? null,
        $log_action,
        $log_detail
    ]);
    die($msg);
}

$qrcode_url = (string)$xml_res->code_url;

// 写入微信下单日志
$log_action = '微信下单';
$log_detail = '订单号：'.$order_no.'，套餐：'.$pkg['name'].'，金额：'.$order['amount'].'元，生成二维码成功，等待支付。';
$stmt_log = $db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt_log->execute([
    $order['tenant_id'],
    $order['user_id'] ?? null,
    $log_action,
    $log_detail
]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>微信扫码支付</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {
        background: #f5f7fa;
        font-family: "Helvetica Neue", Helvetica, Arial, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
        margin: 0;
        padding: 0;
    }
    .pay-container {
        max-width: 420px;
        margin: 60px auto 0 auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.09);
        padding: 36px 30px 28px 30px;
        text-align: center;
    }
    .pay-title {
        font-size: 22px;
        color: #3183f4;
        margin-bottom: 15px;
        font-weight: 600;
        letter-spacing: 2px;
    }
    .qr-area {
        padding: 18px 0;
    }
    .qr-area img {
        width: 210px;
        height: 210px;
        border: 6px solid #e9eff6;
        border-radius: 10px;
        background: #fff;
    }
    .order-info {
        margin-top: 22px;
        text-align: left;
        color: #333;
        font-size: 16px;
        line-height: 2;
    }
    .order-info b {
        color: #3183f4;
        font-size: 18px;
        font-weight: 700;
    }
    .pay-tip {
        color: #999;
        font-size: 14px;
        margin-top: 18px;
        margin-bottom: 6px;
        letter-spacing: 1px;
    }
    @media (max-width: 600px) {
        .pay-container {padding: 12px 5vw;}
        .qr-area img {width: 150px; height: 150px;}
    }
    </style>
</head>
<body>
    <div class="pay-container">
        <div class="pay-title">微信扫码支付</div>
        <div class="qr-area">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=210x210&data=<?=urlencode($qrcode_url)?>" alt="微信支付二维码">
        </div>
        <div class="pay-tip">请使用微信扫一扫完成支付</div>
        <div class="order-info">
            订单号：<?=htmlspecialchars($order_no)?><br>
            套餐：<?=htmlspecialchars($pkg['name'])?><br>
            金额：<b><?=htmlspecialchars($order['amount'])?></b> 元
            <?php if ($order['amount'] < $pkg['price']): ?>
            <div style="color:#3183f4;font-size:14px;margin-top:8px;">升级补差价</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        setInterval(function(){
            fetch("pay_status_check.php?order_no=<?=urlencode($order_no)?>")
            .then(r=>r.json()).then(d=>{
                if(d.paid){
                    alert('支付成功！即将跳转...');
                    window.location.href='user_package_detail.php';
                }
            });
        }, 2000);
    </script>
</body>
</html>