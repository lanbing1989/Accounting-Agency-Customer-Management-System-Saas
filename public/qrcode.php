<?php
// 需 composer require endroid/qr-code
require __DIR__ . '/../vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$text = $_GET['text'] ?? '';
header('Content-Type: image/png');
// 限制二维码内容长度，防止恶意大数据DoS
if (mb_strlen($text, 'utf-8') > 1024) $text = mb_substr($text, 0, 1024, 'utf-8');
$qr = QrCode::create($text)
    ->setSize(160)
    ->setMargin(10);
echo (new PngWriter())->write($qr)->getString();
?>