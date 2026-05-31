<?php
require_once 'functions.php';
requireLogin();

if ($_SESSION['user']['role'] !== 'company') {
    header('Location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$activeProfileTab = 'applications';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = intval($_POST['application_id'] ?? 0);
    $jobId = intval($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($jobId > 0 && $action === 'toggle_job_status') {
        if (toggleJobStatus($jobId, $_SESSION['user']['id'])) {
            $jobStatus = getJobPositionsSummary($jobId);
            $message = (($jobStatus['status'] ?? 'open') === 'open')
                ? 'Job has been reopened.'
                : 'Job has been closed.';
        }
    } elseif ($applicationId > 0) {
        if (in_array($action, ['accept', 'reject'])) {
            $status         = $action === 'accept' ? 'accepted' : 'rejected';
            $interviewDate  = !empty($_POST['interview_date'])  ? $_POST['interview_date']  : null;
            $interviewTime  = !empty($_POST['interview_time'])  ? $_POST['interview_time']  : null;
            $companyMessage = !empty($_POST['company_message']) ? $_POST['company_message'] : null;

            if ($status === 'accepted') {
                $application = getApplicationById($applicationId);
                if ($application) {
                    $jobStatus = getJobPositionsSummary($application['job_id']);
                    if (($jobStatus['status'] ?? 'open') === 'closed') {
                        $message = 'This job is closed. You cannot accept more candidates.';
                    } elseif (($jobStatus['filled_positions'] ?? 0) >= ($jobStatus['total_positions'] ?? 1)) {
                        $message = 'All positions are already filled.';
                    } else {
                        if (updateApplicationStatus($applicationId, $status, $interviewDate, $interviewTime, $companyMessage)) {
                            $updatedStatus = getJobPositionsSummary($application['job_id']);
                            if (($updatedStatus['filled_positions'] ?? 0) >= ($updatedStatus['total_positions'] ?? 1)) {
                                closeJob($application['job_id']);
                            }
                            $message = 'Candidate has been accepted. Notification sent.';
                        }
                    }
                }
            } else {
                if (updateApplicationStatus($applicationId, $status, $interviewDate, $interviewTime, $companyMessage)) {
                    $message = 'Candidate has been rejected. Notification sent.';
                }
            }
        }
    }
}

$applications = getCompanyApplications($_SESSION['user']['id']);
$companyJobs  = getCompanyJobs($_SESSION['user']['id']);
$pageTitle    = 'Applications | JobDZ';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
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

        .btn-success {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }

        .btn-success:hover {
            background: #d1fae5;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }

        .btn-danger:hover {
            background: #FEE2E2;
        }

        /* ── PILLS ── */
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

        /* ── STATS GRID ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }

        .sc {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: .9rem 1rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            text-align: center;
        }

        .sc-val {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sc-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* ── APPLICATION CARD ── */
        .app-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
            transition: box-shadow .15s, border-color .15s;
        }

        .app-card:hover {
            border-color: #a5b4fc;
            box-shadow: 0 6px 20px rgba(99, 102, 241, .08);
        }

        .app-card.is-accepted {
            border-left: 3px solid #059669;
            border-radius: 0 20px 20px 0;
        }

        .app-card.is-rejected {
            border-left: 3px solid #DC2626;
            border-radius: 0 20px 20px 0;
        }

        .app-card.is-pending {
            border-left: 3px solid #D97706;
            border-radius: 0 20px 20px 0;
        }

        /* ── ACCEPTED BOX ── */
        .accepted-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .accepted-box-title {
            font-size: 13px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .accepted-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .accepted-cell {
            background: white;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .accepted-cell label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .accepted-cell p {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
        }

        .company-msg {
            background: #dcfce7;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #14532d;
            line-height: 1.65;
        }

        .company-msg strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        /* ── REJECTED BOX ── */
        .rejected-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .rejected-box-title {
            font-size: 13px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rejected-msg {
            background: #fecaca;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #7f1d1d;
            line-height: 1.65;
        }

        .rejected-msg strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        /* ── REVIEW FORM ── */
        .form-section {
            background: #f5f3ff;
            border: 1px solid #e9d5ff;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .form-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            color: #111827;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        .form-textarea {
            grid-column: 1 / -1;
        }

        .form-textarea textarea {
            min-height: 90px;
            resize: vertical;
        }

        /* ── SUCCESS MESSAGE ── */
        .success-msg {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 13px 16px;
            color: #065f46;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 2px;
        }

        /* ── EMPTY STATE ── */
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

        @media(max-width:640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                            <i class="ti ti-inbox" style="font-size:11px;margin-right:4px;"></i>Applications
                        </p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">Received applications</h1>
                        <p style="font-size:12px;color:#64748b;">Review candidates, accept or reject them, and schedule interviews.</p>
                    </div>
                    <a href="add_job.php" class="btn-pri">
                        <i class="ti ti-plus"></i> Post new job
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="success-msg" style="margin-bottom:14px;">
                        <i class="ti ti-circle-check" style="font-size:15px;"></i>
                        <?php echo e($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($applications)): ?>

                    <div class="empty-state">
                        <i class="ti ti-inbox"></i>
                        <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No applications yet</p>
                        <p style="font-size:12px;color:#94a3b8;margin-bottom:18px;">Post a job to start receiving applications from candidates.</p>
                        <a href="add_job.php" class="btn-pri" style="display:inline-flex;margin:0 auto;">
                            <i class="ti ti-briefcase"></i> Post a job
                        </a>
                    </div>

                <?php else: ?>

                    <?php
                    $total    = count($applications);
                    $accepted = count(array_filter($applications, fn($a) => $a['status'] === 'accepted'));
                    $rejected = count(array_filter($applications, fn($a) => $a['status'] === 'rejected'));
                    $pending  = $total - $accepted - $rejected;
                    ?>

                    <!-- STATS -->
                    <div class="stats-grid">
                        <div class="sc">
                            <div class="sc-val" style="color:#6366f1;"><?php echo $total; ?></div>
                            <div class="sc-label">Total</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#D97706;"><?php echo $pending; ?></div>
                            <div class="sc-label">Pending</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#059669;"><?php echo $accepted; ?></div>
                            <div class="sc-label">Accepted</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#DC2626;"><?php echo $rejected; ?></div>
                            <div class="sc-label">Rejected</div>
                        </div>
                    </div>

                    <!-- APPLICATION CARDS -->
                    <div style="display:flex;flex-direction:column;gap:0;">
                        <?php foreach ($applications as $application): ?>
                            <?php
                            $status = $application['status'] ?? 'pending';
                            if ($status === 'accepted') {
                                $statusPill = '<span class="pill-green"><i class="ti ti-check" style="font-size:10px;"></i>Accepted</span>';
                                $cardClass  = 'is-accepted';
                            } elseif ($status === 'rejected') {
                                $statusPill = '<span class="pill-red"><i class="ti ti-x" style="font-size:10px;"></i>Rejected</span>';
                                $cardClass  = 'is-rejected';
                            } else {
                                $statusPill = '<span class="pill-amber"><i class="ti ti-clock" style="font-size:10px;"></i>Pending</span>';
                                $cardClass  = 'is-pending';
                            }
                            ?>
                            <article class="app-card <?php echo $cardClass; ?>">

                                <!-- Top row -->
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                        <span class="pill-purple">
                                            <i class="ti ti-user" style="font-size:10px;"></i>
                                            <?php echo e($application['candidate_name'] ?: $application['candidate_email']); ?>
                                        </span>
                                        <span class="pill-gray">
                                            <i class="ti ti-calendar" style="font-size:10px;"></i>
                                            <?php echo e(date('M d, Y', strtotime($application['created_at']))); ?>
                                        </span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                        <?php echo $statusPill; ?>
                                        <span class="pill-gray">
                                            <?php echo timeAgo($application['created_at']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Job title -->
                                <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:4px;">
                                    <?php echo e($application['job_title']); ?>
                                </div>

                                <!-- Pending: Review Form -->
                                <?php if ($status === 'pending'): ?>
                                    <form method="POST" class="form-section">
                                        <input type="hidden" name="application_id" value="<?php echo e($application['id']); ?>">
                                        <p class="form-section-title">
                                            <i class="ti ti-edit" style="font-size:13px;"></i>
                                            Review this application
                                        </p>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Interview Date</label>
                                                <input type="date" name="interview_date" />
                                            </div>
                                            <div class="form-group">
                                                <label>Interview Time</label>
                                                <input type="time" name="interview_time" />
                                            </div>
                                        </div>
                                        <div class="form-group form-textarea">
                                            <label>Message to Candidate</label>
                                            <textarea name="company_message" placeholder="Send a message with your decision..."></textarea>
                                        </div>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                                            <button type="submit" name="action" value="accept" class="btn-success">
                                                <i class="ti ti-check"></i> Accept
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn-danger">
                                                <i class="ti ti-x"></i> Reject
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <!-- Accepted info box -->
                                <?php if ($status === 'accepted'): ?>
                                    <div class="accepted-box">
                                        <p class="accepted-box-title">
                                            <i class="ti ti-confetti" style="color:#059669;font-size:15px;"></i>
                                            Candidate accepted. Interview scheduled.
                                        </p>
                                        <div class="accepted-grid">
                                            <?php if (!empty($application['interview_date'])): ?>
                                                <div class="accepted-cell">
                                                    <label>Interview date</label>
                                                    <p><?php echo e(date('F d, Y', strtotime($application['interview_date']))); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($application['interview_time'])): ?>
                                                <div class="accepted-cell">
                                                    <label>Interview time</label>
                                                    <p><?php echo e(date('g:i A', strtotime($application['interview_time']))); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($application['company_message'])): ?>
                                            <div class="company-msg">
                                                <strong>Your message</strong>
                                                <?php echo e($application['company_message']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Rejected info box -->
                                <?php if ($status === 'rejected'): ?>
                                    <div class="rejected-box">
                                        <p class="rejected-box-title">
                                            <i class="ti ti-ban" style="font-size:13px;"></i>
                                            Candidate rejected
                                        </p>
                                        <?php if (!empty($application['company_message'])): ?>
                                            <div class="rejected-msg">
                                                <strong>Your message</strong>
                                                <?php echo e($application['company_message']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="pf-divider"></div>

                                <!-- Actions row -->
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a href="job_details.php?id=<?php echo e($application['job_id']); ?>" class="btn-sec">
                                            <i class="ti ti-eye"></i> View job
                                        </a>
                                    </div>
                                    <p style="font-size:11px;color:#94a3b8;">
                                        Updated <?php echo timeAgo($application['created_at']); ?>
                                    </p>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>

</html>