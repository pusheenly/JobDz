<?php
require_once 'config.php';

$error   = '';
$success = '';

$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'resend') {
        require_once __DIR__ . '/send_verefication.php';
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            sendResetCode($pdo, $user['id'], $email);
            $success = 'A new reset code has been sent to your inbox.';
        }
    }

    if ($action === 'reset') {
        $code     = trim($_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (strlen($code) < 6) {
            $error = 'Please enter the complete 6-digit code.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must be at least 8 characters with letters and numbers.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare("SELECT id, reset_token, reset_expires FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || empty($user['reset_token'])) {
                $error = 'No reset code found. Please request a new one.';
            } elseif (new DateTime() > new DateTime($user['reset_expires'])) {
                $error = 'Code expired. Please request a new one.';
            } elseif ($code !== $user['reset_token']) {
                $error = 'Incorrect code. Please try again.';
            } else {
                $hash = password_hash($password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
                $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?")->execute([$hash, $email]);
                unset($_SESSION['reset_email']);
                $success = 'done';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | JobDZ</title>
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
            max-width: 500px;
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

        /* ── CARD ICON ── */
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
            margin-bottom: 20px;
        }

        /* ── EMAIL BADGE ── */
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

        /* ── ALERTS ── */
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

        /* ── CODE INPUTS ── */
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

        /* ── DIVIDER ── */
        .divider {
            height: 1px;
            background: var(--line);
            margin: 24px 0;
        }

        /* ── FIELD ── */
        .field {
            margin-bottom: 18px;
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

        .field-wrap .f-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            pointer-events: none;
            transition: color .2s;
        }

        .field-wrap:focus-within .f-icon {
            color: var(--accent);
        }

        .field-wrap .f-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            cursor: pointer;
            transition: color .15s;
            padding: 4px;
        }

        .field-wrap .f-toggle:hover {
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
            padding: 13px 44px;
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

        /* ── STRENGTH BAR ── */
        .strength-wrap {
            margin-top: 8px;
        }

        .strength-track {
            height: 4px;
            border-radius: 4px;
            background: var(--line);
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width .3s, background .3s;
        }

        .strength-hint {
            font-size: 11px;
            color: var(--muted);
            margin-top: 5px;
            font-weight: 500;
        }

        /* ── MATCH INDICATOR ── */
        .match-hint {
            font-size: 11px;
            margin-top: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .match-ok {
            color: var(--green);
        }

        .match-err {
            color: #dc2626;
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
            transition: transform .2s, box-shadow .2s, opacity .2s;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .38);
        }

        .btn-submit:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        /* ── RESEND ── */
        .resend-area {
            margin-top: 24px;
            text-align: center;
        }

        .resend-area p {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            margin-bottom: 10px;
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

        /* ── SUCCESS STATE ── */
        .success-state {
            text-align: center;
            padding: 12px 0;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--green-lt);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: var(--green);
            margin: 0 auto 24px;
            position: relative;
        }

        .success-icon::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 1.5px dashed rgba(29, 158, 117, .3);
        }

        .success-state h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 10px;
        }

        .success-state p {
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
            line-height: 1.7;
            margin-bottom: 28px;
            max-width: 320px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            border-radius: 14px;
            padding: 13px 30px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .28);
            transition: transform .2s, box-shadow .2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .38);
            color: #fff;
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
                padding: 36px 20px;
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

    <!-- ── TOPBAR ── -->
    <header class="topbar">

        <a href="forgot_password.php" class="back-btn">
            <i class="fas fa-arrow-left" style="font-size:11px"></i> Back
        </a>
    </header>

    <!--  MAIN -->
    <div class="main">
        <div class="main-grid"></div>

        <div class="card">

            <?php if ($success === 'done'): ?>

                <!-- SUCCESS  -->
                <div class="success-state">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="eyebrow">All Done</div>
                    <h2>Password Updated!</h2>
                    <p>Your password has been reset successfully. You can now sign in with your new credentials.</p>
                    <a href="login.php" class="btn-login">
                        Sign In Now <i class="fas fa-arrow-right" style="font-size:12px"></i>
                    </a>
                </div>

            <?php else: ?>

                <div class="card-icon">
                    <i class="fas fa-key"></i>
                </div>

                <div class="eyebrow">Account Recovery</div>
                <h1 class="card-title">Reset Password</h1>
                <p class="card-sub">Enter the 6-digit code sent to</p>

                <div class="email-badge">
                    <span>
                        <i class="fas fa-envelope" style="font-size:12px"></i>
                        <?php echo htmlspecialchars($email); ?>
                    </span>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle" style="margin-top:1px;flex-shrink:0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success && $success !== 'done'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle" style="margin-top:1px;flex-shrink:0"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <!--RESET FORM -->
                <form method="POST" id="resetForm">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="code" id="hiddenCode">

                    <span class="code-label">Verification Code</span>
                    <div class="code-inputs" id="codeInputs">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" maxlength="1" class="code-box" inputmode="numeric" autocomplete="off">
                        <?php endfor; ?>
                    </div>

                    <div class="divider"></div>

                    <!-- New Password -->
                    <div class="field">
                        <label>New Password</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock f-icon"></i>
                            <input type="password" name="password" id="password" required placeholder="Min. 8 chars, letters & numbers">
                            <i class="fas fa-eye f-toggle" id="togglePass"></i>
                        </div>
                        <div class="strength-wrap">
                            <div class="strength-track">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-hint" id="strengthHint"></div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="field">
                        <label>Confirm Password</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock f-icon"></i>
                            <input type="password" name="confirm" id="confirm" required placeholder="Repeat your password">
                            <i class="fas fa-eye f-toggle" id="toggleConfirm"></i>
                        </div>
                        <div class="match-hint" id="matchHint" style="display:none"></div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="fas fa-shield-alt" style="font-size:13px"></i>
                        Reset Password
                    </button>
                </form>

                <!-- RESEND  -->
                <div class="resend-area">
                    <p>Didn't receive the code?</p>
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

            <?php endif; ?>

        </div>
    </div>

    <footer>
        © 2026 JobDZ — Algeria's #1 Job Platform. All rights reserved.
    </footer>

    <script>
        const boxes = document.querySelectorAll('.code-box');
        const hidden = document.getElementById('hiddenCode');
        const submitBtn = document.getElementById('submitBtn');
        const passInput = document.getElementById('password');
        const confInput = document.getElementById('confirm');

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
            if (hidden) hidden.value = code;
            boxes.forEach(b => b.classList.toggle('filled', b.value !== ''));
            checkReady();
        }


        const strengthFill = document.getElementById('strengthFill');
        const strengthHint = document.getElementById('strengthHint');
        const matchHint = document.getElementById('matchHint');

        const strengthLevels = [{
                color: '#ef4444',
                label: 'Too weak',
                pct: '25%'
            },
            {
                color: '#f97316',
                label: 'Fair',
                pct: '50%'
            },
            {
                color: '#eab308',
                label: 'Good',
                pct: '75%'
            },
            {
                color: '#1D9E75',
                label: 'Strong',
                pct: '100%'
            },
        ];

        passInput && passInput.addEventListener('input', () => {
            const v = passInput.value;
            let s = 0;
            if (v.length >= 8) s++;
            if (/[A-Z]/.test(v)) s++;
            if (/[0-9]/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;

            if (v.length === 0) {
                strengthFill.style.width = '0%';
                strengthHint.textContent = '';
            } else {
                const lvl = strengthLevels[Math.max(0, s - 1)];
                strengthFill.style.width = lvl.pct;
                strengthFill.style.background = lvl.color;
                strengthHint.textContent = lvl.label;
                strengthHint.style.color = lvl.color;
            }
            updateMatchHint();
            checkReady();
        });

        confInput && confInput.addEventListener('input', () => {
            updateMatchHint();
            checkReady();
        });

        function updateMatchHint() {
            if (!matchHint) return;
            if (!confInput.value) {
                matchHint.style.display = 'none';
                return;
            }
            matchHint.style.display = 'flex';
            if (passInput.value === confInput.value) {
                matchHint.className = 'match-hint match-ok';
                matchHint.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            } else {
                matchHint.className = 'match-hint match-err';
                matchHint.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            }
        }

        function checkReady() {
            const code = Array.from(boxes).map(b => b.value).join('');
            const ok = code.length === 6 &&
                passInput.value.length >= 8 &&
                passInput.value === confInput.value;
            submitBtn.disabled = !ok;
        }


        function makeToggle(inputId, btnId) {
            const inp = document.getElementById(inputId);
            const btn = document.getElementById(btnId);
            if (!inp || !btn) return;
            btn.addEventListener('click', () => {
                const show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                btn.className = `fas fa-eye${show ? '-slash' : ''} f-toggle`;
            });
        }
        makeToggle('password', 'togglePass');
        makeToggle('confirm', 'toggleConfirm');


        let seconds = 60;
        const resend = document.getElementById('resendBtn');
        const cd = document.getElementById('countdown');

        if (resend && cd) {
            const timer = setInterval(() => {
                seconds--;
                cd.textContent = seconds > 0 ?
                    `Resend available in ${seconds}s` :
                    'You can resend now';
                if (seconds <= 0) {
                    clearInterval(timer);
                    resend.disabled = false;
                }
            }, 1000);
        }
    </script>
</body>

</html>