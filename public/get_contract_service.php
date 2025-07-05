<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$client_id = intval($_GET['client_id'] ?? 0);

// 只查本租户客户
$stmt = $db->prepare("SELECT service_start, service_end, package_type, price_per_year, segment_fee FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt->bindValue(':id', $client_id, PDO::PARAM_INT);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode(['ok'=>true, 'data'=>$row]);
} else {
    echo json_encode(['ok'=>false, 'msg'=>'未找到客户信息']);
}
?>