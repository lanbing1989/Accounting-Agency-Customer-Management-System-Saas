<?php
require_once __DIR__.'/db.php';
$order_no = $_GET['order_no'] ?? '';
$stmt = $db->prepare("SELECT status FROM tenant_orders WHERE order_no=?");
$stmt->execute([$order_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(['paid'=>$order && $order['status']=='paid']);