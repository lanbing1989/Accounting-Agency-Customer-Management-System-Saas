<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
$phone = $_POST['phone'] ?? '';
$code = $_POST['code'] ?? '';

$phone = trim($phone);
$code = trim($code);

if (!preg_match('/^1\d{10}$/', $phone) || !preg_match('/^\d{6}$/', $code)) {
    exit(json_encode(['ok'=>0, 'msg'=>'参数错误']));
}

// 只做校验，不清除验证码
if (
    isset($_SESSION['sms_verify_phone'], $_SESSION['sms_verify_code'], $_SESSION['sms_verify_time']) &&
    $_SESSION['sms_verify_phone'] === $phone &&
    $_SESSION['sms_verify_code'] == $code &&
    time() - $_SESSION['sms_verify_time'] < 300
) {
    exit(json_encode(['ok'=>1]));
} else {
    exit(json_encode(['ok'=>0, 'msg'=>'验证码错误或已过期']));
}
?>