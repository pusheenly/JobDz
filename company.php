<?php
require_once 'config.php';
require_once 'functions.php';

ensureCompanyReviewsTable();

$companyId = intval($_GET['id'] ?? 0);
$company = $companyId ? getCompanyProfile($companyId) : [];

// Get company rating stats
$ratingQuery = $pdo->prepare("
    SELECT 
        AVG(rating) as avg_rating,
        COUNT(*) as total_reviews
    FROM company_reviews
    WHERE company_user_id = ?
");

$ratingQuery->execute([$companyId]);
$ratingData = $ratingQuery->fetch();

$company['rating'] = $ratingData['avg_rating'] ?? 0;
$company['reviews_count'] = $ratingData['total_reviews'] ?? 0;
$jobs = $companyId ? getCompanyJobs($companyId) : [];

$pageTitle = ($company['company_name'] ?? 'Company') . ' | JobDZ';
$companyDescription = $company['description'] ?? $company['company_description'] ?? '';

// Submit Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'candidate') {
        $candidateId = $_SESSION['user']['id'];
        $rating = intval($_POST['rating']);
        $review = trim($_POST['review'] ?? '');

        $check = $pdo->prepare("
            SELECT id FROM company_reviews
            WHERE company_user_id = ?
            AND candidate_user_id = ?
        ");
        $check->execute([$companyId, $candidateId]);

        if (!$check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO company_reviews
                (company_user_id, candidate_user_id, rating, review)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$companyId, $candidateId, $rating, $review]);
        }
    }

    header("Location: company.php?id=" . $companyId);
    exit;
}
$companyId = (int)$_GET['id'];

$stmt = $pdo->prepare("
    UPDATE companies_profiles
    SET profile_views = profile_views + 1
    WHERE user_id = ?
");
$stmt->execute([$companyId]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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

        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 16px 60px;
        }

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

        .company-logo {
            width: 80px;
            height: 80px;
            border-radius: 18px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
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
            font-size: 26px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 14px;
            font-weight: 500;
            flex-wrap: wrap;
        }

        .hero-meta i {
            font-size: 14px;
            color: #94a3b8;
        }

        .badges-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 13px;
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

        .hero-btn-primary {
            background: #6366f1;
            color: white;
        }

        .hero-btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }

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

        .text-body {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.8;
            font-weight: 400;
        }

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

        .check-ico i {
            color: #6366f1;
            font-size: 13px;
        }

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

        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .rating-number {
            background: #ede9fe;
            color: #5b21b6;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 22px;
            font-weight: 700;
        }

        .rating-stars {
            display: flex;
            gap: 3px;
            margin-bottom: 6px;
        }

        .rating-stars i {
            font-size: 16px;
        }

        .rating-count {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
        }

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

        .tabs-bar {
            background: white;
            border-bottom: 1px solid #e8eef6;
            padding: 0 22px;
            margin-bottom: 16px;
            border-radius: 20px 20px 0 0;
            display: flex;
            gap: 0;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            position: relative;
            transition: color 0.2s;
            font-family: 'Poppins', sans-serif;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }

        .tab-btn.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .tab-btn:first-child {
            padding-left: 0;
        }

        .job-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            border: 1px solid #e8eef6;
            border-radius: 14px;
            text-decoration: none;
            transition: .2s;
            background: #fafafe;
            margin-bottom: 10px;
        }

        .job-card:last-child {
            margin-bottom: 0;
        }

        .job-card:hover {
            border-color: #c7d2fe;
            background: #f5f3ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, .08);
        }

        .job-card-info {
            flex: 1;
        }

        .job-card-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
        }

        .job-card-meta {
            font-size: 12px;
            color: #94a3b8;
        }

        .job-card-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .time-ago {
            font-size: 11px;
            color: #94a3b8;
        }

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

        .review-box {
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 14px;
            margin-top: 12px;
        }

        .star-group {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .star-group input {
            display: none;
        }

        .star-group label {
            cursor: pointer;
            font-size: 24px;
            transition: .2s;
        }

        .submit-rating-btn {
            width: 100%;
            padding: 10px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: .2s;
        }

        .submit-rating-btn:hover {
            background: #4f46e5;
        }

        .empty-state {
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
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
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="page-wrapper">

        <!-- BREADCRUMB -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <i class="ti ti-chevron-right"></i>
            <a href="companies.php">Companies</a>
            <i class="ti ti-chevron-right"></i>
            <span style="color:#6366f1;"><?php echo e($company['company_name'] ?? 'Company'); ?></span>
        </div>

        <?php if (!$companyId || empty($company)): ?>

            <div class="card" style="text-align:center; padding: 60px 30px;">
                <i class="ti ti-building" style="font-size:48px; color:#e8eef6; margin-bottom:16px; display:block;"></i>
                <h2 style="font-size:20px; font-weight:700; color:#111827; margin-bottom:8px;">Company not found</h2>
                <p style="color:#64748b; margin-bottom:24px; font-size:13px;">This company profile is unavailable.</p>
                <a href="companies.php" style="display:inline-flex; align-items:center; gap:8px; background:#6366f1; color:white; padding:12px 24px; border-radius:10px; text-decoration:none; font-weight:600; font-size:13px;">
                    <i class="ti ti-arrow-left"></i> Back to Companies
                </a>
            </div>

        <?php else: ?>

            <!-- ── HERO CARD ── -->
            <div class="hero-card">
                <div class="hero-top">

                    <div class="hero-left">
                        <div class="company-logo">
                            <?php if (!empty($company['logo_url'])): ?>
                                <img src="<?php echo e($company['logo_url']); ?>" alt="<?php echo e($company['company_name']); ?>">
                            <?php else: ?>
                                <?php echo strtoupper(substr($company['company_name'] ?? 'C', 0, 2)); ?>
                            <?php endif; ?>
                        </div>

                        <div class="hero-info">
                            <h1><?php echo e($company['company_name']); ?></h1>
                            <div class="hero-meta">
                                <?php if (!empty($company['industry'])): ?>
                                    <i class="ti ti-briefcase"></i>
                                    <span><?php echo e($company['industry']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($company['city'])): ?>
                                    <i class="ti ti-map-pin"></i>
                                    <span><?php echo e($company['city']); ?>, Algeria</span>
                                <?php endif; ?>
                            </div>
                            <div class="badges-row">
                                <?php if (!empty($company['employees_count'])): ?>
                                    <span class="badge badge-purple">
                                        <i class="ti ti-users"></i> <?php echo e($company['employees_count']); ?> Employees
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($company['founded_year'])): ?>
                                    <span class="badge badge-default">
                                        <i class="ti ti-calendar"></i> Founded <?php echo e($company['founded_year']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-green">
                                    <i class="ti ti-briefcase"></i> <?php echo count($jobs); ?> Open Jobs
                                </span>
                            </div>
                        </div>
                    </div>



                </div>
            </div>

            <!-- ── TABS BAR ── -->
            <div class="tabs-bar">
                <button class="tab-btn active" data-tab="about">About Company</button>
                <button class="tab-btn" data-tab="jobs">Open Positions (<?php echo count($jobs); ?>)</button>
            </div>

            <!-- ── MAIN GRID ── -->
            <div class="main-grid">

                <!-- LEFT COLUMN -->
                <div>

                    <!-- ABOUT TAB -->
                    <div id="aboutContent">

                        <!-- Description -->
                        <div class="card">
                            <div class="ch">
                                <span class="ct"><i class="ti ti-building"></i> About Company</span>
                            </div>
                            <div class="text-body">
                                <?php echo e($companyDescription ?: 'No description available.'); ?>
                            </div>
                        </div>

                        <?php if (!empty($company['mission'])): ?>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct"><i class="ti ti-target"></i> Our Mission</span>
                                </div>
                                <div class="text-body"><?php echo e($company['mission']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['vision'])): ?>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct"><i class="ti ti-eye"></i> Our Vision</span>
                                </div>
                                <div class="text-body"><?php echo e($company['vision']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['specialties'])): ?>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct"><i class="ti ti-star"></i> Specialties</span>
                                </div>
                                <div class="skills-wrap">
                                    <?php foreach (explode(',', $company['specialties']) as $item): ?>
                                        <span class="skill-pill"><?php echo e(trim($item)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['benefits'])): ?>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct"><i class="ti ti-gift"></i> Employee Benefits</span>
                                </div>
                                <?php foreach (explode(',', $company['benefits']) as $benefit): ?>
                                    <div class="list-item">

                                        <span><?php echo e(trim($benefit)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Company Rating -->
                        <div class="card">
                            <div class="rating-header">
                                <div>
                                    <div class="ct" style="margin-bottom:8px;">
                                        <i class="ti ti-star"></i> Company Rating
                                    </div>
                                    <div class="rating-display">
                                        <div class="rating-number">
                                            <?php echo number_format($company['rating'] ?? 0, 1); ?>
                                        </div>
                                        <div>
                                            <div class="rating-stars">
                                                <?php
                                                $rating = round($company['rating'] ?? 0);
                                                for ($i = 1; $i <= 5; $i++):
                                                ?>
                                                    <i class="ti ti-star-filled" style="color: <?php echo $i <= $rating ? '#fbbf24' : '#e2e8f0'; ?>;"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="rating-count">
                                                <?php echo $company['reviews_count'] ?? 0; ?> reviews
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button id="toggleReviewBox" class="hero-btn hero-btn-primary" style="height: auto; padding: 8px 14px; font-size: 12px;">
                                    <i class="ti ti-star"></i> Rate
                                </button>
                            </div>

                            <!-- Hidden Review Box -->
                            <div id="reviewBox" class="review-box hidden">
                                <form method="POST">
                                    <div style="margin-bottom:12px;">
                                        <label style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; display: block;">Your Rating</label>
                                        <div class="star-group" id="starGroup">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio"
                                                    name="rating"
                                                    id="star-<?php echo $i; ?>"
                                                    value="<?php echo $i; ?>"
                                                    required>

                                                <label for="star-<?php echo $i; ?>"
                                                    class="ti ti-star"
                                                    style="color:#e2e8f0;font-size:24px;"></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <button type="submit" class="submit-rating-btn">Submit Rating</button>
                                </form>
                            </div>

                        </div>

                    </div>

                    <!-- JOBS TAB -->
                    <div id="jobsContent" class="hidden">
                        <div class="card">
                            <div class="ch">
                                <span class="ct"><i class="ti ti-briefcase"></i> Open Positions</span>
                            </div>

                            <?php if (empty($jobs)): ?>
                                <div class="empty-state">
                                    <i class="ti ti-briefcase" style="font-size:32px; margin-bottom:10px; display:block; opacity:0.5;"></i>
                                    No active job openings at the moment.
                                </div>
                            <?php else: ?>
                                <?php foreach ($jobs as $job): ?>
                                    <a href="job_details.php?id=<?php echo e($job['id']); ?>" class="job-card">
                                        <div class="job-card-info">
                                            <div class="job-card-title"><?php echo e($job['title']); ?></div>
                                            <div class="job-card-meta">
                                                <i class="ti ti-map-pin"></i>
                                                <?php echo e($job['city']); ?>, Algeria
                                            </div>
                                        </div>
                                        <div class="job-card-right">
                                            <?php if (!empty($job['contract_type'])): ?>
                                                <span class="badge badge-purple"><?php echo e($job['contract_type']); ?></span>
                                            <?php endif; ?>
                                            <span class="time-ago"><?php echo e(timeAgo($job['created_at'])); ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- SIDEBAR -->
                <aside class="sidebar">

                    <!-- Company Info -->
                    <div class="s-card">
                        <div class="s-card-title"><i class="ti ti-info-circle"></i> Company Information</div>

                        <div class="stat-row">
                            <span class="sr-label"><i class="ti ti-map-pin"></i> Location</span>
                            <span class="sr-val"><?php echo e($company['city'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="sr-label"><i class="ti ti-briefcase"></i> Industry</span>
                            <span class="sr-val"><?php echo e($company['industry'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="sr-label"><i class="ti ti-users"></i> Employees</span>
                            <span class="sr-val"><?php echo e($company['employees_count'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="sr-label"><i class="ti ti-calendar"></i> Founded</span>
                            <span class="sr-val"><?php echo e($company['founded_year'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($company['working_hours'])): ?>
                            <div class="stat-row">
                                <span class="sr-label"><i class="ti ti-clock"></i> Hours</span>
                                <span class="sr-val"><?php echo e($company['working_hours']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contact -->
                    <div class="s-card">
                        <div class="s-card-title"><i class="ti ti-phone"></i> Contact</div>

                        <?php if (!empty($company['phone'])): ?>
                            <div class="contact-row">
                                <i class="ti ti-phone"></i>
                                <a href="tel:<?php echo e($company['phone']); ?>">
                                    <?php echo e($company['phone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['email'])): ?>
                            <div class="contact-row">
                                <i class="ti ti-mail"></i>
                                <a href="mailto:<?php echo e($company['email']); ?>">
                                    <?php echo e($company['email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['website'])): ?>
                            <div class="contact-row">
                                <i class="ti ti-world"></i>
                                <a href="<?php echo e($company['website']); ?>" target="_blank">
                                    Visit Site
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Social -->
                        <?php if (!empty($company['linkedin']) || !empty($company['facebook']) || !empty($company['twitter']) || !empty($company['instagram'])): ?>
                            <div class="divider-line"></div>
                            <div style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Social Links</div>
                            <div style="display: flex; gap: 8px;">
                                <?php if (!empty($company['linkedin'])): ?>
                                    <a href="<?php echo e($company['linkedin']); ?>" target="_blank" style="color: #0a66c2; font-size: 16px; text-decoration: none;">
                                        <i class="ti ti-brand-linkedin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($company['facebook'])): ?>
                                    <a href="<?php echo e($company['facebook']); ?>" target="_blank" style="color: #1877f2; font-size: 16px; text-decoration: none;">
                                        <i class="ti ti-brand-facebook"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($company['twitter'])): ?>
                                    <a href="<?php echo e($company['twitter']); ?>" target="_blank" style="color: #1d9bf0; font-size: 16px; text-decoration: none;">
                                        <i class="ti ti-brand-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($company['instagram'])): ?>
                                    <a href="<?php echo e($company['instagram']); ?>" target="_blank" style="color: #e1306c; font-size: 16px; text-decoration: none;">
                                        <i class="ti ti-brand-instagram"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Location Map -->
                    <div class="s-card">
                        <div class="s-card-title"><i class="ti ti-map-2"></i> Company Location</div>
                        <div class="map-container">
                            <iframe
                                src="https://maps.google.com/maps?q=<?php echo urlencode($company['city'] ?? 'Algiers'); ?>,Algeria&output=embed"
                                loading="lazy">
                            </iframe>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode(($company['city'] ?? '') . ',Algeria'); ?>"
                            target="_blank" class="btn-outline" style="margin-top: 0;">
                            Open in Maps <i class="ti ti-external-link"></i>
                        </a>
                    </div>

                </aside>

            </div>

        <?php endif; ?>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Toggle review box
        document.getElementById('toggleReviewBox')?.addEventListener('click', function() {
            document.getElementById('reviewBox').classList.toggle('hidden');
        });

        // Star rating
        const stars = document.querySelectorAll('.star-group input');

        stars.forEach(star => {

            star.addEventListener('change', function() {

                const value = parseInt(this.value);

                document.querySelectorAll('.star-group label').forEach(label => {

                    const labelValue = parseInt(
                        label.getAttribute('for').replace('star-', '')
                    );

                    if (labelValue <= value) {

                        label.classList.remove('ti-star');
                        label.classList.add('ti-star-filled');
                        label.style.color = '#fbbf24';

                    } else {

                        label.classList.remove('ti-star-filled');
                        label.classList.add('ti-star');
                        label.style.color = '#e2e8f0';
                    }
                });
            });
        });

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function() {

                const tabName = this.dataset.tab;

                document
                    .querySelectorAll('[id$="Content"]')
                    .forEach(el => el.classList.add('hidden'));

                document
                    .getElementById(tabName + 'Content')
                    .classList.remove('hidden');

                document
                    .querySelectorAll('.tab-btn')
                    .forEach(t => t.classList.remove('active'));

                this.classList.add('active');
            });
        });
    </script>

    <script src="js/script.js"></script>
</body>

</html>