<?php
require_once 'config.php';
require_once __DIR__ . '/send_verefication.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'verify') {
        $inputCode = trim($_POST['code'] ?? '');

        $stmt = $pdo->prepare("SELECT verification_token, token_expires, is_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'User not found.';
        } elseif ($user['is_verified']) {
            header('Location: index.php');
            exit;
        } elseif (empty($user['verification_token'])) {
            $error = 'No verification code found. Please request a new one.';
        } elseif (new DateTime() > new DateTime($user['token_expires'])) {
            $error = 'Code expired. Please request a new one.';
        } elseif ($inputCode !== $user['verification_token']) {
            $error = 'Incorrect code. Please try again.';
        } else {

            $pdo->prepare("
        UPDATE users 
        SET 
            is_verified = 1,
            verification_token = NULL,
            token_expires = NULL
        WHERE id = ?
    ")->execute([$_SESSION['user_id']]);

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['user'] = $userData;

            if ($userData['role'] === 'candidate') {
                header('Location: edit_profile.php');
            } else {
                header('Location: edit_company_profile.php');
            }

            exit;
        }
    }

    if ($_POST['action'] === 'resend') {
        $stmt = $pdo->prepare(
            "SELECT u.role, cp.full_name, comp.company_name
             FROM users u
             LEFT JOIN candidates_profiles cp ON cp.user_id = u.id
             LEFT JOIN companies_profiles comp ON comp.user_id = u.id
             WHERE u.id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch();

        $name = $profile['full_name'] ?? $profile['company_name'] ?? explode('@', $_SESSION['email'])[0];

        $sent = sendVerificationEmail($pdo, $_SESSION['user_id'], $_SESSION['email'], $name);
        if ($sent) {
            $success = 'A new code has been sent to your email.';
        } else {
            $error = 'Failed to send email. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | JobDZ</title>
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

        .help-link {
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

        .help-link:hover {
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
            padding: 52px 44px;
            width: 100%;
            max-width: 490px;
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

        .card-icon {
            width: 68px;
            height: 68px;
            background: var(--accent-lt);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--accent);
            margin: 0 auto 26px;
            position: relative;
        }

        .card-icon::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 26px;
            border: 1.5px dashed rgba(99, 102, 241, .25);
        }

        /* pulse ring */
        .card-icon::before {
            content: '';
            position: absolute;
            inset: -14px;
            border-radius: 34px;
            border: 1px solid rgba(99, 102, 241, .1);
            animation: pulseRing 2.4s ease-out infinite;
        }

        @keyframes pulseRing {
            0% {
                transform: scale(.9);
                opacity: .6;
            }

            70% {
                transform: scale(1.1);
                opacity: 0;
            }

            100% {
                opacity: 0;
            }
        }

        /*  EYEBROW  */
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
            margin-bottom: 18px;
        }

        /* EMAIL BADGE */
        .email-badge {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }

        .email-badge span {
            background: var(--accent-lt);
            border: 1.5px solid #c7d2fe;
            border-radius: 100px;
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ALERTS */
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

        /* CODE INPUTS */
        .code-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ink-3);
            letter-spacing: .02em;
            display: block;
            margin-bottom: 12px;
        }

        .code-inputs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 28px;
        }

        .code-inputs input {
            width: 52px;
            height: 62px;
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--accent);
            background: var(--bg);
            border: 1.5px solid var(--line);
            border-radius: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
            caret-color: var(--accent);
        }

        .code-inputs input:focus {
            border-color: var(--accent);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .12);
        }

        .code-inputs input.filled {
            border-color: var(--accent);
            background: var(--accent-lt);
        }

        /* PROGRESS DOTS */
        .code-progress {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-bottom: 26px;
            margin-top: -18px;
        }

        .cp-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--line);
            transition: background .2s, transform .2s;
        }

        .cp-dot.active {
            background: var(--accent);
            transform: scale(1.3);
        }

        /* SUBMIT */
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
            transition: transform .2s, box-shadow .2s, opacity .2s;
            margin-bottom: 22px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .38);
        }

        .btn-submit:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        /* DIVIDER */
        .divider {
            height: 1px;
            background: var(--line);
            margin: 0 0 22px;
        }

        /*  RESEND  */
        .resend-area {
            text-align: center;
        }

        .resend-area p {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            margin-bottom: 12px;
        }

        .btn-resend {
            background: var(--surface);
            border: 1.5px solid var(--line);
            color: var(--accent);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            padding: 9px 22px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: border-color .15s, box-shadow .15s, transform .15s;
        }

        .btn-resend:hover:not(:disabled) {
            border-color: var(--accent);
            box-shadow: 0 4px 14px rgba(99, 102, 241, .12);
            transform: translateY(-1px);
        }

        .btn-resend:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .timer {
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* TIPS  */
        .tips {
            margin-top: 22px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
            line-height: 1.6;
        }

        .tips i {
            color: var(--accent);
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* FOOTER  */
        footer {
            text-align: center;
            padding: 20px 24px;
            font-size: 12px;
            color: var(--muted);
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 520px) {
            .card {
                padding: 38px 22px;
            }

            .topbar {
                padding: 0 20px;
            }

            .code-inputs input {
                width: 42px;
                height: 52px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

    <header class="topbar">

        <a href="contact.php" class="help-link">
            <i class="fas fa-circle-question" style="font-size:13px"></i>
            Need help?
        </a>
    </header>

    <div class="main">
        <div class="main-grid"></div>

        <div class="card">

            <div class="card-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>

            <div class="eyebrow">Email Verification</div>
            <h1 class="card-title">Check your inbox</h1>
            <p class="card-sub">We sent a 6-digit verification code to</p>

            <div class="email-badge">
                <span>
                    <i class="fas fa-envelope" style="font-size:12px"></i>
                    <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
                </span>
            </div>

            <!-- Alerts -->
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
            <?php endif; ?>

            <!-- Verify form -->
            <form method="POST" id="verifyForm">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="code" id="hiddenCode">

                <span class="code-label">Enter verification code</span>

                <div class="code-inputs" id="codeInputs">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" class="code-box" inputmode="numeric" autocomplete="off">
                    <?php endfor; ?>
                </div>

                <!-- Progress dots -->
                <div class="code-progress" id="codeDots">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="cp-dot" id="dot-<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn-submit" id="verifyBtn" disabled>
                    <i class="fas fa-shield-alt" style="font-size:13px"></i>
                    Verify Account
                </button>
            </form>

            <div class="divider"></div>

            <!-- Resend form -->
            <div class="resend-area">
                <p>Didn't receive the code? Check your spam folder or</p>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn-resend" id="resendBtn" disabled>
                        <i class="fas fa-paper-plane" style="font-size:11px"></i>
                        Resend Code
                    </button>
                </form>
                <div class="timer">
                    <i class="fas fa-clock" style="font-size:11px"></i>
                    <span id="countdown">Resend available in 60s</span>
                </div>
            </div>

            <!-- Tip -->
            <div class="tips">
                <i class="fas fa-lightbulb"></i>
                <span>The code expires in <strong>10 minutes</strong>. If you don't see the email, check your spam or junk folder.</span>
            </div>

        </div>
    </div>

    <footer>
        © 2026 JobDZ — Algeria's #1 Job Platform. All rights reserved. · <a href="#">Contact Support</a>
    </footer>

    <script>
        const boxes = document.querySelectorAll('.code-box');
        const hidden = document.getElementById('hiddenCode');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');
        const countdown = document.getElementById('countdown');
        const dots = document.querySelectorAll('.cp-dot');

        boxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                box.value = box.value.replace(/\D/g, '');
                if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
                syncCode();
            });

            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
            });

            box.addEventListener('paste', e => {
                e.preventDefault();
                const p = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                p.split('').forEach((c, idx) => {
                    if (boxes[idx]) boxes[idx].value = c;
                });
                syncCode();
                boxes[Math.min(p.length, 5)].focus();
            });
        });

        function syncCode() {
            const code = Array.from(boxes).map(b => b.value).join('');
            hidden.value = code;

            boxes.forEach((b, i) => {
                b.classList.toggle('filled', b.value !== '');
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i < code.length);
            });

            verifyBtn.disabled = code.length < 6;
        }

        let t = 60;
        resendBtn.disabled = true;
        const timer = setInterval(() => {
            t--;
            countdown.textContent = t > 0 ? `Resend available in ${t}s` : 'You can resend now';
            if (t <= 0) {
                clearInterval(timer);
                resendBtn.disabled = false;
            }
        }, 1000);
    </script>

</body>

</html>