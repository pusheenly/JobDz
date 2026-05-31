<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

if ($_SESSION['user']['role'] !== 'company') {
    header('Location: index.php');
    exit;
}

if (!isCompanyProfileComplete($_SESSION['user']['id'])) {
    header('Location: edit_company_profile.php');
    exit;
}

$profile = getCompanyProfile($_SESSION['user']['id']);
$activeProfileTab = 'profile';

$initials = strtoupper(substr($profile['company_name'] ?? 'C', 0, 1));

$stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
    FROM company_reviews WHERE company_user_id = ?
");
$stmt->execute([$_SESSION['user']['id']]);
$ratingData   = $stmt->fetch();
$avgRating    = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile | JobDZ</title>
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

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .tb-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .tb-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #ede9fe;
            color: #3C3489;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 600;
            border: 1px solid #e8eef6;
            flex-shrink: 0;
            overflow: hidden;
            text-decoration: none;
        }

        .tb-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 13px;
        }

        .tb-name {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .tb-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .online-dot {
            width: 7px;
            height: 7px;
            background: #1D9E75;
            border-radius: 50%;
        }

        .tb-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            border: 1px solid #e8eef6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            font-size: 17px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
            text-decoration: none;
        }

        .profile-banner {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 1.4rem 1.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            position: relative;
            overflow: hidden;
        }

        .profile-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(99, 102, 241, .10) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .banner-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .prof-avatar-lg {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
            overflow: hidden;
        }

        .prof-avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 17px;
        }

        .banner-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
        }

        .banner-sub {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .banner-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .banner-meta span {
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .banner-meta i {
            font-size: 13px;
            color: #94a3b8;
        }

        .banner-right {
            display: flex;
            gap: 8px;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
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
        }

        /* ── STATS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 1.25rem;
        }

        .sc {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: .9rem 1rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .sc-label {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sc-label i {
            font-size: 13px;
        }

        .sc-val {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        .sc-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 99px;
            margin-top: 6px;
            display: inline-block;
        }

        .b-neu {
            background: #EEEDFE;
            color: #3C3489;
        }

        .b-up {
            background: #E1F5EE;
            color: #085041;
        }

        .b-warn {
            background: #FAEEDA;
            color: #633806;
        }

        .b-pink {
            background: #FBEAF0;
            color: #72243E;
        }

        .card {
            background: white;
            border: 1px solid #ececf3;
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

        .cl {
            font-size: 11px;
            color: #6366f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
            cursor: pointer;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 0;
        }

        @media(max-width:860px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        .summary-block {
            background: #f5f3ff;
            border-left: 3px solid #6366f1;
            border-radius: 0 12px 12px 0;
            padding: 12px 16px;
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.7;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 4px 0;
        }

        .pill {
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 500;
            background: #ede9fe;
            color: #3C3489;
            border: none;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12.5px;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-row i {
            font-size: 16px;
            color: #94a3b8;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .info-row-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            width: 100px;
            flex-shrink: 0;
        }

        .info-row-val {
            color: #111827;
            font-weight: 500;
            flex: 1;
        }

        .info-link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }

        .info-link:hover {
            text-decoration: underline;
        }

        .social-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            border-radius: 12px;
            transition: background .15s;
        }

        .social-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .social-row:hover {
            background: #f8fafc;
        }

        .social-ico {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .social-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
        }

        .social-url {
            font-size: 11px;
            color: #94a3b8;
        }

        .stars {
            display: flex;
            gap: 3px;
        }

        .star-filled {
            color: #fbbf24;
            font-size: 14px;
        }

        .star-empty {
            color: #d1d5db;
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 1.5rem 1rem;
            background: #fafafe;
            border: 1.5px dashed #e0e7ff;
            border-radius: 16px;
            font-size: 12.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        .empty-state i {
            font-size: 24px;
            color: #c7d2fe;
            margin-bottom: 8px;
            display: block;
        }

        @media(max-width:640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .banner-right {
                width: 100%;
            }

            .btn-pri,
            .btn-sec {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">

            <?php
            $activeProfileTab = 'profile';
            include 'includes/profile-sidebar.php';
            ?>

            <div style="min-width:0;">

                <!-- TOPBAR -->
                <div class="topbar">
                    <div class="tb-left">
                        <a href="edit_company_profile.php" class="tb-avatar">
                            <?php if (!empty($profile['logo_url'])): ?>
                                <img src="<?php echo e($profile['logo_url']); ?>" alt="logo">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </a>
                        <div>
                            <div class="tb-name"><?php echo e($profile['company_name'] ?? 'Company'); ?></div>
                            <div class="tb-sub">
                                <span class="online-dot"></span>
                                Company Profile
                                <?php if (!empty($profile['city'])): ?>
                                    · <?php echo e($profile['city']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="tb-right">
                        <a href="company_notifications.php" class="icon-btn"><i class="ti ti-bell"></i></a>
                        <a href="settings.php" class="icon-btn"><i class="ti ti-settings"></i></a>
                    </div>
                </div>

                <!-- BANNER -->
                <div class="profile-banner">
                    <div class="banner-left">
                        <div class="prof-avatar-lg">
                            <?php if (!empty($profile['logo_url'])): ?>
                                <img src="<?php echo e($profile['logo_url']); ?>" alt="Company Logo">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="banner-title">
                                <?php echo e($profile['company_name'] ?? 'Company'); ?>
                            </div>
                            <div class="banner-sub">
                                <?php echo e($profile['industry'] ?? 'Industry not set'); ?>
                                <?php if (!empty($profile['city'])): ?>
                                    · <i class="ti ti-map-pin" style="font-size:11px"></i> <?php echo e($profile['city']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="banner-meta">
                                <span>
                                    <i class="ti ti-mail"></i>
                                    <a href="mailto:<?php echo e($_SESSION['user']['email']); ?>" class="info-link">
                                        <?php echo e($_SESSION['user']['email']); ?>
                                    </a>
                                </span>
                                <?php if (!empty($profile['phone'])): ?>
                                    <span><i class="ti ti-phone"></i> <?php echo e($profile['phone']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($profile['website'])): ?>
                                    <span>
                                        <i class="ti ti-world"></i>
                                        <a href="<?php echo e($profile['website']); ?>" target="_blank" class="info-link">
                                            <?php echo e(str_replace(['https://', 'http://'], '', $profile['website'])); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="banner-right">
                        <a href="edit_company_profile.php" class="btn-pri">
                            <i class="ti ti-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>

                <!-- STATS -->
                <div class="card">

                    <div class="ch">
                        <span class="ct">
                            <i class="ti ti-building"></i> Basic Information
                        </span>


                        <a href="edit_company_profile.php?section=basic" class="cl">
                            <i class="ti ti-edit"></i>Edit
                        </a>

                    </div>

                    <div class="stats-grid">

                        <div class="sc">
                            <div class="sc-label"><i class="ti ti-users"></i> Company Size</div>
                            <div class="sc-val"><?php echo e($profile['size'] ?? 'Not set'); ?></div>
                            <div class="sc-badge b-neu">Employees</div>
                        </div>

                        <div class="sc">
                            <div class="sc-label"><i class="ti ti-star"></i> Rating</div>

                            <div class="sc-val">
                                <?php if ($totalReviews > 0): ?>

                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'star-filled' : 'star-empty'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>

                                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">
                                        <?php echo $totalReviews; ?> reviews
                                    </div>

                                <?php else: ?>

                                    <span style="color:#94a3b8;font-size:12px;">
                                        No reviews yet
                                    </span>

                                <?php endif; ?>
                            </div>

                            <div class="sc-badge b-up">Score</div>
                        </div>

                        <div class="sc">
                            <div class="sc-label"><i class="ti ti-calendar"></i> Founded</div>
                            <div class="sc-val"><?php echo e($profile['founded_year'] ?? 'Not set'); ?></div>
                            <div class="sc-badge b-warn">Year</div>
                        </div>

                        <div class="sc">
                            <div class="sc-label"><i class="ti ti-chart-bar"></i> Employees</div>
                            <div class="sc-val"><?php echo e($profile['employees_count'] ?? 'Not set'); ?></div>
                            <div class="sc-badge b-pink">Headcount</div>
                        </div>

                    </div>

                </div>

                <!-- ABOUT -->
                <div class="card">
                    <div class="ch">
                        <span class="ct"><i class="ti ti-file-text"></i> About the Company</span>
                        <a href="edit_company_profile.php?section=description" class="cl">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                    </div>
                    <?php if (!empty($profile['description'])): ?>
                        <div class="summary-block"><?php echo e($profile['description']); ?></div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-file-text"></i>
                            No company description added yet
                        </div>
                    <?php endif; ?>
                </div>

                <!-- MISSION + VISION -->
                <div class="two-col">
                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-target"></i> Our Mission</span>
                            <a href="edit_company_profile.php?section=mission" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>
                        <?php if (!empty($profile['mission'])): ?>
                            <div class="summary-block"><?php echo e($profile['mission']); ?></div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ti ti-target"></i>
                                No mission added yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-eye"></i> Our Vision</span>
                            <a href="edit_company_profile.php?section=vision" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>
                        <?php if (!empty($profile['vision'])): ?>
                            <div class="summary-block"><?php echo e($profile['vision']); ?></div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ti ti-eye"></i>
                                No vision added yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SPECIALTIES + BENEFITS -->
                <div class="two-col">
                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-bulb"></i> Specialties</span>
                            <a href="edit_company_profile.php?section=specialties" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>
                        <?php if (!empty($profile['specialties'])): ?>
                            <div class="pills">
                                <?php foreach (explode(',', $profile['specialties']) as $s): ?>
                                    <span class="pill"><?php echo e(trim($s)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ti ti-bulb"></i>
                                No specialties added yet
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-gift"></i> Benefits &amp; Perks</span>
                            <a href="edit_company_profile.php?section=benefits" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>
                        <?php if (!empty($profile['benefits'])): ?>
                            <div class="summary-block"><?php echo nl2br(e($profile['benefits'])); ?></div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="ti ti-gift"></i>
                                No benefits added yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CONTACT + SOCIAL -->
                <div class="two-col">

                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-phone"></i> Contact Information</span>
                            <a href="edit_company_profile.php?section=contact" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>

                        <div class="info-row">
                            <i class="ti ti-mail"></i>
                            <span class="info-row-label">Email</span>
                            <span class="info-row-val">
                                <a href="mailto:<?php echo e($_SESSION['user']['email']); ?>" class="info-link">
                                    <?php echo e($_SESSION['user']['email']); ?>
                                </a>
                            </span>
                        </div>
                        <div class="info-row">
                            <i class="ti ti-phone"></i>
                            <span class="info-row-label">Phone</span>
                            <span class="info-row-val"><?php echo e($profile['phone'] ?? '—'); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="ti ti-clock"></i>
                            <span class="info-row-label">Hours</span>
                            <span class="info-row-val"><?php echo e($profile['working_hours'] ?? '—'); ?></span>
                        </div>
                        <div class="info-row">
                            <i class="ti ti-world"></i>
                            <span class="info-row-label">Website</span>
                            <span class="info-row-val">
                                <?php if (!empty($profile['website'])): ?>
                                    <a href="<?php echo e($profile['website']); ?>" target="_blank" class="info-link">
                                        <?php echo e(str_replace(['https://', 'http://'], '', $profile['website'])); ?>
                                    </a>
                                    <?php else: ?>—<?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <i class="ti ti-map-pin"></i>
                            <span class="info-row-label">Address</span>
                            <span class="info-row-val"><?php echo e($profile['address'] ?? '—'); ?></span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="ch">
                            <span class="ct"><i class="ti ti-share"></i> Social Media</span>
                            <a href="edit_company_profile.php?section=social" class="cl">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        </div>

                        <?php
                        $socials = [
                            'linkedin'  => ['ti ti-brand-linkedin',  'LinkedIn'],
                            'facebook'  => ['ti ti-brand-facebook',  'Facebook'],
                            'twitter'   => ['ti ti-brand-twitter',   'Twitter / X'],
                            'instagram' => ['ti ti-brand-instagram', 'Instagram'],
                            'github'    => ['ti ti-brand-github',    'GitHub'],
                        ];
                        $hasSocial = false;
                        foreach ($socials as $key => [$icon, $label]):
                            if (empty($profile[$key])) continue;
                            $hasSocial = true;
                        ?>
                            <a href="<?php echo e($profile[$key]); ?>" target="_blank" class="social-row">
                                <div class="social-ico"><i class="<?php echo $icon; ?>"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="social-name"><?php echo $label; ?></div>
                                    <div class="social-url"><?php echo e($profile[$key]); ?></div>
                                </div>
                                <i class="ti ti-external-link" style="font-size:13px;color:#94a3b8;"></i>
                            </a>
                        <?php endforeach; ?>

                        <?php if (!$hasSocial): ?>
                            <div class="empty-state">
                                <i class="ti ti-share"></i>
                                No social links added yet
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </div>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>

</body>

</html>