<?php
require_once 'functions.php';
requireLogin();

if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

global $pdo;

$page   = $_GET['page']   ?? 'dashboard';
$search = $_GET['search'] ?? '';

/* ── Delete User ── */
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    foreach (['applications', 'saved_jobs', 'candidates_profiles', 'companies_profiles', 'jobs', 'users'] as $tbl) {
        $col = $tbl === 'users' ? 'id' : 'user_id';
        $pdo->prepare("DELETE FROM $tbl WHERE $col = ?")->execute([$uid]);
    }
    header('Location: admin.php?page=users');
    exit;
}

/* ── Delete Job ── */
if (isset($_GET['delete_job']) && is_numeric($_GET['delete_job'])) {
    $jid = intval($_GET['delete_job']);
    $pdo->prepare("DELETE FROM applications WHERE job_id = ?")->execute([$jid]);
    $pdo->prepare("DELETE FROM saved_jobs   WHERE job_id = ?")->execute([$jid]);
    $pdo->prepare("DELETE FROM jobs         WHERE id     = ?")->execute([$jid]);
    header('Location: admin.php?page=jobs');
    exit;
}

/* ── Stats ── */
$stats = [
    'users'        => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'candidates'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role='candidate'")->fetchColumn(),
    'companies'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='company'")->fetchColumn(),
    'jobs'         => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
    'unread_msgs'  => $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(),
];

$usersByRole = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
$jobsByCity  = $pdo->query("SELECT city, COUNT(*) as count FROM jobs GROUP BY city ORDER BY count DESC LIMIT 6")->fetchAll();

/* ── Activity ── */
$activityCount = $pdo->query("
    SELECT COUNT(*) as total FROM (
        SELECT created_at FROM users
        UNION ALL SELECT created_at FROM jobs
        UNION ALL SELECT created_at FROM applications
    ) as all_activity
")->fetch()['total'];

$activityPage  = max(1, intval($_GET['activity_page'] ?? 1));
$itemsPerPage  = 6;
$offset        = ($activityPage - 1) * $itemsPerPage;
$totalPages    = ceil($activityCount / $itemsPerPage);

$recentActivity = $pdo->query("
    SELECT 'user' as type, email as info, created_at FROM users
    UNION ALL SELECT 'job', title, created_at FROM jobs
    UNION ALL SELECT 'application', CONCAT('Application #', id), created_at FROM applications
    ORDER BY created_at DESC LIMIT $itemsPerPage OFFSET $offset
")->fetchAll();

/* ── Page data ── */
$pageDescriptions = [
    'dashboard'    => 'Monitor platform activity and manage everything.',
    'users'        => 'Manage all users.',
    'candidates'   => 'Manage all candidates.',
    'companies'    => 'Manage all companies.',
    'jobs'         => 'Manage all jobs.',
    'applications' => 'Manage all applications.',
    'messages'     => 'View and reply to contact form messages.',
];

switch ($page) {
    case 'users':
        $q = "SELECT u.id, u.email, u.role, u.created_at,
                CASE WHEN u.role='candidate' THEN cp.full_name
                     WHEN u.role='company'   THEN cmp.company_name
                     ELSE 'Admin' END as name
              FROM users u
              LEFT JOIN candidates_profiles cp  ON u.id = cp.user_id
              LEFT JOIN companies_profiles  cmp ON u.id = cmp.user_id";
        if ($search) {
            $stmt = $pdo->prepare($q . " WHERE cp.full_name LIKE ? OR cmp.company_name LIKE ? ORDER BY u.created_at DESC");
            $stmt->execute(["%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY u.created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    case 'candidates':
        $q = "SELECT u.id, cp.full_name as name, u.email, u.role, u.created_at, cp.phone, cp.city as location, cp.summary
              FROM users u INNER JOIN candidates_profiles cp ON u.id=cp.user_id WHERE u.role='candidate'";
        if ($search) {
            $stmt = $pdo->prepare($q . " AND cp.full_name LIKE ? ORDER BY u.created_at DESC");
            $stmt->execute(["%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY u.created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    case 'companies':
        $q = "SELECT u.id, cmp.company_name as name, u.email, u.role, u.created_at, cmp.phone, cmp.city as location, cmp.description
              FROM users u INNER JOIN companies_profiles cmp ON u.id=cmp.user_id WHERE u.role='company'";
        if ($search) {
            $stmt = $pdo->prepare($q . " AND cmp.company_name LIKE ? ORDER BY u.created_at DESC");
            $stmt->execute(["%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY u.created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    case 'jobs':
        $q = "SELECT j.*, cp.company_name FROM jobs j LEFT JOIN companies_profiles cp ON j.user_id=cp.user_id";
        if ($search) {
            $stmt = $pdo->prepare($q . " WHERE j.title LIKE ? OR j.city LIKE ? OR cp.company_name LIKE ? ORDER BY j.created_at DESC");
            $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY j.created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    case 'applications':
        $q = "SELECT a.*, j.title as job_title, cp.full_name as candidate_name
              FROM applications a LEFT JOIN jobs j ON a.job_id=j.id LEFT JOIN candidates_profiles cp ON a.user_id=cp.user_id";
        if ($search) {
            $stmt = $pdo->prepare($q . " WHERE j.title LIKE ? OR cp.full_name LIKE ? ORDER BY a.created_at DESC");
            $stmt->execute(["%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY a.created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    case 'messages':
        $q = "SELECT * FROM contact_messages";
        if ($search) {
            $stmt = $pdo->prepare($q . " WHERE full_name LIKE ? OR email LIKE ? OR subject LIKE ? ORDER BY created_at DESC");
            $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->prepare($q . " ORDER BY created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll();
        break;

    default:
        $data = [];
        break;
}

$usersByRoleJson = json_encode(array_map(fn($r) => ['role' => ucfirst($r['role']), 'count' => (int)$r['count']], $usersByRole));
$jobsByCityJson  = json_encode(array_map(fn($r) => ['city' => $r['city'], 'count' => (int)$r['count']], $jobsByCity));


$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

$totalApplications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

$newMessages = $pdo->query("
    SELECT COUNT(*) FROM contact_messages
    WHERE is_read=0
")->fetchColumn();


$score = 40;

if ($totalUsers > 10) $score += 15;

if ($totalJobs > 5) $score += 15;

if ($totalApplications > 20) $score += 20;

if ($newMessages < 10) $score += 10;

$score = min($score, 100);


$statusText = 'Critical issues detected';

if ($score >= 80) {
    $statusText = 'All systems operating normally';
} elseif ($score >= 50) {
    $statusText = 'Some areas need attention';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <title>Admin Dashboard | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            font-family: 'Poppins', sans-serif;
        }

        .fa,
        .fas,
        .far,
        .fal,
        .fab,
        .fad {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        }

        body {
            background: #f8fafc !important;
            color: #0f172a;
        }

        :root {
            --ind: #6366f1;
            --ind-d: #4f46e5;
            --ind-s: #ede9fe;
            --ind-m: #c7d2fe;
            --sl-dk: #0f172a;
            --sl-md: #334155;
            --sl-lt: #64748b;
            --sl-mu: #94a3b8;
            --bdr: #e8eef6;
            --surf: #ffffff;
            --bg: #f8fafc;
        }

        .sidebar {
            background: var(--surf);
            border: 1px solid var(--bdr);
            border-radius: 24px;
            padding: 20px;
            position: sticky;
            top: 88px;
        }

        .adm-avatar {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--ind-d), var(--ind));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(99, 102, 241, .25);
            flex-shrink: 0;
        }

        .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--sl-mu);
            margin: 14px 0 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            border-radius: 14px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: .2s;
            color: var(--sl-md);
            margin-bottom: 2px;
        }

        .nav-link:hover {
            background: var(--ind-s);
            color: var(--ind-d);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--ind-d), var(--ind));
            color: #fff;
            box-shadow: 0 6px 18px rgba(99, 102, 241, .25);
        }

        .nav-link i {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }

        .nbadge {
            margin-left: auto;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .nbadge-ind {
            margin-left: auto;
            background: var(--ind-s);
            color: var(--ind-d);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        /* ── PAGE HEADER ── */
        .pg-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--ind-s);
            color: var(--ind-d);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 10px;
        }

        /* ── PROFILE BAR ── */
        .profile-bar {
            background: var(--surf);
            border: 1px solid var(--bdr);
            border-radius: 24px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .03);
        }

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
            background: var(--ind);
            transition: width .4s;
        }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .sc {
            background: var(--surf);
            border: 1px solid var(--bdr);
            border-radius: 20px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            transition: .2s;
        }

        .sc::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 90px;
            height: 90px;
            background: radial-gradient(circle, rgba(99, 102, 241, .07) 0%, transparent 70%);
            border-radius: 50%;
        }

        .sc:hover {
            transform: translateY(-2px);
            border-color: var(--ind-m);
            box-shadow: 0 8px 24px rgba(99, 102, 241, .10);
        }

        .sc-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--ind-s);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ind);
            font-size: 16px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .sc-label {
            font-size: 10px;
            color: var(--sl-mu);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .sc-num {
            font-size: 22px;
            font-weight: 700;
            color: var(--sl-dk);
            line-height: 1;
            position: relative;
            z-index: 1;
        }

        .sc-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
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
            grid-template-columns: 1fr 280px;
            gap: 14px;
            margin-bottom: 16px;
        }

        /* ── CARD ── */
        .card {
            background: var(--surf);
            border: 1px solid #ececf3;
            border-radius: 24px;
            padding: 16px 20px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .03);
        }

        .ch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .ct {
            font-size: 14px;
            font-weight: 600;
            color: var(--sl-dk);
        }

        .cl {
            font-size: 11px;
            color: var(--ind);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
        }

        /* ── ACTIVITY ROW ── */
        .act-row {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 6px;
            border-bottom: 1px solid #f1f5f9;
            border-radius: 12px;
            transition: .15s;
            text-decoration: none;
            color: inherit;
        }

        .act-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .act-row:hover {
            background: #f8fafc;
        }

        .act-ico {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .act-ico.user {
            background: var(--ind-s);
            color: var(--ind);
        }

        .act-ico.job {
            background: #ecfdf5;
            color: #059669;
        }

        .act-ico.application {
            background: #fef3c7;
            color: #d97706;
        }

        /* ── TIMELINE ── */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .tl-item {
            display: flex;
            gap: 10px;
            padding: 8px 8px 12px;
            border-radius: 14px;
            transition: .15s;
            text-decoration: none;
            color: inherit;
        }

        .tl-item:last-child {
            padding-bottom: 0;
        }

        .tl-item:hover {
            background: #f8fafc;
        }

        .tl-item.tl-unread {
            background: #f5f3ff;
            border-left: 3px solid var(--ind);
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
            width: 34px;
            flex-shrink: 0;
        }

        .tl-dot {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .tl-dot-read {
            background: #f1f5f9;
            color: var(--sl-mu);
        }

        .tl-dot-unread {
            background: var(--ind);
            color: #fff;
        }

        .tl-line {
            width: 2px;
            flex: 1;
            background: #f1f5f9;
            margin-top: 4px;
            border-radius: 99px;
        }

        .tl-item:last-child .tl-line {
            display: none;
        }

        .tl-body {
            flex: 1;
            padding-top: 2px;
        }

        .tl-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--sl-dk);
        }

        .tl-title-unread {
            color: #3730a3;
        }

        .tl-unread-dot {
            width: 7px;
            height: 7px;
            background: var(--ind);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .tl-sub {
            font-size: 11px;
            color: var(--sl-lt);
            line-height: 1.5;
            margin-top: 2px;
        }

        .tl-sub-unread {
            color: #4338ca;
        }

        .tl-time {
            font-size: 10px;
            color: var(--sl-mu);
            margin-top: 4px;
        }

        /* ── CHARTS ── */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }

        /* ── ROLE BADGE ── */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .role-badge.candidate {
            background: #ecfdf5;
            color: #065f46;
        }

        .role-badge.company {
            background: var(--ind-s);
            color: var(--ind-d);
        }

        .role-badge.admin {
            background: #fef3c7;
            color: #92400e;
        }

        /* ── SEARCH BAR ── */
        .srch-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .srch-inp {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 9px;
            height: 46px;
            background: #fff;
            border: 1px solid var(--bdr);
            border-radius: 16px;
            padding: 0 16px;
        }

        .srch-inp i {
            color: var(--sl-mu);
            font-size: 14px;
        }

        .srch-inp input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 13px;
            color: var(--sl-dk);
            font-family: 'Poppins', sans-serif;
        }

        .srch-inp input::placeholder {
            color: #c0c4cc;
        }

        .srch-btn {
            height: 46px;
            padding: 0 20px;
            background: linear-gradient(135deg, var(--ind-d), var(--ind));
            color: #fff;
            border: none;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 6px 18px rgba(99, 102, 241, .22);
        }

        .srch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(99, 102, 241, .30);
        }

        .srch-clr {
            height: 46px;
            padding: 0 18px;
            background: #fff;
            color: var(--sl-md);
            border: 1px solid var(--bdr);
            border-radius: 16px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
            transition: .2s;
        }

        .srch-clr:hover {
            background: var(--ind-s);
            color: var(--ind-d);
            border-color: var(--ind-m);
        }

        /* ── TABLE ── */
        .tbl-wrap {
            background: #fff;
            border: 1px solid var(--bdr);
            border-radius: 24px;
            overflow: hidden;
        }

        .tbl-head {
            padding: 16px 22px;
            border-bottom: 1px solid var(--bdr);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8fafc;
            padding: 11px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--sl-mu);
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid var(--bdr);
        }

        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: .15s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafafe;
        }

        tbody td {
            padding: 12px 16px;
            font-size: 12.5px;
            color: var(--sl-md);
            vertical-align: middle;
        }

        .av {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            background: var(--ind-s);
            color: var(--ind-d);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ── ACTION BUTTONS ── */
        .abtn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 11px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: .2s;
        }

        .abtn-view {
            background: #e0f2fe;
            color: #0369a1;
        }

        .abtn-view:hover {
            background: #0369a1;
            color: #fff;
        }

        .abtn-del {
            background: #fee2e2;
            color: #dc2626;
        }

        .abtn-del:hover {
            background: #dc2626;
            color: #fff;
        }

        /* ── STATUS TAGS ── */
        .stag {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 99px;
        }

        .s-new {
            background: #fef3f2;
            color: #dc2626;
        }

        .s-acc {
            background: #E1F5EE;
            color: #085041;
        }

        .s-read {
            background: #f1f5f9;
            color: var(--sl-mu);
        }

        /* ── SHOW MORE ── */
        .show-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            background: linear-gradient(135deg, var(--ind-d), var(--ind));
            color: #fff;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(99, 102, 241, .22);
            transition: .2s;
        }

        .show-more-btn:hover {
            transform: translateY(-2px);
            color: #fff;
        }

        /* ── CTA BANNER ── */
        .cta {
            margin-top: 16px;
            background: linear-gradient(135deg, var(--ind) 0%, var(--ind-d) 60%, #4338ca 100%);
            border-radius: 24px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .cta-ico {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, .15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
            flex-shrink: 0;
        }

        .cta-ttl {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
        }

        .cta-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .75);
        }

        .cta-btn {
            padding: 9px 18px;
            background: rgba(255, 255, 255, .15);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s;
        }

        .cta-btn:hover {
            background: rgba(255, 255, 255, .25);
        }

        .cta-btn-pri {
            background: #fff !important;
            color: var(--ind-d) !important;
            border: none;
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: .3s;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .2);
            max-width: 480px;
            width: 90%;
            padding: 36px;
            transform: scale(.96) translateY(16px);
            transition: .3s;
            position: relative;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }

        .modal-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 18px;
        }

        .modal-icon.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .modal-icon.info {
            background: var(--ind-s);
            color: var(--ind);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            color: var(--sl-dk);
            margin-bottom: 8px;
        }

        .modal-subtitle {
            font-size: 13px;
            color: var(--sl-lt);
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.65;
        }

        .modal-details {
            background: #f8fafc;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 20px;
            border-left: 4px solid var(--ind);
        }

        .modal-di {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12.5px;
        }

        .modal-dl {
            color: var(--sl-mu);
            font-weight: 500;
        }

        .modal-dv {
            color: var(--sl-dk);
            font-weight: 600;
            text-align: right;
            max-width: 220px;
            word-break: break-word;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-btn {
            flex: 1;
            padding: 11px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: .2s;
        }

        .modal-btn-cancel {
            background: #f1f5f9;
            color: var(--sl-dk);
        }

        .modal-btn-cancel:hover {
            background: #e2e8f0;
        }

        .modal-btn-delete {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .modal-btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, .28);
        }

        /* ── MESSAGE MODAL ── */
        .msg-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: .3s;
            backdrop-filter: blur(4px);
            padding: 24px 0;
            overflow-y: auto;
        }

        .msg-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .msg-modal-content {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .2);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(.96) translateY(16px);
            transition: .3s;
        }

        .msg-modal-overlay.active .msg-modal-content {
            transform: scale(1) translateY(0);
        }

        /* ── EMPTY ── */
        .empty-cell {
            padding: 48px 24px !important;
            text-align: center;
            color: var(--sl-mu);
        }

        .empty-cell i {
            font-size: 26px;
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
        }

        /* ── QUICK STATS sidebar ── */
        .qs-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .qs-row:last-child {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-icon danger"><i class="fas fa-trash"></i></div>
            <h3 class="modal-title">Delete <span id="modalItemType"></span></h3>
            <p class="modal-subtitle">Are you sure? This action cannot be undone.</p>
            <div class="modal-details" id="modalDetails"></div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times" style="margin-right:5px"></i>Cancel
                </button>
                <a id="modalDeleteLink" class="modal-btn modal-btn-delete">
                    <i class="fas fa-trash"></i> Yes, Delete
                </a>
            </div>
        </div>
    </div>

    <!-- ══ PROFILE MODAL ══ -->
    <div class="modal-overlay" id="profileModal">
        <div class="modal-content">
            <button onclick="closeProfileModal()"
                style="position:absolute;top:14px;right:14px;background:#f1f5f9;border:none;width:34px;height:34px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:13px;">
                <i class="fas fa-times"></i>
            </button>
            <div id="profileContent"></div>
        </div>
    </div>

    <!-- ══ MESSAGE MODAL ══ -->
    <div class="msg-modal-overlay" id="messageModal">
        <div class="msg-modal-content">

            <!-- Header -->
            <div style="padding:22px 28px 16px;border-bottom:1px solid var(--bdr);position:sticky;top:0;background:#fff;z-index:2;border-radius:24px 24px 0 0;">
                <button onclick="closeMessageModal()"
                    style="position:absolute;top:16px;right:16px;background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;">
                    <i class="fas fa-times"></i>
                </button>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,var(--ind-d),var(--ind));display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <p style="margin:0;font-size:15px;font-weight:700;color:var(--sl-dk)" id="modalMsgSubject"></p>
                        <p style="margin:2px 0 0;font-size:11px;color:var(--sl-mu)" id="modalMsgMeta"></p>
                    </div>
                </div>
            </div>

            <!-- Sender -->
            <div style="padding:16px 28px;background:#f8fafc;border-bottom:1px solid var(--bdr);display:flex;gap:24px;flex-wrap:wrap;">
                <div>
                    <p style="margin:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sl-mu)">From</p>
                    <p style="margin:3px 0 0;font-size:13px;font-weight:600;color:var(--sl-dk)" id="modalMsgName"></p>
                </div>
                <div>
                    <p style="margin:0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sl-mu)">Email</p>
                    <p style="margin:3px 0 0;font-size:13px;color:var(--ind);font-weight:500" id="modalMsgEmail"></p>
                </div>
            </div>

            <!-- Body -->
            <div style="padding:20px 28px;">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sl-mu);margin:0 0 10px">Message</p>
                <div id="modalMsgBody"
                    style="background:#f8fafc;border:1px solid var(--bdr);border-radius:16px;padding:16px;font-size:13px;color:var(--sl-md);line-height:1.8;white-space:pre-wrap;word-break:break-word;"></div>
            </div>

            <!-- Reply -->
            <div style="padding:0 28px 24px">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sl-mu);margin:0 0 10px">
                    <i class="fas fa-reply" style="color:var(--ind);margin-right:4px"></i>Reply
                </p>
                <textarea id="replyText" placeholder="Type your reply here..."
                    style="width:100%;min-height:120px;padding:14px 16px;border:1px solid var(--bdr);border-radius:16px;font-family:'Poppins',sans-serif;font-size:13px;color:var(--sl-dk);background:#fafafe;outline:none;resize:vertical;transition:.2s;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--ind)';this.style.boxShadow='0 0 0 4px rgba(99,102,241,.08)'"
                    onblur="this.style.borderColor='';this.style.boxShadow=''"></textarea>
                <div style="margin-top:12px;display:flex;gap:10px;">
                    <button id="sendReplyBtn" onclick="sendReply()"
                        style="flex:1;height:44px;background:linear-gradient(135deg,var(--ind-d),var(--ind));color:#fff;border:none;border-radius:12px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 6px 18px rgba(99,102,241,.22);transition:.2s;">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                    <button onclick="closeMessageModal()"
                        style="height:44px;padding:0 18px;background:#f1f5f9;color:var(--sl-dk);border:none;border-radius:12px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;">
                        Cancel
                    </button>
                </div>
                <div id="replyStatus" style="display:none;margin-top:12px;padding:12px 14px;border-radius:12px;font-size:12px;font-weight:500;text-align:center;"></div>
            </div>
        </div>
    </div>

    <!-- ══ PAGE ══ -->
    <section style="padding:36px 0;">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div style="display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start;">

                <!-- ── SIDEBAR ── -->
                <aside class="sidebar">
                    <div style="display:flex;align-items:center;gap:12px;padding-bottom:16px;border-bottom:1px solid var(--bdr);margin-bottom:14px;">
                        <div class="adm-avatar"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <p style="font-size:13px;font-weight:700;color:var(--sl-dk);margin:0;">Administrator</p>
                            <p style="font-size:11px;color:var(--sl-mu);margin:3px 0 0;display:flex;align-items:center;gap:5px;">
                                <span style="width:6px;height:6px;background:#1D9E75;border-radius:50%;display:inline-block;"></span> Admin Panel
                            </p>
                        </div>
                    </div>

                    <p class="nav-section">Navigation</p>
                    <nav style="display:flex;flex-direction:column;gap:0;">
                        <a href="admin.php?page=dashboard" class="nav-link <?= $page === 'dashboard'    ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                        <a href="admin.php?page=users" class="nav-link <?= $page === 'users'        ? 'active' : '' ?>"><i class="fas fa-users"></i> Users <span class="nbadge-ind"><?= $stats['users'] ?></span></a>
                        <a href="admin.php?page=candidates" class="nav-link <?= $page === 'candidates'   ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Candidates</a>
                        <a href="admin.php?page=companies" class="nav-link <?= $page === 'companies'    ? 'active' : '' ?>"><i class="fas fa-building"></i> Companies</a>
                        <a href="admin.php?page=jobs" class="nav-link <?= $page === 'jobs'         ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> Jobs</a>
                        <a href="admin.php?page=applications" class="nav-link <?= $page === 'applications' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Applications</a>
                        <a href="admin.php?page=messages" class="nav-link <?= $page === 'messages'     ? 'active' : '' ?>">
                            <i class="fas fa-envelope"></i> Messages
                            <?php if ($stats['unread_msgs'] > 0): ?>
                                <span class="nbadge"><?= $stats['unread_msgs'] ?></span>
                            <?php endif; ?>
                        </a>
                    </nav>

                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--bdr);">
                        <p class="nav-section">Quick stats</p>
                        <div class="qs-row"><span style="color:var(--sl-lt)">Active jobs</span><span style="font-weight:600;color:var(--sl-dk)"><?= $stats['jobs'] ?></span></div>
                        <div class="qs-row"><span style="color:var(--sl-lt)">Applications</span><span style="font-weight:600;color:#d97706"><?= $stats['applications'] ?></span></div>
                        <div class="qs-row"><span style="color:var(--sl-lt)">Unread msgs</span><span style="font-weight:600;color:#dc2626"><?= $stats['unread_msgs'] ?></span></div>
                    </div>
                </aside>


                <div>

                    <!-- PAGE HEADER -->
                    <div style="margin-bottom:20px;">
                        <div class="pg-tag"><i class="fas fa-shield-alt" style="font-size:9px;"></i> Admin</div>
                        <h1 style="font-size:26px;font-weight:700;color:var(--sl-dk);margin:0 0 4px;">
                            <?= ucfirst(str_replace('_', ' ', $page)) ?>
                        </h1>
                        <p style="font-size:13px;color:var(--sl-lt);margin:0;">
                            <?= $pageDescriptions[$page] ?? 'Manage platform content' ?>
                        </p>
                    </div>

                    <!-- ══════════════ DASHBOARD ══════════════ -->
                    <?php if ($page === 'dashboard'): ?>

                        <!-- Platform bar -->
                        <div class="profile-bar">

                            <div style="width:42px;height:42px;border-radius:14px;
    background:var(--ind-s);display:flex;align-items:center;
    justify-content:center;color:var(--ind);font-size:18px;flex-shrink:0;">
                                <i class="fas fa-database"></i>
                            </div>

                            <div style="flex:1;">

                                <div style="display:flex;justify-content:space-between;
        align-items:center;margin-bottom:7px;">

                                    <span style="font-size:12px;font-weight:500;color:var(--sl-dk);">
                                        Platform health score
                                    </span>

                                    <span style="font-size:12px;font-weight:700;color:var(--ind);">
                                        <?= $score ?>%
                                    </span>

                                </div>

                                <div class="prog-track">
                                    <div class="prog-fill"
                                        style="width:<?= $score ?>%;"></div>
                                </div>

                                <div style="font-size:10px;color:var(--sl-mu);margin-top:5px;">
                                    <i class="fas fa-info-circle"></i>
                                    <?= $statusText ?>
                                </div>

                            </div>

                        </div>

                        <!-- Stats -->
                        <div class="stats-grid">
                            <?php
                            $statItems = [
                                ['fas fa-users',        'Total Users',  $stats['users'],        'b-up',  'All registered'],
                                ['fas fa-user-graduate', 'Candidates',   $stats['candidates'],   'b-up',  'Job seekers'],
                                ['fas fa-building',     'Companies',    $stats['companies'],    'b-neu', 'Employers'],
                                ['fas fa-briefcase',    'Active Jobs',  $stats['jobs'],         'b-up',  'Open positions'],
                                ['fas fa-file-alt',     'Applications', $stats['applications'], 'b-warn', 'Total submitted'],
                            ];
                            foreach ($statItems as [$icon, $label, $val, $badge, $sub]):
                            ?>
                                <div class="sc">
                                    <div class="sc-icon" style="position:relative;z-index:1;"><i class="<?= $icon ?>"></i></div>
                                    <p class="sc-label"><?= $label ?></p>
                                    <h3 class="sc-num"><?= $val ?></h3>
                                    <div class="sc-badge <?= $badge ?>"><?= $sub ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Main grid: activity + inbox -->
                        <div class="main-grid">

                            <!-- Recent Activity -->
                            <div class="card">
                                <div class="ch">
                                    <span class="ct">Recent activity</span>
                                    <a href="admin.php?page=dashboard&activity_page=<?= $activityPage + 1 ?>" class="cl">Show more <i class="fas fa-arrow-right"></i></a>
                                </div>
                                <?php foreach ($recentActivity as $act): ?>
                                    <div class="act-row">
                                        <div class="act-ico <?= $act['type'] ?>">
                                            <i class="fas <?= $act['type'] === 'user' ? 'fa-user' : ($act['type'] === 'job' ? 'fa-briefcase' : 'fa-file-alt') ?>"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:12px;font-weight:600;color:var(--sl-dk);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                <?= ucfirst($act['type']) ?> — <?= e($act['info']) ?>
                                            </div>
                                            <div style="font-size:11px;color:var(--sl-mu);margin-top:2px;">
                                                <?= date('M d, Y · H:i', strtotime($act['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($totalPages > 1): ?>
                                    <p style="font-size:10px;color:var(--sl-mu);text-align:center;margin-top:10px;">Page <?= $activityPage ?> of <?= $totalPages ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Inbox / Messages Timeline -->
                            <?php
                            $inboxMsgs = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
                            ?>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct">Inbox</span>
                                    <a href="admin.php?page=messages" class="cl">View all <i class="fas fa-arrow-right"></i></a>
                                </div>
                                <div class="timeline">
                                    <?php foreach ($inboxMsgs as $msg): ?>
                                        <?php
                                        $isRead    = (bool)$msg['is_read'];
                                        $itemClass = $isRead ? '' : 'tl-unread';
                                        $dotClass  = $isRead ? 'tl-dot-read' : 'tl-dot-unread';
                                        ?>
                                        <a href="#" onclick="openMessageModal(<?= htmlspecialchars(json_encode($msg)) ?>);return false;" class="tl-item <?= $itemClass ?>">
                                            <div class="tl-left">
                                                <div class="tl-dot <?= $dotClass ?>"><i class="fas fa-envelope"></i></div>
                                                <div class="tl-line"></div>
                                            </div>
                                            <div class="tl-body">
                                                <div style="display:flex;align-items:center;gap:5px;margin-bottom:2px;">
                                                    <span class="tl-title <?= $isRead ? '' : 'tl-title-unread' ?>"><?= e($msg['full_name']) ?></span>
                                                    <?php if (!$isRead): ?><span class="tl-unread-dot"></span><?php endif; ?>
                                                </div>
                                                <div class="tl-sub <?= $isRead ? '' : 'tl-sub-unread' ?>" style="-webkit-line-clamp:1;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;">
                                                    <?= e($msg['subject']) ?>
                                                </div>
                                                <div class="tl-time"><?= date('M d · H:i', strtotime($msg['created_at'])) ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>

                        <!-- Charts -->
                        <div class="charts-grid">
                            <div class="card">
                                <div class="ch">
                                    <span class="ct">Users distribution</span>
                                    <span style="font-size:11px;color:var(--sl-mu);">By role</span>
                                </div>
                                <div style="position:relative;height:240px;"><canvas id="usersByRoleChart"></canvas></div>
                            </div>
                            <div class="card">
                                <div class="ch">
                                    <span class="ct">Jobs by location</span>
                                    <span style="font-size:11px;color:var(--sl-mu);">Top cities</span>
                                </div>
                                <div style="position:relative;height:240px;"><canvas id="jobsByCityChart"></canvas></div>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="cta">
                            <div class="cta-ico"><i class="fas fa-rocket"></i></div>
                            <div style="flex:1;">
                                <div class="cta-ttl">Platform running smoothly</div>
                                <div class="cta-sub">All systems nominal — manage users, jobs and messages from one place.</div>
                            </div>
                            <div style="display:flex;gap:10px;flex-shrink:0;">
                                <a href="admin.php?page=jobs" class="cta-btn cta-btn-pri">Manage Jobs</a>
                                <a href="admin.php?page=messages" class="cta-btn">View Messages</a>
                            </div>
                        </div>

                        <script>
                            const IND = {
                                main: '#6366f1',
                                dark: '#4f46e5',
                                soft: '#ede9fe',
                                mid: '#c7d2fe',
                                grid: '#f1f5f9',
                                text: '#334155'
                            };
                            const roleData = <?= $usersByRoleJson ?>;
                            new Chart(document.getElementById('usersByRoleChart'), {
                                type: 'doughnut',
                                data: {
                                    labels: roleData.map(r => r.role),
                                    datasets: [{
                                        data: roleData.map(r => r.count),
                                        backgroundColor: [IND.dark, IND.main, '#a5b4fc'],
                                        borderColor: '#ffffff',
                                        borderWidth: 3,
                                        borderRadius: 4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                font: {
                                                    size: 12,
                                                    weight: '500'
                                                },
                                                color: IND.text,
                                                padding: 14,
                                                usePointStyle: true
                                            }
                                        }
                                    }
                                }
                            });
                            const cityData = <?= $jobsByCityJson ?>;
                            new Chart(document.getElementById('jobsByCityChart'), {
                                type: 'bar',
                                data: {
                                    labels: cityData.map(r => r.city),
                                    datasets: [{
                                        label: 'Jobs',
                                        data: cityData.map(r => r.count),
                                        backgroundColor: IND.main,
                                        borderRadius: 6,
                                        borderSkipped: false
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        x: {
                                            ticks: {
                                                color: IND.text,
                                                font: {
                                                    size: 11
                                                }
                                            },
                                            grid: {
                                                color: IND.grid
                                            }
                                        },
                                        y: {
                                            ticks: {
                                                color: IND.text,
                                                font: {
                                                    size: 11
                                                }
                                            },
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            });
                        </script>

                    <?php endif; ?>

                    <!-- ══════════════ DATA PAGES ══════════════ -->
                    <?php if ($page !== 'dashboard' && $page !== 'messages'): ?>

                        <div class="srch-bar">
                            <form method="GET" style="display:contents;">
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <div class="srch-inp">
                                    <i class="fas fa-search"></i>
                                    <input type="text" name="search" placeholder="Search..." value="<?= e($search) ?>">
                                </div>
                                <button type="submit" class="srch-btn"><i class="fas fa-search"></i> Search</button>
                                <?php if ($search): ?>
                                    <a href="admin.php?page=<?= $page ?>" class="srch-clr"><i class="fas fa-times"></i> Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="tbl-wrap">
                            <div class="tbl-head">
                                <p style="font-size:14px;font-weight:700;color:var(--sl-dk);margin:0;"><?= ucfirst($page) ?> List</p>
                                <p style="font-size:11px;color:var(--sl-mu);margin:3px 0 0;"><?= count($data) ?> record<?= count($data) !== 1 ? 's' : '' ?> found</p>
                            </div>
                            <div style="overflow-x:auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php if (in_array($page, ['users', 'candidates', 'companies'])): ?>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <?php if ($page === 'users'): ?><th>Role</th><?php endif; ?>
                                                <th>Joined</th>
                                                <th>Actions</th>
                                            <?php elseif ($page === 'jobs'): ?>
                                                <th>ID</th>
                                                <th>Company</th>
                                                <th>Title</th>
                                                <th>City</th>
                                                <th>Posted</th>
                                                <th>Actions</th>
                                            <?php elseif ($page === 'applications'): ?>
                                                <th>ID</th>
                                                <th>Candidate</th>
                                                <th>Job</th>
                                                <th>Date</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data)): ?>
                                            <tr>
                                                <td colspan="6" class="empty-cell"><i class="fas fa-inbox"></i>No records found</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <?php if (in_array($page, ['users', 'candidates', 'companies'])): ?>
                                                    <td style="font-size:11px;color:var(--sl-mu);">#<?= $row['id'] ?></td>
                                                    <td>
                                                        <div style="display:flex;align-items:center;gap:8px;">
                                                            <div class="av"><?= strtoupper(substr($row['name'] ?? '?', 0, 2)) ?></div>
                                                            <span style="font-weight:600;color:var(--sl-dk);"><?= e($row['name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td><?= e($row['email']) ?></td>
                                                    <?php if ($page === 'users'): ?>
                                                        <td><span class="role-badge <?= $row['role'] ?>"><?= e($row['role']) ?></span></td>
                                                    <?php endif; ?>
                                                    <td style="color:var(--sl-mu);font-size:12px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                    <td>
                                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                                            <?php if ($page === 'candidates'): ?>
                                                                <button class="abtn abtn-view" onclick="showCandidateProfile(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-eye"></i> View</button>
                                                            <?php elseif ($page === 'companies'): ?>
                                                                <button class="abtn abtn-view" onclick="showCompanyProfile(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-eye"></i> View</button>
                                                            <?php endif; ?>
                                                            <button class="abtn abtn-del" onclick="openDeleteModal('user',<?= $row['id'] ?>,'<?= e($row['name']) ?>','<?= e($row['email']) ?>','<?= $page ?>')"><i class="fas fa-trash"></i> Delete</button>
                                                        </div>
                                                    </td>
                                                <?php elseif ($page === 'jobs'): ?>
                                                    <td style="font-size:11px;color:var(--sl-mu);">#<?= $row['id'] ?></td>
                                                    <td><?= e($row['company_name'] ?? 'N/A') ?></td>
                                                    <td style="font-weight:600;color:var(--sl-dk);"><?= e($row['title']) ?></td>
                                                    <td><?= e($row['city']) ?></td>
                                                    <td style="color:var(--sl-mu);font-size:12px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                    <td>
                                                        <div style="display:flex;gap:6px;">
                                                            <button class="abtn abtn-view" onclick="showJobDetails(<?= htmlspecialchars(json_encode($row)) ?>)"><i class="fas fa-eye"></i> View</button>
                                                            <button class="abtn abtn-del" onclick="openDeleteModal('job',<?= $row['id'] ?>,'<?= e($row['title']) ?>','<?= e($row['company_name'] ?? 'Unknown') ?>','jobs')"><i class="fas fa-trash"></i> Delete</button>
                                                        </div>
                                                    </td>
                                                <?php elseif ($page === 'applications'): ?>
                                                    <td style="font-size:11px;color:var(--sl-mu);">#<?= $row['id'] ?></td>
                                                    <td style="font-weight:600;color:var(--sl-dk);"><?= e($row['candidate_name'] ?? 'N/A') ?></td>
                                                    <td><?= e($row['job_title'] ?? 'N/A') ?></td>
                                                    <td style="color:var(--sl-mu);font-size:12px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>

                    <!-- ══════════════ MESSAGES ══════════════ -->
                    <?php if ($page === 'messages'): ?>

                        <div class="srch-bar">
                            <form method="GET" style="display:contents;">
                                <input type="hidden" name="page" value="messages">
                                <div class="srch-inp">
                                    <i class="fas fa-search"></i>
                                    <input type="text" name="search" placeholder="Search by name, email or subject..." value="<?= e($search) ?>">
                                </div>
                                <button type="submit" class="srch-btn"><i class="fas fa-search"></i> Search</button>
                                <?php if ($search): ?>
                                    <a href="admin.php?page=messages" class="srch-clr"><i class="fas fa-times"></i> Clear</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="tbl-wrap">
                            <div class="tbl-head">
                                <p style="font-size:14px;font-weight:700;color:var(--sl-dk);margin:0;">Messages</p>
                                <p style="font-size:11px;color:var(--sl-mu);margin:3px 0 0;">
                                    <?= count($data) ?> message<?= count($data) !== 1 ? 's' : '' ?> found
                                    <?php if ($stats['unread_msgs'] > 0): ?>
                                        · <span style="color:#ef4444;font-weight:700;"><?= $stats['unread_msgs'] ?> unread</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="overflow-x:auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data)): ?>
                                            <tr>
                                                <td colspan="7" class="empty-cell"><i class="fas fa-envelope-open"></i>No messages found</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($data as $msg): ?>
                                            <tr style="<?= !$msg['is_read'] ? 'background:#fafafe;' : '' ?>">
                                                <td style="font-size:11px;color:var(--sl-mu);">#<?= $msg['id'] ?></td>
                                                <td>
                                                    <?php if (!$msg['is_read']): ?>
                                                        <span class="stag s-new" style="display:inline-flex;align-items:center;gap:4px;">
                                                            <span style="width:6px;height:6px;background:#ef4444;border-radius:50%;"></span> New
                                                        </span>
                                                    <?php elseif ($msg['replied_at']): ?>
                                                        <span class="stag s-acc" style="display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-check" style="font-size:9px;"></i> Replied</span>
                                                    <?php else: ?>
                                                        <span class="stag s-read">Read</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight:600;color:var(--sl-dk);"><?= e($msg['full_name']) ?></td>
                                                <td style="color:var(--sl-lt);font-size:12px;"><?= e($msg['email']) ?></td>
                                                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($msg['subject']) ?></td>
                                                <td style="color:var(--sl-mu);font-size:11px;white-space:nowrap;">
                                                    <?= date('M d, Y', strtotime($msg['created_at'])) ?>
                                                    <span style="display:block;font-size:10px;color:#cbd5e1;"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                                </td>
                                                <td>
                                                    <button class="abtn abtn-view" onclick="openMessageModal(<?= htmlspecialchars(json_encode($msg)) ?>)">
                                                        <i class="fas fa-envelope-open-text"></i> View & Reply
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <script>
        /* ── DELETE MODAL ── */
        function openDeleteModal(type, id, name, detail, page) {
            const labels = type === 'job' ? ['Job Title', 'Company'] : ['Name', 'Email'];
            document.getElementById('modalItemType').textContent =
                type === 'job' ? 'Job' : page === 'candidates' ? 'Candidate' : page === 'companies' ? 'Company' : 'User';
            document.getElementById('modalDetails').innerHTML = `
            <div class="modal-di"><span class="modal-dl">${labels[0]}:</span><span class="modal-dv">${name}</span></div>
            <div class="modal-di"><span class="modal-dl">${labels[1]}:</span><span class="modal-dv">${detail}</span></div>`;
            document.getElementById('modalDeleteLink').href =
                type === 'job' ? `admin.php?page=jobs&delete_job=${id}` : `admin.php?page=${page}&delete_user=${id}`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
        }

        /* ── PROFILE MODALS ── */
        function buildProfileHTML(avatarContent, title, subtitle, fields) {
            const rows = fields.map(([label, val]) => val ? `
            <div style="background:#f8fafc;border-radius:14px;padding:12px 14px;border:1px solid var(--bdr);margin-bottom:8px;">
                <p style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sl-mu);margin:0 0 4px;">${label}</p>
                <p style="font-size:13px;font-weight:500;color:var(--sl-dk);margin:0;">${val}</p>
            </div>` : '').join('');
            return `
            <div style="text-align:center;margin-bottom:20px;">
                <div style="width:72px;height:72px;background:linear-gradient(135deg,var(--ind-d),var(--ind));border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:26px;font-weight:700;box-shadow:0 8px 24px rgba(99,102,241,.28);">${avatarContent}</div>
                <h2 style="font-size:18px;font-weight:700;color:var(--sl-dk);margin:0 0 4px;">${title}</h2>
                <p style="font-size:12px;color:var(--sl-mu);margin:0;">${subtitle}</p>
            </div>${rows}`;
        }

        function showCandidateProfile(c) {
            document.getElementById('profileContent').innerHTML = buildProfileHTML(
                c.name.charAt(0).toUpperCase(), c.name, 'Candidate Profile',
                [
                    ['Email', c.email],
                    ['Phone', c.phone],
                    ['Location', c.location],
                    ['Summary', c.summary],
                    ['Member Since', new Date(c.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })]
                ]
            );
            document.getElementById('profileModal').classList.add('active');
        }

        function showCompanyProfile(c) {
            document.getElementById('profileContent').innerHTML = buildProfileHTML(
                '<i class="fas fa-building"></i>', c.name, 'Company Profile',
                [
                    ['Email', c.email],
                    ['Phone', c.phone],
                    ['Location', c.location],
                    ['Description', c.description],
                    ['Member Since', new Date(c.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })]
                ]
            );
            document.getElementById('profileModal').classList.add('active');
        }

        function showJobDetails(j) {
            document.getElementById('profileContent').innerHTML = buildProfileHTML(
                '<i class="fas fa-briefcase"></i>', j.title, j.company_name || 'Unknown Company',
                [
                    ['Location', j.city],
                    ['Salary', j.salary],
                    ['Description', j.description],
                    ['Requirements', j.requirements],
                    ['Posted', new Date(j.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })]
                ]
            );
            document.getElementById('profileModal').classList.add('active');
        }

        /* ── MESSAGE MODAL ── */
        let currentMsgId = null;

        function openMessageModal(msg) {
            currentMsgId = msg.id;
            document.getElementById('modalMsgSubject').textContent = msg.subject;
            document.getElementById('modalMsgMeta').textContent = 'Received ' + new Date(msg.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalMsgName').textContent = msg.full_name;
            document.getElementById('modalMsgEmail').textContent = msg.email;
            document.getElementById('modalMsgBody').textContent = msg.message;
            document.getElementById('replyText').value = '';
            const st = document.getElementById('replyStatus');
            st.style.display = 'none';
            if (msg.replied_at) {
                st.style.display = 'block';
                st.style.background = '#f0fdf4';
                st.style.color = '#15803d';
                st.style.border = '1px solid #bbf7d0';
                st.textContent = '✓ Already replied on ' + new Date(msg.replied_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            document.getElementById('messageModal').classList.add('active');
            if (!msg.is_read) {
                fetch('admin_mark_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + msg.id
                });
            }
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('active');
            location.reload();
        }

        function sendReply() {
            const reply = document.getElementById('replyText').value.trim();
            if (!reply) {
                document.getElementById('replyText').style.borderColor = '#ef4444';
                return;
            }
            const btn = document.getElementById('sendReplyBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            const fd = new FormData();
            fd.append('id', currentMsgId);
            fd.append('reply', reply);
            fetch('admin_reply.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(data => {
                const st = document.getElementById('replyStatus');
                st.style.display = 'block';
                if (data.success) {
                    st.style.background = '#f0fdf4';
                    st.style.color = '#15803d';
                    st.style.border = '1px solid #bbf7d0';
                    st.textContent = '✓ Reply sent successfully!';
                    btn.innerHTML = '<i class="fas fa-check"></i> Sent!';
                    btn.style.background = 'linear-gradient(135deg,#059669,#10b981)';
                    document.getElementById('replyText').disabled = true;
                } else {
                    st.style.background = '#fef2f2';
                    st.style.color = '#dc2626';
                    st.style.border = '1px solid #fecaca';
                    st.textContent = '✗ Failed: ' + (data.error || 'Unknown error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
                }
            }).catch(() => {
                const st = document.getElementById('replyStatus');
                st.style.display = 'block';
                st.style.background = '#fef2f2';
                st.style.color = '#dc2626';
                st.style.border = '1px solid #fecaca';
                st.textContent = '✗ Network error. Please try again.';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
            });
        }

        /* ── CLOSE ON BACKDROP ── */
        ['deleteModal', 'profileModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });
        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) closeMessageModal();
        });
    </script>

</body>

</html>