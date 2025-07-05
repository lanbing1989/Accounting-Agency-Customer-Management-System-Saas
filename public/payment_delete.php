<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$id = intval($_GET['id']);
$period_id = intval($_GET['period_id']);

// 只允许删除本租户收费记录
$stmt = $db->prepare("DELETE FROM payments WHERE id=:id AND tenant_id=:tenant_id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();

header('Location: payment_list.php?period_id='.$period_id);
exit;
?>