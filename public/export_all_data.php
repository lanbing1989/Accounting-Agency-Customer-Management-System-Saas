<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/check_package.php';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="accounting_export_all.csv"');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    '客户ID', '客户名称', '联系人', '联系电话', '联系邮箱', '客户备注',
    '服务期ID', '服务开始', '服务结束', '服务月数', '套餐类型',
    '分段ID', '分段开始', '分段结束', '年费', '分段金额', '分段套餐', '分段备注',
    '收费ID', '收款日期', '收款金额', '备注', '是否临时收费'
]);

// 只导出本租户的数据
$clients_stmt = $db->prepare("SELECT * FROM contracts WHERE tenant_id = :tenant_id ORDER BY id");
$clients_stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
$clients_stmt->execute();
while ($client = $clients_stmt->fetch(PDO::FETCH_ASSOC)) {
    $periods_stmt = $db->prepare("SELECT * FROM service_periods WHERE contract_id=:cid AND tenant_id=:tenant_id ORDER BY service_start");
    $periods_stmt->bindValue(':cid', $client['id'], PDO::PARAM_INT);
    $periods_stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
    $periods_stmt->execute();
    $has_period = false;
    while ($period = $periods_stmt->fetch(PDO::FETCH_ASSOC)) {
        $has_period = true;
        $segments_stmt = $db->prepare("SELECT * FROM service_segments WHERE service_period_id=:pid AND tenant_id=:tenant_id ORDER BY start_date");
        $segments_stmt->bindValue(':pid', $period['id'], PDO::PARAM_INT);
        $segments_stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
        $segments_stmt->execute();
        $has_segment = false;
        while ($seg = $segments_stmt->fetch(PDO::FETCH_ASSOC)) {
            $has_segment = true;
            $payments_stmt = $db->prepare("SELECT * FROM payments WHERE service_segment_id=:sid AND tenant_id=:tenant_id ORDER BY pay_date");
            $payments_stmt->bindValue(':sid', $seg['id'], PDO::PARAM_INT);
            $payments_stmt->bindValue(':tenant_id', $_SESSION['tenant_id'], PDO::PARAM_INT);
            $payments_stmt->execute();
            $has_payment = false;
            while ($pay = $payments_stmt->fetch(PDO::FETCH_ASSOC)) {
                $has_payment = true;
                fputcsv($output, [
                    $client['id'], $client['client_name'], $client['contact_person'], $client['contact_phone'], $client['contact_email'] ?? '', $client['remark'] ?? '',
                    $period['id'], $period['service_start'], $period['service_end'], $period['month_count'], $period['package_type'],
                    $seg['id'], $seg['start_date'], $seg['end_date'], $seg['price_per_year'], $seg['segment_fee'], $seg['package_type'], $seg['remark'] ?? '',
                    $pay['id'], $pay['pay_date'], $pay['amount'], $pay['remark'] ?? '', $pay['is_temp'] ? '是' : '否'
                ]);
            }
            // 如果分段下没有任何收费，也要输出一行
            if (!$has_payment) {
                fputcsv($output, [
                    $client['id'], $client['client_name'], $client['contact_person'], $client['contact_phone'], $client['contact_email'] ?? '', $client['remark'] ?? '',
                    $period['id'], $period['service_start'], $period['service_end'], $period['month_count'], $period['package_type'],
                    $seg['id'], $seg['start_date'], $seg['end_date'], $seg['price_per_year'], $seg['segment_fee'], $seg['package_type'], $seg['remark'] ?? '',
                    '', '', '', '', ''
                ]);
            }
        }
        // 如果服务期下没有任何分段
        if (!$has_segment) {
            fputcsv($output, [
                $client['id'], $client['client_name'], $client['contact_person'], $client['contact_phone'], $client['contact_email'] ?? '', $client['remark'] ?? '',
                $period['id'], $period['service_start'], $period['service_end'], $period['month_count'], $period['package_type'],
                '', '', '', '', '', '', '',
                '', '', '', '', ''
            ]);
        }
    }
    // 如果客户没有服务期
    if (!$has_period) {
        fputcsv($output, [
            $client['id'], $client['client_name'], $client['contact_person'], $client['contact_phone'], $client['contact_email'] ?? '', $client['remark'] ?? '',
            '', '', '', '', '',
            '', '', '', '', '', '', '',
            '', '', '', '', ''
        ]);
    }
}
fclose($output);
exit;
?>