<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) die('参数错误');

// 查询合同，获取签名图片路径（仅本租户）
$stmt = $db->prepare("SELECT id, sign_image FROM contracts_agreement WHERE uuid = :uuid AND tenant_id = :tenant_id");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die('合同不存在');

// 删除签名图片
if (!empty($row['sign_image']) && file_exists($row['sign_image'])) {
    unlink($row['sign_image']);
}

// 删除合同记录（仅本租户）
$stmt = $db->prepare("DELETE FROM contracts_agreement WHERE uuid = :uuid AND tenant_id = :tenant_id");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$stmt->execute();

header("Location: ht_agreements.php");
exit;
?>