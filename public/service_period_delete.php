<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$id = intval($_GET['id']);
$contract_id = intval($_GET['contract_id']);

// 只删除本租户的分段和对应收费
$stmt_segs = $db->prepare("SELECT id FROM service_segments WHERE service_period_id=:spid AND tenant_id=:tenant_id");
$stmt_segs->bindValue(':spid', $id, PDO::PARAM_INT);
$stmt_segs->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_segs->execute();
while($seg = $stmt_segs->fetch(PDO::FETCH_ASSOC)) {
    $segid = $seg['id'];
    $stmt_del_pay = $db->prepare("DELETE FROM payments WHERE service_segment_id=:sid AND tenant_id=:tenant_id");
    $stmt_del_pay->bindValue(':sid', $segid, PDO::PARAM_INT);
    $stmt_del_pay->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_del_pay->execute();
    $stmt_del_seg = $db->prepare("DELETE FROM service_segments WHERE id=:sid AND tenant_id=:tenant_id");
    $stmt_del_seg->bindValue(':sid', $segid, PDO::PARAM_INT);
    $stmt_del_seg->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt_del_seg->execute();
}
// 删除服务期（仅本租户）
$stmt_del_sp = $db->prepare("DELETE FROM service_periods WHERE id=:id AND tenant_id=:tenant_id");
$stmt_del_sp->bindValue(':id', $id, PDO::PARAM_INT);
$stmt_del_sp->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt_del_sp->execute();

header('Location: contract_detail.php?id='.$contract_id);
exit;
?>