<?php
require_once 'functions.php';
requireLogin();

global $pdo;

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$activeProfileTab = 'alerts';

if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'];
        if ($action === 'mark_read') {
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            markNotificationAsRead($notificationId, $_SESSION['user']['id']);
            exit;
        } elseif ($action === 'delete_notification') {
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
            $stmt->execute([$notificationId, $_SESSION['user']['id']]);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    markAllNotificationsAsRead($_SESSION['user']['id']);
}

$allNotifications = getNotifications($_SESSION['user']['id']);
if (!is_array($allNotifications)) $allNotifications = [];

$perPage            = 10;
$totalNotifications = count($allNotifications);
$totalPages         = ceil($totalNotifications / $perPage);
$currentPage        = max(1, (int)($_GET['page'] ?? 1));
if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;
$offset             = ($currentPage - 1) * $perPage;
$notifications      = array_slice($allNotifications, $offset, $perPage);
$unreadCount        = getUnreadNotificationCount($_SESSION['user']['id']);
$targetNotificationId = (int)($_GET['id'] ?? 0);

function getSenderInfo(PDO $pdo, int $senderId): array
{
    if ($senderId <= 0) return ['name' => 'System', 'avatar' => null];
    try {
        $stmt = $pdo->prepare("SELECT company_name, logo_url FROM companies_profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$senderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$senderId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['name' => $user['name'] ?? 'System', 'avatar' => null];
        }
        return ['name' => $row['company_name'] ?? 'Company', 'avatar' => $row['logo_url'] ?? null];
    } catch (Exception $e) {
        return ['name' => 'System', 'avatar' => null];
    }
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR receiver_id = ?)");
    $stmt->execute([$id, $_SESSION['user']['id'], $_SESSION['user']['id']]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Alerts | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/variables.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            font-family: 'Poppins', sans-serif;
        }

        .ti {
            font-family: "tabler-icons" !important;
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

        .btn-pri:disabled {
            opacity: .5;
            cursor: not-allowed;
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

        .pill-amber {
            background: #fffbeb;
            color: #92400e;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .notification-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
            transition: box-shadow .15s, border-color .15s;
        }

        .notification-card:hover {
            border-color: #a5b4fc;
            box-shadow: 0 6px 20px rgba(99, 102, 241, .08);
        }

        .notification-card.is-unread {
            border-left: 3px solid #6366f1;
            border-radius: 0 20px 20px 0;
        }

        .notification-card.active-notification {
            border: 2px solid #6366f1;
            background: #f5f3ff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .10);
            animation: notifPulse .8s ease;
        }

        @keyframes notifPulse {
            0% {
                transform: scale(1.01);
            }

            100% {
                transform: scale(1);
            }
        }

        .company-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .company-logo {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
        }

        .company-logo-placeholder {
            width: 52px;
            height: 52px;
            background: #ede9fe;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: #5b21b6;
            flex-shrink: 0;
            border: 1px solid #ddd6fe;
        }

        .company-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .company-type {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6366f1;
        }

        .message-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }

        .message-text {
            font-size: 12px;
            color: #64748b;
            line-height: 1.65;
            margin-bottom: 10px;
        }

        .message-time {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .message-time i {
            font-size: 11px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: #fafafe;
            border: 1.5px dashed #e0e7ff;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 28px;
            color: #c7d2fe;
            margin-bottom: 10px;
            display: block;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            width: 36px;
            border: 1px solid #e8eef6;
            background: white;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }

        .pagination-btn:hover {
            border-color: #6366f1;
            color: #6366f1;
        }

        .pagination-btn.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, .3);
        }

        .pagination-next {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 36px;
            padding: 0 16px;
            border: 1px solid #e8eef6;
            background: white;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }

        .pagination-next:hover {
            border-color: #6366f1;
            color: #6366f1;
        }

        .notif-link {
            display: flex;
            text-decoration: none;
            color: inherit;
            padding: 10px;
            border-radius: 14px;
            transition: .2s;
        }

        .notif-link:hover {
            background: #f8fafc;
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">

            <?php include 'includes/profile-sidebar.php'; ?>

            <div style="display:flex;flex-direction:column;gap:0;min-width:0;">

                <!-- HEADER CARD -->
                <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div>
                        <p class="pf-label">
                            <i class="ti ti-bell" style="font-size:11px;margin-right:4px;"></i>Notifications
                        </p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">Job Alerts</h1>
                        <p style="font-size:12px;color:#64748b;">Stay updated with your applications, recruiter messages, and important job activity.</p>
                    </div>
                    <form method="POST">
                        <button type="submit" name="mark_read" value="1" class="btn-pri"
                            <?php echo $unreadCount === 0 ? 'disabled' : ''; ?>>
                            <i class="ti ti-checks"></i> Mark All as Read
                        </button>
                    </form>
                </div>

                <!-- NOTIFICATIONS LIST -->
                <div style="display:flex;flex-direction:column;gap:0;">

                    <?php if (empty($notifications)): ?>

                        <div class="empty-state">
                            <i class="ti ti-bell"></i>
                            <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No notifications yet</p>
                            <p style="font-size:12px;color:#94a3b8;">You'll receive updates about applications and recruiters here.</p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($notifications as $notification):
                            $senderId   = (int)($notification['sender_id'] ?? 0);
                            $sender     = getSenderInfo($pdo, $senderId);
                            $senderName = htmlspecialchars($sender['name'] ?? 'System');
                            $senderAvatar = $sender['avatar'] ?? null;
                            $isUnread   = !$notification['is_read'];
                            $isTarget   = ($notification['id'] == $targetNotificationId);
                        ?>

                            <article class="notification-card
                            <?php echo $isUnread  ? 'is-unread' : ''; ?>
                            <?php echo $isTarget  ? 'active-notification' : ''; ?>">

                                <!-- Company Header -->
                                <div class="company-header">
                                    <?php if ($senderAvatar): ?>
                                        <img src="<?php echo htmlspecialchars($senderAvatar); ?>"
                                            alt="<?php echo $senderName; ?>"
                                            class="company-logo">
                                    <?php else: ?>
                                        <div class="company-logo-placeholder">
                                            <?php echo mb_strtoupper(mb_substr($senderName, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="flex:1;min-width:0;">
                                        <div class="company-type">Company</div>
                                        <div class="company-name"><?php echo $senderName; ?></div>
                                    </div>

                                    <?php if ($isUnread): ?>
                                        <span class="pill-amber">
                                            <i class="ti ti-star" style="font-size:10px;"></i>New
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Message -->
                                <div>
                                    <div class="message-title">
                                        <?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?>
                                    </div>
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <i class="ti ti-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($notification['created_at'])); ?>
                                        <i class="ti ti-clock" style="margin-left:6px;"></i>
                                        <?php echo date('g:i A', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>

                                <div class="pf-divider"></div>

                                <!-- Actions -->
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <?php if (!empty($notification['type']) && $notification['type'] === 'message'): ?>
                                            <a href="messages.php?user=<?php echo $notification['sender_id']; ?>" class="btn-sec">
                                                <i class="ti ti-mail"></i> View Message
                                            </a>
                                        <?php elseif (!empty($notification['related_id'])): ?>
                                            <a href="job_details.php?id=<?php echo $notification['related_id']; ?>" class="btn-sec">
                                                <i class="ti ti-eye"></i> View Details
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size:11px;color:#94a3b8;">
                                        <?php echo timeAgo($notification['created_at']); ?>
                                    </span>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                    <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $currentPage == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-next">
                                Next <i class="ti ti-chevron-right" style="font-size:11px;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>

</body>

</html>