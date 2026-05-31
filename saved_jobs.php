<?php
require_once 'functions.php';

requireLogin();

$current_page = basename($_SERVER['PHP_SELF'], '.php');

if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}

$removed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_job']) && is_numeric($_POST['remove_job'])) {
    removeSavedJob(intval($_POST['remove_job']), $_SESSION['user']['id']);
    $removed = true;
}

$savedJobs = getSavedJobs($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = 'Saved Jobs | JobDZ';
    include 'includes/tailwind-head.php'; ?>
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

        /* ── CARD ── */
        .card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            margin-bottom: 14px;
        }

        .ch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .9rem;
        }

        .ct {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ct i {
            font-size: 16px;
            color: #6366f1;
        }

        .pf-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6366f1;
            margin-bottom: 4px;
        }

        /* ── BUTTONS ── */
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

        /* ── SORT BAR ── */
        .sort-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 12px 18px;
            flex-wrap: wrap;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            margin-bottom: 14px;
        }

        .sort-bar select {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 12px;
            color: #0f172a;
            outline: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .sort-bar select:focus {
            border-color: #a5b4fc;
        }

        /* ── JOB CARD ── */
        .job-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
            transition: box-shadow .15s, border-color .15s;
        }

        .job-card:hover {
            border-color: #a5b4fc;
            box-shadow: 0 6px 20px rgba(99, 102, 241, .08);
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

        /* ── TOAST ── */
        .toast-success {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 16px;
            padding: 12px 18px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: #fafafe;
            border: 1.5px dashed #e0e7ff;
            border-radius: 20px;
            font-size: 12.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        .empty-state i {
            font-size: 28px;
            color: #c7d2fe;
            margin-bottom: 10px;
            display: block;
        }

        .pf-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 12px 0;
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">

            <?php $activeProfileTab = 'saved_jobs';
            include 'includes/profile-sidebar.php'; ?>

            <div style="display:flex;flex-direction:column;gap:0;min-width:0;">

                <!-- HEADER CARD -->
                <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div>
                        <p class="pf-label"><i class="ti ti-bookmark" style="font-size:11px;margin-right:4px"></i>Saved jobs</p>
                        <h1 style="font-size:18px;font-weight:700;color:#111827;margin-bottom:4px;">Your saved opportunities</h1>
                        <p style="font-size:12px;color:#64748b;">Keep the roles you love in one place.</p>
                    </div>
                    <a href="job.php" class="btn-pri">
                        <i class="ti ti-briefcase"></i> Browse jobs
                    </a>
                </div>

                <?php if ($removed): ?>
                    <div class="toast-success">
                        <i class="ti ti-circle-check" style="font-size:17px;"></i>
                        Job removed from your saved list.
                    </div>
                <?php endif; ?>

                <?php if (empty($savedJobs)): ?>

                    <div class="empty-state">
                        <i class="ti ti-bookmark"></i>
                        <p style="font-size:15px;font-weight:600;color:#374151;margin-bottom:6px;">No saved jobs yet</p>
                        <p style="margin-bottom:18px;">Save interesting jobs while browsing to revisit them later.</p>
                        <a href="job.php" class="btn-pri" style="display:inline-flex;margin:0 auto;">
                            <i class="ti ti-search"></i> Browse jobs
                        </a>
                    </div>

                <?php else: ?>

                    <!-- SORT BAR -->
                    <div class="sort-bar">
                        <p style="font-size:12px;color:#64748b;">
                            Showing <strong style="color:#0f172a;"><?php echo count($savedJobs); ?></strong>
                            saved job<?php echo count($savedJobs) !== 1 ? 's' : ''; ?>
                        </p>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <label for="sort-select" style="font-size:12px;color:#64748b;">Sort by</label>
                            <select id="sort-select" onchange="sortJobs(this.value)">
                                <option value="date_desc">Newest first</option>
                                <option value="date_asc">Oldest first</option>
                                <option value="title">Title</option>
                                <option value="company">Company</option>
                            </select>
                        </div>
                    </div>

                    <!-- JOB CARDS -->
                    <div id="saved-jobs-list">
                        <?php foreach ($savedJobs as $job): ?>
                            <div class="job-card"
                                data-saved-date="<?php echo strtotime($job['saved_at']); ?>"
                                data-title="<?php echo strtolower(e($job['title'])); ?>"
                                data-company="<?php echo strtolower(e($job['company_name'] ?? 'company')); ?>">

                                <!-- Top row -->
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                        <span class="pill-purple">
                                            <i class="ti ti-building" style="font-size:10px;"></i>
                                            <?php echo e($job['company_name'] ?? 'Company'); ?>
                                        </span>
                                        <?php if (!empty($job['city'])): ?>
                                            <span class="pill-gray">
                                                <i class="ti ti-map-pin" style="font-size:10px;"></i>
                                                <?php echo e($job['city']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                        <span class="pill-gray">
                                            <i class="ti ti-clock" style="font-size:10px;"></i>
                                            <?php echo timeAgo($job['saved_at']); ?>
                                        </span>
                                        <?php if (hasAppliedJob($job['id'], $_SESSION['user']['id'])): ?>
                                            <span class="pill-green">
                                                <i class="ti ti-check" style="font-size:10px;"></i>Applied
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Title + meta -->
                                <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:3px;">
                                    <?php echo e($job['title']); ?>
                                </div>
                                <div style="font-size:12px;color:#64748b;margin-bottom:10px;">
                                    <?php echo e($job['specialty'] ?? ''); ?>
                                    <?php if (!empty($job['salary'])): ?>
                                        <span style="margin:0 5px;color:#e2e8f0;">•</span>
                                        <?php echo e($job['salary']); ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Description snippet -->
                                <div style="font-size:12px;color:#64748b;line-height:1.65;margin-bottom:14px;">
                                    <?php echo e(substr(strip_tags($job['description']), 0, 160)); ?>…
                                </div>

                                <div class="pf-divider"></div>

                                <!-- Actions -->
                                <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                                    <a href="job_details.php?id=<?php echo e($job['id']); ?>" class="btn-sec">
                                        <i class="ti ti-eye"></i> View details
                                    </a>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="remove_job" value="<?php echo e($job['id']); ?>">
                                        <button type="submit" class="btn-danger"
                                            onclick="return confirm('Remove this job from saved jobs?')">
                                            <i class="ti ti-trash"></i> Remove
                                        </button>
                                    </form>
                                    <?php if (!hasAppliedJob($job['id'], $_SESSION['user']['id'])): ?>
                                        <form method="POST" action="job.php?id=<?php echo e($job['id']); ?>" style="margin:0;margin-left:auto;">
                                            <button type="submit" name="action" value="apply" class="btn-pri">
                                                <i class="ti ti-send"></i> Quick apply
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function sortJobs(sortBy) {
            const list = document.getElementById('saved-jobs-list');
            if (!list) return;
            const jobs = Array.from(list.children);
            jobs.sort((a, b) => {
                switch (sortBy) {
                    case 'date_desc':
                        return parseInt(b.dataset.savedDate) - parseInt(a.dataset.savedDate);
                    case 'date_asc':
                        return parseInt(a.dataset.savedDate) - parseInt(b.dataset.savedDate);
                    case 'title':
                        return a.dataset.title.localeCompare(b.dataset.title);
                    case 'company':
                        return a.dataset.company.localeCompare(b.dataset.company);
                    default:
                        return 0;
                }
            });
            jobs.forEach(j => list.appendChild(j));
        }
    </script>
</body>

</html>