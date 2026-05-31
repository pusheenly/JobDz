<?php
require_once 'config.php';
require_once __DIR__ . '/send_verefication.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            sendResetCode($pdo, $user['id'], $user['email']);
        }

        $success = 'If this email exists, a reset code has been sent.';
        $_SESSION['reset_email'] = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | JobDZ</title>
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
            position: sticky;
            top: 0;
            z-index: 10;
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-3);
            text-decoration: none;
            padding: 8px 18px;
            border: 1.5px solid var(--line);
            border-radius: 100px;
            transition: border-color .15s, color .15s;
        }

        .back-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
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
            padding: 48px 44px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 16px 56px rgba(15, 23, 42, .08);
            position: relative;
            z-index: 2;
            animation: fadeUp .5s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── ICON ── */
        .card-icon {
            width: 64px;
            height: 64px;
            background: var(--accent-lt);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent);
            margin: 0 auto 24px;
            position: relative;
        }

        .card-icon::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 24px;
            border: 1.5px dashed rgba(99, 102, 241, .25);
        }

        /* ── EYEBROW ── */
        .eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 9px;
            justify-content: center;
            margin-bottom: 10px;
        }

        .eyebrow::before,
        .eyebrow::after {
            content: '';
            width: 18px;
            height: 2px;
            background: var(--accent);
            border-radius: 2px;
        }

        .card-title {
            text-align: center;
            font-size: 26px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.4px;
            margin-bottom: 8px;
        }

        .card-sub {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        /* ── ALERT ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 13px 16px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 22px;
            animation: fadeUp .3s ease;
        }

        .alert-error {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: var(--green-lt);
            border: 1.5px solid #a7f3d0;
            color: var(--green);
        }

        /* ── FIELD ── */
        .field {
            margin-bottom: 22px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--ink-3);
            margin-bottom: 8px;
            letter-spacing: .02em;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            pointer-events: none;
            transition: color .2s;
        }

        .field-wrap:focus-within i {
            color: var(--accent);
        }

        .field-wrap input {
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--ink);
            background: var(--bg);
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 13px 16px 13px 44px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .field-wrap input::placeholder {
            color: var(--muted);
        }

        .field-wrap input:focus {
            border-color: var(--accent);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .1);
        }

        /* ── SUBMIT ── */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            padding: 14px 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .28);
            transition: transform .2s, box-shadow .2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .38);
        }

        /* ── RESET CODE BUTTON ── */
        .btn-code {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border-radius: 14px;
            padding: 13px 28px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .25);
            transition: transform .2s, box-shadow .2s;
            margin-top: 6px;
        }

        .btn-code:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .35);
            color: #fff;
        }

        /* ── SUCCESS STATE ── */
        .success-body {
            text-align: center;
        }

        .success-steps {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0 26px;
            text-align: left;
        }

        .success-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
            font-size: 13px;
            color: var(--ink-3);
            font-weight: 500;
        }

        .success-step-num {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: var(--accent-lt);
            color: var(--accent);
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* ── DIVIDER ── */
        .divider {
            height: 1px;
            background: var(--line);
            margin: 26px 0;
        }

        .login-hint {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .login-hint a {
            color: var(--accent);
            font-weight: 700;
            text-decoration: none;
        }

        .login-hint a:hover {
            text-decoration: underline;
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
                padding: 36px 24px;
            }

            .topbar {
                padding: 0 20px;
            }
        }
    </style>
</head>

<body>

    <header class="topbar">

        <a href="login.php" class="back-btn">
            <i class="fas fa-arrow-left" style="font-size:11px"></i> Back to Login
        </a>
    </header>

    <div class="main">
        <div class="main-grid"></div>

        <div class="card">

            <div class="card-icon">
                <i class="fas fa-lock"></i>
            </div>

            <div class="eyebrow">Account Recovery</div>
            <h1 class="card-title">Forgot Password?</h1>
            <p class="card-sub">Enter your email address and we'll send you a 6-digit reset code right away.</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" style="margin-top:1px;flex-shrink:0"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>

                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="margin-top:1px;flex-shrink:0"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>

                <div class="success-body">
                    <div class="success-steps">
                        <div class="success-step">
                            <div class="success-step-num">1</div>
                            Check your inbox for an email from JobDZ
                        </div>
                        <div class="success-step">
                            <div class="success-step-num">2</div>
                            Copy the 6-digit code from the email
                        </div>
                        <div class="success-step">
                            <div class="success-step-num">3</div>
                            Enter it on the next page to reset your password
                        </div>
                    </div>

                    <a href="reset_password.php" class="btn-code">
                        <i class="fas fa-key"></i> Enter Reset Code
                    </a>
                </div>

            <?php else: ?>

                <form method="POST">
                    <div class="field">
                        <label>Email address</label>
                        <div class="field-wrap">
                            <i class="fas fa-envelope"></i>
                            <input
                                type="email"
                                name="email"
                                required
                                placeholder="you@example.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                autocomplete="email">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane" style="font-size:12px"></i>
                        Send Reset Code
                    </button>
                </form>

            <?php endif; ?>

            <div class="divider"></div>

            <p class="login-hint">
                Remember your password? <a href="login.php">Sign in</a>
            </p>

        </div>
    </div>

    <footer>
        © 2026 JobDZ — Algeria's #1 Job Platform. All rights reserved.
    </footer>

</body>

</html>