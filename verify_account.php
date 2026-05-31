<?php
session_start();
require 'config.php';

$message = '';
$success = false;

if (isset($_GET['token']) && !empty($_GET['token'])) {

    $token = trim($_GET['token']);

    $stmt = $pdo->prepare("
        SELECT id, email, name, token_expires, is_verified
        FROM users
        WHERE verification_token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = 'Invalid or already used verification link.';
    } elseif ($user['is_verified']) {
        $message = 'Your account is already verified. You can log in.';
        $success = true;
    } elseif (new DateTime() > new DateTime($user['token_expires'])) {
        $message = 'This verification link has expired. Please request a new one.';
    } else {
        $update = $pdo->prepare("
            UPDATE users
            SET is_verified        = 1,
                verification_token = NULL,
                token_expires      = NULL
            WHERE id = :id
        ");
        $update->execute([':id' => $user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['name']    = $user['name'];

        $success = true;
        $message = 'Your email has been verified successfully! Welcome to JobDZ.';
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | JobDZ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --ink: #0f172a;
            --ink-2: #1e293b;
            --ink-3: #475569;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #ffffff;
            --bg: #f8fafc;
            --accent: #6366f1;
            --accent2: #4f46e5;
            --accent-lt: #ede9fe;
            --green: #1D9E75;
            --green-lt: #E1F5EE;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, .3);
        }

        .logo-text {
            font-size: 19px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.4px;
        }

        .logo-text span {
            color: var(--accent);
        }

        .topbar-hint {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .topbar-hint a {
            color: var(--accent);
            font-weight: 700;
            text-decoration: none;
        }

        .topbar-hint a:hover {
            text-decoration: underline;
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 24px;
            position: relative;
            overflow: hidden;
        }

        .main::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 50% 50% at 80% 20%, rgba(99, 102, 241, .07) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(99, 102, 241, .05) 0%, transparent 60%);
            pointer-events: none;
        }

        .main-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(99, 102, 241, .03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, .03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ── CARD ── */
        .card {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 24px;
            padding: 56px 48px;
            width: 100%;
            max-width: 480px;
            text-align: center;
            box-shadow: 0 16px 56px rgba(15, 23, 42, .08);
            position: relative;
            z-index: 2;
            animation: fadeUp .55s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(28px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── STATE ICON ── */
        .state-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 28px;
            position: relative;
        }

        .state-icon::after {
            content: '';
            position: absolute;
            inset: -7px;
            border-radius: 50%;
            border: 1.5px dashed;
            opacity: .3;
        }

        .state-icon.success {
            background: var(--green-lt);
            color: var(--green);
        }

        .state-icon.success::after {
            border-color: var(--green);
        }

        .state-icon.error {
            background: #fef2f2;
            color: #dc2626;
        }

        .state-icon.error::after {
            border-color: #dc2626;
        }

        /* ── CONFETTI DOTS (success only) ── */
        @keyframes confettiFall {
            0% {
                transform: translateY(-10px) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(60px) rotate(360deg);
                opacity: 0;
            }
        }

        .confetti-wrap {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 60px;
            pointer-events: none;
            overflow: visible;
        }

        .dot {
            position: absolute;
            width: 7px;
            height: 7px;
            border-radius: 2px;
            animation: confettiFall 1.4s ease-out both;
        }

        .dot:nth-child(1) {
            left: 10px;
            background: var(--accent);
            animation-delay: .1s;
        }

        .dot:nth-child(2) {
            left: 30px;
            background: #f59e0b;
            animation-delay: .2s;
        }

        .dot:nth-child(3) {
            left: 50px;
            background: var(--green);
            animation-delay: .05s;
        }

        .dot:nth-child(4) {
            left: 70px;
            background: var(--accent2);
            animation-delay: .3s;
        }

        .dot:nth-child(5) {
            left: 90px;
            background: #ec4899;
            animation-delay: .15s;
        }

        .dot:nth-child(6) {
            left: 20px;
            background: var(--green);
            animation-delay: .35s;
        }

        .dot:nth-child(7) {
            left: 60px;
            background: #f59e0b;
            animation-delay: .25s;
        }

        .dot:nth-child(8) {
            left: 100px;
            background: var(--accent);
            animation-delay: .4s;
        }

        /* ── EYEBROW ── */
        .eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 9px;
            justify-content: center;
            margin-bottom: 12px;
        }

        .eyebrow::before,
        .eyebrow::after {
            content: '';
            width: 18px;
            height: 2px;
            border-radius: 2px;
        }

        .eyebrow.success {
            color: var(--green);
        }

        .eyebrow.success::before,
        .eyebrow.success::after {
            background: var(--green);
        }

        .eyebrow.error {
            color: #dc2626;
        }

        .eyebrow.error::before,
        .eyebrow.error::after {
            background: #dc2626;
        }

        .card-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.5px;
            margin-bottom: 12px;
        }

        .card-msg {
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
            line-height: 1.75;
            max-width: 340px;
            margin: 0 auto 32px;
        }

        /* ── SUCCESS DETAILS ── */
        .success-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 32px;
            text-align: left;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
            font-size: 13px;
            color: var(--ink-3);
            font-weight: 500;
        }

        .detail-row i {
            color: var(--green);
            font-size: 14px;
            flex-shrink: 0;
        }

        /* ── BUTTONS ── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            border-radius: 14px;
            padding: 14px 32px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .28);
            transition: transform .2s, box-shadow .2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .38);
            color: #fff;
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            color: var(--ink);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 13px 32px;
            text-decoration: none;
            transition: border-color .15s, color .15s, transform .15s;
        }

        .btn-ghost:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-1px);
        }

        /* ── DIVIDER ── */
        .divider {
            height: 1px;
            background: var(--line);
            margin: 28px 0;
        }

        /* ── FOOTER ── */
        footer {
            text-align: center;
            padding: 20px 24px;
            font-size: 12px;
            color: var(--muted);
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        @media (max-width: 520px) {
            .card {
                padding: 40px 24px;
            }

            .topbar {
                padding: 0 20px;
            }
        }
    </style>
</head>

<body>

    <!--  TOPBAR  -->
    <header class="topbar">
        <a href="index.php" class="logo">
            <div class="logo-mark">J</div>
            <span class="logo-text">Job<span>DZ</span></span>
        </a>
        <p class="topbar-hint">
            Already verified? <a href="login.php">Sign in</a>
        </p>
    </header>

    <!--  MAIN  -->
    <div class="main">
        <div class="main-grid"></div>

        <div class="card">

            <?php if ($success): ?>

                <!-- confetti -->
                <div class="confetti-wrap" aria-hidden="true">
                    <?php for ($i = 0; $i < 8; $i++) echo '<div class="dot"></div>'; ?>
                </div>

                <div class="state-icon success">
                    <i class="fas fa-check"></i>
                </div>

                <div class="eyebrow success">Verification Complete</div>
                <h1 class="card-title">Email Verified!</h1>
                <p class="card-msg"><?php echo htmlspecialchars($message); ?></p>

                <div class="success-details">
                    <div class="detail-row">
                        <i class="fas fa-shield-alt"></i>
                        Your account is now fully activated and secured
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-bolt"></i>
                        You can now apply to jobs and manage your profile
                    </div>
                    <div class="detail-row">
                        <i class="fas fa-bell"></i>
                        Job alerts and notifications are now enabled
                    </div>
                </div>

                <a href="index.php" class="btn-primary">
                    Go to Dashboard <i class="fas fa-arrow-right" style="font-size:12px"></i>
                </a>

            <?php else: ?>

                <div class="state-icon error">
                    <i class="fas fa-times"></i>
                </div>

                <div class="eyebrow error">Verification Failed</div>
                <h1 class="card-title">Something Went Wrong</h1>
                <p class="card-msg"><?php echo htmlspecialchars($message); ?></p>

                <div class="divider"></div>

                <div style="display:flex;flex-direction:column;gap:10px;align-items:center;">
                    <a href="verify_email.php" class="btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-paper-plane" style="font-size:12px"></i>
                        Request a New Code
                    </a>
                    <a href="login.php" class="btn-ghost" style="width:100%;justify-content:center;">
                        <i class="fas fa-arrow-left" style="font-size:12px"></i>
                        Back to Sign In
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <footer>
        © 2026 JobDZ — Algeria's #1 Job Platform. All rights reserved.
    </footer>

</body>

</html>