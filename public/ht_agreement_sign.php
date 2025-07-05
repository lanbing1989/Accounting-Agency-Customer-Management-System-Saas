<?php
require 'db.php';
session_start();
// === 用uuid参数，不用id ===
$uuid = $_GET['uuid'] ?? '';
if(!$uuid) die('参数错误');

// === 用uuid查询合同 ===
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id, a.content_snapshot, a.sign_image, a.contract_no, a.contract_hash
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
$stmt->execute();
$agreement = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$agreement) die('合同不存在');

// 服务期
$period = null;
if ($agreement['service_period_id']) {
    $stmt_period = $db->prepare("SELECT * FROM service_periods WHERE id=:id");
    $stmt_period->bindValue(':id', $agreement['service_period_id'], PDO::PARAM_INT);
    $stmt_period->execute();
    $period = $stmt_period->fetch(PDO::FETCH_ASSOC);
}
// 分段
$segment = null;
if ($agreement['service_segment_id']) {
    $stmt_segment = $db->prepare("SELECT * FROM service_segments WHERE id=:id");
    $stmt_segment->bindValue(':id', $agreement['service_segment_id'], PDO::PARAM_INT);
    $stmt_segment->execute();
    $segment = $stmt_segment->fetch(PDO::FETCH_ASSOC);
}

// 获取盖章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $stmt_seal = $db->prepare("SELECT image_path FROM seal_templates WHERE id=:id");
    $stmt_seal->bindValue(':id', $agreement['seal_id'], PDO::PARAM_INT);
    $stmt_seal->execute();
    $seal = $stmt_seal->fetch(PDO::FETCH_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 签名图片
$signature_img = $agreement['sign_image'] ?? '';

// 处理签署日期
if (!empty($agreement['sign_date'])) {
    $sign_date = $agreement['sign_date'];
    $sign_year = date('Y', strtotime($sign_date));
    $sign_month = date('m', strtotime($sign_date));
    $sign_day = date('d', strtotime($sign_date));
} else {
    $sign_date = date('Y-m-d');
    $sign_year = date('Y');
    $sign_month = date('m');
    $sign_day = date('d');
}

// 合同编号和哈希
$contract_no = $agreement['contract_no'] ?? '';
$contract_hash = $agreement['contract_hash'] ?? '';

$vars = [
    'client_name'    => $agreement['client_name'] ?? '',
    'contact_person' => $agreement['contact_person'] ?? '',
    'contact_phone'  => $agreement['contact_phone'] ?? '',
    'contact_email'  => $agreement['contact_email'] ?? '',
    'remark'         => $agreement['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
    'contract_no'    => $contract_no
];

// 渲染函数
function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '', $nohashfooter = false, $contract_hash = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if (strpos($tpl, '{signature}') !== false) {
        if ($signature_img) {
            $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
        } else {
            $tpl = str_replace('{signature}', '<button id="showSignPad" class="btn btn-outline-primary btn-sm w-100 mt-3 mb-2">甲方在线签字</button><div id="signPadArea" style="display:none;margin-top:10px;"></div>', $tpl);
        }
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    if (!$nohashfooter) {
        $footer = "<hr style='border:1px dashed #bbb; margin:20px 0;'>
        <div style='font-size:15px;color:#666;'>
        ".($vars['contract_no']?"合同编号：".htmlspecialchars($vars['contract_no'])."<br>":"").
        ($contract_hash?"合同哈希：".htmlspecialchars($contract_hash)."<br>":"").
        ($vars['contract_no']?"<span>扫码查验真伪：</span>
        <img src='qrcode.php?text=".urlencode($vars['contract_no'])."' height='80'>":"")."
        </div>";
        $tpl .= $footer;
    }
    return $tpl;
}

// ========== 关键：未签署时动态渲染，已签署时用快照 ==========
if (empty($agreement['sign_image'])) {
    // 未签署，允许签字
    $content = render_contract_template($agreement['template_content'], $vars, $seal_img, '', true);
} else {
    // 已签署，优先用快照（含签字图片、编号、哈希、二维码）
    if (!empty($agreement['content_snapshot'])) {
        $content = $agreement['content_snapshot'];
    } else {
        $content = render_contract_template($agreement['template_content'], $vars, $seal_img, $signature_img, false, $contract_hash);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同在线签署</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <style>
    #signature-pad {
        border:1px solid #aaa;
        border-radius:8px;
        background:#fff;
        width: 100%;
        max-width: 400px;
        height: 180px;
        touch-action: none;
        display: block;
        margin: 0 auto;
    }
    @media (max-width: 600px) {
      #signature-pad {
        width: 100% !important;
        max-width: 100vw !important;
        height: 180px !important;
      }
      .btn {
        font-size: 18px;
        padding: 10px 0;
        width: 100%;
        margin-bottom: 12px;
      }
      body {
        font-size: 16px;
      }
      .container {
        padding-left: 3px;
        padding-right: 3px;
      }
    }
    /* 打印优化：隐藏按钮/警告等不需要打印的内容 */
    @media print {
      .noprint, .btn, .alert {
        display: none !important;
      }
      body {
        background: #fff !important;
      }
      .container {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
      }
    }
    </style>
    <!-- 引入html2canvas CDN -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-3 mb-3" style="max-width:800px;">
    <h4 class="mb-3 text-center">合同在线签署</h4>
    <div class="bg-white p-3 p-md-4 rounded shadow-sm mb-4" style="white-space:pre-line;" id="contractContent">
        <?= $content ?>
    </div>
    <?php if ($signature_img): ?>
        <div class="noprint text-end mb-3">
            <button class="btn btn-primary" id="printBtn">打印合同</button>
            <button class="btn btn-success" id="genJpgBtn">生成图片</button>
        </div>
        <div class="alert alert-success">合同已签署完成，您可随时查看。</div>
        <div class="alert alert-info">如需下载PDF，请联系您的服务顾问发送给您。</div>
    <?php endif;?>
</div>

<?php if (empty($signature_img)): ?>
<script src="/bootstrap/signature_pad.umd.min.js"></script>
<script>
// ========== 短信验证弹窗 ==========
function showSmsVerify(callback) {
    let html = `<div id="smsDialog" style="z-index:9999;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,.2);display:flex;align-items:center;justify-content:center;">
      <div style="background:#fff;padding:28px 18px 18px 18px;border-radius:10px;max-width:330px;width:100vw;box-shadow:0 2px 10px #aaa">
        <div class="mb-3">
          <input type="text" id="smsPhone" class="form-control" placeholder="请输入手机号">
        </div>
        <div class="mb-3 d-flex" style="gap:8px;">
          <input type="text" id="smsCode" class="form-control" placeholder="验证码">
          <button type="button" class="btn btn-primary" id="smsSendBtn" style="min-width:90px;">获取验证码</button>
        </div>
        <div style="color:#888;font-size:13px;margin-bottom:8px;">为保障签署有效性，需短信验证</div>
        <button type="button" class="btn btn-success w-100" id="smsCheckBtn">提交</button>
      </div>
    </div>`;
    let div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div);
    let smsSendBtn = div.querySelector('#smsSendBtn');
    let smsCheckBtn = div.querySelector('#smsCheckBtn');
    let smsPhone = div.querySelector('#smsPhone');
    let smsCode = div.querySelector('#smsCode');
    let sending = false, countdown = 0, timer = null;

    smsSendBtn.onclick = function() {
        let phone = smsPhone.value.trim();
        if (!/^1\d{10}$/.test(phone)) { alert('请输入正确手机号'); return; }
        if (sending || countdown > 0) return;
        sending = true; smsSendBtn.innerText = '发送中...';
        fetch('send_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'phone=' + encodeURIComponent(phone)
        }).then(r=>r.json()).then(res=>{
            sending = false;
            if(res.ok){
                countdown = 60;
                smsSendBtn.innerText = '60秒后重发';
                timer = setInterval(()=>{
                  countdown--;
                  smsSendBtn.innerText = countdown>0 ? countdown+'秒后重发' : '获取验证码';
                  if(countdown<=0){clearInterval(timer);}
                }, 1000);
                alert('验证码已发送，请查收短信');
            }else{
                smsSendBtn.innerText = '获取验证码';
                alert(res.msg||'发送失败');
            }
        }).catch(()=>{sending=false;smsSendBtn.innerText='获取验证码';});
    };

    smsCheckBtn.onclick = function() {
        let phone = smsPhone.value.trim();
        let code = smsCode.value.trim();
        if (!/^1\d{10}$/.test(phone)) { alert('请输入正确手机号'); return; }
        if (!/^\d{6}$/.test(code)) { alert('请输入6位验证码'); return; }
        smsCheckBtn.disabled = true;
        smsCheckBtn.innerText = "验证中...";
        fetch('check_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'phone=' + encodeURIComponent(phone) + '&code=' + encodeURIComponent(code)
        }).then(r=>r.json()).then(res=>{
            smsCheckBtn.disabled = false;
            smsCheckBtn.innerText = "验证通过";
            if(res.ok){
                document.body.removeChild(div);
                callback(phone, code);
            }else{
                alert(res.msg||'短信验证失败');
            }
        }).catch(()=>{
            smsCheckBtn.disabled = false;
            smsCheckBtn.innerText = "验证通过";
            alert('网络错误，请重试');
        });
    };
}
document.addEventListener('DOMContentLoaded', function() {
    let showBtn = document.getElementById('showSignPad');
    if (showBtn) {
        showBtn.onclick = function() {
            showSmsVerify(function(phone, code){
                // 弹出签名板
                const area = document.getElementById('signPadArea');
                area.innerHTML = `
                    <div class="mb-2">
                      <canvas id="signature-pad" width="400" height="180" style="width:100%;height:180px;touch-action:none;"></canvas>
                    </div>
                    <div class="mb-2 d-flex gap-2 flex-wrap">
                      <button id="clear-sign" class="btn btn-warning btn-lg flex-fill">清除</button>
                      <button id="save-sign" class="btn btn-success btn-lg flex-fill">确认签署</button>
                    </div>
                    <div class="text-muted text-center" style="font-size:15px;">请用手指或触控笔在上方区域签名</div>
                `;
                area.style.display = '';
                area.scrollIntoView({behavior: 'smooth', block: 'center'});

                function resizeCanvas() {
                    var canvas = document.getElementById('signature-pad');
                    var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                    var width = area.offsetWidth > 0 ? area.offsetWidth : 350;
                    canvas.width = width * ratio;
                    canvas.height = 180 * ratio;
                    canvas.style.width = width + 'px';
                    canvas.style.height = '180px';
                    var ctx = canvas.getContext('2d');
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.scale(ratio, ratio);
                }
                setTimeout(resizeCanvas, 100);
                window.addEventListener('resize', resizeCanvas);

                let canvas = document.getElementById('signature-pad');
                let pad = new SignaturePad(canvas, { backgroundColor: '#fff', minWidth: 1.5, maxWidth: 3 });

                document.getElementById('clear-sign').onclick = function() { pad.clear(); }
                document.getElementById('save-sign').onclick = function() {
                    if (pad.isEmpty()) { alert('请先签名'); return; }
                    let data = pad.toDataURL('image/png');
                    fetch('ht_agreement_sign_save.php?uuid=<?= urlencode($agreement['uuid']) ?>',{
                        method:'POST',
                        headers:{'Content-Type':'application/json'},
                        body: JSON.stringify({ signature: data, phone: phone, code: code })
                    }).then(r=>r.json()).then(res=>{
                        if(res.ok){
                            location.reload();
                        }else{
                            alert(res.msg||'保存失败');
                        }
                    });
                }
            });
        }
    }
});
</script>
<?php endif;?>

<?php if ($signature_img): ?>
<script>
function isSpecialBrowser() {
    return /MiuiBrowser|QQBrowser|UCBrowser|Quark|MicroMessenger|AlipayClient/i.test(navigator.userAgent);
}
document.addEventListener('DOMContentLoaded', function() {
    var printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.onclick = function() {
            if (isSpecialBrowser()) {
                alert('当前浏览器不支持打印功能，请点击右上角菜单用系统浏览器（如Chrome/Safari）打开本页再打印，或联系顾问获取PDF文件。');
            } else {
                window.print();
            }
        }
    }
    var genJpgBtn = document.getElementById('genJpgBtn');
    if (genJpgBtn) {
        genJpgBtn.onclick = function() {
            var node = document.getElementById('contractContent');
            html2canvas(node, {backgroundColor: '#fff', scale: 2}).then(function(canvas) {
                var imgData = canvas.toDataURL('image/jpeg', 0.95);
                var link = document.createElement('a');
                link.href = imgData;
                link.download = 'contract.jpg';
                link.click();
            });
        };
    }
});
</script>
<?php endif;?>
</body>
</html>