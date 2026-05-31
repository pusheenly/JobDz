<?php
require_once 'config.php';
require_once 'functions.php';

global $pdo;

$userId = $_SESSION['user']['id'];
$companyProfile = getCompanyProfile($userId);
$companyJobs    = getCompanyJobs($userId);
$companyProfileViews = $companyProfile['profile_views'] ?? 0;
$postedCount    = count($companyJobs);

$applicationsReceived = 0;
foreach ($companyJobs as $jobItem) {
    $applicationsReceived += count(getApplicantsByJob($jobItem['id']));
}

$recentApplicants = [];
foreach ($companyJobs as $jobItem) {
    foreach (getApplicantsByJob($jobItem['id']) as $applicant) {
        $applicant['job_title'] = $jobItem['title'];
        $recentApplicants[]     = $applicant;
    }
}
usort($recentApplicants, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$recentApplicants = array_slice($recentApplicants, 0, 4);

$todayApplicants = array_filter(
    $recentApplicants,
    fn($a) =>
    date('Y-m-d', strtotime($a['created_at'] ?? 'now')) === date('Y-m-d')
);

$initials = '';
$companyName = $companyProfile['company_name'] ?? 'Company';
$words = explode(' ', $companyName);
foreach ($words as $w) $initials .= strtoupper(substr($w, 0, 1));
$initials = substr($initials, 0, 2);

$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE receiver_id = ?
    ORDER BY created_at DESC
    LIMIT 4
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

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

    .db {
        padding: 0;
        background: transparent;
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

    .avatar-lg {
        width: 48px;
        height: 48px;
        border-radius: 50%;
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
    }

    .avatar-lg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
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
        position: relative;
        font-size: 17px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
        text-decoration: none;
    }

    .ndot {
        width: 8px;
        height: 8px;
        background: #6366f1;
        border-radius: 50%;
        position: absolute;
        top: 7px;
        right: 7px;
    }

    /* ── BANNER ── */
    .company-banner {
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

    .company-banner::before {
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

    .company-logo-lg {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: #ede9fe;
        color: #5b21b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 700;
        flex-shrink: 0;
        border: 1px solid #e8eef6;
        overflow: hidden;
        text-decoration: none;
        transition: transform .2s;
        cursor: pointer;
    }

    .company-logo-lg:hover {
        transform: scale(1.05);
    }

    .company-logo-lg img {
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
    }

    .banner-sub {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .verified-badge {
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

    .post-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 14px;
        padding: 11px 22px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        transition: background .2s, transform .2s;
        position: relative;
        z-index: 1;
        white-space: nowrap;
    }

    .post-btn:hover {
        background: #4f46e5;
        transform: translateY(-2px);
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
        text-decoration: none;
        display: block;
        color: inherit;
        transition: border-color .2s, transform .2s, box-shadow .2s;
        cursor: pointer;
    }

    .sc:hover {
        border-color: #c4bffa;
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(99, 102, 241, .10);
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

    .sc-num {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }

    .sc-badge {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
        margin-top: 6px;
        display: inline-block;
    }

    .b-up {
        background: #E1F5EE;
        color: #085041;
    }

    .b-neu {
        background: #EEEDFE;
        color: #3C3489;
    }

    .b-warn {
        background: #FAEEDA;
        color: #633806;
    }

    /* ── MAIN GRID ── */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 14px;
        margin-bottom: 1.25rem;
    }

    /* ── CARDS ── */
    .card {
        background: white;
        border: 1px solid #ececf3;
        border-radius: 24px;
        padding: 1.1rem 1.25rem;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
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

    /* ── JOB ROWS ── */
    .job-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        border-radius: 12px;
        transition: background .15s;
    }

    .job-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .job-row:hover {
        background: #f8fafc;
    }

    .job-ico {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #ede9fe;
        color: #5b21b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .job-info {
        flex: 1;
        min-width: 0;
    }

    .job-title-text {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .job-meta-text {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .job-actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }

    .jbtn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 11px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        border: none;
        font-family: 'Poppins', sans-serif;
        transition: opacity .15s;
    }

    .jbtn-view {
        background: #6366f1;
        color: white;
    }

    .jbtn-edit {
        background: #f1f5f9;
        color: #334155;
    }

    .jbtn-del {
        background: #FBEAF0;
        color: #72243E;
    }

    .jbtn:hover {
        opacity: .88;
    }

    .app-count-badge {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
        background: #EEEDFE;
        color: #3C3489;
        white-space: nowrap;
    }

    /* ── APPLICANTS ── */
    .appl-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        border-radius: 12px;
        transition: background .15s;
    }

    .appl-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .appl-row:hover {
        background: #f8fafc;
    }

    .appl-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #ede9fe;
        color: #5b21b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .appl-info {
        flex: 1;
        min-width: 0;
    }

    .appl-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .appl-meta {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
        font-weight: 500;
    }

    .appl-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 11px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        background: #f1f5f9;
        color: #334155;
        flex-shrink: 0;
        transition: background .15s, color .15s;
    }

    .appl-btn:hover {
        background: #6366f1;
        color: white;
    }

    .timeline {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .tl-item {
        display: flex;
        gap: 12px;
        padding: 10px 10px 14px;
        position: relative;
        text-decoration: none;
        color: inherit;
        border-radius: 14px;
        transition: background .15s;
    }

    .tl-item:last-child {
        padding-bottom: 0;
    }

    .tl-item:hover {
        background: #f8fafc;
    }

    .tl-item.tl-unread {
        background: #f5f3ff;
        border-left: 3px solid #6366f1;
        border-radius: 0 14px 14px 0;
        margin-left: 2px;
    }

    .tl-item.tl-unread:hover {
        background: #ede9fe;
    }

    .tl-left {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 36px;
        flex-shrink: 0;
    }

    .tl-dot {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .tl-dot-read {
        background: #f1f5f9;
        color: #94a3b8;
    }

    .tl-dot-unread {
        background: #6366f1;
        color: white;
    }

    .tl-line {
        width: 2px;
        flex: 1;
        background: #f1f5f9;
        margin-top: 6px;
        border-radius: 99px;
    }

    .tl-item:last-child .tl-line {
        display: none;
    }

    .tl-body {
        flex: 1;
        padding-top: 2px;
        min-width: 0;
    }

    .tl-title-row {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 3px;
    }

    .tl-title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .tl-title-unread {
        color: #3730a3;
    }

    .tl-unread-dot {
        width: 7px;
        height: 7px;
        background: #6366f1;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .tl-sub {
        font-size: 11.5px;
        color: #64748b;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tl-sub-unread {
        color: #4338ca;
    }

    .tl-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 5px;
    }

    .tl-time {
        font-size: 10.5px;
        color: #94a3b8;
    }

    .tl-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 99px;
    }

    .tl-badge-unread {
        background: #ede9fe;
        color: #5b21b6;
    }

    .tl-badge-read {
        background: #f1f5f9;
        color: #94a3b8;
    }

    /* ── BOTTOM GRID ── */
    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 1.25rem;
    }

    /* ── QUICK ACTIONS ── */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .action-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 16px;
        border: 1px solid #e8eef6;
        background: #fafafe;
        text-decoration: none;
        transition: border-color .2s, background .2s, transform .2s;
    }

    .action-item:hover {
        border-color: #c7d2fe;
        background: #f5f3ff;
        transform: translateY(-2px);
    }

    .action-ico {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        background: #ede9fe;
        color: #5b21b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
    }

    .action-label {
        font-size: 12px;
        font-weight: 600;
        color: #111827;
    }

    .action-sub {
        font-size: 10px;
        color: #94a3b8;
        margin-top: 1px;
        font-weight: 500;
    }

    /* ── FREE BANNER ── */
    .free-banner {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
        border-radius: 20px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .free-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #6366f1;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        background: #fafafe;
        border: 1.5px dashed #e0e7ff;
        border-radius: 18px;
    }

    .empty-state i {
        font-size: 28px;
        color: #c7d2fe;
        margin-bottom: 10px;
        display: block;
    }

    .empty-state p {
        font-size: 13px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ── HIDDEN JOB ── */
    .hidden-job {
        display: none;
    }

    .see-more-link {
        font-size: 12px;
        font-weight: 600;
        color: #6366f1;
        text-decoration: none;
        transition: .2s;
    }

    .see-more-link:hover {
        opacity: .7;
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .bottom-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {

        main.mx-auto {
            padding-left: 12px !important;
            padding-right: 12px !important;
            padding-top: 16px !important;
            padding-bottom: 24px !important;
        }


        .tb-name {
            font-size: 14px;
        }

        .tb-sub {
            font-size: 11px;
        }

        .tb-name {
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Banner ── */
        .company-banner {
            padding: 1rem;
            border-radius: 18px;
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }

        .banner-left {
            gap: 12px;
        }

        .company-logo-lg {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 18px;
        }

        .banner-title {
            font-size: 15px;
        }

        .banner-sub {
            font-size: 11px;
            flex-wrap: wrap;
        }

        .post-btn {
            width: 100%;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 12px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .sc {
            border-radius: 16px;
            padding: .75rem .85rem;
        }

        .sc-num {
            font-size: 20px;
        }

        .sc-label {
            font-size: 10px;
        }

        .main-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .bottom-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .card {
            border-radius: 18px;
            padding: .9rem 1rem;
        }

        .ct {
            font-size: 14px;
        }

        .job-row {
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 6px;
        }

        .job-ico {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            font-size: 14px;
        }

        .job-title-text {
            font-size: 12.5px;
        }

        .job-meta-text {
            font-size: 10.5px;
        }

        .job-actions {
            width: 100%;
            padding-left: 44px;
        }

        .jbtn-view .btn-label {
            display: none;
        }

        .appl-avatar {
            width: 34px;
            height: 34px;
            font-size: 12px;
        }

        .appl-name {
            font-size: 12.5px;
        }

        .appl-meta {
            font-size: 10.5px;
        }

        .appl-btn {
            padding: 5px 10px;
            font-size: 11px;
        }

        .tl-dot {
            width: 30px;
            height: 30px;
            font-size: 13px;
        }

        .tl-left {
            width: 30px;
        }

        .tl-title {
            font-size: 12.5px;
        }

        .tl-sub {
            font-size: 11px;
        }

        .actions-grid {
            gap: 8px;
        }

        .action-item {
            padding: 10px 10px;
            border-radius: 14px;
            gap: 8px;
        }

        .action-ico {
            width: 32px;
            height: 32px;
            font-size: 14px;
            border-radius: 9px;
        }

        .action-label {
            font-size: 11.5px;
        }

        .action-sub {
            font-size: 9.5px;
        }

        .free-banner {
            border-radius: 16px;
            padding: .85rem 1rem;
        }

        .free-icon {
            width: 38px;
            height: 38px;
            font-size: 16px;
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 16px;
        }

        .avatar-lg {
            width: 40px;
            height: 40px;
        }
    }

    @media (max-width: 375px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .sc-num {
            font-size: 18px;
        }

        .banner-title {
            font-size: 14px;
        }
    }
</style>

<main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="db">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="tb-left">
                <a href="edit_company_profile.php" class="avatar-lg">
                    <?php if (!empty($companyProfile['logo'])): ?>
                        <img src="<?= htmlspecialchars($companyProfile['logo']) ?>" alt="">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </a>
                <div>
                    <div class="tb-name"><?= htmlspecialchars($companyName) ?></div>
                    <div class="tb-sub">
                        <span class="online-dot"></span>
                        Recruiting ·
                        <?= htmlspecialchars($companyProfile['city'] ?? 'Algeria') ?>
                    </div>
                </div>
            </div>
            <div class="tb-right">
                <a href="company_notifications.php" class="icon-btn">
                    <i class="ti ti-bell"></i>
                    <?php if (!empty($notifications)): ?>
                        <span class="ndot"></span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="icon-btn">
                    <i class="ti ti-settings"></i>
                </a>
            </div>
        </div>

        <!-- COMPANY BANNER -->
        <div class="company-banner">
            <div class="banner-left">
                <a href="edit_company_profile.php" class="company-logo-lg">
                    <?php if (!empty($companyProfile['logo'])): ?>
                        <img src="<?= htmlspecialchars($companyProfile['logo']) ?>" alt="">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </a>
                <div>
                    <div class="banner-title"><?= htmlspecialchars($companyName) ?></div>
                    <div class="banner-sub">
                        <?php if (!empty($companyProfile['industry'])): ?>
                            <?= htmlspecialchars($companyProfile['industry']) ?>
                        <?php endif; ?>
                        <?php if (!empty($companyProfile['city'])): ?>
                            · <i class="ti ti-map-pin" style="font-size:11px"></i> <?= htmlspecialchars($companyProfile['city']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="add_job.php" class="post-btn">
                <i class="ti ti-plus"></i> Post New Job
            </a>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <a href="job_positions.php" class="sc">
                <div class="sc-label"><i class="ti ti-briefcase"></i> Jobs Posted</div>
                <div class="sc-num"><?= $postedCount ?></div>
                <div class="sc-badge b-neu">Active</div>
            </a>
            <a href="recruiter_applications.php" class="sc">
                <div class="sc-label"><i class="ti ti-users"></i> Applications</div>
                <div class="sc-num"><?= $applicationsReceived ?></div>
                <div class="sc-badge b-up">Total</div>
            </a>
            <a href="recruiter_applications.php" class="sc">
                <div class="sc-label"><i class="ti ti-user-plus"></i> New Today</div>
                <div class="sc-num"><?= count($todayApplicants) ?></div>
                <div class="sc-badge b-warn">Today</div>
            </a>
            <a href="edit_company_profile.php" class="sc">
                <div class="sc-label"><i class="ti ti-eye"></i> Profile Views</div>
                <div class="sc-num"><?= $companyProfileViews ?></div>
                <div class="sc-badge b-up">Growing</div>
            </a>
        </div>

        <div class="main-grid">

            <!-- JOB LISTINGS -->
            <div class="card">
                <div class="ch">
                    <span class="ct">Active Job Listings</span>
                    <a href="add_job.php" class="cl">
                        <i class="ti ti-plus"></i> Add Job
                    </a>
                </div>

                <?php if (empty($companyJobs)): ?>
                    <div class="empty-state">
                        <i class="ti ti-briefcase"></i>
                        <p>No jobs posted yet.</p>
                        <a href="add_job.php"
                            style="display:inline-flex;align-items:center;gap:6px;margin-top:12px;padding:8px 18px;background:#6366f1;color:white;border-radius:12px;font-size:12px;font-weight:600;text-decoration:none;">
                            <i class="ti ti-plus"></i> Post First Job
                        </a>
                    </div>
                <?php else: ?>
                    <?php
                    usort($companyJobs, function ($a, $b) {
                        return strtotime($b['created_at'] ?? 'now') - strtotime($a['created_at'] ?? 'now');
                    });
                    ?>

                    <div id="jobsContainer">
                        <?php foreach ($companyJobs as $index => $job):
                            $appCount   = count(getApplicantsByJob($job['id']));
                            $hiddenClass = $index >= 5 ? 'hidden-job' : '';
                        ?>
                            <div class="job-row <?= $hiddenClass ?>" style="cursor:default;">
                                <a href="job_positions.php?job=<?= $job['id'] ?>"
                                    style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;text-decoration:none;color:inherit;">

                                    <div class="job-ico">
                                        <i class="ti ti-briefcase"></i>
                                    </div>

                                    <div class="job-info">
                                        <div class="job-title-text"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="job-meta-text">
                                            <i class="ti ti-map-pin"></i>
                                            <?= htmlspecialchars($job['city'] ?? '—') ?>
                                            ·
                                            <i class="ti ti-users"></i>
                                            <?= $appCount ?> applicants
                                            <?php if (!empty($job['created_at'])): ?>
                                                ·
                                                <span style="font-size:11px;color:#9ca3af;display:inline-flex;align-items:center;gap:3px;font-weight:500;">
                                                    <i class="ti ti-clock" style="font-size:11px"></i>
                                                    <?= timeAgo($job['created_at']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                </a>
                                <div class="job-actions">
                                    <a href="job_details.php?id=<?= $job['id'] ?>" class="jbtn jbtn-view">
                                        <i class="ti ti-eye"></i>
                                        <span class="btn-label">View</span>
                                    </a>
                                    <a href="edit_job.php?id=<?= $job['id'] ?>" class="jbtn jbtn-edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?= $job['id'] ?>"
                                        onclick="return confirm('Delete this job?')"
                                        class="jbtn jbtn-del">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($companyJobs) > 5): ?>
                        <div style="text-align:center;margin-top:12px;">
                            <a href="javascript:void(0)" id="toggleJobsBtn" class="see-more-link">See More</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- NOTIFICATIONS TIMELINE -->
            <div class="card">
                <div class="ch">
                    <span class="ct">Recent notifications</span>
                    <a href="notifications.php" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <div class="timeline">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="ti ti-bell"></i>
                            <p>No notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif):
                            $isRead    = (bool)$notif['is_read'];
                            $itemClass = $isRead ? '' : 'tl-unread';
                            $dotClass  = $isRead ? 'tl-dot-read' : 'tl-dot-unread';
                            $icon      = $isRead ? 'check' : 'bell';
                        ?>
                            <a href="notifications.php?id=<?= $notif['id'] ?>" class="tl-item <?= $itemClass ?>">
                                <div class="tl-left">
                                    <div class="tl-dot <?= $dotClass ?>">
                                        <i class="ti ti-<?= $icon ?>"></i>
                                    </div>
                                    <div class="tl-line"></div>
                                </div>
                                <div class="tl-body">
                                    <div class="tl-title-row">
                                        <span class="tl-title <?= $isRead ? '' : 'tl-title-unread' ?>">
                                            <?= htmlspecialchars($notif['title']) ?>
                                        </span>
                                        <?php if (!$isRead): ?>
                                            <span class="tl-unread-dot"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tl-sub <?= $isRead ? '' : 'tl-sub-unread' ?>">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </div>
                                    <div class="tl-footer">
                                        <span class="tl-time"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></span>
                                        <span class="tl-badge <?= $isRead ? 'tl-badge-read' : 'tl-badge-unread' ?>">
                                            <?= $isRead ? 'Read' : 'Unread' ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="bottom-grid">

            <!-- RECENT APPLICANTS -->
            <div class="card">
                <div class="ch">
                    <span class="ct">Recent Applicants</span>
                    <a href="recruiter_applications.php" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                </div>

                <?php if (empty($recentApplicants)): ?>
                    <div class="empty-state">
                        <i class="ti ti-users"></i>
                        <p>No applicants yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentApplicants as $ap):
                        $apInitials = strtoupper(substr($ap['full_name'] ?? 'C', 0, 2));
                        $isToday    = date('Y-m-d', strtotime($ap['created_at'] ?? 'now')) === date('Y-m-d');
                    ?>
                        <a href="candidate-profile.php?id=<?= $ap['user_id'] ?>&job_id=<?= $ap['job_id'] ?>" class="appl-row">
                            <div class="appl-avatar"><?= $apInitials ?></div>
                            <div class="appl-info">
                                <div class="appl-name"><?= htmlspecialchars($ap['full_name'] ?? 'Candidate') ?></div>
                                <div class="appl-meta">
                                    <?= htmlspecialchars($ap['job_title'] ?? '') ?>
                                    <?php if ($isToday): ?>
                                        · <span style="color:#1D9E75;font-weight:600">New Today</span>
                                    <?php else: ?>
                                        · <?= date('M d', strtotime($ap['created_at'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="appl-btn">
                                <i class="ti ti-eye"></i> View
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- RIGHT SIDE -->
            <div style="display:flex;flex-direction:column;gap:14px">

                <!-- QUICK ACTIONS -->
                <div class="card">
                    <div class="ch"><span class="ct">Quick actions</span></div>
                    <div class="actions-grid">
                        <a href="add_job.php" class="action-item">
                            <div class="action-ico"><i class="ti ti-circle-plus"></i></div>
                            <div>
                                <div class="action-label">Post Job</div>
                                <div class="action-sub">Create opening</div>
                            </div>
                        </a>
                        <a href="recruiter_applications.php" class="action-item">
                            <div class="action-ico"><i class="ti ti-users"></i></div>
                            <div>
                                <div class="action-label">Applicants</div>
                                <div class="action-sub">Review resumes</div>
                            </div>
                        </a>
                        <a href="edit_company_profile.php" class="action-item">
                            <div class="action-ico"><i class="ti ti-building"></i></div>
                            <div>
                                <div class="action-label">Edit Profile</div>
                                <div class="action-sub">Update info</div>
                            </div>
                        </a>
                        <a href="job_positions.php" class="action-item">
                            <div class="action-ico"><i class="ti ti-briefcase"></i></div>
                            <div>
                                <div class="action-label">Job Management</div>
                                <div class="action-sub">Manage listings</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- FREE BANNER -->
                <div class="free-banner">
                    <div class="free-icon"><i class="ti ti-gift"></i></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:3px">Completely Free Forever</div>
                        <div style="font-size:11px;color:#64748b;line-height:1.6;font-weight:500">
                            Post unlimited jobs with no fees. Connect with thousands of job seekers across Algeria.
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<script>
    const toggleBtn = document.getElementById('toggleJobsBtn');
    if (toggleBtn) {
        let expanded = false;
        toggleBtn.addEventListener('click', () => {
            const hiddenJobs = document.querySelectorAll('.hidden-job');
            hiddenJobs.forEach(job => {
                job.style.display = expanded ? 'none' : 'flex';
            });
            expanded = !expanded;
            toggleBtn.textContent = expanded ? 'See Less' : 'See More';
        });
    }
</script>