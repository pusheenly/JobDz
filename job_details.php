<?php

require_once 'config.php';
require_once 'functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: job.php');
    exit;
}

$jobId = (int) $_GET['id'];
$job = getJobById($jobId);

if (!$job) {
    header('Location: job.php');
    exit;
}

$viewedJobs = [];
if (isset($_COOKIE['viewed_jobs'])) {
    $viewedJobs = json_decode($_COOKIE['viewed_jobs'], true);
}
if (!is_array($viewedJobs)) $viewedJobs = [];
if (!in_array($jobId, $viewedJobs)) {
    incrementJobViews($jobId);
    $viewedJobs[] = $jobId;
    setcookie('viewed_jobs', json_encode($viewedJobs), time() + (86400 * 30), "/");
}

$isLoggedIn  = isLoggedIn();
$isCandidate = $isLoggedIn && $_SESSION['user']['role'] === 'candidate';
$applicationData = $isCandidate ? hasAppliedJob($jobId, $_SESSION['user']['id']) : false;
$isApplied   = (bool)$applicationData;
$isSaved     = $isCandidate ? hasSavedJob($jobId, $_SESSION['user']['id']) : false;
$company     = getCompanyProfile($job['user_id']);
$companyJobs = getCompanyJobs($job['user_id'], $jobId);
$relatedJobs = getRelatedJobs($job['category'], $jobId, $job['city']);
$canApply    = $job['status'] === 'open';
$expiresAt   = !empty($job['expires_at']) ? strtotime($job['expires_at']) : null;
$daysLeft    = $expiresAt ? ceil(($expiresAt - time()) / 86400) : 0;
$activeTab   = $_GET['tab'] ?? 'job';
$applicationsCount = $job['applications_count'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $pageTitle = e($job['title']) . ' | JobDZ';
    include 'includes/tailwind-head.php';
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 16px 60px;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .breadcrumb a {
            color: #94a3b8;
            text-decoration: none;
            transition: color .2s;
        }

        .breadcrumb a:hover {
            color: #6366f1;
        }

        .breadcrumb i {
            font-size: 13px;
        }

        /* ── HERO CARD ── */
        .hero-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 28px 28px 24px;
            margin-bottom: 16px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            position: relative;
            overflow: hidden;
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(99, 102, 241, .08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero-left {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex: 1;
            min-width: 260px;
        }

        /* Company Logo */
        .company-logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
            overflow: hidden;
            text-decoration: none;
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 17px;
        }

        .hero-info h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 14px;
            font-weight: 500;
            flex-wrap: wrap;
        }

        .hero-meta a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }

        .hero-meta .sep {
            color: #cbd5e1;
        }

        .hero-meta i {
            font-size: 14px;
            color: #94a3b8;
        }

        /* Badges */
        .badges-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .badge i {
            font-size: 13px;
        }

        .badge-default {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }

        .badge-purple {
            background: #ede9fe;
            color: #5b21b6;
            border-color: #ddd6fe;
        }

        .badge-green {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        /* Hero Right — Action buttons */
        .hero-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .hero-actions {
            display: flex;
            gap: 8px;
        }

        .hero-btn {
            height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            transition: .2s;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
        }

        .hero-btn i {
            font-size: 16px;
        }

        .hero-btn-save {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .hero-btn-save:hover,
        .hero-btn-save.saved {
            background: #ede9fe;
            color: #5b21b6;
            border-color: #ddd6fe;
        }

        .hero-btn-apply {
            background: #6366f1;
            color: white;
        }

        .hero-btn-apply:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .hero-btn-applied {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: default;
        }

        /* ── MAIN GRID ── */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 16px;
        }

        @media (max-width: 900px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .hero-right {
                align-items: flex-start;
            }
        }

        /* ── CARD ── */
        .card {
            background: white;
            border: 1px solid #ececf3;
            border-radius: 20px;
            padding: 20px 22px;
            margin-bottom: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .card:last-child {
            margin-bottom: 0;
        }

        .ch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .ct {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ct i {
            color: #6366f1;
            font-size: 17px;
        }

        .cl {
            font-size: 11px;
            color: #6366f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
        }

        .cl i {
            font-size: 14px;
        }

        /* Text body */
        .text-body {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.8;
            font-weight: 400;
        }

        /* ── SECTION SUB TITLE ── */
        .section-sub {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin: 18px 0 10px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .section-sub:first-child {
            margin-top: 0;
        }

        .section-sub i {
            color: #6366f1;
            font-size: 16px;
        }

        /* ── LIST ITEMS ── */
        .list-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
        }

        .check-ico {
            width: 22px;
            height: 22px;
            min-width: 22px;
            border-radius: 7px;
            background: #ede9fe;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }

        .check-ico i {
            color: #6366f1;
            font-size: 13px;
        }

        .show-more-btn {
            color: #6366f1;
            font-size: 12px;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
            font-family: 'Poppins', sans-serif;
            transition: opacity .2s;
        }

        .show-more-btn i {
            font-size: 14px;
        }

        .show-more-btn:hover {
            opacity: .75;
        }

        /* ── SKILLS ── */
        .skills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .skill-pill {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
            padding: 5px 13px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
        }

        /* ── INFO GRID ── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 11px 13px;
        }

        .i-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }

        .i-value {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .i-value i {
            color: #6366f1;
            font-size: 14px;
        }

        /* ── TWO-COL ── */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        /* ── RELATED JOBS ── */
        .related-job-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 10px;
            border: 1px solid #e8eef6;
            border-radius: 14px;
            text-decoration: none;
            transition: .2s;
            background: #fafafe;
        }

        .related-job-card:hover {
            border-color: #c7d2fe;
            background: #f5f3ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, .08);
        }

        .rj-ico {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .rj-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .rj-company {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .s-card {
            background: white;
            border: 1px solid #ececf3;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .s-card-title {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .s-card-title i {
            color: #6366f1;
            font-size: 16px;
        }

        /* Apply buttons */
        .btn-apply {
            width: 100%;
            padding: 13px;
            border-radius: 13px;
            background: #6366f1;
            color: white;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            transition: background .2s, transform .2s;
            text-decoration: none;
        }

        .btn-apply i {
            font-size: 16px;
        }

        .btn-apply:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

        .btn-applied {
            width: 100%;
            padding: 13px;
            border-radius: 13px;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: default;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-applied i {
            font-size: 16px;
        }

        .btn-outline {
            width: 100%;
            padding: 11px;
            border-radius: 13px;
            background: white;
            color: #6366f1;
            font-size: 13px;
            font-weight: 600;
            border: 1.5px solid #6366f1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            transition: all .2s;
            text-decoration: none;
            margin-top: 10px;
        }

        .btn-outline i {
            font-size: 15px;
        }

        .btn-outline:hover {
            background: #f5f3ff;
        }

        /* Apply status box */
        .apply-status-box {
            border-radius: 12px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            margin-bottom: 12px;
        }

        .apply-status-open {
            font-size: 12px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .apply-status-open::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #1D9E75;
            border-radius: 50%;
            display: inline-block;
        }

        .days-badge {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        .already-applied-note {
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
            margin-top: 8px;
            font-weight: 500;
        }

        /* Stat rows */
        .stat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .sr-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #64748b;
            font-weight: 500;
        }

        .sr-label i {
            color: #6366f1;
            font-size: 15px;
            width: 16px;
            text-align: center;
        }

        .sr-val {
            font-weight: 700;
            color: #111827;
        }

        .status-open-badge {
            background: #ecfdf5;
            color: #065f46;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
        }

        /* Company sidebar */
        .company-name-s {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        .cd-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 10px;
        }

        .cd-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }

        .cd-val {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .divider-line {
            height: 1px;
            background: #f1f5f9;
            margin: 10px 0;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: #475569;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .contact-row i {
            color: #6366f1;
            font-size: 15px;
            width: 16px;
            text-align: center;
        }

        .contact-row a {
            color: #6366f1;
            text-decoration: none;
        }

        /* Map */
        .map-container {
            width: 100%;
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e8eef6;
            margin-bottom: 10px;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .hidden {
            display: none;
        }

        .hidden-item {
            display: none !important;
        }

        @media (max-width: 768px) {
            .hero-top {
                flex-direction: column;
            }

            .hero-right {
                width: 100%;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-btn {
                flex: 1;
                justify-content: center;
            }
        }

        .list-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
        }

        .check-ico {
            width: 20px;
            height: 20px;
            min-width: 20px;
            border-radius: 6px;
            background: #ede9fe;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="page-wrapper">

        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <i class="ti ti-chevron-right"></i>
            <a href="jobs.php">Jobs</a>
            <i class="ti ti-chevron-right"></i>
            <span style="color:#6366f1;"><?php echo e($job['title']); ?></span>
        </div>

        <!-- ── HERO CARD ── -->
        <div class="hero-card">
            <div class="hero-top">

                <div class="hero-left">
                    <a href="company.php?id=<?php echo $job['user_id']; ?>" class="company-logo">
                        <?php if (!empty($job['logo_url'])): ?>
                            <img src="<?php echo e($job['logo_url']); ?>" alt="">
                        <?php else: ?>
                            <?php echo strtoupper(substr($job['company_name'] ?? 'J', 0, 2)); ?>
                        <?php endif; ?>
                    </a>

                    <div class="hero-info">
                        <h1><?php echo e($job['title']); ?></h1>
                        <div class="hero-meta">
                            <i class="ti ti-building"></i>
                            <a href="company.php?id=<?php echo $job['user_id']; ?>">
                                <?php echo e($job['company_name']); ?>
                            </a>
                            <span class="sep">·</span>
                            <i class="ti ti-map-pin"></i>
                            <span><?php echo e($job['city']); ?>, Algeria</span>
                        </div>
                        <div class="badges-row">
                            <?php if ($canApply): ?>
                                <span class="badge badge-green">
                                    <i class="ti ti-point-filled" style="font-size:10px;"></i> Open
                                </span>
                            <?php else: ?>
                                <span class="badge badge-default">Closed</span>
                            <?php endif; ?>
                            <?php if (!empty($job['category'])): ?>
                                <span class="badge badge-purple">
                                    <i class="ti ti-layout-grid"></i> <?php echo e($job['category']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($job['contract_type'])): ?>
                                <span class="badge badge-default">
                                    <i class="ti ti-file-text"></i> <?php echo e($job['contract_type']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($job['work_mode'])): ?>
                                <span class="badge badge-default">
                                    <i class="ti ti-device-laptop"></i> <?php echo e(ucfirst($job['work_mode'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($job['experience_level'])): ?>
                                <span class="badge badge-default">
                                    <i class="ti ti-trending-up"></i> <?php echo e($job['experience_level']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Hero Right -->
                <div class="hero-right">
                    <div class="hero-actions">

                        <?php if ($isCandidate): ?>
                            <button id="saveJobBtn" class="hero-btn hero-btn-save <?php echo $isSaved ? 'saved' : ''; ?>">
                                <i id="saveIcon" class="ti <?php echo $isSaved ? 'ti-bookmark-filled' : 'ti-bookmark'; ?>"></i>
                                <?php echo $isSaved ? 'Saved' : 'Save Job'; ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($isCandidate && $isApplied): ?>
                            <button class="hero-btn hero-btn-applied" disabled>
                                <i class="ti ti-circle-check"></i> Applied
                            </button>
                        <?php elseif ($isLoggedIn && $isCandidate && $canApply): ?>
                            <button id="applyBtnHero" data-job-id="<?php echo $jobId; ?>" class="hero-btn hero-btn-apply">
                                <i class="ti ti-send"></i> Apply Now
                            </button>
                        <?php elseif (!$isLoggedIn && $canApply): ?>
                            <a href="login.php" class="hero-btn hero-btn-apply">
                                <i class="ti ti-login"></i> Login to Apply
                            </a>
                        <?php elseif (!$canApply): ?>
                            <button class="hero-btn hero-btn-applied" disabled>
                                <i class="ti ti-lock"></i> Closed
                            </button>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

        <!-- ── MAIN GRID ── -->
        <div class="main-grid">

            <!-- LEFT COLUMN -->
            <div>

                <!-- Description -->
                <div class="card">
                    <div class="ch">
                        <span class="ct"><i class="ti ti-file-description"></i> Job Description</span>
                    </div>

                    <div class="text-body">
                        <?php echo e($job['description'] ?? 'No description available.'); ?>
                    </div>

                    <?php if (!empty($job['responsibilities'])): ?>
                        <div class="section-sub">
                            <i class="ti ti-checks"></i> Key Responsibilities
                        </div>
                        <?php
                        $resp_lines = array_filter(array_map('trim', explode("\n", $job['responsibilities'])));
                        foreach ($resp_lines as $index => $line):
                        ?>
                            <div class="list-item responsibility-item <?php echo $index >= 2 ? 'hidden-item' : ''; ?>">

                                <span><?php echo e($line); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($resp_lines) > 2): ?>
                            <button class="show-more-btn" data-target="responsibility-item" data-open="false">
                                Show More <i class="ti ti-chevron-down"></i>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Skills -->
                <?php if (!empty($job['skills'])): ?>
                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-code"></i> Skills Required</span>
                        </div>
                        <div class="skills-wrap">
                            <?php
                            $skills = array_filter(array_map('trim', explode(",", $job['skills'])));
                            foreach ($skills as $skill):
                            ?>
                                <span class="skill-pill"><?php echo e($skill); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Job Information -->
                <div class="card">
                    <div class="ch">
                        <span class="ct"><i class="ti ti-briefcase"></i> Job Information</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-box">
                            <div class="i-label">Salary</div>
                            <div class="i-value"><i class="ti ti-coin"></i><?php echo e($job['salary'] ?? 'Negotiable'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="i-label">Contract Type</div>
                            <div class="i-value"><i class="ti ti-file-text"></i><?php echo e($job['contract_type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="i-label">Category</div>
                            <div class="i-value"><i class="ti ti-layout-grid"></i><?php echo e($job['category'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="i-label">Experience</div>
                            <div class="i-value"><i class="ti ti-clock"></i><?php echo e($job['experience'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="i-label">Experience Level</div>
                            <div class="i-value"><i class="ti ti-trending-up"></i><?php echo e($job['experience_level'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="i-label">Work Mode</div>
                            <div class="i-value"><i class="ti ti-device-laptop"></i><?php echo e($job['work_mode'] ?? 'N/A'); ?></div>
                        </div>
                        <?php if (!empty($job['education_level'])): ?>
                            <div class="info-box">
                                <div class="i-label">Education Level</div>
                                <div class="i-value"><i class="ti ti-school"></i><?php echo e($job['education_level']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($job['language_required'])): ?>
                            <div class="info-box">
                                <div class="i-label">Language Required</div>
                                <div class="i-value"><i class="ti ti-language"></i><?php echo e($job['language_required']); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="info-box">
                            <div class="i-label">Location</div>
                            <div class="i-value"><i class="ti ti-map-pin"></i><?php echo e($job['city']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Requirements & Benefits -->
                <?php if (!empty($job['requirements']) || !empty($job['benefits'])): ?>
                    <div class="card">
                        <div class="two-col">
                            <?php if (!empty($job['requirements'])): ?>
                                <div>
                                    <div class="section-sub" style="margin-top:0;">
                                        <i class="ti ti-circle-check"></i> Requirements
                                    </div>
                                    <?php
                                    $req_lines = array_filter(array_map('trim', explode("\n", $job['requirements'])));
                                    foreach ($req_lines as $index => $line):
                                    ?>
                                        <div class="list-item requirement-item <?php echo $index >= 2 ? 'hidden-item' : ''; ?>">

                                            <span><?php echo e($line); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($req_lines) > 4): ?>
                                        <button class="show-more-btn" data-target="requirement-item" data-open="false">
                                            Show More <i class="ti ti-chevron-down"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($job['benefits'])): ?>
                                <div>
                                    <div class="section-sub" style="margin-top:0;">
                                        <i class="ti ti-gift"></i> What We Offer
                                    </div>
                                    <?php
                                    $benefit_lines = array_filter(array_map('trim', explode("\n", $job['benefits'])));
                                    foreach ($benefit_lines as $line):
                                    ?>
                                        <div class="list-item">

                                            <span><?php echo e($line); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Related Jobs -->
                <?php if (!empty($relatedJobs)): ?>
                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-briefcase"></i> Related Jobs</span>
                            <a href="jobs.php" class="cl">Show all <i class="ti ti-arrow-right"></i></a>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px;">
                            <?php foreach ($relatedJobs as $relatedJob): ?>
                                <a href="job_details.php?id=<?php echo $relatedJob['id']; ?>" class="related-job-card">
                                    <div class="rj-ico">
                                        <?php echo strtoupper(substr($relatedJob['company_name'] ?? 'J', 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div class="rj-title"><?php echo e($relatedJob['title']); ?></div>
                                        <div class="rj-company"><?php echo e($relatedJob['company_name']); ?></div>
                                        <div style="margin-top:5px; display:flex; gap:5px; flex-wrap:wrap;">
                                            <span class="badge badge-purple" style="font-size:10px;padding:3px 9px;"><?php echo e($relatedJob['contract_type']); ?></span>
                                            <span class="badge badge-default" style="font-size:10px;padding:3px 9px;"><?php echo e($relatedJob['experience_level']); ?></span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- SIDEBAR -->
            <aside class="sidebar">

                <!-- Apply Box -->
                <div class="s-card">
                    <div class="s-card-title"><i class="ti ti-send"></i> Apply for this Job</div>

                    <div class="apply-status-box">
                        <div class="apply-status-open">
                            <?php echo $canApply ? 'This job is currently open.' : 'This job is closed.'; ?>
                        </div>
                        <?php if ($daysLeft > 0): ?>
                            <div class="days-badge"><?php echo $daysLeft; ?> days left to apply</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isCandidate && $isApplied): ?>
                        <?php
                        $status = $applicationData['status'] ?? 'pending';
                        $statusConfig = [
                            'pending'  => ['icon' => 'ti-clock',        'label' => 'Pending Review',  'bg' => '#fff7ed', 'color' => '#92400e', 'border' => '#fed7aa'],
                            'accepted' => ['icon' => 'ti-circle-check', 'label' => 'Accepted',        'bg' => '#ecfdf5', 'color' => '#065f46', 'border' => '#a7f3d0'],
                            'rejected' => ['icon' => 'ti-circle-x',     'label' => 'Rejected',        'bg' => '#fff1f2', 'color' => '#9f1239', 'border' => '#fecdd3'],
                        ];
                        $cfg = $statusConfig[$status] ?? $statusConfig['pending'];
                        ?>
                        <div style="
        background:<?php echo $cfg['bg']; ?>;
        border: 1px solid <?php echo $cfg['border']; ?>;
        color: <?php echo $cfg['color']; ?>;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    ">
                            <i class="ti <?php echo $cfg['icon']; ?>" style="font-size:17px;"></i>
                            Application Status: <?php echo $cfg['label']; ?>
                        </div>
                        <div class="already-applied-note">
                            Applied<?php if (!empty($applicationData['created_at'])): ?> on <?php echo date('d M Y', strtotime($applicationData['created_at'])); ?><?php endif; ?>
                        </div>
                    <?php elseif ($isLoggedIn && $isCandidate && $canApply): ?>
                        <button id="applyBtn" data-job-id="<?php echo $jobId; ?>" class="btn-apply">
                            <i class="ti ti-send"></i> Apply Now
                        </button>
                    <?php elseif (!$isLoggedIn && $canApply): ?>
                        <a href="login.php" class="btn-apply">
                            <i class="ti ti-login"></i> Login to Apply
                        </a>
                    <?php elseif (!$canApply): ?>
                        <button class="btn-applied" disabled>
                            <i class="ti ti-lock"></i> Application Closed
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Job Statistics -->
                <div class="s-card">
                    <div class="s-card-title"><i class="ti ti-chart-bar"></i> Job Statistics</div>
                    <div class="stat-row">
                        <span class="sr-label"><i class="ti ti-eye"></i> Views</span>
                        <span class="sr-val"><?php echo number_format($job['views_count'] ?? 0); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="sr-label"><i class="ti ti-users"></i> Applicants</span>
                        <span class="sr-val"><?php echo $job['applications_count'] ?? 0; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="sr-label"><i class="ti ti-calendar-plus"></i> Posted</span>
                        <span class="sr-val"><?php echo date('d M Y', strtotime($job['created_at'])); ?></span>
                    </div>
                    <?php if (!empty($job['expires_at'])): ?>
                        <div class="stat-row">
                            <span class="sr-label"><i class="ti ti-calendar-x"></i> Expires</span>
                            <span class="sr-val"><?php echo date('d M Y', strtotime($job['expires_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="stat-row">
                        <span class="sr-label"><i class="ti ti-point-filled"></i> Status</span>
                        <span class="status-open-badge"><?php echo ucfirst($job['status']); ?></span>
                    </div>
                </div>

                <!-- About Company -->
                <div class="s-card">
                    <div class="s-card-title"><i class="ti ti-building"></i> About Company</div>
                    <div class="company-name-s"><?php echo e($company['company_name'] ?? 'N/A'); ?></div>
                    <div class="cd-row">
                        <span class="cd-label">Industry</span>
                        <span class="cd-val"><?php echo e($company['industry'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="divider-line"></div>
                    <div class="contact-row">
                        <i class="ti ti-phone"></i>
                        <a href="tel:<?php echo e($company['phone'] ?? ''); ?>">
                            <?php echo e($company['phone'] ?? 'N/A'); ?>
                        </a>
                    </div>
                    <div class="contact-row">
                        <i class="ti ti-world"></i>
                        <a href="<?php echo e($company['website'] ?? '#'); ?>" target="_blank">
                            <?php echo e($company['website'] ?? 'N/A'); ?>
                        </a>
                    </div>
                    <a href="company.php?id=<?php echo $job['user_id']; ?>" class="btn-outline">
                        View Company Profile <i class="ti ti-external-link"></i>
                    </a>
                </div>

                <!-- Location Map -->
                <div class="s-card">
                    <div class="s-card-title"><i class="ti ti-map-2"></i> Company Location</div>
                    <div class="map-container">
                        <iframe
                            src="https://maps.google.com/maps?q=<?php echo urlencode($job['city']); ?>,Algeria&output=embed"
                            loading="lazy">
                        </iframe>
                    </div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($job['city']); ?>,Algeria"
                        target="_blank" class="btn-outline" style="margin-top:0;">
                        Open in Maps <i class="ti ti-external-link"></i>
                    </a>
                </div>

            </aside>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Save Job (both buttons sync)
        function syncSaveBtns(saved) {
            ['saveJobBtn', 'saveJobBtn2'].forEach(id => {
                const btn = document.getElementById(id);
                if (!btn) return;
                btn.classList.toggle('saved', saved);
                btn.childNodes[btn.childNodes.length - 1].textContent = saved ? ' Saved' : ' Save Job';
            });
            ['saveIcon', 'saveIcon2'].forEach(id => {
                const icon = document.getElementById(id);
                if (!icon) return;
                icon.className = saved ? 'ti ti-bookmark-filled' : 'ti ti-bookmark';
            });
        }
        ['saveJobBtn', 'saveJobBtn2'].forEach(id => {
            const btn = document.getElementById(id);
            if (!btn) return;
            btn.addEventListener('click', async function() {
                const response = await fetch('save_job.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `job_id=<?php echo $jobId; ?>`
                });
                const data = await response.json();
                if (data.success) syncSaveBtns(data.saved);
            });
        });

        // Apply Job (sidebar + hero)
        async function handleApply() {
            const response = await fetch('apply_job.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `job_id=<?php echo $jobId; ?>`
            });
            const data = await response.json();
            if (data.success) {
                const applyBtn = document.getElementById('applyBtn');
                if (applyBtn) {
                    const today = new Date().toLocaleDateString('en-GB', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                    applyBtn.outerHTML = `
                <div style="background:#fff7ed;border:1px solid #fed7aa;color:#92400e;border-radius:12px;padding:12px 14px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <i class="ti ti-clock" style="font-size:17px;"></i>
                    Application Status: Pending Review
                </div>
                <div class="already-applied-note">Applied on ${today}</div>
            `;
                }

                const heroBtn = document.getElementById('applyBtnHero');
                if (heroBtn) {
                    heroBtn.outerHTML = `<button class="hero-btn hero-btn-applied" disabled><i class="ti ti-circle-check"></i> Applied</button>`;
                }
            }
        }

        const applyBtn = document.getElementById('applyBtn');
        if (applyBtn) applyBtn.addEventListener('click', handleApply);

        const applyBtnHero = document.getElementById('applyBtnHero');
        if (applyBtnHero) applyBtnHero.addEventListener('click', handleApply);

        // Show more toggle
        document.querySelectorAll('.show-more-btn').forEach(button => {
            button.addEventListener('click', function() {
                const target = this.dataset.target;
                const items = document.querySelectorAll('.' + target);
                const isOpen = this.dataset.open === 'true';
                items.forEach((item, index) => {
                    if (index >= 2) {
                        item.classList.toggle('hidden-item', isOpen);
                    }
                });
                this.innerHTML = isOpen ?
                    'Show More <i class="ti ti-chevron-down"></i>' :
                    'Show Less <i class="ti ti-chevron-up"></i>';
                this.dataset.open = isOpen ? 'false' : 'true';
            });
        });
    </script>

    <script src="js/script.js"></script>
</body>

</html>