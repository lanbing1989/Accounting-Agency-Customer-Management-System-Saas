<?php
require_once __DIR__.'/db.php';
$config = require __DIR__ . '/config_wechat_pay.php';

$xmlData = file_get_contents("php://input");
$data = (array)simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);

if(!isset($data['sign'])) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[参数错误]]></return_msg></xml>";
    exit;
}

$sign = $data['sign'];
unset($data['sign']);
ksort($data);
$stringA = '';
foreach($data as $k => $v) {
    $stringA .= "$k=$v&";
}
$stringSignTemp = $stringA . "key=" . $config['api_key'];
$sign_check = strtoupper(md5($stringSignTemp));
if ($sign !== $sign_check) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>";
    exit;
}

$order_no = $data['out_trade_no'];
$stmt = $db->prepare("SELECT * FROM tenant_orders WHERE order_no=?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo "<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单不存在]]></return_msg></xml>";
    exit;
}
if ($order['status'] === 'paid') {
    echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
    exit;
}

// 获取当前租户信息和套餐
$tenantStmt = $db->prepare("SELECT * FROM tenants WHERE id=?");
$tenantStmt->execute([$order['tenant_id']]);
$tenantInfo = $tenantStmt->fetch(PDO::FETCH_ASSOC);

$old_package_id = $tenantInfo['package_id'];
$old_expire = $tenantInfo['package_expire'] ?? date('Y-m-d');

$old_pkg_stmt = $db->prepare("SELECT * FROM tenant_packages WHERE id=?");
$old_pkg_stmt->execute([$old_package_id]);
$old_pkg = $old_pkg_stmt->fetch(PDO::FETCH_ASSOC);

$new_pkg_stmt = $db->prepare("SELECT * FROM tenant_packages WHERE id=?");
$new_pkg_stmt->execute([$order['package_id']]);
$new_pkg = $new_pkg_stmt->fetch(PDO::FETCH_ASSOC);

// 升级只补差价
$today = date('Y-m-d');
$remain_days = ceil((strtotime($old_expire) - strtotime($today)) / 86400);
if ($remain_days < 1) $remain_days = 0;

$old_price = floatval($old_pkg['price']);
$new_price = floatval($new_pkg['price']);
$old_day_price = $old_price / 365;
$new_day_price = $new_price / 365;
$diff = $remain_days * ($new_day_price - $old_day_price);
$diff = max(0, round($diff, 2));

// 标记订单已支付
$db->prepare("UPDATE tenant_orders SET status='paid', paid_at=NOW() WHERE id=?")->execute([$order['id']]);

// 只升级套餐ID，到期日不变
$db->prepare("UPDATE tenants SET package_id=? WHERE id=?")
    ->execute([$order['package_id'], $order['tenant_id']]);

// 写入日志：包含原套餐、目标套餐、原到期日、升级后到期日(不变)、补差价金额、剩余天数等
$log_action = "套餐升级";
$log_detail = "订单号：{$order['order_no']}，由【{$old_pkg['name']}】升级为【{$new_pkg['name']}】，原到期日：{$old_expire}，"
    ."新到期日：{$old_expire}，剩余{$remain_days}天，补差价：{$diff}元，实际支付：{$order['amount']}元，支付渠道：微信，微信流水号：{$data['transaction_id']}";

$db->prepare("INSERT INTO tenant_logs (tenant_id, user_id, action, detail, created_at) VALUES (?, ?, ?, ?, NOW())")
   ->execute([
        $order['tenant_id'],
        $order['user_id'] ?? null,
        $log_action,
        $log_detail
   ]);

echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
?>