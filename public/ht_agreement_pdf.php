<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
require __DIR__ . '/../vendor/autoload.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) die('参数错误');

// 先查tenant_id
$stmt = $db->prepare("SELECT tenant_id FROM contracts_agreement WHERE uuid = :uuid");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die('合同不存在');
$tenant_id = $row['tenant_id'];

// 查询合同（含多租户限制）
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id, a.contract_no, a.contract_hash, a.sign_date, a.sign_image, a.content_snapshot
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid AND a.tenant_id = :tenant_id
");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
$stmt->execute();
$agreement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agreement) die('合同不存在');

// 查询服务期表（含多租户限制）
$period = null;
if (!empty($agreement['service_period_id'])) {
    $stmt_per = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_per->bindValue(':id', $agreement['service_period_id'], PDO::PARAM_INT);
    $stmt_per->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
    $stmt_per->execute();
    $period = $stmt_per->fetch(PDO::FETCH_ASSOC);
}
// 查询分段表（含多租户限制）
$segment = null;
if (!empty($agreement['service_segment_id'])) {
    $stmt_seg = $db->prepare("SELECT * FROM service_segments WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_seg->bindValue(':id', $agreement['service_segment_id'], PDO::PARAM_INT);
    $stmt_seg->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
    $stmt_seg->execute();
    $segment = $stmt_seg->fetch(PDO::FETCH_ASSOC);
}

// 获取盖章图片（含多租户限制）
$seal_img = '';
if ($agreement['seal_id']) {
    $stmt_seal = $db->prepare("SELECT image_path FROM seal_templates WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_seal->bindValue(':id', $agreement['seal_id'], PDO::PARAM_INT);
    $stmt_seal->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
    $stmt_seal->execute();
    $seal = $stmt_seal->fetch(PDO::FETCH_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 获取签名图片
$signature_img = '';
if (!empty($agreement['sign_image']) && file_exists($agreement['sign_image'])) {
    $signature_img = $agreement['sign_image'];
}

// 处理签署日期
if (!empty($agreement['sign_date'])) {
    $sign_date = $agreement['sign_date'];
    $sign_year = date('Y', strtotime($sign_date));
    $sign_month = date('m', strtotime($sign_date));
    $sign_day = date('d', strtotime($sign_date));
} else {
    $sign_date = date('Y-m-d');
    $sign_year = date('Y');
    $sign_month = date('m');
    $sign_day = date('d');
}

$contract_no = $agreement['contract_no'] ?? '';
$contract_hash = $agreement['contract_hash'] ?? '';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$check_url = $protocol . $host . '/contract_verify.php?no=' . urlencode($contract_no);

$qrcode_img_data = file_get_contents($protocol . $host . '/qrcode.php?text=' . urlencode($check_url));
$qrcode_base64 = 'data:image/png;base64,' . base64_encode($qrcode_img_data);

$vars = [
    'client_name'    => $agreement['client_name'] ?? '',
    'contact_person' => $agreement['contact_person'] ?? '',
    'contact_phone'  => $agreement['contact_phone'] ?? '',
    'contact_email'  => $agreement['contact_email'] ?? '',
    'remark'         => $agreement['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    'today'          => $sign_date,
    'year'           => $sign_year,
    'month'          => $sign_month,
    'day'            => $sign_day,
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

if (!empty($agreement['content_snapshot'])) {
    $content = $agreement['content_snapshot'];
    $content = str_replace(["\r\n", "\r", "\n", "\t"], '<br>', $content);
} else {
    $content = $agreement['template_content'];
    if ($seal_img) {
        $content = str_replace('{seal}', '<img src="' . $seal_img . '" style="width:42mm; height:42mm;">', $content);
    } else {
        $content = str_replace('{seal}', '', $content);
    }
    if ($signature_img) {
        $content = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:20mm;">', $content);
    } else {
        $content = str_replace('{signature}', '', $content);
    }
    foreach ($vars as $k => $v) {
        $content = str_replace('{' . $k . '}', htmlspecialchars($v), $content);
    }
    $content = nl2br($content);
}

$content .= '<img src="'.$qrcode_base64.'" style="height:80px;"><br>'.
           htmlspecialchars($check_url).
           '</div>';

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('stsongstdlight', '', 13, '', false); // 中文支持
$pdf->writeHTML($content, true, false, true, false, '');
$pdf->Output('agreement_'.$uuid.'.pdf', 'I');
?>