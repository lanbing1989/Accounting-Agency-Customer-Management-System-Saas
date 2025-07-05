<?php
require_once __DIR__.'/db.php';
$no = $_GET['no'] ?? '';
if (!$no) die('参数错误');
// 若允许租户查验自己的合同，需指定 tenant_id。假设查验口令包含租户信息或全局唯一编号可查全库。
// 如果要限制为某租户（如 $_SESSION['tenant_id']），则需加条件：AND tenant_id=xxx
$stmt = $db->prepare("SELECT contract_no,contract_hash,sign_date,sign_phone,sign_ip FROM contracts_agreement WHERE contract_no=:no");
$stmt->bindValue(':no', $no, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die('未查到该合同');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>合同查验</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; font-size: 16px; }
        .verify-container { max-width: 420px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #ddd; padding: 28px 14px 22px 14px; }
        .verify-title { font-weight: 700; font-size: 1.3rem; text-align: center; margin-bottom: 22px; color: #1377c8; }
        .verify-table { width: 100%; }
        .verify-table th, .verify-table td { padding: 10px 6px; border-bottom: 1px solid #f1f1f1; }
        .verify-table th { color: #555; font-weight: 500; width: 34%; background: #f9f9f9; }
        .verify-table tr:last-child td, .verify-table tr:last-child th { border-bottom: none; }
        .verify-footer { text-align: center; color: #aaa; font-size: 13px; margin-top: 16px; }
        @media (max-width: 600px) {
            .verify-container { margin: 10px 0; padding: 16px 2vw 12px 2vw; border-radius: 0; box-shadow: none;}
        }
    </style>
</head>
<body>
<div class="verify-container">
    <div class="verify-title">合同查验</div>
    <table class="verify-table">
        <tr>
            <th>合同编号</th>
            <td><?=htmlspecialchars($row['contract_no'])?></td>
        </tr>
        <tr>
            <th>合同哈希</th>
            <td style="word-break:break-all;"><?=htmlspecialchars($row['contract_hash'])?></td>
        </tr>
        <tr>
            <th>签署日期</th>
            <td><?=htmlspecialchars($row['sign_date'])?></td>
        </tr>
        <tr>
            <th>签署手机号</th>
            <td><?=htmlspecialchars($row['sign_phone'])?></td>
        </tr>
        <tr>
            <th>签署IP</th>
            <td><?=htmlspecialchars($row['sign_ip'])?></td>
        </tr>
    </table>
    <div class="verify-footer">
        © <?=date('Y')?> 合同查验平台
    </div>
</div>
</body>
</html>