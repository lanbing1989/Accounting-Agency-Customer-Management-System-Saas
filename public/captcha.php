<?php
session_start();
$code = '';
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
for ($i=0;$i<4;$i++) {
    $code .= $chars[rand(0, strlen($chars)-1)];
}
$_SESSION['captcha_code'] = $code;

header('Content-Type: image/png');
$img = imagecreate(90, 34);
$bg  = imagecolorallocate($img, 245, 250, 255);
$text= imagecolorallocate($img, 36, 112, 225);
$line= imagecolorallocate($img, 200, 220, 245);

// 干扰线
for($i=0;$i<3;$i++){
    imageline($img, rand(0,90), rand(0,34), rand(0,90), rand(0,34), $line);
}
// 干扰点
for($i=0;$i<35;$i++){
    imagesetpixel($img, rand(0,90), rand(0,34), $line);
}

// 显示验证码
imagestring($img, 5, 22, 8, $code, $text);
imagepng($img);
imagedestroy($img);