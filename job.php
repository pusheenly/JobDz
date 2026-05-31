<?php
require_once 'config.php';
require_once 'functions.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
if (isset($_GET['json'])) {
    $jobs = searchJobs('', '', '', '', '', '', '');
    $out = [];
    foreach (array_slice($jobs, 0, 300) as $j) {
        $out[] = [
            'id'           => $j['id'],
            'title'        => $j['title'] ?? '',
            'company_name' => $j['company_name'] ?? '',
            'city'         => $j['city'] ?? '',
            'contract'     => $j['contract'] ?? '',
            'experience'   => $j['experience'] ?? '',
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

$jobId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$job = $jobId ? getJobById($jobId) : null;
$message = '';

$perPage = 4;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

if ($jobId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if ($_SESSION['user']['role'] !== 'candidate') {
        header('Location: index.php');
        exit;
    }
    if (!isCandidateProfileComplete($_SESSION['user']['id'])) {
        header('Location: edit_profile.php');
        exit;
    }
    $candidateId = $_SESSION['user']['id'];
    if ($_POST['action'] === 'apply') {
        $message = applyJob($jobId, $candidateId) ? 'Successfully applied for the job.' : 'You have already applied for the job.';
    }
    if ($_POST['action'] === 'save') {
        $message = saveJob($jobId, $candidateId) ? 'Job saved successfully.' : 'This job was already saved.';
    }
}

if ($jobId && !$job) {
    header('Location: job.php');
    exit;
}

$search     = trim($_GET['q']          ?? '');
$city       = trim($_GET['city']       ?? '');
$specialty  = trim($_GET['specialty']  ?? '');
$category   = trim($_GET['category']   ?? '');
$contract   = trim($_GET['contract']   ?? '');
$experience = trim($_GET['experience'] ?? '');
$worktime   = trim($_GET['worktime']   ?? '');
$tab        = $_GET['tab'] ?? 'all';

$allJobs = [];
if ($tab === 'recommended' && isLoggedIn() && $_SESSION['user']['role'] === 'candidate') {
    $candidateProfile = getCandidateProfile($_SESSION['user']['id']);
    $allJobs = getRecommendedJobs($candidateProfile, $search, $city, $category, $specialty, $contract, $experience);
} else {
    $allJobs = searchJobs($search, $city, $category, $specialty, $contract, $experience, $worktime);
}

$totalJobs  = !empty($allJobs) ? count($allJobs) : 0;
$totalPages = $totalJobs > 0 ? ceil($totalJobs / $perPage) : 1;
$jobs       = array_slice($allJobs ?? [], $offset, $perPage);

$algeriaWilayas = [
    'Adrar',
    'Chlef',
    'Laghouat',
    'Oum El Bouaghi',
    'Batna',
    'Béjaïa',
    'Biskra',
    'Béchar',
    'Blida',
    'Bouira',
    'Tamanrasset',
    'Tébessa',
    'Tlemcen',
    'Tiaret',
    'Tizi Ouzou',
    'Alger',
    'Djelfa',
    'Jijel',
    'Sétif',
    'Saïda',
    'Skikda',
    'Sidi Bel Abbès',
    'Annaba',
    'Guelma',
    'Constantine',
    'Médéa',
    'Mostaganem',
    'M\'Sila',
    'Mascara',
    'Ouargla',
    'Oran',
    'El Bayadh',
    'Illizi',
    'Bordj Bou Arréridj',
    'Boumerdès',
    'El Tarf',
    'Tindouf',
    'Tissemsilt',
    'El Oued',
    'Khenchela',
    'Souk Ahras',
    'Tipaza',
    'Mila',
    'Aïn Defla',
    'Naâma',
    'Aïn Témouchent',
    'Ghardaïa',
    'Relizane',
    'Timimoun',
    'Bordj Badji Mokhtar',
    'Ouled Djellal',
    'Béni Abbès',
    'In Salah',
    'In Guezzam',
    'Touggourt',
    'Djanet',
    'El M\'Ghair',
    'El Meniaa',
];

$contractOptions = [
    'CDI'        => ['label' => 'CDI',        'icon' => 'fa-file-contract',  'color' => '#6d28d9', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
    'CDD'        => ['label' => 'CDD',        'icon' => 'fa-file-alt',       'color' => '#be185d', 'bg' => '#fce7f3', 'border' => '#fbcfe8'],
    'Full_Time'  => ['label' => 'Full Time',  'icon' => 'fa-briefcase',      'color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
    'Part_Time'  => ['label' => 'Part Time',  'icon' => 'fa-clock',          'color' => '#065f46', 'bg' => '#ecfdf5', 'border' => '#a7f3d0'],
    'Freelance'  => ['label' => 'Freelance',  'icon' => 'fa-laptop',         'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
    'Internship' => ['label' => 'Internship', 'icon' => 'fa-graduation-cap', 'color' => '#166534', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
    'Temporary'  => ['label' => 'Temporary',  'icon' => 'fa-calendar-day',   'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
];

$experienceOptions = [
    'Entry_Level' => ['label' => 'Entry Level', 'color' => '#0c4a6e', 'bg' => '#e0f2fe', 'border' => '#bae6fd'],
    'Junior'      => ['label' => 'Junior',      'color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
    'Mid_Level'   => ['label' => 'Mid Level',   'color' => '#166534', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
    'Senior'      => ['label' => 'Senior',      'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
    'Expert'      => ['label' => 'Expert',      'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
];

$worktimeOptions = [
    'On_Site' => ['label' => 'On Site', 'icon' => 'fa-building'],
    'Remote'  => ['label' => 'Remote',  'icon' => 'fa-house-laptop'],
    'Hybrid'  => ['label' => 'Hybrid',  'icon' => 'fa-shuffle'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = 'Jobs | JobDZ';
    include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/jobs.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #f8fafc !important;
            color: #0f172a;
        }

        header,
        body,
        .nb-wrap {
            background: #f8fafc !important;
        }

        .nb {
            background: white !important;
        }

        header {
            border-bottom: 1px solid #e8eef6;
        }

        .hero-section {
            background: white;
            border-radius: 28px;
            border: 1px solid #e8eef6;
            padding: 48px 52px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, #c7d2fe 0%, transparent 70%);
            border-radius: 50%;
            opacity: .45;
            pointer-events: none;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: 160px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, #bfdbfe 0%, transparent 70%);
            border-radius: 50%;
            opacity: .35;
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ede9fe;
            color: #6d28d9;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
            border: 1px solid #ddd6fe;
        }

        .hero-section h1 {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 12px;
        }

        .hero-section h1 span {
            color: #6366f1;
        }

        .hero-section>p {
            color: #64748b;
            font-size: 15px;
            max-width: 520px;
            line-height: 1.7;
        }

        .hero-stats {
            display: flex;
            gap: 32px;
            margin-top: 32px;
            flex-wrap: wrap;
        }

        .stat-num {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        .filter-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 22px 20px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03), 0 8px 20px rgba(15, 23, 42, .04);
        }

        .filter-section-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-section-title i {
            font-size: 9px;
        }

        .filter-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 16px 0;
        }

        .wilaya-search-wrap {
            position: relative;
        }

        .wilaya-input {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 13px;
            padding: 10px 14px 10px 36px;
            font-size: 13px;
            color: #334155;
            outline: none;
            font-family: 'Poppins', sans-serif;
            transition: .2s;
        }

        .wilaya-input:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, .12);
            background: white;
        }

        .wilaya-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }

        .wilaya-dropdown {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .1);
            z-index: 999;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            padding: 4px;
        }

        .wilaya-dropdown.open {
            display: block;
        }

        .wilaya-dropdown::-webkit-scrollbar {
            width: 4px;
        }

        .wilaya-dropdown::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 99px;
        }

        .wilaya-opt {
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            color: #334155;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: .15s;
        }

        .wilaya-opt:hover {
            background: #f8fafc;
            color: #6366f1;
        }

        .wilaya-opt i {
            color: #94a3b8;
            font-size: 10px;
        }

        .chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .fchip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: .18s;
            user-select: none;
        }

        .fchip i {
            font-size: 9px;
        }

        .fchip:hover {
            filter: brightness(.93);
        }

        .fchip.active {
            box-shadow: 0 0 0 2px currentColor;
            filter: brightness(.88);
        }

        .wt-row {
            display: flex;
            gap: 6px;
        }

        .wt-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 9px 6px;
            border-radius: 13px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid #e8eef6;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: .18s;
            user-select: none;
        }

        .wt-btn i {
            font-size: 14px;
        }

        .wt-btn:hover {
            border-color: #818cf8;
            color: #6366f1;
            background: #f5f3ff;
        }

        .wt-btn.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, .25);
        }

        .filter-apply-btn {
            width: 100%;
            background: #6366f1;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 14px;
            padding: 13px;
            font-size: 13.5px;
            cursor: pointer;
            transition: .2s;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .filter-apply-btn:hover {
            background: #4f46e5;
            box-shadow: 0 8px 20px rgba(99, 102, 241, .25);
            transform: translateY(-1px);
        }

        .filter-reset-link {
            display: block;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            text-decoration: none;
            margin-top: 10px;
            transition: .15s;
        }

        .filter-reset-link:hover {
            color: #6366f1;
        }

        .filter-count-badge {
            background: #6366f1;
            color: white;
            font-size: 10px;
            font-weight: 700;
            border-radius: 99px;
            padding: 1px 7px;
            margin-left: 6px;
        }

        .filter-toggle-btn {
            display: none;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 13px 18px;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 12px;
            transition: .2s;
        }

        .filter-toggle-btn:hover {
            border-color: #c7d2fe;
            color: #6366f1;
        }

        .filter-toggle-btn .ftb-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-button {
            border-radius: 16px;
            transition: .25s ease;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .tab-button.active {
            background: #6366f1;
            color: white;
        }

        .tab-button.inactive {
            background: #f8fafc;
            color: #475569;
        }

        .tab-button.inactive:hover {
            background: #f1f5f9;
        }

        .empty-state {
            background: white;
            border: 1.5px dashed #e2e8f0;
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
        }

        .empty-state .es-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 26px;
            color: #94a3b8;
        }

        .empty-state h3 {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.65;
        }

        .es-reset-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #6366f1;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 14px;
            padding: 11px 22px;
            font-size: 13.5px;
            cursor: pointer;
            text-decoration: none;
            transition: .2s;
            font-family: 'Poppins', sans-serif;
        }

        .es-reset-btn:hover {
            background: #4f46e5;
            box-shadow: 0 8px 20px rgba(99, 102, 241, .25);
        }

        .pagination-link {
            background: white;
            border: 1px solid #e8eef6;
            color: #475569;
            padding: 9px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            transition: .22s ease;
            text-decoration: none;
        }

        .pagination-link:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: #f5f3ff;
        }

        .pagination-link.active-page {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .job-search-results {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            background: white;
            border-radius: 18px;
            border: 1px solid #ececf5;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .08);
            overflow: hidden;
            z-index: 100;
        }

        .job-result {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 13.5px;
            transition: .18s;
        }

        .modern-job-card {
            background: white;
            border: 1px solid #ececf3;
            border-radius: 20px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            text-decoration: none;
            transition: .25s ease;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .modern-job-card:hover {
            transform: translateY(-3px);
            border-color: #dcd7ff;
            box-shadow: 0 18px 45px rgba(91, 79, 207, .08);
        }

        .modern-job-card:hover .job-title {
            color: #5b4fcf;
        }

        .job-left {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            flex: 1;
            min-width: 0;
        }

        .company-avatar {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .job-main-info {
            flex: 1;
            min-width: 0;
        }

        .job-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .job-company {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .verified-icon {
            color: #6366f1;
            font-size: 13px;
        }

        .job-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .job-meta i {
            font-size: 12px;
            color: #9ca3af;
        }

        .job-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modern-tag {
            padding: 5px 12px;
            border-radius: 999px;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 12px;
            font-weight: 600;
        }

        .modern-tag.soft {
            background: #ecfeff;
            color: #0f766e;
        }

        .job-description {
            max-width: 280px;
            font-size: 13px;
            line-height: 1.5;
            color: #64748b;
            flex-shrink: 0;
        }

        .job-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .view-job-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f5f3ff;
            color: #5b4fcf;
            padding: 10px 16px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
            white-space: nowrap;
        }

        .modern-job-card:hover .view-job-btn {
            background: #6366f1;
            color: white;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1023px) {
            .filter-toggle-btn {
                display: flex;
            }

            .filter-card {
                position: static !important;
                display: none;
                margin-bottom: 16px;
            }

            .filter-card.filter-open {
                display: block;
            }
        }

        @media (max-width: 767px) {
            .hero-section {
                padding: 24px 18px;
            }

            .hero-section h1 {
                font-size: 24px;
            }

            .hero-section>p {
                font-size: 13px;
            }

            .hero-stats {
                gap: 20px;
                margin-top: 20px;
            }

            .stat-num {
                font-size: 18px;
            }

            .stat-label {
                font-size: 11px;
            }

            .search-bar-inner {
                flex-direction: column !important;
                gap: 8px !important;
                border-radius: 16px !important;
                padding: 10px !important;
            }

            .search-input-wrap {
                width: 100% !important;
                min-width: unset !important;
            }

            .search-city-wrap {
                width: 100% !important;
                min-width: unset !important;
            }

            .search-submit-btn {
                width: 100% !important;
                justify-content: center !important;
            }

            .modern-job-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 16px;
            }

            .job-description {
                display: none;
            }

            .job-right {
                flex-direction: row;
                width: 100%;
                justify-content: flex-end;
                align-items: center;
            }

            .job-title {
                font-size: 16px;
                white-space: normal;
            }

            .job-left {
                width: 100%;
            }

            /* Tabs */
            .tab-button {
                font-size: 12px;
                padding: 10px 8px !important;
            }

            .tab-button .tab-icon {
                display: none;
            }

            /* Empty state */
            .empty-state {
                padding: 40px 20px;
            }

            /* Pagination */
            .pagination-link {
                padding: 8px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .hero-section h1 {
                font-size: 20px;
            }

            .hero-badge {
                font-size: 11px;
            }

            .modern-job-card {
                padding: 14px;
            }

            .company-avatar {
                width: 44px;
                height: 44px;
                font-size: 16px;
                border-radius: 12px;
            }

            .job-title {
                font-size: 15px;
            }

            .job-company {
                font-size: 12px;
            }

            .job-meta span {
                font-size: 12px;
            }
        }
    </style>
</head>

<body class="text-slate-900">
    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="space-y-8">

            <!-- Hero -->
            <section class="hero-section">
                <?php
                $displayJobs      = max(2480, (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn());
                $displayCompanies = max(620,  (int)$pdo->query("SELECT COUNT(*) FROM companies_profiles")->fetchColumn());
                $displayWilayas   = max(58,   (int)$pdo->query("SELECT COUNT(DISTINCT city) FROM companies_profiles WHERE city IS NOT NULL AND city != ''")->fetchColumn());
                ?>
                <div class="hero-badge">
                    <i class="fas fa-bolt" style="font-size:11px"></i>
                    Latest opportunities
                </div>
                <h1>Find Your Next <span>Opportunity</span></h1>
                <p>Discover jobs that match your profile and start your next career move with confidence.</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-num"><?php echo number_format($displayJobs); ?>+</div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num"><?php echo number_format($displayCompanies); ?>+</div>
                        <div class="stat-label">Companies</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-num"><?php echo number_format($displayWilayas); ?></div>
                        <div class="stat-label">Wilayas</div>
                    </div>
                </div>
            </section>

            <!-- SEARCH SECTION -->
            <section style="margin-bottom:28px;">
                <form method="GET" action="job.php">
                    <input type="hidden" name="tab" value="<?php echo e($tab); ?>">

                    <div class="search-bar-inner" style="display:flex; gap:10px; background:white; border:1px solid #e8eef6; border-radius:20px; padding:10px; box-shadow:0 2px 12px rgba(99,102,241,.06);">

                        <!-- Search input -->
                        <div class="search-input-wrap" style="flex:1; display:flex; align-items:center; gap:10px; background:#f8fafc; border-radius:13px; padding:0 14px; min-height:48px; position:relative;">
                            <i class="fas fa-search" style="color:#9ca3af; font-size:14px; flex-shrink:0;"></i>
                            <input type="text" id="jobSearch" name="q"
                                value="<?php echo e($search); ?>"
                                placeholder="Job title, skills, or company..."
                                autocomplete="off"
                                style="border:none; background:transparent; outline:none; font-size:14px; width:100%; color:#0f172a; font-family:'Poppins',sans-serif;">
                            <div id="jobResults" class="job-search-results hidden"></div>
                        </div>

                        <!-- Location -->
                        <div class="search-city-wrap" style="display:flex; align-items:center; gap:8px; background:#f8fafc; border-radius:13px; padding:0 14px; min-width:175px; min-height:48px;">
                            <i class="fas fa-map-marker-alt" style="color:#9ca3af; font-size:14px; flex-shrink:0;"></i>
                            <select name="city" style="border:none; background:transparent; outline:none; font-size:14px; color:#0f172a; cursor:pointer; width:100%; font-family:'Poppins',sans-serif; appearance:none; -webkit-appearance:none;">
                                <option value="">All Algeria</option>
                                <?php foreach ($algeriaWilayas as $wilaya): ?>
                                    <option value="<?php echo e($wilaya); ?>" <?php echo $city === $wilaya ? 'selected' : ''; ?>>
                                        <?php echo e($wilaya); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Button -->
                        <button type="submit" class="search-submit-btn"
                            style="background:#6366f1; color:white; border:none; border-radius:13px; padding:0 24px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap; min-height:48px; font-family:'Poppins',sans-serif; transition:.2s;"
                            onmouseover="this.style.background='#4f46e5'"
                            onmouseout="this.style.background='#6366f1'">
                            <i class="fas fa-search"></i> <span>Search Jobs</span>
                        </button>
                    </div>

                    <!-- Popular tags -->
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px; padding:0 4px;">
                        <span style="font-size:12px; color:#94a3b8; font-weight:500;">Popular:</span>
                        <?php
                        $popularTags = ['Developer', 'Marketing', 'Design', 'Finance', 'Engineering', 'Accounting'];
                        foreach ($popularTags as $tag):
                        ?>
                            <a href="job.php?q=<?php echo urlencode($tag); ?>&tab=<?php echo e($tab); ?>"
                                style="font-size:12px; color:#64748b; background:white; border:1px solid #e8eef6; border-radius:99px; padding:3px 13px; text-decoration:none; transition:.15s; font-weight:500;"
                                onmouseover="this.style.borderColor='#c7d2fe';this.style.color='#6366f1'"
                                onmouseout="this.style.borderColor='#e8eef6';this.style.color='#64748b'">
                                <?php echo $tag; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </form>
            </section>

            <!-- Main Grid -->
            <div class="grid gap-8 lg:grid-cols-[300px_1fr] items-start">

                <!--  FILTER SIDEBAR  -->
                <aside>
                    <?php
                    $activeFiltersCount = (int)(!empty($contract)) + (int)(!empty($experience)) + (int)(!empty($worktime)) + (int)(!empty($category)) + (int)(!empty($city));
                    ?>

                    <!-- Mobile toggle button -->
                    <button class="filter-toggle-btn" onclick="toggleFilterCard(this)" aria-expanded="false">
                        <span class="ftb-left">
                            <i class="fas fa-sliders"></i>
                            Filters
                            <?php if ($activeFiltersCount > 0): ?>
                                <span class="filter-count-badge"><?php echo $activeFiltersCount; ?></span>
                            <?php endif; ?>
                        </span>
                        <i class="fas fa-chevron-down" id="filterChevron" style="font-size:12px; transition:.2s;"></i>
                    </button>

                    <div class="filter-card sticky top-24" id="filterCard">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
                            <div style="display:flex; align-items:center;">
                                <span style="font-size:15px; font-weight:700; color:#0f172a;">Filters</span>
                                <?php if ($activeFiltersCount > 0): ?>
                                    <span class="filter-count-badge"><?php echo $activeFiltersCount; ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="job.php?tab=<?php echo e($tab); ?>" style="font-size:12px; color:#94a3b8; font-weight:500; text-decoration:none;">
                                <i class="fas fa-rotate-left" style="margin-right:3px; font-size:10px;"></i>Reset all
                            </a>
                        </div>

                        <form method="GET" action="job.php">
                            <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
                            <input type="hidden" name="q" value="<?php echo e($search); ?>">
                            <input type="hidden" name="contract" id="contractValue" value="<?php echo e($contract); ?>">
                            <input type="hidden" name="experience" id="experienceValue" value="<?php echo e($experience); ?>">
                            <input type="hidden" name="worktime" id="worktimeValue" value="<?php echo e($worktime); ?>">
                            <input type="hidden" name="city" id="cityFilterValue" value="<?php echo e($city); ?>">
                            <input type="hidden" name="specialty" id="specialtyValue" value="<?php echo e($specialty); ?>">

                            <!-- Wilaya -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-map-marker-alt"></i> Wilaya</p>
                                <div class="wilaya-search-wrap">
                                    <i class="fas fa-search wilaya-icon"></i>
                                    <input type="text" class="wilaya-input" id="wilayaFilterInput"
                                        readonly placeholder="Select wilaya..."
                                        value="<?php echo e($city); ?>" autocomplete="off">
                                    <div class="wilaya-dropdown" id="wilayaDropdown">
                                        <div class="wilaya-opt" data-val=""><i class="fas fa-globe"></i> All wilayas</div>
                                        <?php foreach ($algeriaWilayas as $w): ?>
                                            <div class="wilaya-opt" data-val="<?php echo e($w); ?>">
                                                <i class="fas fa-location-dot"></i> <?php echo e($w); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <!-- Category -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-layer-group"></i> Job Category</p>
                                <div class="wilaya-search-wrap">
                                    <i class="fas fa-layer-group wilaya-icon"></i>
                                    <input type="text" name="category" id="categoryFilterInput" class="wilaya-input"
                                        placeholder="Search or type a category..."
                                        value="<?php echo e($category); ?>" autocomplete="off">
                                    <div class="wilaya-dropdown" id="categoryDropdown">
                                        <div class="wilaya-opt" data-val=""><i class="fas fa-th-large"></i> All categories</div>
                                        <?php foreach (getCategoryOptions() as $option): ?>
                                            <div class="wilaya-opt" data-val="<?php echo e($option); ?>">
                                                <i class="fas fa-tag"></i> <?php echo e($option); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <!-- Specialty -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-briefcase"></i> Specialty</p>
                                <div class="wilaya-search-wrap">
                                    <i class="fas fa-briefcase wilaya-icon"></i>
                                    <input type="text" name="specialty" id="specialtyFilterInput" class="wilaya-input"
                                        placeholder="Search or choose specialty..."
                                        value="<?php echo e($specialty); ?>" autocomplete="off">
                                    <div class="wilaya-dropdown" id="specialtyDropdown">
                                        <div class="wilaya-opt" data-val=""><i class="fas fa-layer-group"></i> All specialties</div>
                                        <?php foreach (getSpecialtyOptions() as $option): ?>
                                            <div class="wilaya-opt" data-val="<?php echo e($option); ?>">
                                                <i class="fas fa-tag"></i> <?php echo e($option); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <!-- Contract Type -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-file-signature"></i> Contract Type</p>
                                <div class="chip-grid">
                                    <?php foreach ($contractOptions as $val => $opt): ?>
                                        <span class="fchip <?php echo $contract === $val ? 'active' : ''; ?>"
                                            data-val="<?php echo $val; ?>"
                                            data-group="contract"
                                            style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                            onclick="toggleFChip(this,'contractValue')">
                                            <i class="fas <?php echo $opt['icon']; ?>"></i>
                                            <?php echo $opt['label']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <!-- Experience Level -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-chart-line"></i> Experience Level</p>
                                <div class="chip-grid">
                                    <?php foreach ($experienceOptions as $val => $opt): ?>
                                        <span class="fchip <?php echo $experience === $val ? 'active' : ''; ?>"
                                            data-val="<?php echo $val; ?>"
                                            data-group="experience"
                                            style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                            onclick="toggleFChip(this,'experienceValue')">
                                            <?php echo $opt['label']; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <!-- Work Type -->
                            <div>
                                <p class="filter-section-title"><i class="fas fa-clock"></i> Work Type</p>
                                <div class="wt-row">
                                    <?php foreach ($worktimeOptions as $val => $opt): ?>
                                        <div class="wt-btn <?php echo $worktime === $val ? 'active' : ''; ?>"
                                            data-val="<?php echo $val; ?>"
                                            onclick="toggleWT(this,'worktimeValue')">
                                            <i class="fas <?php echo $opt['icon']; ?>"></i>
                                            <?php echo $opt['label']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="filter-divider"></div>

                            <button type="submit" class="filter-apply-btn">
                                <i class="fas fa-sliders"></i> Apply Filters
                            </button>
                            <a href="job.php?tab=<?php echo e($tab); ?>" class="filter-reset-link">Clear all filters</a>
                        </form>
                    </div>
                </aside>

                <!-- JOBS SECTION  -->
                <section class="space-y-6">

                    <!-- Tabs -->
                    <div class="rounded-[20px] bg-white p-3 shadow-soft flex gap-3" style="border:1px solid #e8eef6;">
                        <a href="job.php?tab=all&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>"
                            class="flex-1 px-4 py-3 text-center font-semibold text-sm tab-button <?php echo $tab === 'all' ? 'active' : 'inactive'; ?>">
                            <i class="fas fa-briefcase mr-2 tab-icon"></i>All Jobs
                        </a>
                        <?php if (isLoggedIn() && $_SESSION['user']['role'] === 'candidate'): ?>
                            <a href="job.php?tab=recommended&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>"
                                class="flex-1 px-4 py-3 text-center font-semibold text-sm tab-button <?php echo $tab === 'recommended' ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-star mr-2 tab-icon"></i>For You
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Active filter pills -->
                    <?php if ($activeFiltersCount > 0 || !empty($search)): ?>
                        <div style="display:flex; flex-wrap:wrap; gap:7px; align-items:center;">
                            <span style="font-size:12px; color:#94a3b8; font-weight:500;">Active:</span>
                            <?php if (!empty($search)): ?>
                                <a href="job.php?tab=<?php echo e($tab); ?>&city=<?php echo urlencode($city); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>&category=<?php echo urlencode($category); ?>"
                                    style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;border-radius:20px;padding:4px 11px;font-size:12px;font-weight:600;text-decoration:none;">
                                    <i class="fas fa-search" style="font-size:9px;"></i><?php echo e($search); ?> <i class="fas fa-times" style="font-size:9px;"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($city)): ?>
                                <a href="job.php?tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>&category=<?php echo urlencode($category); ?>"
                                    style="display:inline-flex;align-items:center;gap:5px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;border-radius:20px;padding:4px 11px;font-size:12px;font-weight:600;text-decoration:none;">
                                    <i class="fas fa-map-marker-alt" style="font-size:9px;"></i><?php echo e($city); ?> <i class="fas fa-times" style="font-size:9px;"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($contract) && isset($contractOptions[$contract])): ?>
                                <a href="job.php?tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>&category=<?php echo urlencode($category); ?>"
                                    style="display:inline-flex;align-items:center;gap:5px;background:<?php echo $contractOptions[$contract]['bg']; ?>;color:<?php echo $contractOptions[$contract]['color']; ?>;border:1px solid <?php echo $contractOptions[$contract]['border']; ?>;border-radius:20px;padding:4px 11px;font-size:12px;font-weight:600;text-decoration:none;">
                                    <?php echo $contractOptions[$contract]['label']; ?> <i class="fas fa-times" style="font-size:9px;"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($experience) && isset($experienceOptions[$experience])): ?>
                                <a href="job.php?tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&contract=<?php echo urlencode($contract); ?>&worktime=<?php echo urlencode($worktime); ?>&category=<?php echo urlencode($category); ?>"
                                    style="display:inline-flex;align-items:center;gap:5px;background:<?php echo $experienceOptions[$experience]['bg']; ?>;color:<?php echo $experienceOptions[$experience]['color']; ?>;border:1px solid <?php echo $experienceOptions[$experience]['border']; ?>;border-radius:20px;padding:4px 11px;font-size:12px;font-weight:600;text-decoration:none;">
                                    <?php echo $experienceOptions[$experience]['label']; ?> <i class="fas fa-times" style="font-size:9px;"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($worktime) && isset($worktimeOptions[$worktime])): ?>
                                <a href="job.php?tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&category=<?php echo urlencode($category); ?>"
                                    style="display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;border-radius:20px;padding:4px 11px;font-size:12px;font-weight:600;text-decoration:none;">
                                    <?php echo $worktimeOptions[$worktime]['label']; ?> <i class="fas fa-times" style="font-size:9px;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Jobs List -->
                    <div class="flex flex-col gap-5">
                        <?php if (empty($jobs)): ?>
                            <div class="empty-state">
                                <div class="es-icon"><i class="fas fa-briefcase"></i></div>
                                <h3>No jobs match your search</h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        We couldn't find any jobs for <strong>"<?php echo e($search); ?>"</strong>.
                                        Try different keywords or remove some filters.
                                    <?php else: ?>
                                        No jobs found with the current filters.<br>Try adjusting or clearing them.
                                    <?php endif; ?>
                                </p>
                                <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                                    <a href="job.php?tab=<?php echo e($tab); ?>" class="es-reset-btn">
                                        <i class="fas fa-rotate-left"></i> Clear filters
                                    </a>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'candidate'): ?>
                                        <a href="create_alert.php?q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&category=<?php echo urlencode($category); ?>&specialty=<?php echo urlencode($specialty); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>"
                                            style="display:inline-flex;align-items:center;gap:7px;background:white;color:#6366f1;font-weight:600;border:1.5px solid #c7d2fe;border-radius:14px;padding:11px 22px;font-size:13.5px;cursor:pointer;text-decoration:none;transition:.2s;">
                                            <i class="fas fa-bell"></i> Create job alert
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php
                            $avatarStyles = [
                                'background:#ede9fe;color:#6d28d9;',
                                'background:#fce7f3;color:#be185d;',
                                'background:#ecfdf5;color:#065f46;',
                                'background:#fef3c7;color:#92400e;',
                                'background:#dbeafe;color:#1e40af;',
                                'background:#fdf4ff;color:#7e22ce;',
                            ];
                            $idx = 0;
                            ?>
                            <?php foreach ($jobs as $resultJob): ?>
                                <?php
                                $avatarStyle = $avatarStyles[$idx % count($avatarStyles)];
                                $initial = strtoupper(substr($resultJob['company_name'] ?? 'J', 0, 1));
                                $idx++;
                                ?>
                                <a href="job_details.php?id=<?php echo $resultJob['id']; ?>&tab=job" class="modern-job-card">

                                    <div class="job-left">
                                        <div class="company-avatar" style="<?php echo $avatarStyle; ?>">
                                            <?php echo $initial; ?>
                                        </div>
                                        <div class="job-main-info">
                                            <h3 class="job-title"><?php echo e($resultJob['title']); ?></h3>
                                            <div class="job-company">
                                                <?php echo e($resultJob['company_name']); ?>
                                                <i class="fas fa-check-circle verified-icon"></i>
                                            </div>
                                            <div class="job-meta">
                                                <span><i class="fas fa-map-marker-alt"></i><?php echo e($resultJob['city']); ?></span>
                                                <span><i class="fas fa-clock"></i><?php echo timeAgo($resultJob['created_at']); ?></span>
                                            </div>
                                            <div class="job-tags">
                                                <?php if (!empty($resultJob['contract'])): ?>
                                                    <span class="modern-tag"><?php echo e($resultJob['contract']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($resultJob['experience'])): ?>
                                                    <span class="modern-tag soft"><?php echo e($resultJob['experience']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="job-description">
                                        <?php echo e(substr($resultJob['description'], 0, 120)); ?>...
                                    </div>

                                    <div class="job-right">
                                        <span class="view-job-btn">
                                            View Job <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </div>

                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex items-center justify-center gap-2 mt-10 flex-wrap">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>"
                                    class="pagination-link"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            if ($start > 1) {
                                echo '<a class="pagination-link" href="?page=1&tab=' . e($tab) . '">1</a>';
                                if ($start > 2) echo '<span class="px-2 text-slate-400">...</span>';
                            }
                            for ($i = $start; $i <= $end; $i++) {
                                $active = $i === $page ? 'active-page' : '';
                                echo '<a href="?page=' . $i . '&tab=' . e($tab) . '&q=' . urlencode($search) . '&city=' . urlencode($city) . '&contract=' . urlencode($contract) . '&experience=' . urlencode($experience) . '&worktime=' . urlencode($worktime) . '" class="pagination-link ' . $active . '">' . $i . '</a>';
                            }
                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) echo '<span class="px-2 text-slate-400">...</span>';
                                echo '<a class="pagination-link" href="?page=' . $totalPages . '&tab=' . e($tab) . '">' . $totalPages . '</a>';
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&tab=<?php echo e($tab); ?>&q=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>&contract=<?php echo urlencode($contract); ?>&experience=<?php echo urlencode($experience); ?>&worktime=<?php echo urlencode($worktime); ?>"
                                    class="pagination-link"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <script>
        function toggleFilterCard(btn) {
            const card = document.getElementById('filterCard');
            const chevron = document.getElementById('filterChevron');
            const isOpen = card.classList.contains('filter-open');
            card.classList.toggle('filter-open', !isOpen);
            btn.setAttribute('aria-expanded', !isOpen);
            chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        const jobSearch = document.getElementById('jobSearch');
        const jobResults = document.getElementById('jobResults');
        let allJobsCache = null;
        let fetchPromise = null;

        const contractColors = {
            'CDI': {
                bg: '#ede9fe',
                color: '#6d28d9'
            },
            'CDD': {
                bg: '#fce7f3',
                color: '#be185d'
            },
            'Full_Time': {
                bg: '#dbeafe',
                color: '#1e40af'
            },
            'Part_Time': {
                bg: '#ecfdf5',
                color: '#065f46'
            },
            'Freelance': {
                bg: '#fef3c7',
                color: '#92400e'
            },
            'Internship': {
                bg: '#f0fdf4',
                color: '#166534'
            },
            'Temporary': {
                bg: '#fdf4ff',
                color: '#7e22ce'
            },
        };

        function getInitialColor(idx) {
            const palette = [
                'background:#ede9fe;color:#6d28d9;',
                'background:#fce7f3;color:#be185d;',
                'background:#ecfdf5;color:#065f46;',
                'background:#fef3c7;color:#92400e;',
                'background:#dbeafe;color:#1e40af;',
                'background:#fdf4ff;color:#7e22ce;',
            ];
            return palette[idx % palette.length];
        }

        function highlightMatch(text, q) {
            if (!q) return text;
            const idx = text.toLowerCase().indexOf(q.toLowerCase());
            if (idx === -1) return text;
            return text.slice(0, idx) +
                '<mark style="background:#ede9fe;color:#6d28d9;border-radius:3px;padding:0 2px;">' +
                text.slice(idx, idx + q.length) + '</mark>' +
                text.slice(idx + q.length);
        }

        function renderResults(jobs, q) {
            if (!jobs.length) {
                jobResults.innerHTML = `<div style="padding:18px 16px;text-align:center;color:#94a3b8;font-size:13px;">
                    <i class="fas fa-search" style="display:block;font-size:22px;margin-bottom:8px;opacity:.4;"></i>
                    No results for "<strong style="color:#0f172a;">${q}</strong>"
                </div>`;
                jobResults.classList.remove('hidden');
                return;
            }
            jobResults.innerHTML = jobs.slice(0, 7).map((job, i) => {
                const cc = contractColors[job.contract] || {
                    bg: '#f1f5f9',
                    color: '#475569'
                };
                const ini = (job.company_name || 'J')[0].toUpperCase();
                return `
                <div class="job-result" data-title="${job.title.replace(/"/g, '&quot;')}"
                    style="padding:11px 14px;display:flex;align-items:center;gap:11px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:.15s;"
                    onmouseenter="this.style.background='#f8fafc'"
                    onmouseleave="this.style.background='white'">
                    <div style="${getInitialColor(i)}width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;">${ini}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${highlightMatch(job.title, q)}</div>
                        <div style="font-size:11.5px;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:5px;">
                            ${highlightMatch(job.company_name, q)}
                            <span style="opacity:.35;">·</span>
                            <i class="fas fa-map-marker-alt" style="font-size:10px;color:#9ca3af;"></i>
                            ${job.city}
                        </div>
                    </div>
                    <span style="background:${cc.bg};color:${cc.color};font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:20px;flex-shrink:0;white-space:nowrap;">${job.contract || ''}</span>
                </div>`;
            }).join('');

            if (jobs.length > 7) {
                jobResults.innerHTML += `<div style="padding:10px 14px;text-align:center;border-top:1px solid #f1f5f9;">
                    <a href="job.php?q=${encodeURIComponent(q)}&tab=all" style="font-size:12.5px;font-weight:600;color:#6366f1;text-decoration:none;">
                        Show all ${jobs.length} results <i class="fas fa-arrow-right" style="font-size:10px;margin-left:4px;"></i>
                    </a>
                </div>`;
            }

            jobResults.classList.remove('hidden');
            jobResults.querySelectorAll('.job-result').forEach(item => {
                item.addEventListener('click', function() {
                    jobSearch.value = this.dataset.title;
                    jobResults.classList.add('hidden');
                    jobSearch.closest('form').submit();
                });
            });
        }

        function filterAndShow(q) {
            if (!q || q.length < 1) {
                jobResults.classList.add('hidden');
                return;
            }
            if (!allJobsCache) {
                if (!fetchPromise) {
                    fetchPromise = fetch('live-search.php?json=1')
                        .then(r => r.json())
                        .then(data => {
                            allJobsCache = data;
                            fetchPromise = null;
                        })
                        .catch(() => {
                            fetchPromise = null;
                        });
                }
                (fetchPromise || Promise.resolve()).then(() => {
                    if (allJobsCache) filterAndShow(jobSearch.value.trim());
                });
                return;
            }
            const ql = q.toLowerCase();
            const results = allJobsCache.filter(j =>
                j.title.toLowerCase().includes(ql) ||
                (j.company_name || '').toLowerCase().includes(ql) ||
                (j.city || '').toLowerCase().includes(ql) ||
                (j.contract || '').toLowerCase().includes(ql)
            );
            renderResults(results, q);
        }

        fetch('live-search.php?json=1').then(r => r.json()).then(data => {
            allJobsCache = data;
        });

        let debounce;
        jobSearch.addEventListener('input', function() {
            clearTimeout(debounce);
            debounce = setTimeout(() => filterAndShow(this.value.trim()), 120);
        });
        jobSearch.addEventListener('focus', function() {
            if (this.value.trim().length >= 1) filterAndShow(this.value.trim());
        });

        const wilayaInput = document.getElementById('wilayaFilterInput');
        const wilayaDropdown = document.getElementById('wilayaDropdown');
        const wilayaHidden = document.getElementById('cityFilterValue');

        wilayaInput.addEventListener('click', () => wilayaDropdown.classList.toggle('open'));
        document.querySelectorAll('#wilayaDropdown .wilaya-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                wilayaInput.value = opt.dataset.val;
                wilayaHidden.value = opt.dataset.val;
                wilayaDropdown.classList.remove('open');
            });
        });

        const categoryInput = document.getElementById('categoryFilterInput');
        const categoryDropdown = document.getElementById('categoryDropdown');
        const categoryOpts = categoryDropdown.querySelectorAll('.wilaya-opt');

        categoryInput.addEventListener('focus', () => categoryDropdown.classList.add('open'));
        categoryInput.addEventListener('input', () => {
            categoryDropdown.classList.add('open');
            const v = categoryInput.value.toLowerCase();
            categoryOpts.forEach(opt => {
                opt.style.display = opt.textContent.toLowerCase().includes(v) ? 'flex' : 'none';
            });
        });
        categoryOpts.forEach(opt => {
            opt.addEventListener('click', () => {
                categoryInput.value = opt.dataset.val;
                categoryDropdown.classList.remove('open');
            });
        });

        const specialtyInput = document.getElementById('specialtyFilterInput');
        const specialtyDropdown = document.getElementById('specialtyDropdown');
        const specialtyOpts = specialtyDropdown.querySelectorAll('.wilaya-opt');

        specialtyInput.addEventListener('focus', () => specialtyDropdown.classList.add('open'));
        specialtyInput.addEventListener('input', () => {
            specialtyDropdown.classList.add('open');
            const v = specialtyInput.value.toLowerCase();
            specialtyOpts.forEach(opt => {
                opt.style.display = opt.textContent.toLowerCase().includes(v) ? 'flex' : 'none';
            });
        });
        specialtyOpts.forEach(opt => {
            opt.addEventListener('click', () => {
                specialtyInput.value = opt.dataset.val;
                specialtyDropdown.classList.remove('open');
            });
        });

        function toggleFChip(chip, hiddenId) {
            const group = chip.dataset.group;
            const hidden = document.getElementById(hiddenId);
            document.querySelectorAll(`.fchip[data-group="${group}"]`).forEach(c => c.classList.remove('active'));
            if (hidden.value === chip.dataset.val) {
                hidden.value = '';
            } else {
                chip.classList.add('active');
                hidden.value = chip.dataset.val;
            }
        }

        function toggleWT(btn, hiddenId) {
            const hidden = document.getElementById(hiddenId);
            if (hidden.value === btn.dataset.val) {
                btn.classList.remove('active');
                hidden.value = '';
            } else {
                document.querySelectorAll('.wt-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                hidden.value = btn.dataset.val;
            }
        }

        document.addEventListener('click', function(e) {
            if (!jobSearch.contains(e.target) && !jobResults.contains(e.target))
                jobResults.classList.add('hidden');
            if (!wilayaInput.contains(e.target) && !wilayaDropdown.contains(e.target))
                wilayaDropdown.classList.remove('open');
            if (!categoryInput.contains(e.target) && !categoryDropdown.contains(e.target))
                categoryDropdown.classList.remove('open');
            if (!specialtyInput.contains(e.target) && !specialtyDropdown.contains(e.target))
                specialtyDropdown.classList.remove('open');
        });

        function saveJobQuick(jobId) {
            <?php if (!isLoggedIn()): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?id=' + jobId;
            form.innerHTML = '<input type="hidden" name="action" value="save">';
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>