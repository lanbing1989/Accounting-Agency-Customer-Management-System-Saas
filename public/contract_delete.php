<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$id = intval($_GET['id']);

// 只删除本租户的数据
// 删除 payments
$stmt = $db->prepare("DELETE FROM payments WHERE contract_id=:id AND tenant_id=:tenant_id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
// 删除 service_segments
$stmt2 = $db->prepare("SELECT id FROM service_periods WHERE contract_id=:id AND tenant_id=:tenant_id");
$stmt2->bindValue(':id', $id, PDO::PARAM_INT);
$stmt2->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt2->execute();
while($p = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $pid = $p['id'];
    $stmt3 = $db->prepare("DELETE FROM service_segments WHERE service_period_id=:pid AND tenant_id=:tenant_id");
    $stmt3->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt3->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt3->execute();
}
// 删除 service_periods
$stmt4 = $db->prepare("DELETE FROM service_periods WHERE contract_id=:id AND tenant_id=:tenant_id");
$stmt4->bindValue(':id', $id, PDO::PARAM_INT);
$stmt4->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt4->execute();
// 删除 contract
$stmt5 = $db->prepare("DELETE FROM contracts WHERE id=:id AND tenant_id=:tenant_id");
$stmt5->bindValue(':id', $id, PDO::PARAM_INT);
$stmt5->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt5->execute();
header('Location: index.php');
exit;
?>