<?php
require_once 'config.php';
require_once 'functions.php';

if (isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin') {
    header('Location: admin.php');
    exit;
}

global $pdo;

$current_page = basename($_SERVER['PHP_SELF'], '.php');
if ($current_page === 'index') $current_page = 'index';

$search     = trim($_GET['q']          ?? '');
$city       = trim($_GET['city']       ?? '');
$specialty  = trim($_GET['specialty']  ?? '');
$experience = trim($_GET['experience'] ?? '');

$jobs = [];

$cityOptions       = getCityOptions();
$categoryOptions   = getCategoryOptions();
$experienceOptions = getExperienceOptions();

$globalStats = [
    'total_jobs'         => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'total_companies'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company'")->fetchColumn(),
    'total_candidates'   => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'candidate'")->fetchColumn(),
    'total_applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn()
];

if (isLoggedIn() && $_SESSION['user']['role'] === 'company' && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    deleteJob($_GET['delete'], $_SESSION['user']['id']);
    header('Location: index.php');
    exit;
}

if (isLoggedIn()) {
    if ($_SESSION['user']['role'] === 'candidate') {
        $profile = getCandidateProfile($_SESSION['user']['id']);
        $jobs    = getRecommendedJobs($profile, $search, $city, $specialty, '', $experience);
    } else {
        $jobs        = searchJobs($search, $city, $specialty, '', $experience);
        $companyJobs = getCompanyJobs($_SESSION['user']['id']);
        $postedCount = count($companyJobs);

        $applicationsReceived = 0;
        foreach ($companyJobs as $jobItem) {
            $applicationsReceived += count(getApplicantsByJob($jobItem['id']));
        }

        $recentApplicants = [];
        foreach ($companyJobs as $jobItem) {
            foreach (getApplicantsByJob($jobItem['id']) as $applicant) {
                if (date('Y-m-d', strtotime($applicant['created_at'] ?? 'now')) === date('Y-m-d')) {
                    $applicant['job_title'] = $jobItem['title'];
                    $recentApplicants[]     = $applicant;
                }
            }
        }
        $recentApplicants = array_slice($recentApplicants, 0, 5);
    }
} else {
    if ($search || $city || $specialty || $experience) {
        $jobs = searchJobs($search, $city, $specialty, '', $experience);
    } else {
        $jobs = getRecentlyAddedJobs();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "JobDZ | Algeria's #1 Job Platform";
    include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        :root {
            --ink: #0f172a;
            --ink-2: #1e293b;
            --ink-3: #475569;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #ffffff;
            --bg: #f8fafc;
            --accent: #6366f1;
            --accent2: #4f46e5;
            --accent-lt: #ede9fe;
            --green: #1D9E75;
            --green-lt: #E1F5EE;
            --amber: #d97706;
            --amber-lt: #fef3c7;
            --red: #dc2626;
            --red-lt: #fee2e2;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        /* ── Utilities ── */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 100px;
        }

        .tag-accent {
            background: var(--accent-lt);
            color: var(--accent);
        }

        .tag-green {
            background: var(--green-lt);
            color: var(--green);
        }

        .tag-amber {
            background: var(--amber-lt);
            color: var(--amber);
        }

        .tag-surface {
            background: var(--surface);
            color: var(--ink-3);
            border: 1px solid var(--line);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            padding: 10px 22px;
            cursor: pointer;
            text-decoration: none;
            transition: transform .2s, box-shadow .2s, background .15s;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-accent {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 8px 24px rgba(99, 102, 241, .25);
        }

        .btn-accent:hover {
            background: var(--accent2);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .35);
            color: #fff;
        }

        .btn-ghost {
            background: var(--surface);
            color: var(--ink);
            border: 1.5px solid var(--line);
        }

        .btn-ghost:hover {
            background: var(--bg);
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-sm {
            font-size: 12px;
            padding: 8px 16px;
            border-radius: 10px;
        }

        /* ── Section label ── */
        .section-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .section-eyebrow::before {
            content: '';
            display: block;
            width: 24px;
            height: 2px;
            background: var(--accent);
            border-radius: 2px;
        }

        .hero {
            position: relative;
            padding: 90px 0 70px;
            overflow: hidden;
            background: var(--surface);
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 55% at 85% 20%, rgba(99, 102, 241, .08) 0%, transparent 65%),
                radial-gradient(ellipse 40% 40% at 10% 80%, rgba(99, 102, 241, .06) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(99, 102, 241, .03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, .03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .hero-title {
            font-size: clamp(34px, 4.5vw, 52px);
            font-weight: 700;
            line-height: 1.15;
            color: var(--ink);
            margin-bottom: 16px;
        }

        .hero-title em {
            font-style: normal;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 28px;
            max-width: 460px;
            font-weight: 500;
        }

        .hero-search {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 18px;
            padding: 8px;
            display: flex;
            gap: 6px;
            box-shadow: 0 8px 32px rgba(15, 23, 42, .07);
            flex-wrap: wrap;
        }

        .hero-search-field {
            flex: 1;
            min-width: 130px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--bg);
            border-radius: 10px;
        }

        .hero-search-field i {
            color: var(--muted);
            font-size: 13px;
        }

        .hero-search-field input,
        .hero-search-field select {
            background: transparent;
            border: none;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: var(--ink);
            width: 100%;
        }

        .hero-search-field input::placeholder {
            color: var(--muted);
        }

        .hero-search-btn {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 6px 20px rgba(99, 102, 241, .25);
        }

        .hero-search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(99, 102, 241, .35);
        }

        .hero-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
            align-items: center;
        }

        .hero-chip-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
        }

        .hero-chip {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 13px;
            border-radius: 100px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink-3);
            text-decoration: none;
            transition: border-color .15s, color .15s;
        }

        .hero-chip:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* Hero right — floating cards */
        .hero-visual {
            position: relative;
            height: 420px;
        }

        .float-card {
            position: absolute;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, .08);
            animation: floatUp 6s ease-in-out infinite;
        }

        .float-card:nth-child(2) {
            animation-delay: -2s;
        }

        .float-card:nth-child(3) {
            animation-delay: -4s;
        }

        @keyframes floatUp {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .fc-job {
            top: 20px;
            left: 10px;
            width: 270px;
        }

        .fc-stat {
            top: 170px;
            right: 10px;
            width: 180px;
        }

        .fc-match {
            bottom: 30px;
            left: 50px;
            width: 240px;
        }

        .fc-company-logo {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--accent-lt);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .fc-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 3px;
        }

        .fc-sub {
            font-size: 12px;
            color: var(--muted);
        }

        .fc-tags {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .fc-big-num {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--ink);
        }

        .fc-big-lbl {
            font-size: 12px;
            color: var(--muted);
            margin-top: 3px;
        }

        .fc-trend {
            font-size: 11px;
            font-weight: 700;
            color: var(--green);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .progress-ring {
            margin-top: 10px;
        }

        .progress-bar-track {
            height: 5px;
            background: var(--bg);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            width: 78%;
        }

        .stats-bar {
            background: var(--ink-2);
            padding: 24px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }

        .stats-item {
            text-align: center;
            padding: 14px 20px;
            position: relative;
        }

        .stats-item+.stats-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            bottom: 20%;
            width: 1px;
            background: rgba(255, 255, 255, .1);
        }

        .stats-num {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }

        .stats-lbl {
            font-size: 12px;
            color: rgba(255, 255, 255, .5);
            margin-top: 5px;
        }

        .jobs-section {
            padding: 80px 0;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 40px;
        }

        .jcard {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            transition: transform .25s, box-shadow .25s, border-color .25s;
            position: relative;
            overflow: hidden;
        }

        .jcard::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            opacity: 0;
            transition: opacity .25s;
        }

        .jcard:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(99, 102, 241, .12);
            border-color: #d6d3f0;
        }

        .jcard:hover::before {
            opacity: 1;
        }

        .jcard-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .jcard-logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            flex-shrink: 0;
            overflow: hidden;
            background: var(--accent-lt);
            border: 1px solid #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
        }

        .jcard-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .jcard-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .jcard-company {
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-3);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .jcard-company i {
            color: var(--accent);
            font-size: 10px;
        }

        .jcard-city {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        .jcard-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }

        .jcard-tag {
            font-size: 11px;
            font-weight: 600;
            padding: 5px 11px;
            border-radius: 100px;
        }

        .jcard-tag-accent {
            background: var(--accent-lt);
            color: var(--accent);
        }

        .jcard-tag-gray {
            background: var(--bg);
            color: var(--ink-3);
            border: 1px solid var(--line);
        }

        .jcard-tag-green {
            background: var(--green-lt);
            color: var(--green);
        }

        .jcard-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        .jcard-time {
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .jcard-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--ink);
            color: #fff;
            border-radius: 10px;
            padding: 8px 16px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: background .15s, transform .15s;
        }

        .jcard-btn:hover {
            background: var(--accent);
            transform: translateX(2px);
            color: #fff;
        }

        .jcard-btn-pending {
            background: var(--amber);
        }

        .jcard-btn-pending:hover {
            background: #b45309;
        }

        .jcard-btn-accepted {
            background: var(--green);
        }

        .jcard-btn-accepted:hover {
            background: #047857;
        }

        .jcard-btn-rejected {
            background: var(--red);
        }

        .jcard-btn-rejected:hover {
            background: #b91c1c;
        }

        .hiw-section {
            padding: 80px 0;
            background: var(--surface);
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .hiw-tabs {
            display: inline-flex;
            gap: 3px;
            background: var(--bg);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 48px;
        }

        .hiw-tab-btn {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            color: var(--muted);
            background: transparent;
            transition: all .2s;
        }

        .hiw-tab-btn.active {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 16px rgba(99, 102, 241, .25);
        }

        .hiw-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            position: relative;
        }

        .hiw-steps::before {
            content: '';
            position: absolute;
            top: 46px;
            left: calc(16% + 24px);
            right: calc(16% + 24px);
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            opacity: .2;
        }

        .hiw-step {
            background: var(--bg);
            border: 1.5px solid var(--line);
            border-radius: 20px;
            padding: 28px;
            transition: transform .2s, box-shadow .2s;
            text-align: center;
        }

        .hiw-step:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(99, 102, 241, .08);
        }

        .hiw-step-num {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--accent-lt);
            color: var(--accent);
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .hiw-step-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 8px;
        }

        .hiw-step-desc {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        .companies-section {
            padding: 80px 0;
        }

        .companies-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 40px;
        }

        .co-card {
            background: var(--surface);
            border: 1.5px solid var(--line);
            border-radius: 20px;
            padding: 24px;
            transition: transform .25s, box-shadow .25s, border-color .25s;
            position: relative;
            overflow: hidden;
        }

        .co-card::after {
            content: '';
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(99, 102, 241, .06) 0%, transparent 70%);
            border-radius: 50%;
        }

        .co-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(99, 102, 241, .1);
            border-color: #d6d3f0;
        }

        .co-logo {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            overflow: hidden;
            background: var(--accent-lt);
            border: 1px solid #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            font-size: 19px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 14px;
        }

        .co-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .co-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .co-verified {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            color: var(--green);
            margin-bottom: 12px;
        }

        .co-verified span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--green);
        }

        .co-jobs-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 16px;
        }

        .co-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .co-hiring {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .features-section {
            padding: 80px 0;
            background: var(--ink-2);
            position: relative;
            overflow: hidden;
        }

        .features-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 10% 50%, rgba(99, 102, 241, .15) 0%, transparent 50%),
                radial-gradient(circle at 90% 20%, rgba(79, 70, 229, .1) 0%, transparent 50%);
            pointer-events: none;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 44px;
        }

        .feat-card {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 18px;
            padding: 24px;
            transition: background .2s, transform .2s;
        }

        .feat-card:hover {
            background: rgba(255, 255, 255, .08);
            transform: translateY(-3px);
        }

        .feat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(99, 102, 241, .25);
            color: #a5b4fc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 14px;
        }

        .feat-title {
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }

        .feat-desc {
            font-size: 13px;
            color: rgba(255, 255, 255, .5);
            line-height: 1.6;
        }

        .about-section {
            padding: 100px 0;
            background: var(--surface);
        }

        .about-section img {
            transition: transform .4s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow .4s ease;
        }

        .about-section img:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 24px 64px rgba(99, 102, 241, .22) !important;
        }

        @keyframes imageSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .about-section>.container>div>div img {
            animation: imageSlideUp .6s ease both;
        }

        .about-section>.container>div>div img:nth-child(1) {
            animation-delay: .1s;
        }

        .about-section>.container>div>div img:nth-child(2) {
            animation-delay: .2s;
        }

        .about-section>.container>div>div img:nth-child(3) {
            animation-delay: .3s;
        }

        .about-section>.container>div>div img:nth-child(4) {
            animation-delay: .4s;
        }

        .photo-banner {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            height: 300px;
            display: flex;
            align-items: flex-end;
        }

        .photo-banner img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, .7) 0%, rgba(15, 23, 42, .1) 60%, transparent 100%);
        }

        .photo-banner-content {
            position: relative;
            z-index: 2;
            padding: 30px;
            color: #fff;
        }


        .cta-section {
            padding: 70px 0;
        }

        .cta-box {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
            border-radius: 28px;
            padding: 60px 52px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-box::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, .1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(0, 0, 0, .06) 0%, transparent 50%);
        }

        .cta-box::after {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 300px;
            height: 300px;
            border: 50px solid rgba(255, 255, 255, .04);
            border-radius: 50%;
        }

        .cta-box * {
            position: relative;
            z-index: 2;
        }

        .cta-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .65);
            margin-bottom: 12px;
        }

        .cta-title {
            font-family: 'Poppins', sans-serif;
            font-size: 36px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
            line-height: 1.1;
        }

        .cta-desc {
            font-size: 15px;
            color: rgba(255, 255, 255, .75);
            max-width: 500px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        .cta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 28px;
        }

        .cta-pill {
            background: rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .85);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, .15);
        }

        .cta-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .btn-white {
            background: #fff;
            color: var(--accent);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            padding: 11px 26px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .15);
            transition: transform .2s, box-shadow .2s;
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .2);
            color: var(--accent2);
        }

        .btn-outline-white {
            background: transparent;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, .3);
            border-radius: 12px;
            padding: 11px 26px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: background .15s, border-color .15s;
        }

        .btn-outline-white:hover {
            background: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .6);
            color: #fff;
        }

        .cats-section {
            padding: 90px 0;
            background: linear-gradient(135deg, var(--surface) 0%, #f0f4ff 100%);
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            position: relative;
        }

        .cats-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(99, 102, 241, .06) 0%, transparent 50%);
            pointer-events: none;
        }

        .cats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-top: 42px;
            position: relative;
            z-index: 1;
        }

        .cat-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 24px 18px;
            border-radius: 18px;
            background: var(--surface);
            border: 1.5px solid var(--line);
            text-decoration: none;
            color: var(--ink);
            font-size: 13px;
            font-weight: 700;
            transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
        }

        .cat-chip i {
            font-size: 24px;
            color: var(--accent);
            transition: all .3s ease;
        }

        .cat-chip:hover {
            background: linear-gradient(135deg, var(--accent-lt) 0%, #f0f4ff 100%);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, .18);
        }

        .cat-chip:hover i {
            transform: scale(1.15) rotate(-5deg);
        }

        @media (max-width: 1024px) {
            .hero-inner {
                grid-template-columns: 1fr;
            }

            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {

            .jobs-grid,
            .companies-grid,
            .reviews-grid,
            .hiw-steps {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hiw-steps::before {
                display: none;
            }

            .cta-box {
                padding: 40px 20px;
            }

            .cta-title {
                font-size: 26px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .cats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .cat-chip {
                padding: 18px 12px;
                gap: 10px;
            }

            .cat-chip i {
                font-size: 20px;
            }

            .about-section>.container>div {
                grid-template-columns: 1fr !important;
                gap: 40px !important;
            }

            .about-section {
                padding: 70px 0;
            }
        }

        @media (max-width: 480px) {
            .cats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .cat-chip {
                padding: 16px 10px;
                font-size: 11px;
            }

            .cat-chip i {
                font-size: 18px;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp .5s ease both;
        }

        .fade-up-1 {
            animation-delay: .05s;
        }

        .fade-up-2 {
            animation-delay: .12s;
        }

        .fade-up-3 {
            animation-delay: .18s;
        }

        .fade-up-4 {
            animation-delay: .24s;
        }

        .cats-grid .cat-chip {
            animation: fadeUp .6s ease both;
        }

        .cats-grid .cat-chip:nth-child(1) {
            animation-delay: .05s;
        }

        .cats-grid .cat-chip:nth-child(2) {
            animation-delay: .1s;
        }

        .cats-grid .cat-chip:nth-child(3) {
            animation-delay: .15s;
        }

        .cats-grid .cat-chip:nth-child(4) {
            animation-delay: .2s;
        }

        .cats-grid .cat-chip:nth-child(5) {
            animation-delay: .25s;
        }

        .cats-grid .cat-chip:nth-child(6) {
            animation-delay: .3s;
        }

        .cats-grid .cat-chip:nth-child(7) {
            animation-delay: .35s;
        }

        .cats-grid .cat-chip:nth-child(8) {
            animation-delay: .4s;
        }

        .cats-grid .cat-chip:nth-child(9) {
            animation-delay: .45s;
        }

        .cats-grid .cat-chip:nth-child(10) {
            animation-delay: .5s;
        }

        .cats-grid .cat-chip:nth-child(11) {
            animation-delay: .55s;
        }

        .cats-grid .cat-chip:nth-child(12) {
            animation-delay: .6s;
        }

        .co-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            padding: 10px;
            background: #fff;
        }

        .co-logo {
            width: 70px;
            height: 70px;
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #heroJobResults::-webkit-scrollbar {
            width: 6px;
        }

        #heroJobResults::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, .35);
            border-radius: 20px;
        }

        #heroJobResults::-webkit-scrollbar-track {
            background: transparent;
        }

        #heroJobResults {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
        }

        #heroJobResults {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;

            max-height: 340px;
            overflow-y: auto !important;
            overflow-x: hidden;

            background: white;
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 12px 36px rgba(15, 23, 42, .1);

            z-index: 9999;
        }

        .hero,
        .hero-inner,
        .container,
        .fade-up-3 {
            overflow: visible !important;
        }



        @media (max-width: 1024px) {
            .hero-inner {
                grid-template-columns: 1fr;
            }

            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {

            /* ── HERO ── */
            .hero {
                padding: 48px 0 36px;
            }

            .hero-title {
                font-size: 28px !important;
            }

            .hero-desc {
                font-size: 13px;
                margin-bottom: 20px;
            }

            .hero-search,
            #heroSearchForm>div {
                flex-direction: column !important;
                gap: 8px !important;
                padding: 10px !important;
                border-radius: 16px !important;
            }

            .hero-search-field,
            #heroSearchForm>div>div {
                min-width: unset !important;
                width: 100% !important;
            }

            .hero-search-btn,
            #heroSearchForm>div>button {
                width: 100% !important;
                justify-content: center !important;
                padding: 12px !important;
            }

            .hero-chips {
                gap: 6px;
                margin-top: 12px;
            }

            /* ── STATS BAR ── */
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0;
            }

            .stats-item+.stats-item::before {
                display: none;
            }

            .stats-num {
                font-size: 22px;
            }

            /* ── CATEGORIES ── */
            .cats-section {
                padding: 60px 0;
            }

            .cats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .cat-chip {
                padding: 18px 12px;
                gap: 8px;
            }

            .cat-chip i {
                font-size: 20px;
            }

            /* ── JOBS SECTION ── */
            .jobs-section {
                padding: 60px 0;
            }

            .jobs-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 24px;
            }

            .jcard {
                padding: 18px;
                border-radius: 18px;
            }

            .jcard-title {
                font-size: 14px;
            }

            .jcard-logo {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                font-size: 14px;
            }

            /* ── FEATURES ── */
            .features-section {
                padding: 60px 0;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .feat-card {
                padding: 18px;
            }

            /* ── HOW IT WORKS ── */
            .hiw-section {
                padding: 60px 0;
            }

            .hiw-steps {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .hiw-steps::before {
                display: none;
            }

            .hiw-step {
                padding: 20px;
            }

            .hiw-tabs {
                width: 100%;
            }

            .hiw-tab-btn {
                flex: 1;
                text-align: center;
                font-size: 12px;
                padding: 8px 14px;
            }

            /* Photo strip */
            div[style*="grid-template-columns:repeat(3,1fr)"][style*="border-radius:20px"] {
                grid-template-columns: 1fr !important;
                gap: 8px !important;
            }

            /* ── COMPANIES ── */
            .companies-section {
                padding: 60px 0;
            }

            .companies-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .co-card {
                border-radius: 18px;
            }

            /* ── ABOUT SECTION ── */
            .about-section {
                padding: 60px 0;
            }

            .about-section>.container>div {
                grid-template-columns: 1fr !important;
                gap: 36px !important;
            }

            div[style*="grid-template-columns: 1.5fr 1fr"][style*="grid-template-rows"] {
                grid-template-columns: 1fr 1fr !important;
                grid-template-rows: repeat(2, 140px) !important;
                gap: 10px !important;
            }

            div[style*="grid-template-columns: 1.5fr 1fr"] img:first-child {
                grid-column: 1 !important;
                grid-row: 1 !important;
            }

            div[style*="grid-template-columns: 1.5fr 1fr"] img:last-child {
                grid-column: 1 / 3 !important;
            }

            /* ── PHOTO BANNER ── */
            .photo-banner {
                height: 240px;
                border-radius: 18px;
            }

            .photo-banner-content {
                padding: 20px;
            }

            .photo-banner-content h2 {
                font-size: 20px !important;
            }

            /* ── CTA ── */
            .cta-section {
                padding: 40px 0;
            }

            .cta-box {
                padding: 36px 20px;
                border-radius: 22px;
            }

            .cta-title {
                font-size: 24px;
            }

            .cta-desc {
                font-size: 13px;
            }

            .cta-btns {
                flex-direction: column;
                align-items: center;
            }

            .btn-white,
            .btn-outline-white {
                width: 100%;
                justify-content: center;
                max-width: 280px;
            }

            /* ── SECTION HEADERS ── */
            div[style*="display:flex"][style*="justify-content:space-between"] {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }

            /* ── CONTAINER PADDING ── */
            .container {
                padding: 0 16px;
            }
        }

        @media (max-width: 480px) {
            .cats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .cat-chip {
                padding: 14px 10px;
                font-size: 11px;
            }

            .cat-chip i {
                font-size: 18px;
            }

            .hero-title {
                font-size: 24px !important;
            }

            .stats-num {
                font-size: 20px;
            }

            .cta-title {
                font-size: 20px;
            }

            .photo-banner {
                height: 200px;
            }

            .photo-banner-content h2 {
                font-size: 17px !important;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main>

        <?php if (!isLoggedIn()): ?>


            <section class="hero">
                <div class="hero-grid"></div>
                <div class="container">
                    <div class="hero-inner">

                        <!-- Left -->
                        <div>
                            <div class="tag tag-accent fade-up" style="margin-bottom:16px">
                                <i class="fas fa-star"></i> Algeria's #1 Job Platform
                            </div>

                            <h1 class="hero-title fade-up fade-up-1">
                                Find Your Next<br><em>Dream Career</em><br>Opportunity
                            </h1>

                            <p class="hero-desc fade-up fade-up-2">
                                Connect with top companies across Algeria. Discover thousands of verified job listings tailored to your skills and ambitions.
                            </p>

                            <!-- SEARCH BAR -->
                            <div class="fade-up fade-up-3"
                                style="margin-bottom:12px; position:relative; z-index:1000;">
                                <form method="GET" action="index.php" id="heroSearchForm">
                                    <div style="display:flex; gap:8px; background:white; border:1.5px solid var(--line); border-radius:18px; padding:8px; box-shadow:0 8px 32px rgba(15,23,42,.07);">

                                        <!-- Search input -->
                                        <div style="flex:1; display:flex; align-items:center; gap:10px; background:var(--bg); border-radius:10px; padding:0 14px; min-height:44px; position:relative;">
                                            <i class="fas fa-search" style="color:var(--muted); font-size:13px; flex-shrink:0;"></i>
                                            <input
                                                type="text"
                                                id="heroJobSearch"
                                                name="q"
                                                value="<?php echo e($search); ?>"
                                                placeholder="Job title, company, or keyword..."
                                                autocomplete="off"
                                                style="border:none; background:transparent; outline:none; font-size:13px; width:100%; color:var(--ink); font-family:'Poppins',sans-serif; font-weight:500;">
                                            <div id="heroJobResults"
                                                style="position:absolute;
top:calc(100% + 8px);
left:0;
right:0;
background:white;
border:1px solid var(--line);
border-radius:16px;
box-shadow:0 12px 36px rgba(15,23,42,.1);
z-index:999;
overflow-y:auto;
overflow-x:hidden;
max-height:340px;
display:none;"></div>
                                        </div>

                                        <!-- Location -->
                                        <div style="display:flex; align-items:center; gap:8px; background:var(--bg); border-radius:10px; padding:0 12px; min-width:155px; min-height:44px;">
                                            <i class="fas fa-map-marker-alt" style="color:var(--muted); font-size:12px; flex-shrink:0;"></i>
                                            <select name="city" style="border:none; background:transparent; outline:none; font-size:13px; color:var(--ink); cursor:pointer; width:100%; font-family:'Poppins',sans-serif; font-weight:500; appearance:none;">
                                                <option value="">All Algeria</option>
                                                <?php foreach ($cityOptions as $opt): ?>
                                                    <option value="<?php echo e($opt); ?>" <?php echo $city === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Button -->
                                        <button type="submit" style="background:linear-gradient(135deg,var(--accent),var(--accent2)); color:white; border:none; border-radius:10px; padding:0 20px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; white-space:nowrap; min-height:44px; font-family:'Poppins',sans-serif; box-shadow:0 6px 20px rgba(99,102,241,.25); transition:.2s;">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Popular chips -->
                            <div class="fade-up fade-up-4"
                                style="position:relative; z-index:1;">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <span style="font-size:12px; color:var(--muted); font-weight:600;">Popular:</span>
                                    <?php
                                    $popularChips = ['Developer', 'Marketing', 'Design', 'Finance', 'Engineering', 'Healthcare'];
                                    foreach ($popularChips as $chip):
                                    ?>
                                        <a href="javascript:void(0)"
                                            onclick="heroQuickSearch('<?php echo addslashes($chip); ?>')"
                                            class="hero-chip"><?php echo $chip; ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right — decorative floating cards -->
                        <div class="hero-visual" aria-hidden="true">

                            <!-- Job card float -->
                            <div class="float-card fc-job">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                    <div class="fc-company-logo" style="margin:0;width:42px;height:42px;flex-shrink:0">DZ</div>
                                    <div>
                                        <div class="fc-title">Senior UI Designer</div>
                                        <div class="fc-sub">Djezzy · Algiers</div>
                                    </div>
                                </div>
                                <div class="fc-tags">
                                    <span class="tag tag-accent" style="font-size:10px;padding:4px 9px">Full-time</span>
                                    <span class="tag tag-green" style="font-size:10px;padding:4px 9px">Remote</span>
                                    <span style="font-size:10px;color:var(--ink-3);">120k – 160k DZD</span>
                                </div>
                            </div>

                            <!-- Stat card -->
                            <div class="float-card fc-stat">
                                <div class="fc-big-num"><?php echo number_format($globalStats['total_jobs']); ?>+</div>
                                <div class="fc-big-lbl">Active Job Listings</div>
                                <div class="fc-trend"><i class="fas fa-arrow-up"></i> +24% this month</div>
                                <div class="progress-ring">
                                    <div style="font-size:10px;color:var(--muted);margin-bottom:5px;">Fill rate</div>
                                    <div class="progress-bar-track">
                                        <div class="progress-bar-fill"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Match card -->
                            <div class="float-card fc-match">
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                    <div style="width:34px;height:34px;background:#E1F5EE;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1D9E75;font-size:13px;">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:700;color:var(--ink)">New Match Found!</div>
                                        <div style="font-size:11px;color:var(--muted)">Product Manager · Oran</div>
                                    </div>
                                </div>
                                <div style="font-size:11px;color:var(--muted);background:var(--bg);border-radius:9px;padding:7px 10px;">
                                    92% match · 3 similar openings available
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>

            <section class="stats-bar">

                <div class="container">

                    <?php
                    $displayJobs        = max(2480, (int)$globalStats['total_jobs']);
                    $displayCompanies   = max(620,  (int)$globalStats['total_companies']);
                    $displayCandidates  = max(12500, (int)$globalStats['total_candidates']);
                    $displayApplications = max(18400, (int)$globalStats['total_applications']);
                    ?>

                    <div class="stats-grid">

                        <div class="stats-item">
                            <div class="stats-num">
                                <?php echo number_format($displayJobs); ?>+
                            </div>

                            <div class="stats-lbl">
                                Open Positions
                            </div>
                        </div>

                        <div class="stats-item">
                            <div class="stats-num">
                                <?php echo number_format($displayCompanies); ?>+
                            </div>

                            <div class="stats-lbl">
                                Hiring Companies
                            </div>
                        </div>

                        <div class="stats-item">
                            <div class="stats-num">
                                <?php echo number_format($displayCandidates); ?>+
                            </div>

                            <div class="stats-lbl">
                                Active Candidates
                            </div>
                        </div>

                        <div class="stats-item">
                            <div class="stats-num">
                                <?php echo number_format($displayApplications); ?>+
                            </div>

                            <div class="stats-lbl">
                                Applications Sent
                            </div>
                        </div>

                    </div>

                </div>

            </section>

            <section class="cats-section">
                <div class="container">

                    <div class="section-eyebrow">Browse by Category</div>

                    <h2 style="font-size:32px;font-weight:700;color:var(--ink);max-width:500px;line-height:1.2;">
                        Explore Opportunities by Industry
                    </h2>

                    <div class="cats-grid">

                        <?php
                        $catIcons = [
                            'IT & Tech'         => 'fa-laptop-code',
                            'Sales'             => 'fa-handshake',
                            'Marketing'         => 'fa-bullhorn',
                            'Finance'           => 'fa-chart-bar',
                            'Engineering'       => 'fa-cogs',
                            'Healthcare'        => 'fa-heartbeat',
                            'Education'         => 'fa-graduation-cap',
                            'Design'            => 'fa-palette',
                            'Logistics'         => 'fa-truck',
                            'Legal'             => 'fa-gavel',
                            'Construction'      => 'fa-hard-hat',
                            'Search by Filter'  => 'fa-sliders-h',
                        ];

                        foreach ($catIcons as $catName => $icon):

                            $link = ($catName === 'Search by Filter')
                                ? 'job.php'
                                : 'job.php?category=' . urlencode($catName);
                        ?>

                            <a href="<?php echo $link; ?>" class="cat-chip">

                                <i class="fas <?php echo $icon; ?>"></i>

                                <?php echo $catName; ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>
            </section>

            <section class="jobs-section">
                <div class="container">
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                        <div>
                            <div class="section-eyebrow">Latest Opportunities</div>
                            <h2 style="font-size:28px;font-weight:700;color:var(--ink);line-height:1.2;">
                                Explore New Jobs
                            </h2>
                            <p style="font-size:14px;color:var(--muted);margin-top:8px;max-width:440px;line-height:1.6;font-weight:500;">
                                Discover opportunities from trusted companies across Algeria, updated daily.
                            </p>
                        </div>
                        <a href="jobs.php" class="btn btn-ghost">View All Jobs <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <?php if (!empty($jobs)): ?>
                        <div class="jobs-grid">
                            <?php foreach (array_slice($jobs, 0, 6) as $job):
                                $jid         = $job['id'] ?? 0;
                                $jtitle      = e($job['title'] ?? 'Job Title');
                                $jcompany    = e($job['company_name'] ?? 'Company');
                                $jcity       = e($job['city'] ?? '');
                                $jsalary     = e($job['salary'] ?? '');
                                $jcontract   = e($job['contract_type'] ?? '');
                                $jwork       = e($job['work_mode'] ?? '');
                                $jlogo       = e($job['logo_url'] ?? '');
                                $jposted     = !empty($job['created_at']) ? timeAgo($job['created_at']) : 'Recently';
                                $jcategory   = e($job['category'] ?? '');
                                $labelText   = 'View Job';
                                $btnClass    = 'jcard-btn';
                                if (isLoggedIn()) {
                                    $appData = hasAppliedJob($jid, $_SESSION['user']['id']);
                                    $status  = $appData['status'] ?? null;
                                    if ($status === 'pending') {
                                        $labelText = 'Pending';
                                        $btnClass .= ' jcard-btn-pending';
                                    }
                                    if ($status === 'accepted') {
                                        $labelText = 'Accepted';
                                        $btnClass .= ' jcard-btn-accepted';
                                    }
                                    if ($status === 'rejected') {
                                        $labelText = 'Rejected';
                                        $btnClass .= ' jcard-btn-rejected';
                                    }
                                }
                            ?>
                                <article class="jcard">
                                    <div class="jcard-header">
                                        <div class="jcard-logo">
                                            <?php if ($jlogo): ?>
                                                <img src="<?php echo $jlogo; ?>" alt="">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($jcompany, 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="min-width:0;flex:1">
                                            <h3 class="jcard-title"><?php echo $jtitle; ?></h3>
                                            <div class="jcard-company"><i class="fas fa-building"></i><?php echo $jcompany; ?></div>
                                            <?php if ($jcity): ?>
                                                <div class="jcard-city"><i class="fas fa-map-marker-alt"></i><?php echo $jcity; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="jcard-tags">
                                        <?php if ($jsalary): ?>
                                            <span class="jcard-tag jcard-tag-accent"><i class="fas fa-wallet" style="font-size:9px"></i> <?php echo $jsalary; ?></span>
                                        <?php endif; ?>
                                        <?php if ($jcontract): ?>
                                            <span class="jcard-tag jcard-tag-gray"><?php echo $jcontract; ?></span>
                                        <?php endif; ?>
                                        <?php if ($jwork): ?>
                                            <span class="jcard-tag jcard-tag-green"><?php echo $jwork; ?></span>
                                        <?php endif; ?>
                                        <?php if ($jcategory): ?>
                                            <span class="jcard-tag jcard-tag-gray"><?php echo $jcategory; ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="jcard-footer">
                                        <div class="jcard-time"><i class="fas fa-clock"></i><?php echo $jposted; ?></div>
                                        <a href="job_details.php?id=<?php echo e($jid); ?>" class="<?php echo $btnClass; ?>">
                                            <?php echo $labelText; ?> <i class="fas fa-arrow-right" style="font-size:10px"></i>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:50px;color:var(--muted);background:var(--surface);border-radius:20px;border:1.5px dashed var(--line);margin-top:40px;">
                            <i class="fas fa-briefcase" style="font-size:36px;margin-bottom:14px;display:block;opacity:.3"></i>
                            No jobs available right now. Check back soon!
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="features-section">
                <div class="container">
                    <div style="text-align:center;max-width:520px;margin:0 auto;">
                        <div class="section-eyebrow" style="justify-content:center;color:rgba(255,255,255,.5)">
                            <span style="width:24px;height:2px;background:rgba(255,255,255,.3);display:block;border-radius:2px"></span>
                            Why JobDZ
                        </div>
                        <h2 style="font-size:30px;font-weight:700;color:#fff;margin-top:8px;line-height:1.2;">
                            Built for Algeria's Job Market
                        </h2>
                        <p style="font-size:15px;color:rgba(255,255,255,.5);margin-top:12px;line-height:1.6;">
                            Powerful tools and a trusted network to help candidates and companies succeed faster.
                        </p>
                    </div>
                    <div class="features-grid">
                        <?php
                        $features = [
                            ['fa-solid fa-bolt',           'Smart Matching',        'AI-powered recommendations that connect the right people to the right jobs instantly.'],
                            ['fa-solid fa-shield-alt',     'Verified Companies',    'Every company on JobDZ is verified, so you can apply with complete confidence.'],
                            ['fa-solid fa-bell',           'Instant Alerts',        'Get notified the moment a job matching your profile is posted — never miss out.'],
                            ['fa-solid fa-chart-line',     'Career Insights',       'Salary benchmarks, demand trends, and skills gap analysis tailored to Algeria.'],
                            ['fa-solid fa-file-alt',       'Easy Applications',     'Apply in seconds with your saved profile. No repeat forms, no friction.'],
                            ['fa-solid fa-handshake',      'Direct Recruiter Chat', 'Message hiring managers directly and move faster through the process.'],
                            ['fa-solid fa-map-marker-alt', 'Wilaya Coverage',       'Jobs from all 58 wilayas — find opportunities right in your city.'],
                            ['fa-solid fa-lock',           'Private & Secure',      'Your data is encrypted and never shared without your explicit consent.'],
                        ];
                        foreach ($features as [$icon, $title, $desc]):
                        ?>
                            <div class="feat-card">
                                <div class="feat-icon"><i class="fas <?php echo substr($icon, 5); ?>"></i></div>
                                <div class="feat-title"><?php echo $title; ?></div>
                                <div class="feat-desc"><?php echo $desc; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="hiw-section">
                <div class="container">
                    <div style="text-align:center;max-width:520px;margin:0 auto 36px;">
                        <div class="section-eyebrow" style="justify-content:center;">How It Works</div>
                        <h2 style="font-size:28px;font-weight:700;color:var(--ink);margin-top:8px;line-height:1.2;">Simple Steps to Get Hired</h2>
                        <p style="font-size:14px;color:var(--muted);margin-top:8px;line-height:1.6;font-weight:500;">A seamless process — whether you're looking for a job or looking to hire.</p>
                    </div>

                    <div style="display:flex;justify-content:center;margin-bottom:44px;">
                        <div class="hiw-tabs">
                            <button id="hiw-tab-c" class="hiw-tab-btn active" onclick="hiwSwitch('c')">For Candidates</button>
                            <button id="hiw-tab-co" class="hiw-tab-btn" onclick="hiwSwitch('co')">For Companies</button>
                        </div>
                    </div>

                    <div id="hiw-c" class="hiw-steps">
                        <?php foreach (
                            [
                                ['01', 'fas fa-user-plus',  'Create Your Profile',   'Sign up in 2 minutes and fill in your skills, experience, and preferences.'],
                                ['02', 'fas fa-search',     'Discover & Apply',      'Browse smart-matched listings and apply with a single click.'],
                                ['03', 'fas fa-trophy',     'Get Hired',             'Receive offers, chat with recruiters, and start your new chapter.'],
                            ] as [$n, $icon, $t, $d]
                        ): ?>
                            <div class="hiw-step">
                                <div class="hiw-step-num"><?php echo $n; ?></div>
                                <div class="hiw-step-title"><?php echo $t; ?></div>
                                <div class="hiw-step-desc"><?php echo $d; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="hiw-co" class="hiw-steps" style="display:none">
                        <?php foreach (
                            [
                                ['01', 'fas fa-building', 'Register Company',    'Create a verified company profile and showcase your brand to top talent.'],
                                ['02', 'fas fa-pen',      'Post Job Openings',   'Publish detailed job listings and reach thousands of qualified candidates instantly.'],
                                ['03', 'fas fa-users',    'Hire the Best',       'Review applications, shortlist candidates, and build your dream team.'],
                            ] as [$n, $icon, $t, $d]
                        ): ?>
                            <div class="hiw-step">
                                <div class="hiw-step-num"><?php echo $n; ?></div>
                                <div class="hiw-step-title"><?php echo $t; ?></div>
                                <div class="hiw-step-desc"><?php echo $d; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Photo strip -->
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:48px;border-radius:20px;overflow:hidden;">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&q=80&auto=format&fit=crop" alt="Team collaboration" loading="lazy" style="width:100%;height:160px;object-fit:cover;display:block;">
                        <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=600&q=80&auto=format&fit=crop" alt="Office meeting" loading="lazy" style="width:100%;height:160px;object-fit:cover;display:block;">
                        <img src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=600&q=80&auto=format&fit=crop" alt="Work presentation" loading="lazy" style="width:100%;height:160px;object-fit:cover;display:block;">
                    </div>
                </div>
            </section>

            <section class="companies-section">
                <div class="container">
                    <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:14px;">
                        <div>
                            <div class="section-eyebrow">Top Employers</div>
                            <h2 style="font-size:28px;font-weight:700;color:var(--ink);line-height:1.2;">Companies Hiring Now</h2>
                            <p style="font-size:14px;color:var(--muted);margin-top:8px;max-width:440px;line-height:1.6;font-weight:500;">
                                Ranked by most applications across Algeria.
                            </p>
                        </div>
                        <a href="companies.php" class="btn btn-ghost">View All <i class="fas fa-arrow-right"></i></a>
                    </div>

                    <div class="companies-grid">
                        <?php
                        $topCompanies = getTopCompaniesByApplications(3);
                        foreach ($topCompanies as $rank => $company):
                            $rankNum = $rank + 1;

                            $profile = getCompanyProfile($company['user_id']);
                        ?>
                            <div class="co-card" style="overflow:hidden;">


                                <div style="padding:<?php echo $rankNum === 1 ? '16px' : '20px'; ?> 16px 12px;">

                                    <!-- Head -->
                                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                                        <div class="co-logo" style="width:42px;height:42px;border-radius:11px;">
                                            <?php if (!empty($company['logo_url'])): ?>
                                                <img src="<?php echo e($company['logo_url']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($company['company_name'], 0, 2)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:2px;"><?php echo e($company['company_name']); ?></div>
                                            <div style="font-size:11px;color:var(--muted);font-weight:500;"><?php echo e($profile['industry'] ?? ''); ?></div>
                                        </div>
                                        <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 8px;border-radius:100px;background:<?php echo $rankNum === 1 ? '#fef3c7' : 'var(--accent-lt)'; ?>;color:<?php echo $rankNum === 1 ? '#92400e' : 'var(--accent)'; ?>;">#<?php echo $rankNum; ?></span>
                                    </div>

                                    <!-- Info rows -->
                                    <?php if (!empty($profile['city'])): ?>
                                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-bottom:7px;font-weight:500;">
                                            <i class="fas fa-map-marker-alt" style="color:var(--accent);font-size:11px;width:14px;"></i> <?php echo e($profile['city']); ?>, Algeria
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($profile['employees_count'])): ?>
                                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-bottom:7px;font-weight:500;">
                                            <i class="fas fa-users" style="color:var(--accent);font-size:11px;width:14px;"></i> <?php echo e($profile['employees_count']); ?> Employees
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($profile['founded_year'])): ?>
                                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--muted);margin-bottom:0;font-weight:500;">
                                            <i class="fas fa-calendar" style="color:var(--accent);font-size:11px;width:14px;"></i> Founded <?php echo e($profile['founded_year']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Stats -->
                                    <div style="display:flex;gap:7px;margin-top:10px;">
                                        <div style="flex:1;background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:6px 8px;text-align:center;">
                                            <div style="font-size:15px;font-weight:700;color:var(--ink);line-height:1;"><?php echo $company['job_count']; ?></div>
                                            <div style="font-size:10px;color:var(--muted);margin-top:2px;font-weight:600;">Open Jobs</div>
                                        </div>
                                        <div style="flex:1;background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:6px 8px;text-align:center;">
                                            <div style="font-size:15px;font-weight:700;color:var(--ink);line-height:1;"><?php echo $company['application_count']; ?></div>
                                            <div style="font-size:10px;color:var(--muted);margin-top:2px;font-weight:600;">Applications</div>
                                        </div>
                                        <?php
                                        $rq = $pdo->prepare("SELECT AVG(rating) as avg FROM company_reviews WHERE company_user_id = ?");
                                        $rq->execute([$company['user_id']]);
                                        $avgRating = $rq->fetchColumn();
                                        if ($avgRating):
                                        ?>
                                            <div style="flex:1;background:var(--bg);border:1px solid var(--line);border-radius:9px;padding:6px 8px;text-align:center;">
                                                <div style="font-size:15px;font-weight:700;color:var(--ink);line-height:1;"><?php echo number_format($avgRating, 1); ?> <i class="fas fa-star" style="font-size:9px;color:#fbbf24"></i></div>
                                                <div style="font-size:10px;color:var(--muted);margin-top:2px;font-weight:600;">Rating</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="border-top:1px solid var(--line);padding:9px 16px;display:flex;align-items:center;justify-content:space-between;">
                                    <div style="font-size:11px;color:var(--muted);display:flex;align-items:center;gap:4px;">
                                        <span style="width:5px;height:5px;border-radius:50%;background:var(--green);display:inline-block;"></span> Actively Hiring
                                    </div>
                                    <a href="company.php?id=<?php echo e($company['user_id']); ?>" class="btn btn-accent btn-sm">
                                        View <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="about-section">
                <div class="container">
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 60px; align-items: center;">

                        <!-- LEFT: TEXT -->
                        <div>
                            <div class="section-eyebrow">Why Choose JobDZ</div>
                            <h2 style="font-size: 32px; font-weight: 700; color: var(--ink); line-height: 1.2; margin-bottom: 20px;">
                                We Help You Get The Best Job And Find The Best Talent
                            </h2>
                            <p style="font-size: 15px; color: var(--muted); line-height: 1.8; margin-bottom: 24px; font-weight: 500;">
                                At JobDZ, we believe that finding the right job or hiring the right person shouldn't be complicated. Our platform connects talented professionals with companies that value their skills, creating opportunities for growth and success across Algeria.
                            </p>

                            <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 32px;">
                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--green-lt); color: var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                        <i class="fas fa-check" style="font-size: 12px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--ink); margin-bottom: 3px;">Smart Matching Technology</div>
                                        <div style="font-size: 13px; color: var(--muted); line-height: 1.6;">AI-powered algorithms connect the right candidates with the right opportunities in seconds.</div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--green-lt); color: var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                        <i class="fas fa-check" style="font-size: 12px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--ink); margin-bottom: 3px;">Verified Companies & Candidates</div>
                                        <div style="font-size: 13px; color: var(--muted); line-height: 1.6;">Every profile is verified to ensure trust and safety for both job seekers and employers.</div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--green-lt); color: var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                                        <i class="fas fa-check" style="font-size: 12px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--ink); margin-bottom: 3px;">Nationwide Coverage</div>
                                        <div style="font-size: 13px; color: var(--muted); line-height: 1.6;">Access jobs from all 58 wilayas of Algeria with opportunities in every industry and field.</div>
                                    </div>
                                </div>
                            </div>

                            <a href="about.php" class="btn btn-accent">
                                Learn More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <!-- RIGHT: IMAGE COLLAGE - MODERN ASYMMETRIC -->
                        <div style="position: relative;">
                            <div style="display: grid; grid-template-columns: 1.5fr 1fr; grid-template-rows: repeat(3, 120px); gap: 14px;">

                                <!-- Large Left Image -->
                                <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=600&q=80&auto=format&fit=crop"
                                    alt="Professional team working"
                                    loading="lazy"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px; grid-column: 1; grid-row: 1 / 3; box-shadow: 0 16px 48px rgba(99, 102, 241, .15); transition: all .3s ease;">

                                <!-- Top Right -->
                                <img src="https://images.unsplash.com/photo-1573497620053-ea5300f94f21?w=400&q=80&auto=format&fit=crop"
                                    alt="Woman working remotely"
                                    loading="lazy"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 18px; box-shadow: 0 10px 32px rgba(99, 102, 241, .12); transition: all .3s ease;">

                                <!-- Middle Right -->
                                <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=400&q=80&auto=format&fit=crop"
                                    alt="Tech office"
                                    loading="lazy"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 18px; box-shadow: 0 10px 32px rgba(99, 102, 241, .12); transition: all .3s ease;">

                                <!-- Bottom Wide -->
                                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&q=80&auto=format&fit=crop"
                                    alt="Job interview meeting"
                                    loading="lazy"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 18px; grid-column: 1 / 3; box-shadow: 0 10px 32px rgba(99, 102, 241, .12); transition: all .3s ease;">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section style="padding:60px 0 60px;">
                <div class="container">
                    <div class="photo-banner">
                        <img
                            src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=1400&q=80&auto=format&fit=crop"
                            alt="Modern office environment"
                            loading="lazy">
                        <div class="photo-banner-overlay"></div>
                        <div class="photo-banner-content">
                            <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:100px;padding:5px 14px;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);margin-bottom:12px;letter-spacing:.06em;text-transform:uppercase;">
                                <i class="fas fa-map-marker-alt"></i> Algeria's Fastest Growing Companies
                            </div>
                            <h2 style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:10px;line-height:1.2;max-width:520px;">
                                Top employers are actively searching<br>for talent just like you
                            </h2>
                            <p style="font-size:14px;color:rgba(255,255,255,.65);max-width:420px;line-height:1.6;margin-bottom:20px;">
                                Join thousands of professionals who already found their next opportunity through JobDZ.
                            </p>
                            <a href="register.php?role=candidate" class="btn btn-white" style="background:#fff;color:var(--accent);border-radius:12px;padding:11px 26px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);">
                                <i class="fas fa-rocket"></i> Create Free Account
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta-section">
                <div class="container">
                    <div class="cta-box">
                        <p class="cta-label">Join Thousands of Professionals</p>
                        <h2 class="cta-title">Ready to Start<br>Your Journey?</h2>
                        <p class="cta-desc">Algeria's most trusted job platform — connecting talent with opportunity every single day.</p>
                        <?php
                        $displayJobs       = max(2480, (int)$globalStats['total_jobs']);
                        $displayCompanies  = max(620, (int)$globalStats['total_companies']);
                        $displayCandidates = max(12500, (int)$globalStats['total_candidates']);
                        ?>

                        <div class="cta-pills">

                            <span class="cta-pill">
                                <i class="fas fa-briefcase"></i>
                                <?php echo number_format($displayJobs); ?>+ Jobs
                            </span>

                            <span class="cta-pill">
                                <i class="fas fa-building"></i>
                                <?php echo number_format($displayCompanies); ?>+ Companies
                            </span>

                            <span class="cta-pill">
                                <i class="fas fa-users"></i>
                                <?php echo number_format($displayCandidates); ?>+ Candidates
                            </span>

                            <span class="cta-pill">
                                <i class="fas fa-check-circle"></i>
                                Free to Join
                            </span>

                        </div>
                        <div class="cta-btns">
                            <a href="register.php?role=candidate" class="btn-white"><i class="fas fa-rocket"></i> Find a Job</a>
                            <a href="register.php?role=company" class="btn-outline-white"><i class="fas fa-building"></i> Post a Job</a>
                            <a href="jobs.php" class="btn-outline-white" style="border-color:rgba(255,255,255,.25)"><i class="fas fa-search"></i> Browse Jobs</a>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif ($_SESSION['user']['role'] === 'candidate'): ?>
            <?php include 'candidate_dashboard.php'; ?>

        <?php elseif ($_SESSION['user']['role'] === 'company'): ?>
            <?php include 'company_dashboard.php'; ?>
        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function hiwSwitch(tab) {
            document.getElementById('hiw-c').style.display = tab === 'c' ? 'grid' : 'none';
            document.getElementById('hiw-co').style.display = tab === 'co' ? 'grid' : 'none';
            document.getElementById('hiw-tab-c').classList.toggle('active', tab === 'c');
            document.getElementById('hiw-tab-co').classList.toggle('active', tab === 'co');
        }
    </script>
    <script src="js/script.js"></script>
    <script>
        /* ── Hero Live Search ── */
        const heroInput = document.getElementById('heroJobSearch');
        const heroResults = document.getElementById('heroJobResults');
        const heroChips = document.querySelector('.fade-up.fade-up-4'); // Popular chips div

        function showDropdown() {
            heroResults.style.display = 'block';

            if (heroChips) {
                heroChips.style.marginTop = '220px';
                heroChips.style.transition = '.25s ease';
            }
        }

        function hideDropdown() {
            heroResults.style.display = 'none';

            if (heroChips) {
                heroChips.style.marginTop = '0';
            }
        }

        if (heroInput) {
            heroInput.addEventListener('input', function() {
                const q = this.value.trim();
                if (q.length < 2) {
                    hideDropdown();
                    return;
                }

                fetch('live-search.php?q=' + encodeURIComponent(q) + '&type=all')
                    .then(r => r.text())
                    .then(html => {
                        if (!html.trim()) {
                            hideDropdown();
                            return;
                        }
                        heroResults.innerHTML = html;
                        showDropdown();

                        heroResults.querySelectorAll('.job-result').forEach(item => {
                            item.addEventListener('click', function() {
                                heroInput.value = this.dataset.title || '';
                                hideDropdown();

                                const form = document.getElementById('heroSearchForm');
                                form.addEventListener('submit', function onSubmit(e) {
                                    form.removeEventListener('submit', onSubmit);
                                });

                                sessionStorage.setItem('scrollToJobs', '1');
                                form.submit();
                            });
                        });

                        // Companies
                        heroResults.querySelectorAll('.company-result').forEach(item => {
                            item.addEventListener('click', function() {
                                hideDropdown();
                                const name = this.dataset.name || '';
                                heroInput.value = name;
                            });
                        });
                    });
            });

            document.addEventListener('click', function(e) {
                if (!heroInput.contains(e.target) && !heroResults.contains(e.target)) {
                    hideDropdown();
                }
            });

            heroInput.addEventListener('focus', function() {
                if (heroResults.innerHTML.trim() && this.value.length >= 2) {
                    showDropdown();
                }
            });
        }

        /* ── Scroll to Jobs after search ── */
        if (sessionStorage.getItem('scrollToJobs') === '1') {
            sessionStorage.removeItem('scrollToJobs');
            const jobsSection = document.querySelector('.jobs-section');
            if (jobsSection) {
                setTimeout(() => {
                    jobsSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 200);
            }
        }

        /* ── Popular chip quick search ── */
        function heroQuickSearch(term) {
            if (heroInput) {
                heroInput.value = term;
                sessionStorage.setItem('scrollToJobs', '1');
                document.getElementById('heroSearchForm').submit();
            }
        }
    </script>
</body>

</html>