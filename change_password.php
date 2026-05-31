<?php
require_once 'functions.php';
requireLogin();
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    if ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        $result = changePassword($_SESSION['user']['id'], $oldPassword, $newPassword);
        if ($result === true) {
            $message = 'Password changed successfully.';
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
<header>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
    </div>
</header>
<main class="container profile-page">
    <div class="card" style="max-width: 520px; margin: 0 auto;">
        <div class="section-title">
            <h3>Change Password</h3>
        </div>
        <?php if ($error): ?><div class="alert error"><?php echo e($error); ?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert success"><?php echo e($message); ?></div><?php endif; ?>
        <form method="POST" action="change_password.php">
            <div class="field-group">
                <div class="field">
                    <label for="old_password">Current Password</label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>
                <div class="field">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <button type="submit" class="btn primary" style="width: 100%;">Update Password</button>
        </form>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
