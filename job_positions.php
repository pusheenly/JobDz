<?php
require_once 'functions.php';
requireLogin();

if ($_SESSION['user']['role'] !== 'company') {
    header('Location: index.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$activeProfileTab = 'jobs';

$message = '';

closeExpiredJobs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = intval($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($jobId > 0 && $action === 'toggle_job_status') {
        if (toggleJobStatus($jobId, $_SESSION['user']['id'])) {
            $message = 'Job status updated successfully.';
        }
    }
}

$companyJobs = getCompanyJobs($_SESSION['user']['id']);
$pageTitle = 'My Jobs | JobDZ';
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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }

        body { background: #f8fafc !important; color: #0f172a; }

        /* ── CARD ── */
        .card {
            background: white; border: 1px solid #e8eef6; border-radius: 24px;
            padding: 1.1rem 1.25rem; box-shadow: 0 6px 18px rgba(15,23,42,.03);
            margin-bottom: 14px;
        }
        .pf-label {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .07em; color: #6366f1; margin-bottom: 4px;
        }
        .pf-divider { height: 1px; background: #f1f5f9; margin: 12px 0; }

        /* ── BUTTONS ── */
        .btn-pri {
            display: inline-flex; align-items: center; gap: 6px;
            background: #6366f1; color: white; border: none; border-radius: 12px;
            padding: 9px 18px; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Poppins', sans-serif;
            text-decoration: none; transition: background .2s;
        }
        .btn-pri:hover { background: #4f46e5; color: white; }

        .btn-sec {
            display: inline-flex; align-items: center; gap: 6px;
            background: white; color: #334155; border: 1px solid #e8eef6;
            border-radius: 12px; padding: 9px 18px; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Poppins', sans-serif;
            text-decoration: none; transition: background .2s;
        }
        .btn-sec:hover { background: #f8fafc; color: #334155; }

        .btn-success {
            display: inline-flex; align-items: center; gap: 6px;
            background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;
            border-radius: 12px; padding: 9px 16px; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Poppins', sans-serif; transition: background .2s;
        }
        .btn-success:hover { background: #d1fae5; }

        .btn-danger {
            display: inline-flex; align-items: center; gap: 6px;
            background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;
            border-radius: 12px; padding: 9px 16px; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: 'Poppins', sans-serif; transition: background .2s;
        }
        .btn-danger:hover { background: #FEE2E2; }

        /* ── PILLS ── */
        .pill-purple { background: #ede9fe; color: #3C3489; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }
        .pill-gray   { background: #f1f5f9; color: #475569; font-size: 11px; padding: 3px 10px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }
        .pill-green  { background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }
        .pill-amber  { background: #fffbeb; color: #92400e; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }
        .pill-red    { background: #fef2f2; color: #991b1b; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }

        /* ── STATS GRID ── */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px; }
        .sc {
            background: white; border: 1px solid #e8eef6; border-radius: 20px;
            padding: .9rem 1rem; box-shadow: 0 6px 18px rgba(15,23,42,.03);
            text-align: center;
        }
        .sc-val   { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .sc-label { font-size: 11px; color: #94a3b8; font-weight: 500; }

        /* ── JOB CARD ── */
        .job-card {
            background: white; border: 1px solid #e8eef6; border-radius: 20px;
            padding: 18px 20px; margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15,23,42,.03);
            transition: box-shadow .15s, border-color .15s;
        }
        .job-card:hover { border-color: #a5b4fc; box-shadow: 0 6px 20px rgba(99,102,241,.08); }
        .job-card.is-open { border-left: 3px solid #059669; border-radius: 0 20px 20px 0; }
        .job-card.is-closed { border-left: 3px solid #DC2626; border-radius: 0 20px 20px 0; }
        .job-card.is-expired { border-left: 3px solid #D97706; border-radius: 0 20px 20px 0; }

        /* ── JOB INFO BOX ── */
        .job-info-box {
            background: #f5f3ff; border: 1px solid #e9d5ff;
            border-radius: 16px; padding: 14px 16px; margin-top: 12px;
        }
        .job-info-title {
            font-size: 13px; font-weight: 600; color: #6366f1;
            margin-bottom: 12px; display: flex; align-items: center; gap: 6px;
        }
        .job-info-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
        }
        .job-info-cell {
            background: white; border-radius: 10px; padding: 10px 12px;
        }
        .job-info-cell label {
            display: block; font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .06em;
            color: #94a3b8; margin-bottom: 4px;
        }
        .job-info-cell p { font-size: 13px; font-weight: 500; color: #111827; }

        /* ── SUCCESS MESSAGE ── */
        .success-msg {
            background: #ecfdf5; border: 1px solid #bbf7d0; border-radius: 16px;
            padding: 13px 16px; color: #065f46; font-size: 12px; font-weight: 500;
            display: flex; align-items: center; gap: 8px; margin-bottom: 2px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 3rem 1.5rem;
            background: #fafafe; border: 1.5px dashed #e0e7ff;
            border-radius: 20px;
        }
        .empty-state i { font-size: 28px; color: #c7d2fe; margin-bottom: 10px; display: block; }

        @media(max-width:640px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
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
                            <i class="ti ti-briefcase" style="font-size:11px;margin-right:4px;"></i>Jobs Management
                        </p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">My Jobs</h1>
                        <p style="font-size:12px;color:#64748b;">Manage your published jobs and control their availability duration.</p>
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

                <?php if (empty($companyJobs)): ?>

                    <div class="empty-state">
                        <i class="ti ti-briefcase"></i>
                        <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No jobs published yet</p>
                        <p style="font-size:12px;color:#94a3b8;margin-bottom:18px;">Post your first job to start receiving applications from candidates.</p>
                        <a href="add_job.php" class="btn-pri" style="display:inline-flex;margin:0 auto;">
                            <i class="ti ti-briefcase"></i> Post a job
                        </a>
                    </div>

                <?php else: ?>

                    <?php
                        $total    = count($companyJobs);
                        $open     = count(array_filter($companyJobs, fn($j) => $j['status'] === 'open'));
                        $closed   = count(array_filter($companyJobs, fn($j) => $j['status'] === 'closed'));
                        $expired  = count(array_filter($companyJobs, fn($j) => !empty($j['expires_at']) && (strtotime($j['expires_at']) - time()) < 0));
                    ?>

                    <!-- STATS -->
                    <div class="stats-grid">
                        <div class="sc">
                            <div class="sc-val" style="color:#6366f1;"><?php echo $total; ?></div>
                            <div class="sc-label">Total</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#059669;"><?php echo $open; ?></div>
                            <div class="sc-label">Open</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#DC2626;"><?php echo $closed; ?></div>
                            <div class="sc-label">Closed</div>
                        </div>
                        <div class="sc">
                            <div class="sc-val" style="color:#D97706;"><?php echo $expired; ?></div>
                            <div class="sc-label">Expired</div>
                        </div>
                    </div>

                    <!-- JOB CARDS -->
                    <div style="display:flex;flex-direction:column;gap:0;">
                        <?php foreach ($companyJobs as $job): ?>
                        <?php
                            $status = $job['status'];
                            $expiresAt = !empty($job['expires_at']) ? strtotime($job['expires_at']) : null;
                            $daysLeft = $expiresAt ? ceil(($expiresAt - time()) / 86400) : 0;

                            if ($status === 'open' && $daysLeft > 0) {
                                $statusPill = '<span class="pill-green"><i class="ti ti-check" style="font-size:10px;"></i>Open</span>';
                                $cardClass  = 'is-open';
                            } elseif ($status === 'closed') {
                                $statusPill = '<span class="pill-red"><i class="ti ti-x" style="font-size:10px;"></i>Closed</span>';
                                $cardClass  = 'is-closed';
                            } else {
                                $statusPill = '<span class="pill-amber"><i class="ti ti-clock" style="font-size:10px;"></i>Expired</span>';
                                $cardClass  = 'is-expired';
                            }
                        ?>
                        <article class="job-card <?php echo $cardClass; ?>">

                            <!-- Top row -->
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                    <span class="pill-purple">
                                        <i class="ti ti-map-pin" style="font-size:10px;"></i>
                                        <?php echo e($job['city'] ?? 'No location'); ?>
                                    </span>
                                    <span class="pill-gray">
                                        <i class="ti ti-clock" style="font-size:10px;"></i>
                                        <?php if ($daysLeft > 0): ?>
                                            <?php echo $daysLeft; ?> days left
                                        <?php else: ?>
                                            Expired
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                    <?php echo $statusPill; ?>
                                </div>
                            </div>

                            <!-- Job title -->
                            <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:4px;">
                                <?php echo e($job['title']); ?>
                            </div>

                            <!-- Job info box -->
                            <div class="job-info-box">
                                <p class="job-info-title">
                                    <i class="ti ti-info-circle" style="font-size:13px;"></i>
                                    Job details
                                </p>
                                <div class="job-info-grid">
                                    <div class="job-info-cell">
                                        <label>Posted</label>
                                        <p><?php echo e(date('M d, Y', strtotime($job['created_at']))); ?></p>
                                    </div>
                                    <div class="job-info-cell">
                                        <label>Expires</label>
                                        <p>
                                            <?php
                                            echo !empty($job['expires_at'])
                                                ? e(date('M d, Y', strtotime($job['expires_at'])))
                                                : 'No expiry';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="job-info-cell">
                                        <label>Category</label>
                                        <p><?php echo e($job['category'] ?? 'General'); ?></p>
                                    </div>
                                    <div class="job-info-cell">
                                        <label>Type</label>
                                        <p><?php echo e(ucfirst($job['employment_type'] ?? 'Full-time')); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="pf-divider"></div>

                            <!-- Actions row -->
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <a href="job_details.php?id=<?php echo e($job['id']); ?>" class="btn-sec">
                                        <i class="ti ti-eye"></i> View job
                                    </a>

                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="job_id" value="<?php echo e($job['id']); ?>">
                                        <input type="hidden" name="action" value="toggle_job_status">

                                        <?php if ($status === 'open'): ?>
                                            <button type="submit" class="btn-danger">
                                                <i class="ti ti-x"></i> Close job
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn-success">
                                                <i class="ti ti-play"></i> Reopen job
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
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