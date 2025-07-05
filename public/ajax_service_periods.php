<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$contract_id = intval($_GET['contract_id'] ?? 0);
$options = '<option value="">请选择服务期</option>';
if ($contract_id > 0) {
    $stmt = $db->prepare("SELECT * FROM service_periods WHERE contract_id = :contract_id AND tenant_id = :tenant_id ORDER BY id DESC");
    $stmt->bindValue(':contract_id', $contract_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $txt = $row['service_start'].' ~ '.$row['service_end'].' / '.$row['package_type'].' / '.$row['month_count'].'月';
        $options .= '<option value="'.$row['id'].'">'.htmlspecialchars($txt).'</option>';
    }
}
echo $options;
?>