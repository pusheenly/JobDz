<?php
require_once 'functions.php';
requireLogin();

global $pdo;

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$activeProfileTab = 'alerts';

if ($_SESSION['user']['role'] !== 'company') {
    header('Location: index.php');
    exit;
}

// Handle accept/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $applicationId = intval($_POST['application_id']);
    $action = $_POST['action'];

    if (in_array($action, ['accept', 'reject'])) {
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        updateApplicationStatus($applicationId, $status);
    }
}

// Mark all notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    markAllNotificationsAsRead($_SESSION['user']['id']);
}

$notifications = getCompanyNotifications($_SESSION['user']['id']);
if (!is_array($notifications)) {
    $notifications = [];
}

$unreadCount = getUnreadNotificationCount($_SESSION['user']['id']);

/* Pagination */
$perPage = 10;
$totalNotifications = count($notifications);
$totalPages = ceil($totalNotifications / $perPage);

$currentPage = max(1, (int)($_GET['page'] ?? 1));

if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;
$paginatedNotifications = array_slice($notifications, $offset, $perPage);

function getCandidateInfo(PDO $pdo, int $candidateId): array
{

    if ($candidateId <= 0) {
        return [
            'name' => 'Unknown Candidate',
            'avatar' => null
        ];
    }

    try {

        $stmt = $pdo->prepare("
            SELECT full_name, profile_picture
            FROM candidates_profiles
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$candidateId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'name' => 'Unknown Candidate',
                'avatar' => null
            ];
        }

        return [
            'name'   => $row['full_name'] ?: 'Unknown Candidate',
            'avatar' => $row['profile_picture']
        ];
    } catch (Exception $e) {

        return [
            'name' => 'Unknown Candidate',
            'avatar' => null
        ];
    }
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

        .btn-accept {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #10b981;
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

        .btn-accept:hover {
            background: #059669;
        }

        .btn-reject {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ef4444;
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

        .btn-reject:hover {
            background: #dc2626;
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

        .pill-emerald {
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

        .pill-rose {
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

        .candidate-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .candidate-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
        }

        .candidate-avatar-placeholder {
            width: 52px;
            height: 52px;
            background: #ede9fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: #5b21b6;
            flex-shrink: 0;
            border: 1px solid #ddd6fe;
        }

        .candidate-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .candidate-type {
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

        .actions-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .actions-left {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions-time {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
        }

        @media (max-width: 640px) {
            .actions-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions-time {
                width: 100%;
            }

            .actions-left {
                width: 100%;
            }
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
                        <p style="font-size:12px;color:#64748b;">Review candidate applications and manage responses.</p>
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

                    <?php if (empty($paginatedNotifications)): ?>

                        <div class="empty-state">
                            <i class="ti ti-bell"></i>
                            <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No notifications yet</p>
                            <p style="font-size:12px;color:#94a3b8;">When candidates apply to your jobs, you'll see them here.</p>
                        </div>

                    <?php else: ?>

                        <?php foreach ($paginatedNotifications as $notification):
                            $candidateName = htmlspecialchars(
                                $notification['candidate_name'] ?? 'Unknown Candidate'
                            );

                            $candidateAvatar = $notification['candidate_image'] ?? null;

                            $applicationId = $notification['related_id'] ?? null;
                            $candidateId = $notification['sender_id'] ?? 0;

                            $jobId = 0;

                            if ($applicationId) {
                                $stmtJob = $pdo->prepare("
                                    SELECT job_id
                                    FROM applications
                                    WHERE id = ?
                                    LIMIT 1
                                ");

                                $stmtJob->execute([$applicationId]);

                                $jobRow = $stmtJob->fetch(PDO::FETCH_ASSOC);

                                $jobId = $jobRow['job_id'] ?? 0;
                            }

                            $appStatus = 'pending';

                            if ($applicationId) {

                                $stmt = $pdo->prepare("
                                    SELECT status
                                    FROM applications
                                    WHERE id = ?
                                    LIMIT 1
                                ");

                                $stmt->execute([$applicationId]);

                                $application = $stmt->fetch(PDO::FETCH_ASSOC);

                                $appStatus = $application['status'] ?? 'pending';
                            }

                            $isUnread = !$notification['is_read'];
                        ?>

                            <article class="notification-card <?php echo $isUnread ? 'is-unread' : ''; ?>">

                                <!-- Candidate Header -->
                                <div class="candidate-header">
                                    <?php if ($candidateAvatar): ?>
                                        <img src="<?php echo htmlspecialchars($candidateAvatar); ?>"
                                            alt="<?php echo $candidateName; ?>"
                                            class="candidate-avatar">
                                    <?php else: ?>
                                        <div class="candidate-avatar-placeholder">
                                            <?php echo mb_strtoupper(mb_substr($candidateName, 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div style="flex:1;min-width:0;">
                                        <div class="candidate-type">Candidate</div>
                                        <div class="candidate-name"><?php echo $candidateName; ?></div>
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
                                        <?php echo htmlspecialchars($notification['title'] ?? 'Application Notification'); ?>
                                    </div>
                                    <div class="message-text">
                                        Applied to: <strong><?php echo htmlspecialchars($notification['message']); ?></strong>
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
                                <div class="actions-section">
                                    <div class="actions-left">
                                        <a href="candidate-profile.php?id=<?php echo $candidateId; ?>&job_id=<?php echo $jobId; ?>" class="btn-sec">
                                            <i class="ti ti-eye"></i> View Profile
                                        </a>

                                        <?php if ($appStatus === 'pending'): ?>
                                            <form method="POST" style="display:inline-flex;gap:6px;">
                                                <input type="hidden" name="application_id" value="<?php echo $applicationId; ?>">
                                                <button type="submit" name="action" value="accept" class="btn-accept">
                                                    <i class="ti ti-check"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn-reject">
                                                    <i class="ti ti-x"></i> Reject
                                                </button>
                                            </form>

                                        <?php elseif ($appStatus === 'accepted'): ?>
                                            <span class="pill-emerald"><i class="ti ti-circle-check" style="font-size:10px;"></i>Accepted</span>

                                        <?php elseif ($appStatus === 'rejected'): ?>
                                            <span class="pill-rose"><i class="ti ti-circle-x" style="font-size:10px;"></i>Rejected</span>
                                        <?php endif; ?>
                                    </div>

                                    <span class="actions-time"><?php echo timeAgo($notification['created_at']); ?></span>
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