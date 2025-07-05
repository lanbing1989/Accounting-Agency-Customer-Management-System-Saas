<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) die('参数错误');

// 查询合同（仅本租户）
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid AND a.tenant_id = :tenant_id
");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$agreement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agreement) {
    die('合同不存在或无权限');
}

// 获取签章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $stmt_seal = $db->prepare("SELECT image_path FROM seal_templates WHERE id=:id AND tenant_id = :tenant_id");
    $stmt_seal->bindValue(':id', $agreement['seal_id'], PDO::PARAM_INT);
    $stmt_seal->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_seal->execute();
    $seal = $stmt_seal->fetch(PDO::FETCH_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 签名图片
$signature_img = $agreement['sign_image'] ?? '';

// 获取服务期详情
$period = null;
if ($agreement['service_period_id']) {
    $stmt_per = $db->prepare("SELECT * FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_per->bindValue(':id', $agreement['service_period_id'], PDO::PARAM_INT);
    $stmt_per->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_per->execute();
    $period = $stmt_per->fetch(PDO::FETCH_ASSOC);
}

// 获取分段详情
$segment = null;
if ($agreement['service_segment_id']) {
    $stmt_seg = $db->prepare("SELECT * FROM service_segments WHERE id=:id AND tenant_id=:tenant_id");
    $stmt_seg->bindValue(':id', $agreement['service_segment_id'], PDO::PARAM_INT);
    $stmt_seg->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_seg->execute();
    $segment = $stmt_seg->fetch(PDO::FETCH_ASSOC);
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

// 组装变量
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
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if (strpos($tpl, '{signature}') !== false) {
        if ($signature_img) {
            $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
        } else {
            $tpl = str_replace('{signature}', 
                '<button id="showSignPad" class="btn btn-outline-primary btn-sm">甲方在线签字</button><div id="signPadArea" style="display:none;margin-top:10px;"></div>', 
                $tpl
            );
        }
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    return $tpl;
}

if (empty($agreement['sign_image'])) {
    $content = render_contract_template($agreement['template_content'], $vars, $seal_img, '');
} else {
    if (!empty($agreement['content_snapshot'])) {
        $content = $agreement['content_snapshot'];
    } else {
        $content = render_contract_template($agreement['template_content'], $vars, $seal_img, $signature_img);
    }
}

$sign_url = "/ht_agreement_sign.php?uuid=" . urlencode($agreement['uuid']);

$pdf_url = '';
$mapfile = __DIR__ . '/uploads/pdf_map.json';
if (file_exists($mapfile)) {
    $map = json_decode(file_get_contents($mapfile), true);
    if (isset($map[$agreement['uuid']])) {
        $pdf_url = $map[$agreement['uuid']];
        if ($pdf_url[0] !== '/' && strpos($pdf_url, 'uploads') === 0) {
            $pdf_url = '/' . $pdf_url;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同详情</title>
    <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
    #signature-pad { border:1px solid #aaa; border-radius:8px; background:#fff; }
    </style>
</head>
<body>
<?php include('navbar.php');?>
<div class="container mt-4" style="max-width:800px;">
    <h4>合同详情</h4>
    <div class="bg-white p-4 rounded shadow-sm mb-4" style="white-space:pre-line;" id="contractContent">
        <?= $content ?>
    </div>
    <div class="mb-3">
        <a class="btn btn-success" href="<?= $sign_url ?>" target="_blank">在线签署</a>
        <button class="btn btn-info" onclick="copySignLink('<?= $agreement['uuid']?>')">复制签署链接</button>
        <?php if ($pdf_url): ?>
            <a class="btn btn-primary" href="<?= $pdf_url ?>" target="_blank">下载PDF</a>
        <?php endif; ?>
        <a class="btn btn-danger" href="ht_agreement_delete.php?uuid=<?= urlencode($agreement['uuid'])?>" onclick="return confirm('确定要删除该合同吗？此操作不可恢复！');">删除</a>
        <a class="btn btn-secondary" href="ht_agreements.php">返回</a>
    </div>
</div>
<script src="/bootstrap/signature_pad.umd.min.js"></script>
<script>
function copySignLink(uuid) {
    var origin = window.location.origin || (window.location.protocol + "//" + window.location.host);
    var link = origin + '/ht_agreement_sign.php?uuid=' + uuid;
    var tips = "您好，以下是您的合同在线签署链接，请在电脑或微信/浏览器中打开，按页面提示完成签署：\n" + link;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(tips).then(function() {
            alert("已复制签署链接，可粘贴发给客户：\n\n" + tips);
        }, function() {
            window.prompt("复制失败，请手动复制：", tips);
        });
    } else {
        window.prompt("请手动复制签署链接：", tips);
    }
}
</script>
</body>
</html>