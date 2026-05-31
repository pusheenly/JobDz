<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

function sendVerificationEmail(PDO $pdo, int $userId, string $email, string $name): bool
{
  $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

  $stmt = $pdo->prepare("
        UPDATE users
        SET verification_token = :token,
            token_expires      = :expires,
            is_verified        = 0
        WHERE id = :id
    ");
  $stmt->execute([':token' => $code, ':expires' => $expires, ':id' => $userId]);

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bouchrachebili81@gmail.com';
    $mail->Password   = 'tgqj ukfx umka gjcn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPOptions = array(
      'ssl' => array(
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true
      )
    );
    $mail->Timeout = 10;

    $mail->setFrom('bouchrachebili81@gmail.com', 'JobDZ');
    $mail->addAddress($email, $name);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = '🔐 Your JobDZ verification code — ' . $code;
    $mail->Body    = buildVerificationTemplate($name, $code);
    $mail->AltBody = "Your JobDZ verification code is: $code\nExpires in 15 minutes.\n\nIf you didn't create a JobDZ account, ignore this email.";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Mailer Error (" . date('Y-m-d H:i:s') . "): " . $e->getMessage() . " | ErrorInfo: " . $mail->ErrorInfo);
    return false;
  }
}

function buildVerificationTemplate(string $name, string $code): string
{
  $digitsHtml = buildDigitsHtml($code);
  $year = date('Y');

  return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify your JobDZ email</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          <!-- LOGO BAR -->
          <tr>
            <td align="center" style="padding-bottom:24px;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:12px;width:38px;height:38px;text-align:center;vertical-align:middle;">
                    <span style="font-size:16px;font-weight:800;color:#ffffff;line-height:38px;display:block;">J</span>
                  </td>
                  <td style="padding-left:10px;">
                    <span style="font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.5px;">Job<span style="color:#6366f1;">DZ</span></span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- MAIN CARD -->
          <tr>
            <td style="background:#ffffff;border-radius:24px;border:1.5px solid #e2e8f0;box-shadow:0 16px 48px rgba(15,23,42,0.08);overflow:hidden;">

              <!-- HEADER BAND -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);padding:40px 48px;text-align:center;">
                    <!-- Icon box -->
                    <div style="width:64px;height:64px;background:rgba(255,255,255,0.15);border-radius:18px;display:inline-block;margin-bottom:18px;line-height:64px;font-size:28px;border:1px solid rgba(255,255,255,0.2);">✉️</div>
                    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.3px;">Verify your email</h1>
                    <p style="margin:8px 0 0;color:rgba(255,255,255,0.75);font-size:14px;font-weight:500;">One step away from your JobDZ account</p>
                  </td>
                </tr>
              </table>

              <!-- BODY -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:40px 48px;">

                    <p style="margin:0 0 6px;color:#0f172a;font-size:16px;font-weight:700;">Hello, $name 👋</p>
                    <p style="margin:0 0 28px;color:#64748b;font-size:14px;line-height:1.75;font-weight:500;">
                      Thanks for signing up! Enter the 6-digit code below to verify your email address and activate your JobDZ account. The code expires in <strong style="color:#0f172a;">15 minutes</strong>.
                    </p>

                    <!-- CODE BLOCK -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;padding:28px 20px;">
                          <p style="margin:0 0 16px;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#64748b;">Your verification code</p>
                          <div>$digitsHtml</div>
                          <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;font-weight:500;">
                            ⏱ Expires in 15 minutes
                          </p>
                        </td>
                      </tr>
                    </table>

                    <!-- DIVIDER -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                      <tr><td style="height:1px;background:#e2e8f0;"></td></tr>
                    </table>

                    <!-- STEPS -->
                    <p style="margin:0 0 14px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">How to verify</p>

                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:0 0 10px;">
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#ede9fe;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#6366f1;">1</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Go back to the JobDZ verification page</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:0 0 10px;">
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#ede9fe;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#6366f1;">2</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Enter the 6-digit code shown above</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#E1F5EE;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#1D9E75;">✓</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Your account will be activated instantly</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- DIVIDER -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                      <tr><td style="height:1px;background:#e2e8f0;"></td></tr>
                    </table>

                    <!-- SECURITY NOTE -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="background:#fef9ec;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;">
                          <p style="margin:0;font-size:12px;color:#92400e;font-weight:600;line-height:1.6;">
                            🔒 <strong>Security note:</strong> JobDZ will never ask for your password by email. If you didn't create this account, you can safely ignore this message.
                          </p>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td align="center" style="padding:28px 0 0;">
              <p style="margin:0 0 6px;font-size:13px;color:#94a3b8;font-weight:500;">© $year JobDZ — Algeria's #1 Job Platform</p>
              <p style="margin:0;font-size:12px;color:#cbd5e1;">You're receiving this because you created an account on JobDZ.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}


function sendResetCode(PDO $pdo, int $userId, string $email): bool
{
  $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

  $requiredColumns = [
    'reset_token'   => 'VARCHAR(6) NULL',
    'reset_expires' => 'DATETIME NULL',
  ];

  foreach ($requiredColumns as $column => $definition) {
    $check = $pdo->prepare(
      "SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'users'
               AND column_name = ?"
    );
    $check->execute([$column]);
    if ((int) $check->fetchColumn() === 0) {
      $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
    }
  }

  $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
    ->execute([$code, $expires, $userId]);

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bouchrachebili81@gmail.com';
    $mail->Password   = 'tgqj ukfx umka gjcn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('bouchrachebili81@gmail.com', 'JobDZ');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = '🔑 Your JobDZ password reset code — ' . $code;
    $mail->Body    = buildResetTemplate($code);
    $mail->AltBody = "Your JobDZ password reset code is: $code\nExpires in 15 minutes.\n\nIf you didn't request this, ignore this email.";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Reset Mailer Error: " . $mail->ErrorInfo);
    return false;
  }
}

function buildResetTemplate(string $code): string
{
  $digitsHtml = buildDigitsHtml($code);
  $year = date('Y');

  return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset your JobDZ password</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          <!-- LOGO BAR -->
          <tr>
            <td align="center" style="padding-bottom:24px;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:12px;width:38px;height:38px;text-align:center;vertical-align:middle;">
                    <span style="font-size:16px;font-weight:800;color:#ffffff;line-height:38px;display:block;">J</span>
                  </td>
                  <td style="padding-left:10px;">
                    <span style="font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.5px;">Job<span style="color:#6366f1;">DZ</span></span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- MAIN CARD -->
          <tr>
            <td style="background:#ffffff;border-radius:24px;border:1.5px solid #e2e8f0;box-shadow:0 16px 48px rgba(15,23,42,0.08);overflow:hidden;">

              <!-- HEADER BAND -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:40px 48px;text-align:center;position:relative;">
                    <div style="width:64px;height:64px;background:rgba(99,102,241,0.2);border-radius:18px;display:inline-block;margin-bottom:18px;line-height:64px;font-size:28px;border:1px solid rgba(99,102,241,0.3);">🔑</div>
                    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-0.3px;">Password Reset</h1>
                    <p style="margin:8px 0 0;color:rgba(255,255,255,0.5);font-size:14px;font-weight:500;">Your reset code is ready</p>
                  </td>
                </tr>
              </table>

              <!-- BODY -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:40px 48px;">

                    <p style="margin:0 0 6px;color:#0f172a;font-size:16px;font-weight:700;">Account Recovery</p>
                    <p style="margin:0 0 28px;color:#64748b;font-size:14px;line-height:1.75;font-weight:500;">
                      We received a request to reset the password for your JobDZ account. Use the code below to set a new password. It expires in <strong style="color:#0f172a;">15 minutes</strong>.
                    </p>

                    <!-- CODE BLOCK -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td align="center" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;padding:28px 20px;">
                          <p style="margin:0 0 16px;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#64748b;">Your reset code</p>
                          <div>$digitsHtml</div>
                          <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;font-weight:500;">
                            ⏱ Expires in 15 minutes
                          </p>
                        </td>
                      </tr>
                    </table>

                    <!-- DIVIDER -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                      <tr><td style="height:1px;background:#e2e8f0;"></td></tr>
                    </table>

                    <!-- STEPS -->
                    <p style="margin:0 0 14px;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Next steps</p>

                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:0 0 10px;">
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#ede9fe;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#6366f1;">1</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Go back to the JobDZ reset password page</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding:0 0 10px;">
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#ede9fe;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#6366f1;">2</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Enter the 6-digit code and your new password</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                              <td style="width:32px;height:32px;background:#E1F5EE;border-radius:8px;text-align:center;vertical-align:middle;font-size:13px;font-weight:800;color:#1D9E75;">✓</td>
                              <td style="padding-left:12px;font-size:13px;color:#475569;font-weight:500;">Sign in with your new password</td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!-- DIVIDER -->
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                      <tr><td style="height:1px;background:#e2e8f0;"></td></tr>
                    </table>

                    <!-- SECURITY NOTE -->
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="background:#fef9ec;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;">
                          <p style="margin:0;font-size:12px;color:#92400e;font-weight:600;line-height:1.6;">
                            🔒 <strong>Didn't request this?</strong> If you didn't ask to reset your password, your account may be at risk. We recommend changing your password and enabling two-factor authentication.
                          </p>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

            </td>
          </tr>
          <tr>
            <td align="center" style="padding:28px 0 0;">
              <p style="margin:0 0 6px;font-size:13px;color:#94a3b8;font-weight:500;">© $year JobDZ — Algeria's #1 Job Platform</p>
              <p style="margin:0;font-size:12px;color:#cbd5e1;">You're receiving this because a password reset was requested for your account.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}

function buildDigitsHtml(string $code): string
{
  $html = '';
  foreach (str_split($code) as $d) {
    $html .= "<span style='"
      . "display:inline-block;"
      . "width:48px;height:58px;line-height:58px;"
      . "text-align:center;"
      . "font-size:26px;font-weight:800;"
      . "color:#6366f1;"
      . "background:#ede9fe;"
      . "border:1.5px solid #c7d2fe;"
      . "border-radius:12px;"
      . "margin:0 4px;"
      . "letter-spacing:0;"
      . "'>$d</span>";
  }
  return $html;
}
