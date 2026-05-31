<?php
require_once 'config.php';
require_once 'functions.php';

global $pdo;

$userId = $_SESSION['user']['id'];

if (!isset($profile)) {
    $profile = getCandidateProfile($userId);
}

$initials = '';

if (!empty($profile['full_name'])) {

    $names = explode(' ', $profile['full_name']);

    foreach ($names as $name) {
        $initials .= strtoupper(substr($name, 0, 1));
    }

    $initials = substr($initials, 0, 2);
}

/* ── APPLICATIONS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.status,
        a.created_at,
        j.id AS job_id,
        j.title,
        j.city AS location,
        cp.company_name,
        j.expires_at AS deadline
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    LEFT JOIN companies_profiles cp ON j.user_id = cp.user_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 6
");

$stmt->execute([$userId]);

$applications = $stmt->fetchAll();

/* ── SAVED JOBS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        j.id,
        j.title,
        j.city AS location,
        cp.company_name,
        s.created_at AS saved_at
    FROM saved_jobs s
    JOIN jobs j ON s.job_id = j.id
    LEFT JOIN companies_profiles cp ON j.user_id = cp.user_id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
    LIMIT 4
");

$stmt->execute([$userId]);
$savedJobs = $stmt->fetchAll();

/* ── SKILLS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT skill_name
    FROM candidate_skills
    WHERE user_id = ?
");

$stmt->execute([$userId]);

$skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ── RECOMMENDED JOBS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        j.id,
        j.title,
        j.city AS location,
        j.category,
        cp.company_name
    FROM jobs j
    LEFT JOIN companies_profiles cp
        ON j.user_id = cp.user_id
    WHERE j.status = 'open'
");

$stmt->execute();

$allJobs = $stmt->fetchAll();

$recommendedJobs = [];

foreach ($allJobs as $job) {

    $match = 40;

    if (
        !empty($profile['job_title']) &&
        stripos(
            strtolower($job['title']),
            strtolower($profile['job_title'])
        ) !== false
    ) {
        $match += 35;
    }

    if (
        !empty($profile['category']) &&
        !empty($job['category']) &&
        strtolower($profile['category']) == strtolower($job['category'])
    ) {
        $match += 20;
    }

    if (
        !empty($profile['city']) &&
        !empty($job['location']) &&
        strtolower($profile['city']) == strtolower($job['location'])
    ) {
        $match += 10;
    }

    if ($match > 99) {
        $match = 99;
    }

    $job['match'] = $match;

    $recommendedJobs[] = $job;
}


usort($recommendedJobs, function ($a, $b) {
    return $b['match'] <=> $a['match'];
});

$recommendedJobs = array_slice($recommendedJobs, 0, 3);

/* ── TOTAL APPLICATIONS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$totalApplications = $stmt->fetchColumn();

/* ── TOTAL SAVED JOBS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM saved_jobs
    WHERE user_id = ?
");

$stmt->execute([$userId]);
$totalSaved = $stmt->fetchColumn();

/* ── PROFILE VIEWS ───────────────────────── */
$profileViews = getProfileViews($userId);

/* ── GLOBAL MATCH SCORE ───────────────────────── */

$totalMatch = 0;
$totalJobs  = count($recommendedJobs);

if ($totalJobs > 0) {

    foreach ($recommendedJobs as $job) {

        $totalMatch += calculateJobMatch($profile, $job);
    }

    $matchScore = round($totalMatch / $totalJobs);
} else {

    $matchScore = 0;
}

/* ── NOTIFICATIONS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
       OR receiver_id = ?
    ORDER BY created_at DESC
    LIMIT 4
");

$stmt->execute([$userId, $userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── TOP COMPANIES ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        cp.company_name,
        cp.logo_url,
        cp.user_id,
        COUNT(j.id) AS open_positions
    FROM companies_profiles cp
    LEFT JOIN jobs j
        ON j.user_id = cp.user_id
       AND j.status = 'open'
    GROUP BY
        cp.user_id,
        cp.company_name,
        cp.logo_url
    ORDER BY open_positions DESC
    LIMIT 6
");

$stmt->execute();
$topCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── PROFILE COMPLETION ───────────────────────── */
$completion = 0;

/* basic info */
if (!empty($profile['image_path'])) $completion += 15;
if (!empty($profile['summary']))   $completion += 15;
if (!empty($profile['job_title'])) $completion += 15;
if (!empty($profile['city']))      $completion += 15;

/* skills */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_skills WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetchColumn() > 0) {
    $completion += 10;
}

/* education */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_educations WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetchColumn() > 0) {
    $completion += 10;
}

/* experience */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_experiences WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetchColumn() > 0) {
    $completion += 10;
}

/* projects */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_projects WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetchColumn() > 0) {
    $completion += 10;
}

/* languages */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM candidate_languages WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetchColumn() > 0) {
    $completion += 10;
}

$completion = min($completion, 100);

/* ── JOB ALERTS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT ja.*
    FROM job_alerts ja
    WHERE ja.user_id = ?
    ORDER BY ja.created_at DESC
");
$stmt->execute([$userId]);
$allAlerts = $stmt->fetchAll();

$activeAlerts = [];

foreach ($allAlerts as $alert) {

    $conditions = ["j.status = 'open'"];
    $params     = [];

    if (!empty($alert['keywords'])) {
        $conditions[] = "j.title LIKE ?";
        $params[]     = '%' . $alert['keywords'] . '%';
    }

    if (!empty($alert['city'])) {
        $conditions[]  = "j.city = ?";
        $params[]      = $alert['city'];
    }

    if (!empty($alert['category'])) {
        $conditions[]  = "j.category = ?";
        $params[]      = $alert['category'];
    }

    if (!empty($alert['contract_type'])) {
        $conditions[]  = "j.contract_type = ?";
        $params[]      = $alert['contract_type'];
    }

    if (!empty($alert['experience_level'])) {
        $conditions[]  = "j.experience_level = ?";
        $params[]      = $alert['experience_level'];
    }

    if (!empty($alert['work_type'])) {

        $conditions[]  = "j.work_mode = ?";
        $params[]      = $alert['work_type'];
    }

    $sql = "SELECT COUNT(*) FROM jobs j WHERE " . implode(' AND ', $conditions);

    $stmtCheck = $pdo->prepare($sql);
    $stmtCheck->execute($params);
    $matchCount = $stmtCheck->fetchColumn();

    if ($matchCount > 0) {

        $pdo->prepare("DELETE FROM job_alerts WHERE id = ?")->execute([$alert['id']]);
    } else {

        $activeAlerts[] = $alert;
    }
}

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

    /* ── PROGRESS ── */
    .prog-track {
        width: 100%;
        height: 7px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
    }

    .prog-fill {
        height: 100%;
        border-radius: 99px;
        background: #6366f1;
        transition: width .4s ease;
    }

    /* ── DASHBOARD WRAPPER ── */
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
        font-weight: 500;
        border: 1px solid #e8eef6;
        flex-shrink: 0;
        overflow: hidden;
        padding: 0;
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

    /* ── PROFILE COMPLETE ── */
    .profile-complete {
        background: white;
        border: 1px solid #e8eef6;
        border-radius: 24px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
    }

    .prog-wrap {
        flex: 1;
    }

    .prog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .prog-label {
        font-size: 13px;
        font-weight: 500;
        color: #0f172a;
    }

    .prog-pct {
        font-size: 13px;
        font-weight: 600;
        color: #6366f1;
    }

    .prog-hint {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 6px;
    }

    .prog-btn {
        padding: 9px 18px;
        background: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
        border-radius: 14px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        font-family: 'Poppins', sans-serif;
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

    /* ── APP ROWS ── */
    .app-row {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        border-radius: 12px;
        transition: background .15s;
    }

    .app-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .app-row:hover {
        background: #f8fafc;
    }

    .co-ico {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .app-info {
        flex: 1;
        min-width: 0;
    }

    .app-title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .app-meta {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    .app-meta i {
        font-size: 11px;
    }

    .stag {
        font-size: 11px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 99px;
        flex-shrink: 0;
    }

    .s-pend {
        background: #FAEEDA;
        color: #633806;
    }

    .s-rev {
        background: #E6F1FB;
        color: #0C447C;
    }

    .s-acc {
        background: #E1F5EE;
        color: #085041;
    }

    .s-rej {
        background: #FBEAF0;
        color: #72243E;
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

    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .saved-row {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        border-radius: 12px;
        transition: background .15s;
    }

    .saved-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .saved-row:hover {
        background: #f8fafc;
    }

    /* ── SKILLS ── */
    .skill-list {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .skill-chip {
        font-size: 12px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 99px;
        background: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }

    /* ── RECOMMENDED ── */
    .rec-row {
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

    .rec-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .rec-row:hover {
        background: #f8fafc;
    }

    .match-bar {
        width: 52px;
        height: 5px;
        background: #f1f5f9;
        border-radius: 99px;
        overflow: hidden;
        margin-top: 4px;
    }

    .match-fill {
        height: 100%;
        background: #6366f1;
        border-radius: 99px;
    }

    /* ── TOP COMPANIES ── */
    .companies-section {
        margin-top: 1.25rem;
    }

    .companies-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .companies-title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
    }

    .companies-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .company-card {
        background: white;
        border: 1px solid #ececf3;
        border-radius: 20px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        text-decoration: none;
        transition: border-color .2s, box-shadow .2s, transform .2s;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .company-card:hover {
        transform: translateY(-2px);
        border-color: #c4bffa;
        box-shadow: 0 12px 32px rgba(99, 102, 241, .10);
    }

    .company-card-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }

    .company-logo {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: #ede9fe;
        color: #5b21b6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        flex-shrink: 0;
        border: 1px solid #e8eef6;
        overflow: hidden;
    }

    .company-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 13px;
    }

    .company-info {
        flex: 1;
        min-width: 0;
    }

    .company-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .company-verified {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #1D9E75;
        font-weight: 500;
        margin-top: 2px;
    }

    .company-card-right {
        flex-shrink: 0;
    }

    .company-view-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #ede9fe;
        color: #5b21b6;
        padding: 8px 13px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        transition: background .2s, color .2s;
        white-space: nowrap;
    }

    .company-card:hover .company-view-btn {
        background: #6366f1;
        color: white;
    }

    .mini-users-wrap {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .mini-users {
        display: flex;
        align-items: center;
    }

    .mini-user {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #ede9fe;
        color: #5b21b6;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        font-weight: 700;
        margin-left: -5px;
        overflow: hidden;
    }

    .mini-user:first-child {
        margin-left: 0;
    }

    .more-users {
        background: #111827;
        color: white;
    }

    .viewers-text {
        font-size: 10px;
        color: #94a3b8;
        font-weight: 600;
    }

    /* ── CTA BANNER ── */
    .cta-banner {
        margin-top: 1.25rem;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 60%, #4338ca 100%);
        border-radius: 24px;
        padding: 1.4rem 1.75rem;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .cta-icon-wrap {
        width: 52px;
        height: 52px;
        background: rgba(255, 255, 255, .15);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 24px;
        color: white;
    }

    .cta-text {
        flex: 1;
    }

    .cta-title {
        font-size: 16px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
    }

    .cta-sub {
        font-size: 12px;
        color: rgba(255, 255, 255, .75);
        font-weight: 400;
    }

    .cta-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    .cta-btn-primary {
        padding: 10px 22px;
        background: white;
        color: #4f46e5;
        border: none;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        white-space: nowrap;
        transition: opacity .2s;
    }

    .cta-btn-primary:hover {
        opacity: .9;
    }

    .cta-btn-secondary {
        padding: 10px 22px;
        background: rgba(255, 255, 255, .15);
        color: white;
        border: 1px solid rgba(255, 255, 255, .35);
        border-radius: 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        text-decoration: none;
        white-space: nowrap;
        transition: background .2s;
    }

    .cta-btn-secondary:hover {
        background: rgba(255, 255, 255, .25);
    }

    .company-meta {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .company-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
    }

    .company-meta i {
        font-size: 14px;
        color: #8b5cf6;
    }

    .company-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .company-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }

    .company-badge.jobs {
        background: #F8F5FF;
        color: #7C3AED;
        border: 1px solid #E9D5FF;
    }

    .company-badge.soft {
        background: #F3F4F6;
        color: #374151;
    }

    .company-view-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        color: #5B21B6;
        transition: .2s;
    }

    .company-card:hover .company-view-btn {
        transform: translateX(3px);
    }

    @media (max-width: 768px) {

        main.mx-auto {
            padding: 1rem 0.85rem !important;
        }

        .topbar {
            flex-wrap: nowrap;
            gap: 8px;
            margin-bottom: 1rem;
            align-items: center;
        }

        .tb-left {
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .tb-name {
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        .tb-sub {
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .tb-right {
            gap: 7px;
            flex-shrink: 0;
        }

        .icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 16px;
        }

        .profile-complete {
            flex-wrap: wrap;
            gap: 10px;
            padding: 0.9rem 1rem;
            border-radius: 18px;
        }

        .prog-wrap {
            flex: 1 1 100%;
            order: 2;
        }

        .profile-complete>div:first-child {
            order: 1;
        }

        .profile-complete>a {
            order: 3;
            width: 100%;
        }

        .prog-btn {
            width: 100%;
            text-align: center;
            padding: 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .sc {
            padding: 0.75rem 0.85rem;
            border-radius: 16px;
        }

        .sc-num {
            font-size: 20px;
        }

        .sc-label {
            font-size: 10px;
        }

        .sc-badge {
            font-size: 9.5px;
            padding: 2px 8px;
        }

        .main-grid {
            grid-template-columns: 1fr !important;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .bottom-grid {
            grid-template-columns: 1fr !important;
            gap: 10px;
        }

        .card {
            border-radius: 18px;
            padding: 0.9rem 1rem;
        }

        .ct {
            font-size: 13.5px;
        }

        .app-row {
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 6px;
            align-items: flex-start;
        }

        .app-info {
            flex: 1 1 calc(100% - 56px);
            min-width: 0;
        }

        .app-title {
            font-size: 12.5px;
            white-space: normal;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .app-meta {
            font-size: 10.5px;
        }

        .stag {
            font-size: 10px;
            padding: 4px 10px;
            margin-left: 0;
        }

        .app-row>div[style*="11px"][style*="color:#94a3b8"] {
            display: none;
        }

        .tl-item {
            padding: 8px 8px 12px;
            gap: 10px;
        }

        .tl-dot {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 13px;
        }

        .tl-left {
            width: 32px;
        }

        .tl-title {
            font-size: 12.5px;
        }

        .tl-sub {
            font-size: 11px;
            -webkit-line-clamp: 2;
        }

        .tl-time {
            font-size: 10px;
        }

        .tl-badge {
            font-size: 9.5px;
            padding: 2px 7px;
        }

        .saved-row {
            flex-wrap: nowrap;
            padding: 9px 6px;
            gap: 9px;
        }

        .saved-row>div[style*="flex:1"] {
            min-width: 0;
        }

        .companies-grid {
            grid-template-columns: 1fr !important;
            gap: 10px;
        }

        .companies-header {
            margin-bottom: 0.75rem;
        }

        .companies-title {
            font-size: 13.5px;
        }

        .company-card {
            padding: 12px 14px;
            border-radius: 16px;
            flex-wrap: nowrap;
            align-items: flex-start;
        }

        .company-logo {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-size: 15px;
            flex-shrink: 0;
        }

        .company-name {
            font-size: 12.5px;
        }

        .company-meta span {
            font-size: 11px;
        }

        .company-meta {
            gap: 8px;
            margin-top: 3px;
        }

        .company-tags {
            gap: 5px;
            margin-top: 6px;
        }

        .company-badge {
            font-size: 10.5px;
            padding: 4px 8px;
        }

        .company-view-btn {
            font-size: 11px;
            margin-top: 4px;
        }

        .company-card-right {
            padding-top: 4px;
        }

        .app-row[style*="justify-content:space-between"] {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 8px;
        }

        .app-row[style*="justify-content:space-between"]>div:last-child {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            width: 100%;
        }

        .cta-banner {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 1.1rem 1.1rem;
            border-radius: 18px;
            margin-top: 1rem;
        }

        .cta-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            font-size: 20px;
        }

        .cta-title {
            font-size: 14px;
        }

        .cta-sub {
            font-size: 11.5px;
        }

        .cta-actions {
            width: 100%;
            flex-direction: column;
            gap: 8px;
        }

        .cta-btn-primary,
        .cta-btn-secondary {
            width: 100%;
            text-align: center;
            padding: 11px 0;
            font-size: 12.5px;
            border-radius: 12px;
        }

        .skill-chip {
            font-size: 11px;
            padding: 5px 12px;
        }

        .rec-row {
            gap: 8px;
            padding: 9px 6px;
        }

        .companies-section {
            margin-top: 1rem;
        }
    }

    @media (max-width: 380px) {

        .stats-grid {
            gap: 8px;
        }

        .sc-num {
            font-size: 18px;
        }

        .tb-name {
            max-width: 120px;
        }

        .tb-sub {
            display: none;
        }

        .company-meta {
            flex-direction: column;
            gap: 3px;
        }

        .cta-title {
            font-size: 13px;
        }
    }
</style>



<main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="db">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="tb-left">
                <div class="avatar-lg">
                    <?php if (!empty($profile['image_path'])): ?>
                        <img src="<?= htmlspecialchars($profile['image_path']) ?>" alt="Profile"
                            style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="tb-name"><?= htmlspecialchars($profile['full_name'] ?? 'Candidate') ?></div>
                    <div class="tb-sub">
                        <span class="online-dot"></span>
                        <?= htmlspecialchars($profile['job_title'] ?? 'Developer') ?> · <?= htmlspecialchars($profile['city'] ?? 'Algeria') ?>
                    </div>
                </div>
            </div>
            <div class="tb-right">
                <a href="notifications.php" class="icon-btn"><i class="ti ti-bell"></i><span class="ndot"></span></a>
                <a href="settings.php" class="icon-btn"><i class="ti ti-settings"></i></a>
            </div>
        </div>

        <!-- PROFILE COMPLETE -->
        <div class="profile-complete">
            <div style="width:46px;height:46px;border-radius:14px;background:#EEEDFE;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:#6366f1">
                <i class="ti ti-user-check"></i>
            </div>
            <div class="prog-wrap">
                <div class="prog-header">
                    <span class="prog-label">Profile completion</span>
                    <span class="prog-pct"><?= $completion ?>%</span>
                </div>
                <div class="prog-track">
                    <div class="prog-fill" style="width:<?= $completion ?>%;"></div>
                </div>
                <div class="prog-hint">
                    <i class="ti ti-info-circle"></i> Complete your profile to increase visibility
                </div>
            </div>
            <a href="profile.php?id=<?= (int) $_SESSION['user']['id'] ?>">
                <button class="prog-btn">Complete profile</button>
            </a>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="sc">
                <div class="sc-label"><i class="ti ti-send"></i> Applications</div>
                <div class="sc-num"><?= $totalApplications ?></div>
                <div class="sc-badge b-up">Active</div>
            </div>
            <div class="sc">
                <div class="sc-label"><i class="ti ti-eye"></i> Profile views</div>
                <div class="sc-num"><?= $profileViews ?></div>
                <div class="sc-badge b-up">Growing</div>
            </div>
            <div class="sc">
                <div class="sc-label"><i class="ti ti-bookmark"></i> Saved jobs</div>
                <div class="sc-num"><?= $totalSaved ?></div>
                <div class="sc-badge b-neu">Updated</div>
            </div>
            <div class="sc">
                <div class="sc-label"><i class="ti ti-star"></i> Match score</div>
                <div class="sc-num"><?= $matchScore ?>%</div>
                <div class="sc-badge b-up">Top candidate</div>
            </div>
        </div>

        <div class="main-grid">

            <!-- APPLICATIONS -->
            <div class="card">
                <div class="ch">
                    <span class="ct">My applications</span>
                    <a href="applications.php" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <?php if (!empty($applications)): ?>
                    <?php foreach ($applications as $app): ?>
                        <?php
                        $statusClass = match ($app['status']) {
                            'pending'  => 's-pend',
                            'reviewed' => 's-rev',
                            'accepted' => 's-acc',
                            'rejected' => 's-rej',
                            default    => 's-pend'
                        };
                        $letter = strtoupper(substr($app['company_name'] ?? 'J', 0, 1));
                        ?>
                        <a href="job_details.php?id=<?= $app['job_id'] ?>&tab=job" class="app-row">
                            <div class="co-ico" style="background:#ede9fe;color:#5b21b6"><?= $letter ?></div>
                            <div class="app-info">
                                <div class="app-title"><?= htmlspecialchars($app['title']) ?></div>
                                <div class="app-meta">
                                    <i class="ti ti-building"></i><?= htmlspecialchars($app['company_name']) ?>
                                    · <i class="ti ti-map-pin"></i><?= htmlspecialchars($app['location']) ?>
                                </div>
                            </div>
                            <span class="stag <?= $statusClass ?>"><?= ucfirst($app['status']) ?></span>
                            <div style="font-size:11px;color:#94a3b8;white-space:nowrap">
                                <?= date('M d, Y', strtotime($app['created_at'])) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size:13px;color:#94a3b8;font-weight:500">No applications yet.</p>
                <?php endif; ?>
            </div>

            <!-- NOTIFICATIONS TIMELINE -->
            <div class="card">
                <div class="ch">
                    <span class="ct">Recent notifications</span>
                    <a href="notifications.php" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <div class="timeline">
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                        $isRead    = (bool)$notif['is_read'];
                        $itemClass = $isRead ? '' : 'tl-unread';
                        $dotClass  = $isRead ? 'tl-dot-read' : 'tl-dot-unread';
                        $type      = $notif['type'] ?? '';
                        $icon      = 'bell';
                        if ($type === 'accepted')  $icon = 'circle-check';
                        elseif ($type === 'rejected')  $icon = 'x';
                        elseif ($type === 'job_alert') $icon = 'briefcase';
                        elseif ($type === 'message')   $icon = 'message';
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
                </div>
            </div>

        </div>

        <!-- BOTTOM GRID -->
        <div class="bottom-grid">

            <!-- SAVED JOBS -->
            <div class="card">
                <div class="ch">
                    <span class="ct">Saved jobs</span>
                    <a href="saved_jobs.php" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <?php foreach ($savedJobs as $job): ?>
                    <?php
                    $letter = strtoupper(substr($job['company_name'] ?? 'J', 0, 1));
                    $savedLabel = 'Recently saved';
                    if (!empty($job['saved_at'])) {
                        $daysAgo = floor((time() - strtotime($job['saved_at'])) / 86400);
                        $savedLabel = $daysAgo <= 0 ? 'Today' : $daysAgo . 'd ago';
                    }
                    ?>
                    <a href="job_details.php?id=<?= $job['id'] ?>&tab=job" class="saved-row">
                        <div class="co-ico" style="background:#ede9fe;color:#5b21b6">
                            <?= $letter ?>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600;color:#111827">
                                <?= htmlspecialchars($job['title']) ?>
                            </div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;font-weight:500">
                                <?= htmlspecialchars($job['company_name']) ?> ·
                                <?= htmlspecialchars($job['location']) ?>
                            </div>
                        </div>
                        <span style="font-size:11px;font-weight:600;background:#FAF3E0;color:#8A6A1F;padding:5px 10px;border-radius:999px;white-space:nowrap;">
                            <?= $savedLabel ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- RIGHT SIDE -->
            <div style="display:flex;flex-direction:column;gap:14px">

                <!-- SKILLS -->
                <div class="card">
                    <div class="ch"><span class="ct">My skills</span></div>
                    <div class="skill-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-chip"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- RECOMMENDED -->
                <div class="card">
                    <div class="ch">
                        <span class="ct">Recommended for you</span>
                        <a href="job.php?tab=recommended" class="cl"> View all <i class="ti ti-arrow-right"></i></a>
                    </div>
                    <?php foreach ($recommendedJobs as $job): ?>
                        <?php
                        $letter = strtoupper(substr($job['company_name'] ?? 'J', 0, 1));
                        $match = $job['match'];
                        ?>
                        <a href="job_details.php?id=<?= $job['id'] ?>&tab=job" class="rec-row">
                            <div class="co-ico" style="background:#ede9fe;color:#5b21b6;width:38px;height:38px;border-radius:12px;font-size:14px"><?= $letter ?></div>
                            <div style="flex:1;min-width:0">
                                <div style="font-size:12px;font-weight:600;color:#111827">
                                    <?= htmlspecialchars($job['title']) ?> — <?= htmlspecialchars($job['company_name']) ?>
                                </div>
                                <div class="match-bar">
                                    <div class="match-fill" style="width:<?= $match ?>%"></div>
                                </div>
                            </div>
                            <span style="font-size:12px;font-weight:600;color:#6366f1;flex-shrink:0"><?= $match ?>%</span>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>
        <!-- JOB ALERTS -->
        <div class="card" style="margin-top:14px">
            <div class="ch">
                <span class="ct">
                    <i class="ti ti-bell-ringing" style="color:#6366f1;margin-right:6px"></i>
                    My Job Alerts
                </span>
                <a href="create_alert.php" class="cl">
                    <i class="ti ti-plus"></i> New alert
                </a>
            </div>

            <?php if (!empty($activeAlerts)): ?>
                <?php foreach ($activeAlerts as $alert): ?>
                    <div class="app-row" style="justify-content:space-between">
                        <div style="display:flex;align-items:center;gap:11px;flex:1;min-width:0">

                            <div class="co-ico" style="background:#ede9fe;color:#5b21b6;font-size:16px">
                                <i class="ti ti-bell"></i>
                            </div>

                            <div class="app-info">
                                <div class="app-title">
                                    <?= htmlspecialchars($alert['alert_name']) ?>
                                </div>
                                <div class="app-meta">
                                    <?php if (!empty($alert['keywords'])): ?>
                                        <i class="ti ti-search"></i><?= htmlspecialchars($alert['keywords']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($alert['city'])): ?>
                                        · <i class="ti ti-map-pin"></i><?= htmlspecialchars($alert['city']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($alert['category'])): ?>
                                        · <i class="ti ti-tag"></i><?= htmlspecialchars($alert['category']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">

                            <span class="stag s-rev">
                                <?= ucfirst($alert['frequency']) ?>
                            </span>

                            <span style="font-size:11px;font-weight:600;background:#FEF9C3;color:#854D0E;padding:5px 11px;border-radius:99px">
                                <i class="ti ti-clock" style="font-size:10px"></i> Waiting for match
                            </span>

                            <a href="delete_alert.php?id=<?= $alert['id'] ?>"
                                onclick="return confirm('Delete this alert?')"
                                style="width:30px;height:30px;border-radius:10px;background:#fbeaf0;color:#be185d;display:flex;align-items:center;justify-content:center;font-size:14px;text-decoration:none">
                                <i class="ti ti-trash"></i>
                            </a>

                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div style="text-align:center;padding:1.5rem 0">
                    <div style="font-size:32px;margin-bottom:8px">🔔</div>
                    <div style="font-size:13px;font-weight:600;color:#64748b">No active alerts</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px">
                        Create an alert and we'll notify you when a matching job appears
                    </div>
                    <a href="create_alert.php" style="display:inline-block;margin-top:12px;padding:8px 20px;background:#ede9fe;color:#5b21b6;border-radius:12px;font-size:12px;font-weight:600;text-decoration:none">
                        + Create Alert
                    </a>
                </div>
            <?php endif; ?>

        </div>

        <!-- TOP COMPANIES -->
        <div class="companies-section">

            <div class="companies-header">
                <span class="companies-title">
                    <i class="ti ti-building" style="font-size:15px;margin-right:6px;color:#6366f1"></i>
                    Top Companies Hiring Now
                </span>
                <a href="companies.php" class="cl"> View all companies <i class="ti ti-arrow-right"></i></a>
            </div>

            <div class="companies-grid">
                <?php foreach ($topCompanies as $company): ?>
                    <?php
                    $letter = strtoupper(substr($company['company_name'] ?? 'C', 0, 1));
                    $pos    = (int) $company['open_positions'];

                    $industry = $company['industry'] ?? 'Company';
                    $location = $company['city'] ?? 'Algeria';
                    ?>

                    <a href="company.php?id=<?= $company['user_id'] ?>" class="company-card">

                        <div class="company-card-left">

                            <div class="company-logo">
                                <?php if (!empty($company['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>">
                                <?php else: ?>
                                    <?= $letter ?>
                                <?php endif; ?>
                            </div>

                            <div class="company-info">

                                <div class="company-name">
                                    <?= htmlspecialchars($company['company_name']) ?>
                                </div>

                                <!-- Industry + Location -->
                                <div class="company-meta">
                                    <span>
                                        <i class="ti ti-building-community"></i>
                                        <?= htmlspecialchars($industry) ?>
                                    </span>

                                    <span>
                                        <i class="ti ti-map-pin"></i>
                                        <?= htmlspecialchars($location) ?>
                                    </span>
                                </div>

                                <!-- Badges -->
                                <div class="company-tags">

                                    <span class="company-badge jobs">
                                        <i class="ti ti-briefcase"></i>
                                        <?= $pos ?> Open Position<?= $pos !== 1 ? 's' : '' ?>
                                    </span>

                                    <?php if (!empty($company['company_size'])): ?>
                                        <span class="company-badge soft">
                                            <i class="ti ti-users"></i>
                                            <?= htmlspecialchars($company['company_size']) ?>
                                        </span>
                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                        <div class="company-card-right">
                            <div class="company-view-btn">
                                View Profile
                                <i class="ti ti-arrow-right"></i>
                            </div>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- CTA BANNER -->
        <div class="cta-banner">
            <div class="cta-icon-wrap">
                <i class="ti ti-rocket"></i>
            </div>
            <div class="cta-text">
                <div class="cta-title">Ready to take the next step?</div>
                <div class="cta-sub">Discover amazing opportunities and accelerate your career.</div>
            </div>
            <div class="cta-actions">
                <a href="job.php" class="cta-btn-primary">Explore Jobs</a>
                <a href="create_alert.php" class="cta-btn-secondary">Create Job Alert</a>
            </div>
        </div>

    </div>
</main>