<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

global $pdo;

$id      = intval($_POST['id']      ?? 0);
$reply   = trim($_POST['reply']     ?? '');

if (!$id || !$reply) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit;
}

// Fetch the original message
$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
$stmt->execute([$id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bouchrachebili81@gmail.com';
    $mail->Password   = 'tgqj ukfx umka gjcn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('bouchrachebili81@gmail.com', 'JobDZ Support');
    $mail->addAddress($msg['email'], $msg['full_name']);
    $mail->addReplyTo('bouchrachebili81@gmail.com', 'JobDZ Support');

    $mail->isHTML(true);
    $mail->Subject = 'Re: ' . $msg['subject'];

    // ── HTML email body ───────────────────────────────────────────────────────
    $replyHtml  = nl2br(htmlspecialchars($reply));
    $origMsg    = nl2br(htmlspecialchars($msg['message']));
    $senderName = htmlspecialchars($msg['full_name']);
    $dateSent   = date('M d, Y · H:i', strtotime($msg['created_at']));

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;}
  .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(99,102,241,.10);}
  .header{background:linear-gradient(135deg,#4f46e5 0%,#6366f1 100%);padding:36px 36px 28px;text-align:center;}
  .header h1{margin:0;color:#fff;font-size:22px;font-weight:700;letter-spacing:-.3px;}
  .header p{margin:6px 0 0;color:rgba(255,255,255,.75);font-size:13px;}
  .body{padding:32px 36px;}
  .greeting{font-size:16px;font-weight:600;color:#0f172a;margin:0 0 12px;}
  .reply-box{background:#f8fafc;border-left:4px solid #6366f1;border-radius:0 14px 14px 0;padding:18px 20px;font-size:14px;color:#334155;line-height:1.75;margin-bottom:28px;}
  .divider{border:none;border-top:1px solid #e8eef6;margin:24px 0;}
  .orig-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin:0 0 10px;}
  .orig-box{background:#fafafe;border:1px solid #e8eef6;border-radius:12px;padding:16px 18px;font-size:13px;color:#64748b;line-height:1.7;}
  .footer{background:#f8fafc;padding:20px 36px;text-align:center;border-top:1px solid #e8eef6;}
  .footer p{margin:0;font-size:12px;color:#94a3b8;}
  .footer a{color:#6366f1;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>JobDZ Support</h1>
    <p>Response to your inquiry</p>
  </div>
  <div class="body">
    <p class="greeting">Hello {$senderName},</p>
    <p style="font-size:14px;color:#475569;margin:0 0 18px;">Thank you for reaching out. Here is our response to your message:</p>
    <div class="reply-box">{$replyHtml}</div>
    <hr class="divider">
    <p class="orig-label">Your original message · {$dateSent}</p>
    <div class="orig-box">{$origMsg}</div>
  </div>
  <div class="footer">
    <p>© 2025 <a href="#">JobDZ</a> · All rights reserved</p>
    <p style="margin-top:6px;">This is a reply to your contact form submission.</p>
  </div>
</div>
</body>
</html>
HTML;

    $mail->AltBody = "Hello {$msg['full_name']},\n\n{$reply}\n\n---\nYour original message:\n{$msg['message']}";

    $mail->send();

    // Mark as replied
    $pdo->prepare("UPDATE contact_messages SET is_read = 1, replied_at = NOW() WHERE id = ?")
        ->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
}
