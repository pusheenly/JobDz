<?php
require_once 'functions.php';
requireLogin();
if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}

$applications = getCandidateApplications($_SESSION['user']['id']);
$pageTitle = 'Applications | JobDZ';
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

            <?php $activeProfileTab = 'applications';
            include 'includes/profile-sidebar.php'; ?>

            <div style="display:flex;flex-direction:column;gap:0;min-width:0;">

                <!-- HEADER CARD -->
                <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div>
                        <p class="pf-label">
                            <i class="ti ti-send" style="font-size:11px;margin-right:4px;"></i>Applications
                        </p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">Your job applications</h1>
                        <p style="font-size:12px;color:#64748b;">Track your status and keep following up with promising roles.</p>
                    </div>
                    <a href="job.php" class="btn-pri">
                        <i class="ti ti-briefcase"></i> Browse more jobs
                    </a>
                </div>

                <?php if (empty($applications)): ?>

                    <div class="empty-state">
                        <i class="ti ti-send"></i>
                        <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No applications yet</p>
                        <p style="font-size:12px;color:#94a3b8;margin-bottom:18px;">Browse roles and apply to positions you're interested in.</p>
                        <a href="job.php" class="btn-pri" style="display:inline-flex;margin:0 auto;">
                            <i class="ti ti-search"></i> View jobs
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
                                            <i class="ti ti-building" style="font-size:10px;"></i>
                                            <?php echo e($application['company_name'] ?? 'Company'); ?>
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
                                    <?php echo e($application['title']); ?>
                                </div>

                                <!-- Accepted info box -->
                                <?php if ($status === 'accepted'): ?>
                                    <div class="accepted-box">
                                        <p class="accepted-box-title">
                                            <i class="ti ti-confetti" style="color:#059669;font-size:15px;"></i>
                                            Congratulations! You've been accepted.
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
                                            <?php if (!empty($application['company_city'])): ?>
                                                <div class="accepted-cell">
                                                    <label>Location</label>
                                                    <p><?php echo e($application['company_city']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($application['company_message'])): ?>
                                            <div class="company-msg">
                                                <strong>Message from company</strong>
                                                <?php echo e($application['company_message']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="pf-divider"></div>

                                <!-- Actions -->
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <a href="job_details.php?id=<?php echo e($application['job_id']); ?>" class="btn-sec">
                                            <i class="ti ti-eye"></i> View job
                                        </a>
                                        <?php if ($status !== 'accepted'): ?>
                                            <button type="button"
                                                class="btn-danger remove-application"
                                                data-action="remove"
                                                data-job-id="<?php echo e($application['job_id']); ?>">
                                                <i class="ti ti-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>
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
    <script>
        document.querySelectorAll('.remove-application').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.dataset.jobId;
                if (confirm('Are you sure you want to remove this application?')) {
                    window.location.href = 'remove_application.php?job_id=' + jobId;
                }
            });
        });
    </script>
</body>

</html>