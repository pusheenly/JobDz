<?php
require_once 'config.php';
require_once 'functions.php';
require_once __DIR__ . '/send_verefication.php';

$mode = $_GET['mode'] ?? 'login';
$error = '';
$success = '';

if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    $success = 'You have successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? 'login';
        $mode = $action;

        if ($action === 'login') {
            $email = sanitizeInput($_POST['email'] ?? '', 'email');
            $password = $_POST['password'] ?? '';
            if (loginUser($email, $password)) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }

        if ($action === 'register') {
            $email = sanitizeInput($_POST['email'] ?? '', 'email');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'candidate';

            if ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $result = registerUser($email, $password, $role);
                if ($result === true) {
                    $_SESSION['email'] = $email;
                    $name = explode('@', $email)[0];
                    sendVerificationEmail($pdo, $_SESSION['user_id'], $email, $name);
                    header('Location: verify_email.php');
                    exit;
                } else {
                    $error = $result;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>JobDZ — <?php echo $mode === 'login' ? 'Sign In' : 'Create Account'; ?></title>

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
            -webkit-font-smoothing: antialiased;
        }

        .auth-shell {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        .auth-left {
            display: flex;
            flex-direction: column;
            padding: 0 60px;
            background: var(--surface);
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -120px;
            left: -120px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, .07) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 32px 0;
            position: relative;
            z-index: 2;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .auth-logo-mark {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 6px 18px rgba(99, 102, 241, .3);
        }

        .auth-logo-text {
            font-size: 20px;
            font-weight: 800;
            color: var(--ink);
            letter-spacing: -.5px;
        }

        .auth-logo-text span {
            color: var(--accent);
        }

        .auth-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-3);
            text-decoration: none;
            padding: 8px 16px;
            border: 1.5px solid var(--line);
            border-radius: 100px;
            transition: border-color .15s, color .15s;
        }

        .auth-back:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .auth-form-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px 0 60px;
            position: relative;
            z-index: 2;
            max-width: 420px;
        }

        .auth-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .auth-eyebrow::before {
            content: '';
            display: block;
            width: 24px;
            height: 2px;
            background: var(--accent);
            border-radius: 2px;
        }

        .auth-title {
            font-size: 34px;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.15;
            margin-bottom: 8px;
            letter-spacing: -.5px;
        }

        .auth-subtitle {
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
            margin-bottom: 36px;
            line-height: 1.6;
        }

        .auth-tabs {
            display: inline-flex;
            gap: 3px;
            background: var(--bg);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 32px;
            border: 1.5px solid var(--line);
        }

        .auth-tab {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            color: var(--muted);
            background: transparent;
            text-decoration: none;
            display: inline-block;
            transition: all .2s;
        }

        .auth-tab.active {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 16px rgba(99, 102, 241, .28);
        }

        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            animation: fadeUp .3s ease;
        }

        .auth-alert-error {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            color: #dc2626;
        }

        .auth-alert-success {
            background: var(--green-lt);
            border: 1.5px solid #a7f3d0;
            color: var(--green);
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 20px;
        }

        .field {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 13px;
            pointer-events: none;
            transition: color .2s;
        }

        .field input,
        .field select {
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
            -webkit-appearance: none;
        }

        .field select {
            cursor: pointer;
        }

        .field input::placeholder {
            color: var(--muted);
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--accent);
            background: var(--surface);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .1);
        }

        .field input:focus~.field-icon,
        .field select:focus~.field-icon {
            color: var(--accent);
        }

        .field:focus-within .field-icon {
            color: var(--accent);
        }

        .field-hint {
            font-size: 11px;
            color: var(--muted);
            margin-top: 5px;
            padding-left: 2px;
        }

        .auth-forgot {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 6px;
        }

        .auth-forgot a {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            transition: color .15s;
        }

        .auth-forgot a:hover {
            color: var(--accent);
        }

        .auth-terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 6px;
        }

        .auth-terms input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 2px solid var(--line);
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .auth-terms label {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
        }

        .auth-terms label a {
            color: var(--accent);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-terms label a:hover {
            text-decoration: underline;
        }

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

        .btn-submit:active {
            transform: translateY(0);
        }

        .auth-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 22px 0;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .btn-social {
            width: 100%;
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 14px;
            padding: 13px 24px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: border-color .15s, box-shadow .15s, transform .15s;
        }

        .btn-social:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 16px rgba(99, 102, 241, .1);
            transform: translateY(-1px);
        }

        .btn-social .g-icon {
            width: 20px;
            height: 20px;
        }

        .auth-right {
            background: var(--ink-2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 52px;
            position: relative;
            overflow: hidden;
        }

        .auth-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 80% 20%, rgba(99, 102, 241, .2) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(79, 70, 229, .15) 0%, transparent 60%);
            pointer-events: none;
        }

        .auth-right-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(99, 102, 241, .04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, .04) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        .auth-right-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 380px;
        }

        .right-card {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 18px;
            padding: 18px 20px;
            backdrop-filter: blur(8px);
            text-align: left;
            margin-bottom: 14px;
            animation: floatUp 5s ease-in-out infinite;
        }

        .right-card:nth-child(2) {
            animation-delay: -1.6s;
        }

        .right-card:nth-child(3) {
            animation-delay: -3.2s;
        }

        @keyframes floatUp {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .rc-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rc-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
        }

        .rc-name {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .rc-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .5);
            margin-top: 2px;
        }

        .rc-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            padding: 4px 10px;
            border-radius: 100px;
            margin-top: 10px;
        }

        .rc-tag-green {
            background: rgba(29, 158, 117, .2);
            color: #6ee7b7;
        }

        .rc-tag-accent {
            background: rgba(99, 102, 241, .25);
            color: #a5b4fc;
        }

        .rc-tag-amber {
            background: rgba(217, 119, 6, .2);
            color: #fcd34d;
        }

        /* Stats row */
        .right-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: rgba(255, 255, 255, .08);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 28px;
        }

        .right-stat {
            padding: 16px;
            background: rgba(255, 255, 255, .04);
            text-align: center;
        }

        .right-stat-num {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
        }

        .right-stat-lbl {
            font-size: 11px;
            color: rgba(255, 255, 255, .4);
            margin-top: 2px;
        }

        .right-tagline {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            justify-content: center;
        }

        .right-tagline::before,
        .right-tagline::after {
            content: '';
            width: 20px;
            height: 1.5px;
            background: var(--accent);
            border-radius: 2px;
        }

        .right-title {
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            margin-bottom: 12px;
            letter-spacing: -.3px;
        }

        .right-title em {
            font-style: normal;
            background: linear-gradient(135deg, var(--accent), #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .right-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, .5);
            line-height: 1.7;
            font-weight: 500;
            margin-bottom: 28px;
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

        .fade-up {
            animation: fadeUp .5s ease both;
        }

        .delay-1 {
            animation-delay: .08s;
        }

        .delay-2 {
            animation-delay: .16s;
        }

        .delay-3 {
            animation-delay: .24s;
        }

        .delay-4 {
            animation-delay: .32s;
        }

        @media (max-width: 900px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .auth-right {
                display: none;
            }

            .auth-left {
                padding: 0 28px;
            }
        }
    </style>
</head>

<body>

    <div class="auth-shell">

        <!--  LEFT: FORM  -->
        <div class="auth-left">

            <!-- Top bar -->
            <div class="auth-topbar">

                <a href="index.php" class="auth-back">
                    <i class="fas fa-arrow-left" style="font-size:11px"></i> Back to Home
                </a>
            </div>

            <!-- Form wrap -->
            <div class="auth-form-wrap">

                <div class="auth-eyebrow fade-up">
                    <?php echo $mode === 'login' ? 'Welcome Back' : 'Join Algeria\'s #1 Platform'; ?>
                </div>

                <h1 class="auth-title fade-up delay-1">
                    <?php echo $mode === 'login' ? 'Sign In to JobDZ' : 'Create Your Account'; ?>
                </h1>

                <p class="auth-subtitle fade-up delay-1">
                    <?php echo $mode === 'login'
                        ? 'Access thousands of job listings and manage your applications.'
                        : 'Join thousands of professionals and companies across Algeria.';
                    ?>
                </p>

                <!-- Tabs -->
                <div class="fade-up delay-2">
                    <div class="auth-tabs">
                        <a href="login.php?mode=login"
                            class="auth-tab <?php echo $mode === 'login' ? 'active' : ''; ?>">
                            Sign In
                        </a>
                        <a href="login.php?mode=register"
                            class="auth-tab <?php echo $mode === 'register' ? 'active' : ''; ?>">
                            Sign Up
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error fade-up">
                        <i class="fas fa-exclamation-circle" style="margin-top:1px;flex-shrink:0"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-alert auth-alert-success fade-up">
                        <i class="fas fa-check-circle" style="margin-top:1px;flex-shrink:0"></i>
                        <span><?php echo e($success); ?></span>
                    </div>
                <?php endif; ?>

                <!-- LOGIN FORM -->
                <?php if ($mode === 'login'): ?>
                    <form method="POST" class="fade-up delay-3">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                        <div class="field-group">
                            <div class="field">
                                <i class="fas fa-envelope field-icon"></i>
                                <input type="email" name="email" required placeholder="Email address" autocomplete="email">
                            </div>
                            <div>
                                <div class="field">
                                    <i class="fas fa-lock field-icon"></i>
                                    <input type="password" name="password" required placeholder="Password" autocomplete="current-password">
                                </div>
                                <div class="auth-forgot" style="margin-top:10px;">
                                    <a href="forget_password.php">Forgot password?</a>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            Continue <i class="fas fa-arrow-right" style="font-size:12px"></i>
                        </button>


                    </form>

                    <!-- REGISTER FORM -->
                <?php else: ?>
                    <form method="POST" class="fade-up delay-3">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

                        <div class="field-group">
                            <div class="field">
                                <i class="fas fa-envelope field-icon"></i>
                                <input type="email" name="email" required placeholder="Email address" autocomplete="email">
                            </div>

                            <div>
                                <div class="field">
                                    <i class="fas fa-lock field-icon"></i>
                                    <input type="password" name="password" required placeholder="Password" autocomplete="new-password">
                                </div>
                                <p class="field-hint">Min. 8 characters — at least one letter and one number.</p>
                            </div>

                            <div class="field">
                                <i class="fas fa-check field-icon"></i>
                                <input type="password" name="confirm_password" required placeholder="Confirm password" autocomplete="new-password">
                            </div>

                            <div class="field">
                                <i class="fas fa-user-tie field-icon"></i>
                                <select name="role">
                                    <option value="candidate">I'm a Candidate — looking for a job</option>
                                    <option value="company">I'm a Company — looking to hire</option>
                                </select>
                            </div>
                        </div>

                        <div class="auth-terms" style="margin-bottom:20px">
                            <input type="checkbox" id="terms" required>
                            <label for="terms">
                                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn-submit">
                            Create Account <i class="fas fa-arrow-right" style="font-size:12px"></i>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>

        <!--  RIGHT: DECORATIVE PANEL  -->
        <div class="auth-right">
            <div class="auth-right-grid"></div>

            <div class="auth-right-content">

                <div class="right-tagline">Algeria's #1 Job Platform</div>

                <h2 class="right-title">
                    <?php if ($mode === 'login'): ?>
                        Your next big<br>opportunity <em>awaits</em>
                    <?php else: ?>
                        Start your career<br><em>journey</em> today
                    <?php endif; ?>
                </h2>

                <p class="right-desc">
                    <?php echo $mode === 'login'
                        ? 'Thousands of companies are actively hiring. Your perfect role is just a search away.'
                        : 'Join a growing community of professionals and companies building Algeria\'s future together.';
                    ?>
                </p>

                <!-- Floating activity cards -->
                <div class="right-card">
                    <div class="rc-row">
                        <div class="rc-avatar" style="background:rgba(99,102,241,.25);color:#a5b4fc;">DZ</div>
                        <div>
                            <div class="rc-name">Senior UI Designer</div>
                            <div class="rc-sub">Djezzy · Algiers, Algeria</div>
                        </div>
                    </div>
                    <div>
                        <span class="rc-tag rc-tag-green"><i class="fas fa-circle" style="font-size:6px"></i> Remote Available</span>
                        <span class="rc-tag rc-tag-accent" style="margin-left:4px">Full-time</span>
                    </div>
                </div>

                <div class="right-card">
                    <div class="rc-row">
                        <div class="rc-avatar" style="background:rgba(29,158,117,.2);color:#6ee7b7;font-size:18px">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div class="rc-name">Application Accepted!</div>
                            <div class="rc-sub">Product Manager · Oran — 2 min ago</div>
                        </div>
                    </div>
                    <div style="margin-top:10px;font-size:11px;color:rgba(255,255,255,.35);background:rgba(255,255,255,.04);border-radius:9px;padding:8px 12px;">
                        92% profile match · Salary: 180k–240k DZD/month
                    </div>
                </div>

                <div class="right-card">
                    <div class="rc-row">
                        <div class="rc-avatar" style="background:rgba(217,119,6,.2);color:#fcd34d;">SN</div>
                        <div>
                            <div class="rc-name">Sonatrach is hiring</div>
                            <div class="rc-sub">12 new positions · Constantine</div>
                        </div>
                    </div>
                    <span class="rc-tag rc-tag-amber" style="margin-top:10px"><i class="fas fa-fire" style="font-size:9px"></i> Trending Today</span>
                </div>

                <!-- Stats -->
                <div class="right-stats">
                    <div class="right-stat">
                        <div class="right-stat-num">5K+</div>
                        <div class="right-stat-lbl">Open Jobs</div>
                    </div>
                    <div class="right-stat">
                        <div class="right-stat-num">2K+</div>
                        <div class="right-stat-lbl">Companies</div>
                    </div>
                    <div class="right-stat">
                        <div class="right-stat-num">10K+</div>
                        <div class="right-stat-lbl">Members</div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>

</html>