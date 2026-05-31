<?php
require_once 'functions.php';

$current_page = 'settings';
$activeProfileTab = 'settings';


requireLogin();

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action']) && $_POST['action'] === 'update_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $result = changePassword($_SESSION['user']['id'], $oldPassword, $newPassword);
        if ($result === true) {
            $message = 'Your password has been updated successfully.';
        } else {
            $error = $result;
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] === 'delete_account') {
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($confirmPassword)) {
            $error = 'Please enter your password to confirm account deletion.';
        } else {
            $userId = $_SESSION['user']['id'];


            if (verifyUserPassword($userId, $confirmPassword)) {
                if (deleteAccount($userId)) {
                    session_destroy();
                    header('Location: index.php');
                    exit;
                }
                $error = 'Unable to delete your account at this time. Please try again later.';
            } else {
                $error = 'Incorrect password. Account deletion cancelled.';
            }
        }
    }
}

$accountTypeLabel = $_SESSION['user']['role'] === 'company' ? 'Company' : 'Candidate';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = 'Settings | JobDZ';
    include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/variables.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8fafc !important;
            color: #0f172a;
        }

        .card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            margin-bottom: 14px;
        }

        .pf-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6366f1;
            margin-bottom: 4px;
        }

        .pf-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 12px 0;
        }

        .btn-pri {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
        }

        .btn-pri:hover {
            background: #4f46e5;
            color: white;
        }

        .btn-sec {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            color: #334155;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
        }

        .btn-sec:hover {
            background: #f8fafc;
            color: #334155;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #DC2626;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .pill-purple {
            background: #ede9fe;
            color: #3C3489;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-gray {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-green {
            background: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-red {
            background: #fef2f2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .section-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
            transition: box-shadow .15s, border-color .15s;
        }

        .section-card:hover {
            border-color: #a5b4fc;
            box-shadow: 0 6px 20px rgba(99, 102, 241, .08);
        }

        .section-card.is-danger {
            border-color: #fecaca;
        }

        .section-card.is-danger:hover {
            border-color: #fca5a5;
            box-shadow: 0 6px 20px rgba(220, 38, 38, .06);
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            border: 1px solid #e8eef6;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            font-family: 'Poppins', sans-serif;
            transition: border .15s, box-shadow .15s;
        }

        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #1e40af;
            margin-top: 10px;
        }

        .info-box-title {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .info-box-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-box-list li {
            padding: 3px 0 3px 16px;
            position: relative;
            font-size: 12px;
        }

        .info-box-list li::before {
            content: "•";
            position: absolute;
            left: 0;
        }

        .alert-box {
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 12px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-box.success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }

        .alert-box.danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-icon {
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .warning-box {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .warning-box-title {
            font-size: 13px;
            font-weight: 600;
            color: #7f1d1d;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .warning-box-text {
            font-size: 12px;
            color: #991b1b;
            line-height: 1.7;
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">

            <?php $activeProfileTab = 'settings';
            include 'includes/profile-sidebar.php'; ?>

            <div style="display:flex;flex-direction:column;gap:0;min-width:0;">

                <!-- HEADER CARD -->
                <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div>
                        <p class="pf-label">
                            <i class="ti ti-settings" style="font-size:11px;margin-right:4px;"></i>Account Management
                        </p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">Account Settings</h1>
                        <p style="font-size:12px;color:#64748b;">Manage your security, password protection, and account preferences.</p>
                    </div>
                    <span class="pill-purple">
                        <i class="ti ti-shield" style="font-size:11px;"></i>
                        <?php echo e($accountTypeLabel); ?> Account
                    </span>
                </div>

                <!-- ALERTS -->
                <?php if ($message): ?>
                    <div class="alert-box success">
                        <i class="ti ti-circle-check alert-icon"></i>
                        <span><?php echo e($message); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert-box danger">
                        <i class="ti ti-alert-circle alert-icon"></i>
                        <span><?php echo e($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- PASSWORD SECTION -->
                <article class="section-card">

                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                        <span class="pill-purple">
                            <i class="ti ti-lock" style="font-size:10px;"></i>Security
                        </span>
                        <span class="pill-green">
                            <i class="ti ti-check" style="font-size:10px;"></i>Recommended
                        </span>
                    </div>

                    <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:3px;">Password Security</div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Update your password regularly to keep your JobDZ account safe and secure.</div>

                    <div class="pf-divider"></div>

                    <form method="POST" style="margin-top:12px;">
                        <input type="hidden" name="action" value="update_password">

                        <div style="margin-bottom:12px;">
                            <label class="form-label">
                                <i class="ti ti-key" style="font-size:12px;margin-right:4px;color:#6366f1;"></i>Current password
                            </label>
                            <input type="password" name="old_password" required placeholder="Enter your current password" class="form-input">
                        </div>

                        <div style="margin-bottom:12px;">
                            <label class="form-label">
                                <i class="ti ti-lock" style="font-size:12px;margin-right:4px;color:#6366f1;"></i>New password
                            </label>
                            <input type="password" name="new_password" required placeholder="Enter your new password" class="form-input">
                        </div>

                        <div class="info-box">
                            <div class="info-box-title">
                                <i class="ti ti-info-circle" style="margin-right:4px;"></i>Password Requirements
                            </div>
                            <ul class="info-box-list">
                                <li>At least 8 characters long</li>
                                <li>Include uppercase and lowercase letters</li>
                                <li>Include at least one number (0-9)</li>
                                <li>Include special symbols (!@#$%^&*)</li>
                            </ul>
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:14px;">
                            <p style="font-size:11px;color:#94a3b8;">Your password will be updated immediately.</p>
                            <button type="submit" class="btn-pri">
                                <i class="ti ti-device-floppy"></i> Update Password
                            </button>
                        </div>
                    </form>

                </article>

                <!-- DELETE ACCOUNT SECTION -->
                <article class="section-card is-danger">

                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                        <span class="pill-purple">
                            <i class="ti ti-alert-triangle" style="font-size:10px;"></i>Danger Zone
                        </span>
                        <span class="pill-red">
                            <i class="ti ti-x" style="font-size:10px;"></i>Permanent
                        </span>
                    </div>

                    <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:3px;">Delete Account</div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Permanently delete your account and all associated data. This action cannot be undone.</div>

                    <div class="pf-divider"></div>

                    <div class="warning-box">
                        <p class="warning-box-title">
                            <i class="ti ti-alert-triangle"></i>
                            Warning: This Cannot Be Reversed
                        </p>
                        <p class="warning-box-text">
                            Deleting your account will permanently remove:<br><br>
                            <strong>✕ Profile information & settings</strong><br>
                            <strong>✕ All job applications & saved jobs</strong><br>
                            <strong>✕ Messages & connections</strong><br>
                            <strong>✕ Account history & data</strong><br><br>
                            <strong>This action is irreversible and cannot be undone.</strong>
                        </p>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:14px;">
                        <form method="POST" id="delete-account-form">
                            <input type="hidden" name="action" value="delete_account">
                            <input type="hidden" name="confirm_password" id="confirm-password-input" value="">
                            <button type="button" class="btn-danger" data-confirm-delete="true">
                                <i class="ti ti-trash"></i> Delete Account
                            </button>
                        </form>
                    </div>

                </article>

            </div>

        </div>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('[data-confirm-delete="true"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const userPassword = prompt(
                        '🔐 Confirm Account Deletion\n\n' +
                        'Enter your password to permanently delete your account.\n' +
                        'This action CANNOT be undone.'
                    );

                    if (userPassword === null) {
                        return;
                    }

                    if (userPassword.trim() === '') {
                        alert('❌ Password cannot be empty. Account deletion cancelled.');
                        return;
                    }

                    document.getElementById('confirm-password-input').value = userPassword;
                    document.getElementById('delete-account-form').submit();
                });
            });
        });
    </script>

</body>

</html>