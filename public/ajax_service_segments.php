<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';
$service_period_id = intval($_GET['service_period_id'] ?? 0);
$options = '<option value="">不选择分段</option>';
if ($service_period_id > 0) {
    $stmt = $db->prepare("SELECT * FROM service_segments WHERE service_period_id = :service_period_id AND tenant_id = :tenant_id ORDER BY id ASC");
    $stmt->bindValue(':service_period_id', $service_period_id, PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $txt = $row['start_date'].' ~ '.$row['end_date'].' / '.$row['package_type'].' / '.$row['price_per_year'].'元/年';
        $options .= '<option value="'.$row['id'].'">'.htmlspecialchars($txt).'</option>';
    }
}
echo $options;
?>