<?php
session_start();
require_once __DIR__.'/db.php';

// 用uuid参数，不用id
$uuid = $_GET['uuid'] ?? '';

// 查询合同及关键信息（含多租户）
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.tenant_id
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['ok'=>false, 'msg'=>'合同不存在']);
    exit;
}
if (!empty($row['sign_image']) && file_exists(__DIR__ . '/' . $row['sign_image'])) {
    echo json_encode(['ok'=>false, 'msg'=>'该合同已签署，不能重复签署！']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$imgBase64 = $data['signature'] ?? '';
$phone = $data['phone'] ?? '';
$code = $data['code'] ?? '';

// ========== 新增：短信验证码校验 ==========
// 检查手机号和验证码格式
if (!$phone || !$code || !preg_match('/^1\d{10}$/', $phone) || !preg_match('/^\d{6}$/', $code)) {
    echo json_encode(['ok'=>false, 'msg'=>'手机号或验证码格式错误']);
    exit;
}
// 校验验证码，只允许一次，验证后即清除
if (
    !isset($_SESSION['sms_verify_code']) ||
    $_SESSION['sms_verify_code'] != $code ||
    $_SESSION['sms_verify_phone'] != $phone ||
    $_SESSION['sms_verify_time'] + 300 < time()
) {
    echo json_encode(['ok'=>false, 'msg'=>'短信验证码无效或已过期']);
    exit;
}
// 验证通过立即清除验证码，防止重复提交
unset($_SESSION['sms_verify_code'], $_SESSION['sms_verify_time'], $_SESSION['sms_verify_phone']);

// ========== 继续后续签署流程 ==========

if (!$uuid || !$imgBase64 || strpos($imgBase64, 'data:image/png;base64,') !== 0) {
    echo json_encode(['ok'=>false, 'msg'=>'参数错误']);
    exit;
}

// 保存为本地图片文件
$imgData = base64_decode(str_replace('data:image/png;base64,','',$imgBase64));
$saveDir = __DIR__.'/signatures/';
if (!is_dir($saveDir)) mkdir($saveDir,0777,true);
$filename = $saveDir . 'sign_' . $row['id'] . '_' . time() . '.png';
file_put_contents($filename, $imgData);
$relativePath = 'signatures/' . basename($filename);
$sign_date = date('Y-m-d');

// 生成合同编号
function generate_contract_no($db, $tenant_id) {
    $prefix = 'HT'.date('Ymd');
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts_agreement WHERE sign_date=:today AND tenant_id=:tenant_id");
    $stmt->bindValue(':today', $today, PDO::PARAM_STR);
    $stmt->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    if ($count === false) $count = 0;
    $serial = str_pad($count+1, 3, '0', STR_PAD_LEFT);
    return $prefix.$serial;
}
$contract_no = $row['contract_no'] ?? '';
if (!$contract_no) {
    $contract_no = generate_contract_no($db, $row['tenant_id']);
}

// 获取服务期、分段（参数绑定，含tenant_id）
$period = null;
if ($row['service_period_id']) {
    $stmt_period = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_period->bindValue(':id', $row['service_period_id'], PDO::PARAM_INT);
    $stmt_period->bindValue(':tenant_id', $row['tenant_id'], PDO::PARAM_INT);
    $stmt_period->execute();
    $period = $stmt_period->fetch(PDO::FETCH_ASSOC);
}
$segment = null;
if ($row['service_segment_id']) {
    $stmt_segment = $db->prepare("SELECT * FROM service_segments WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_segment->bindValue(':id', $row['service_segment_id'], PDO::PARAM_INT);
    $stmt_segment->bindValue(':tenant_id', $row['tenant_id'], PDO::PARAM_INT);
    $stmt_segment->execute();
    $segment = $stmt_segment->fetch(PDO::FETCH_ASSOC);
}
// 盖章
$seal_img = '';
if ($row['seal_id']) {
    $stmt_seal = $db->prepare("SELECT image_path FROM seal_templates WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_seal->bindValue(':id', $row['seal_id'], PDO::PARAM_INT);
    $stmt_seal->bindValue(':tenant_id', $row['tenant_id'], PDO::PARAM_INT);
    $stmt_seal->execute();
    $seal = $stmt_seal->fetch(PDO::FETCH_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 变量
$vars = [
    'client_name'    => $row['client_name'] ?? '',
    'contact_person' => $row['contact_person'] ?? '',
    'contact_phone'  => $row['contact_phone'] ?? '',
    'contact_email'  => $row['contact_email'] ?? '',
    'remark'         => $row['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    'sign_date'      => $sign_date,
    'sign_year'      => date('Y', strtotime($sign_date)),
    'sign_month'     => date('m', strtotime($sign_date)),
    'sign_day'       => date('d', strtotime($sign_date)),
    'contract_no'    => $contract_no
];

// 渲染最终快照（签名、盖章、编号）
function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if ($signature_img && strpos($tpl, '{signature}') !== false) {
        $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    return $tpl;
}
$snapshot_html = render_contract_template($row['template_content'], $vars, $seal_img, $relativePath);

// 生成哈希
$contract_hash = hash('sha256', $snapshot_html);

// 合同底部拼接编号、哈希、查验二维码
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$check_url = $protocol . $host . '/contract_verify.php?no=' . urlencode($contract_no);

$snapshot_html .= "<hr style='border:1px dashed #bbb; margin:20px 0;'>
<div style='font-size:15px;color:#666;'>
合同编号：{$contract_no}<br>
合同哈希：{$contract_hash}<br>
<span>扫码查验真伪：</span>
<img src='qrcode.php?text={$check_url}' height='80'>
</div>";

$sign_ip = $_SERVER['REMOTE_ADDR'];

// 更新合同，增加签署日期和最终快照、编号、哈希、签署IP/手机号（含多租户限制）
$stmt2 = $db->prepare("UPDATE contracts_agreement SET sign_image=:sign_image, sign_date=:sign_date, content_snapshot=:content_snapshot, contract_no=:contract_no, contract_hash=:contract_hash, sign_ip=:sign_ip, sign_phone=:sign_phone WHERE uuid=:uuid AND tenant_id=:tenant_id");
$stmt2->bindValue(':sign_image', $relativePath, PDO::PARAM_STR);
$stmt2->bindValue(':sign_date', $sign_date, PDO::PARAM_STR);
$stmt2->bindValue(':content_snapshot', $snapshot_html, PDO::PARAM_STR);
$stmt2->bindValue(':contract_no', $contract_no, PDO::PARAM_STR);
$stmt2->bindValue(':contract_hash', $contract_hash, PDO::PARAM_STR);
$stmt2->bindValue(':sign_ip', $sign_ip, PDO::PARAM_STR);
$stmt2->bindValue(':sign_phone', $phone, PDO::PARAM_STR);
$stmt2->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt2->bindValue(':tenant_id', $row['tenant_id'], PDO::PARAM_INT);
$stmt2->execute();

echo json_encode(['ok'=>true]);
?>