<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();

if ($_SESSION['user']['role'] !== 'company') {
    header('Location: index.php');
    exit;
}

$profile = getCompanyProfile($_SESSION['user']['id']);
$section = trim($_GET['section'] ?? 'all');
$validSections = ['basic', 'contact', 'description', 'mission', 'vision', 'specialties', 'benefits', 'social', 'all'];
if (!in_array($section, $validSections)) $section = 'all';

$isSectionEdit = ($section !== 'all');
$currentStep   = intval($_GET['step'] ?? 1);
$totalSteps    = 3;
$error         = '';

$sectionMeta = [
    'basic'       => ['label' => 'Basic Info',       'icon' => 'ti-building',  'desc' => 'Company name, industry, size and logo'],
    'contact'     => ['label' => 'Contact',          'icon' => 'ti-phone',     'desc' => 'Phone, website, address and working hours'],
    'description' => ['label' => 'About',            'icon' => 'ti-file-text', 'desc' => 'General description of your company'],
    'mission'     => ['label' => 'Our Mission',      'icon' => 'ti-target',    'desc' => 'What drives your company forward'],
    'vision'      => ['label' => 'Our Vision',       'icon' => 'ti-eye',       'desc' => 'Where your company is heading'],
    'specialties' => ['label' => 'Specialties',      'icon' => 'ti-bulb',      'desc' => 'What your company excels at'],
    'benefits'    => ['label' => 'Benefits & Perks', 'icon' => 'ti-gift',      'desc' => 'What you offer to your employees'],
    'social'      => ['label' => 'Social Media',     'icon' => 'ti-share',     'desc' => 'LinkedIn, Facebook, Twitter, Instagram and GitHub'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uploadedLogoPath = $profile['logo_url'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $newPath = uploadImage($_FILES['logo']);
        if ($newPath) $uploadedLogoPath = $newPath;
    }

    $data = [
        'company_name'    => trim($_POST['company_name']    ?? $profile['company_name']    ?? ''),
        'industry'        => trim($_POST['industry']        ?? $profile['industry']        ?? ''),
        'city'            => trim($_POST['city']            ?? $profile['city']            ?? ''),
        'phone'           => trim($_POST['phone']           ?? $profile['phone']           ?? ''),
        'website'         => trim($_POST['website']         ?? $profile['website']         ?? ''),
        'description'     => trim($_POST['description']     ?? $profile['description']     ?? ''),
        'size'            => trim($_POST['size']            ?? $profile['size']            ?? ''),
        'logo_url'        => $uploadedLogoPath,
        'address'         => trim($_POST['address']         ?? $profile['address']         ?? ''),
        'mission'         => trim($_POST['mission']         ?? $profile['mission']         ?? ''),
        'vision'          => trim($_POST['vision']          ?? $profile['vision']          ?? ''),
        'specialties'     => trim($_POST['specialties']     ?? $profile['specialties']     ?? ''),
        'benefits'        => trim($_POST['benefits']        ?? $profile['benefits']        ?? ''),
        'working_hours'   => trim($_POST['working_hours']   ?? $profile['working_hours']   ?? ''),
        'founded_year'    => trim($_POST['founded_year']    ?? $profile['founded_year']    ?? ''),
        'employees_count' => trim($_POST['employees_count'] ?? $profile['employees_count'] ?? ''),
        'instagram'       => trim($_POST['instagram']       ?? $profile['instagram']       ?? ''),
        'github'          => trim($_POST['github']          ?? $profile['github']          ?? ''),
        'linkedin'        => trim($_POST['linkedin']        ?? $profile['linkedin']        ?? ''),
        'facebook'        => trim($_POST['facebook']        ?? $profile['facebook']        ?? ''),
        'twitter'         => trim($_POST['twitter']         ?? $profile['twitter']         ?? ''),
    ];

    if (!$data['company_name'] || !$data['industry'] || !$data['city']) {
        $error = 'Company Name, Industry and City are required.';
    } else {
        updateCompanyProfile($_SESSION['user']['id'], $data);

        if ($isSectionEdit) {
            header('Location: edit_company_profile.php?section=' . $section . '&saved=1');
            exit;
        }
        if ($currentStep < $totalSteps) {
            header('Location: edit_company_profile.php?step=' . ($currentStep + 1));
            exit;
        }
        header('Location: company_profile.php');
        exit;
    }
}

$saved    = isset($_GET['saved']);
$initials = strtoupper(mb_substr($profile['company_name'] ?? 'C', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $isSectionEdit ? 'Edit ' . $sectionMeta[$section]['label'] : 'Complete Company Profile'; ?> | JobDZ</title>
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
            -webkit-text-size-adjust: 100%;
        }

        main {
            padding: 16px 12px 80px;
        }


        .ep-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .ep-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #64748b;
            text-decoration: none;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: color .15s;
            margin-bottom: 1.2rem;
            padding: 8px 0;
            -webkit-tap-highlight-color: transparent;
        }

        .ep-back-btn:hover {
            color: #6366f1;
        }

        .ep-progress-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
        }

        .ep-progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            gap: 5px;
            position: relative;
            min-width: 80px;
        }

        .ep-progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: #e8eef6;
            z-index: 0;
        }

        .ep-progress-step.done:not(:last-child)::after {
            background: #1D9E75;
        }

        .ep-progress-step.active:not(:last-child)::after {
            background: #6366f1;
        }

        .ep-progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 2px solid #e8eef6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #94a3b8;
            z-index: 1;
            transition: all .3s;
        }

        .ep-progress-step.active .ep-progress-circle {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, .25);
        }

        .ep-progress-step.done .ep-progress-circle {
            background: #1D9E75;
            border-color: #1D9E75;
            color: white;
        }

        .ep-progress-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #94a3b8;
        }

        .ep-progress-step.active .ep-progress-label {
            color: #6366f1;
        }

        .ep-progress-step.done .ep-progress-label {
            color: #1D9E75;
        }

        .ep-progress-name {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-align: center;
            line-height: 1.3;
        }

        .ep-progress-step.active .ep-progress-name {
            color: #0f172a;
        }

        .ep-progress-step.done .ep-progress-name {
            color: #1D9E75;
        }

        .ep-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:968px) {
            .ep-layout {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        .ep-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: sticky;
            top: 100px;
        }

        @media(max-width:968px) {
            .ep-sidebar {
                display: none;
            }
        }

        .ep-sidebar-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .ep-sidebar-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .ep-sidebar-text {
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
        }

        .step-item {
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: all .2s;
        }

        .step-item.active {
            background: #6366f1;
        }

        .step-item.done {
            background: #f0fdf4;
        }

        .step-item.pending {
            background: #f8fafc;
        }

        .step-item-ico {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .step-item.active .step-item-ico {
            background: rgba(255, 255, 255, .2);
            color: white;
        }

        .step-item.done .step-item-ico {
            background: #dcfce7;
            color: #16a34a;
        }

        .step-item.pending .step-item-ico {
            background: white;
            color: #6366f1;
            border: 1px solid #e8eef6;
        }

        .step-item-label {
            font-size: 9.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .step-item.active .step-item-label {
            color: rgba(255, 255, 255, .7);
        }

        .step-item.done .step-item-label {
            color: #16a34a;
        }

        .step-item.pending .step-item-label {
            color: #94a3b8;
        }

        .step-item-name {
            font-size: 12.5px;
            font-weight: 600;
            margin-top: 2px;
        }

        .step-item.active .step-item-name {
            color: white;
        }

        .step-item.done .step-item-name {
            color: #15803d;
        }

        .step-item.pending .step-item-name {
            color: #0f172a;
        }

        .step-item-desc {
            font-size: 11px;
            margin-top: 2px;
        }

        .step-item.active .step-item-desc {
            color: rgba(255, 255, 255, .7);
        }

        .step-item.done .step-item-desc {
            color: #16a34a;
        }

        .step-item.pending .step-item-desc {
            color: #94a3b8;
        }

        .ep-section-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:768px) {
            .ep-section-layout {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .ep-section-nav {
            position: sticky;
            top: 100px;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 10px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        @media(max-width:768px) {
            .ep-section-nav {
                position: static;
                border-radius: 16px 16px 0 0;
                border-bottom: none;
                padding: 10px 8px 0;
                overflow: hidden;
            }
        }

        .ep-section-nav-title {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: 8px 12px 10px;
        }

        @media(max-width:768px) {
            .ep-section-nav-title {
                display: none;
            }
        }

        .ep-snav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            transition: all .15s;
            margin-bottom: 2px;
        }

        .ep-snav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .ep-snav-item.active {
            background: #6366f1;
            color: white;
            font-weight: 600;
        }

        .ep-snav-item i {
            font-size: 15px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .ep-snav-sep {
            height: 1px;
            background: #f1f5f9;
            margin: 6px 0;
        }

        .ep-snav-group-label {
            font-size: 9px;
            font-weight: 700;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: 6px 12px 2px;
        }

        @media(max-width:768px) {
            .ep-snav-sep {
                display: none;
            }

            .ep-snav-group-label {
                display: none;
            }
        }

        @media(max-width:768px) {
            .ep-snav-scroll-wrapper {
                display: flex;
                gap: 6px;
                overflow-x: auto;
                padding: 0 4px 10px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .ep-snav-scroll-wrapper::-webkit-scrollbar {
                display: none;
            }

            .ep-snav-item {
                flex-shrink: 0;
                white-space: nowrap;
                border-radius: 20px;
                padding: 8px 14px;
                font-size: 12px;
                margin-bottom: 0;
                border: 1px solid #e8eef6;
                background: #f8fafc;
            }

            .ep-snav-item:hover {
                background: #f1f5f9;
            }

            .ep-snav-item.active {
                background: #6366f1;
                border-color: #6366f1;
                color: white;
            }

            .ep-snav-item i {
                display: none;
            }
        }

        .ep-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        @media(max-width:768px) {
            .ep-section-layout .ep-card {
                border-radius: 0 0 20px 20px;
                border-top: none;
                padding: 20px 16px 24px;
            }
        }

        @media(max-width:580px) {
            .ep-layout .ep-card {
                padding: 18px 14px;
                border-radius: 18px;
            }
        }

        .ep-card-header {
            margin-bottom: 22px;
        }

        .ep-card-header h2 {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .ep-card-header h2 i {
            font-size: 20px;
            color: #6366f1;
        }

        .ep-card-header p {
            font-size: 13px;
            color: #64748b;
        }

        @media(max-width:480px) {
            .ep-card-header h2 {
                font-size: 17px;
            }
        }

        .ep-field {
            margin-bottom: 18px;
        }

        .ep-field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 7px;
        }

        .ep-field-required::after {
            content: ' *';
            color: #ef4444;
        }

        .ep-field>input,
        .ep-field>textarea,
        .ep-field>select {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: all .15s;
            font-family: inherit;
            font-size: max(16px, 14px);
        }

        .ep-field>input:focus,
        .ep-field>textarea:focus,
        .ep-field>select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
            background: white;
        }

        .ep-field>textarea {
            min-height: 130px;
            resize: vertical;
            line-height: 1.7;
            font-size: max(16px, 14px);
        }

        .ep-field>select {
            appearance: none;
            cursor: pointer;
            padding-right: 36px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath fill='%2394a3b8' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            background-color: #f8fafc;
        }

        .ep-field-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 5px;
        }

        .ep-field-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        @media(max-width:600px) {
            .ep-field-group {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media(max-width:600px) {
            .ep-field-group>.ep-field {
                margin-bottom: 18px;
            }
        }

        .ep-logo-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e8eef6;
        }

        .ep-logo-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .ep-logo-preview {
            width: 84px;
            height: 84px;
            border-radius: 18px;
            background: #ede9fe;
            border: 2px solid #6366f1;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            color: #6366f1;
            flex-shrink: 0;
        }

        .ep-logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ep-logo-content h3 {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .ep-logo-content p {
            font-size: 12px;
            color: #64748b;
            margin: 0 0 10px;
        }

        .ep-file-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            -webkit-tap-highlight-color: transparent;
        }

        .ep-file-label:hover {
            background: #4f46e5;
        }

        .ep-file-label input {
            display: none;
        }

        .social-field-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 11px 14px;
            margin-bottom: 10px;
            transition: border-color .15s;
        }

        .social-field-row:focus-within {
            border-color: #a5b4fc;
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        .social-field-icon {
            font-size: 18px;
            color: #94a3b8;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }

        .social-field-label {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            width: 82px;
            flex-shrink: 0;
        }

        .social-field-row input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            font-size: max(16px, 13px);
            color: #0f172a;
            font-family: inherit;
            min-width: 0;
        }

        @media(max-width:400px) {
            .social-field-label {
                width: 72px;
                font-size: 11px;
            }
        }

        .ep-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #e8eef6;
            flex-wrap: wrap;
        }

        @media(max-width:480px) {
            .ep-actions {
                flex-direction: column-reverse;
                gap: 10px;
            }

            .ep-actions>* {
                width: 100%;
            }

            .ep-actions>div {
                display: flex;
                gap: 8px;
            }
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 14px;
            padding: 13px 22px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
            min-height: 46px;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: #4f46e5;
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #334155;
            border: 1px solid #e8eef6;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #6366f1;
            color: #6366f1;
        }

        @media(min-width:481px) {
            .btn-primary {
                width: auto;
            }
        }

        .ep-alert {
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ep-alert-success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #065f46;
        }

        .ep-alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        input:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .ep-mobile-step-hint {
            display: none;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 16px;
        }

        @media(max-width:968px) {
            .ep-mobile-step-hint {
                display: block;
            }
        }
    </style>
</head>

<body>

    <main>
        <div class="ep-container">

            <a href="company_profile.php" class="ep-back-btn">
                <i class="ti ti-arrow-left"></i> Back to profile
            </a>

            <?php if (!$isSectionEdit): ?>
                <?php
                $steps = [
                    1 => ['label' => 'Step 1', 'name' => 'Company Info',    'icon' => 'ti-building'],
                    2 => ['label' => 'Step 2', 'name' => 'Contact & About', 'icon' => 'ti-phone'],
                    3 => ['label' => 'Step 3', 'name' => 'Social & Finish', 'icon' => 'ti-share'],
                ];
                $stepDescs = [
                    1 => ['icon' => 'ti-building', 'title' => 'Company Info',    'desc' => 'Name, industry & logo'],
                    2 => ['icon' => 'ti-phone',   'title' => 'Contact & About', 'desc' => 'Contact & description'],
                    3 => ['icon' => 'ti-share',   'title' => 'Social & Finish', 'desc' => 'Social media links'],
                ];
                ?>

                <div class="ep-progress-wrapper">
                    <?php for ($i = 1; $i <= $totalSteps; $i++):
                        $cls = $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : ''); ?>
                        <div class="ep-progress-step <?= $cls ?>">
                            <div class="ep-progress-circle"><?= $i < $currentStep ? '<i class="ti ti-check"></i>' : $i ?></div>
                            <div class="ep-progress-label"><?= $steps[$i]['label'] ?></div>
                            <div class="ep-progress-name"><?= $steps[$i]['name'] ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Mobile step hint -->
                <div class="ep-mobile-step-hint">
                    Step <?= $currentStep ?> of <?= $totalSteps ?> &mdash; <?= $steps[$currentStep]['name'] ?>
                </div>

                <div class="ep-layout">
                    <aside class="ep-sidebar">
                        <div class="ep-sidebar-card">
                            <div class="ep-sidebar-title">Complete Your Profile</div>
                            <div class="ep-sidebar-text">3 steps to attract the best candidates.</div>
                            <div style="margin-top:14px;">
                                <?php for ($i = 1; $i <= $totalSteps; $i++):
                                    $cls = $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : 'pending'); ?>
                                    <div class="step-item <?= $cls ?>">
                                        <div class="step-item-ico"><i class="ti <?= $stepDescs[$i]['icon'] ?>"></i></div>
                                        <div>
                                            <div class="step-item-label">Step <?= $i ?></div>
                                            <div class="step-item-name"><?= $stepDescs[$i]['title'] ?></div>
                                            <div class="step-item-desc"><?= $stepDescs[$i]['desc'] ?></div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="ep-sidebar-card">
                            <div style="font-size:28px;margin-bottom:10px;">🏢</div>
                            <div class="ep-sidebar-title">Why complete your profile?</div>
                            <div class="ep-sidebar-text">Complete company profiles attract more qualified candidates and appear higher in search results.</div>
                        </div>
                    </aside>

                    <div class="ep-card">
                        <?php if ($saved): ?><div class="ep-alert ep-alert-success"><i class="ti ti-circle-check"></i> Changes saved successfully.</div><?php endif; ?>
                        <?php if ($error): ?><div class="ep-alert ep-alert-danger"><i class="ti ti-alert-circle"></i> <?php echo e($error); ?></div><?php endif; ?>

                        <div class="ep-card-header">
                            <h2><i class="ti <?= $stepDescs[$currentStep]['icon'] ?>"></i> <?= $steps[$currentStep]['name'] ?></h2>
                            <p>
                                <?php if ($currentStep === 1): ?>Set up your company's basic information and brand identity.
                                <?php elseif ($currentStep === 2): ?>Add contact details and tell candidates about your company.
                                <?php else: ?>Link your social media profiles and finish your setup.<?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" action="edit_company_profile.php?step=<?= $currentStep ?>" enctype="multipart/form-data">

                            <?php if ($currentStep === 1): ?>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label class="ep-field-required">Company Name</label><input type="text" name="company_name" value="<?php echo e($profile['company_name'] ?? ''); ?>" required></div>
                                    <div class="ep-field"><label class="ep-field-required">Industry</label><input type="text" name="industry" value="<?php echo e($profile['industry'] ?? ''); ?>" placeholder="e.g., Information Technology" required></div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label class="ep-field-required">City / Wilaya</label><select name="city" required>
                                            <option value="">— Select your wilaya —</option><?php foreach (getCityOptions() as $opt): ?><option value="<?php echo e($opt); ?>" <?php echo ($profile['city'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option><?php endforeach; ?>
                                        </select></div>
                                    <div class="ep-field"><label>Company Size</label><input type="text" name="size" value="<?php echo e($profile['size'] ?? ''); ?>" placeholder="e.g., 50-100 employees"></div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Founded Year</label><input type="number" name="founded_year" value="<?php echo e($profile['founded_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g., 2015"></div>
                                    <div class="ep-field"><label>Employees Count</label><input type="number" name="employees_count" value="<?php echo e($profile['employees_count'] ?? ''); ?>" min="1" placeholder="e.g., 50"></div>
                                </div>
                                <div class="ep-logo-section">
                                    <label style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Company Logo</label>
                                    <div class="ep-logo-row">
                                        <div class="ep-logo-preview" id="logoPreview"><?php if (!empty($profile['logo_url'])): ?><img src="<?php echo e($profile['logo_url']); ?>" alt="Logo"><?php else: ?><?= $initials ?><?php endif; ?></div>
                                        <div class="ep-logo-content">
                                            <h3>Company Logo</h3>
                                            <p>Help candidates recognize your brand.</p>
                                            <label class="ep-file-label"><i class="ti ti-upload"></i> Upload Logo<input type="file" name="logo" accept="image/*" onchange="previewLogo(this)"></label>
                                            <div class="ep-field-hint" style="margin-top:6px;">JPG, PNG · Max 2MB</div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($currentStep === 2): ?>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>" placeholder="+213 XXX XXX XXX"></div>
                                    <div class="ep-field"><label>Website</label><input type="url" name="website" value="<?php echo e($profile['website'] ?? ''); ?>" placeholder="https://example.com"></div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Address</label><input type="text" name="address" value="<?php echo e($profile['address'] ?? ''); ?>" placeholder="Company address"></div>
                                    <div class="ep-field"><label>Working Hours</label><input type="text" name="working_hours" value="<?php echo e($profile['working_hours'] ?? ''); ?>" placeholder="Sun – Thu · 8AM – 5PM"></div>
                                </div>
                                <div class="ep-field"><label>About the Company</label><textarea name="description" placeholder="Tell candidates about your culture, values and achievements..."><?php echo e($profile['description'] ?? ''); ?></textarea></div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Mission</label><textarea name="mission" placeholder="Your company mission..." style="min-height:90px;"><?php echo e($profile['mission'] ?? ''); ?></textarea></div>
                                    <div class="ep-field"><label>Vision</label><textarea name="vision" placeholder="Your company vision..." style="min-height:90px;"><?php echo e($profile['vision'] ?? ''); ?></textarea></div>
                                </div>
                                <div class="ep-field"><label>Specialties</label><input type="text" name="specialties" value="<?php echo e($profile['specialties'] ?? ''); ?>" placeholder="Web Development, AI, UI/UX ...">
                                    <div class="ep-field-hint">Separate with commas</div>
                                </div>
                                <div class="ep-field"><label>Benefits &amp; Perks</label><textarea name="benefits" placeholder="Health insurance, Remote work, Paid leave..."><?php echo e($profile['benefits'] ?? ''); ?></textarea></div>

                            <?php elseif ($currentStep === 3): ?>
                                <?php $socials = ['linkedin' => ['ti ti-brand-linkedin', 'LinkedIn', 'https://linkedin.com/company/...'], 'facebook' => ['ti ti-brand-facebook', 'Facebook', 'https://facebook.com/...'], 'twitter' => ['ti ti-brand-twitter', 'Twitter', 'https://twitter.com/...'], 'instagram' => ['ti ti-brand-instagram', 'Instagram', 'https://instagram.com/...'], 'github' => ['ti ti-brand-github', 'GitHub', 'https://github.com/...']];
                                foreach ($socials as $key => [$icon, $label, $ph]): ?>
                                    <div class="social-field-row"><i class="<?= $icon ?> social-field-icon"></i><span class="social-field-label"><?= $label ?></span><input type="url" name="<?= $key ?>" value="<?php echo e($profile[$key] ?? ''); ?>" placeholder="<?= $ph ?>"></div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="ep-actions">
                                <div><?php if ($currentStep > 1): ?><a href="edit_company_profile.php?step=<?= $currentStep - 1 ?>" class="btn-secondary"><i class="ti ti-arrow-left"></i> Back</a><?php endif; ?></div>
                                <div><?php if ($currentStep < $totalSteps): ?><button type="submit" class="btn-primary">Continue <i class="ti ti-arrow-right"></i></button><?php else: ?><button type="submit" class="btn-primary"><i class="ti ti-check"></i> Complete Profile</button><?php endif; ?></div>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>

                <div class="ep-section-layout">

                    <nav class="ep-section-nav">
                        <div class="ep-section-nav-title">Edit Section</div>

                        <!-- Mobile: horizontal scroll wrapper -->
                        <div class="ep-snav-scroll-wrapper">
                            <a href="edit_company_profile.php?section=basic" class="ep-snav-item <?= $section === 'basic'      ? 'active' : '' ?>"><i class="ti ti-building"></i> Basic Info</a>
                            <a href="edit_company_profile.php?section=contact" class="ep-snav-item <?= $section === 'contact'    ? 'active' : '' ?>"><i class="ti ti-phone"></i> Contact</a>
                            <a href="edit_company_profile.php?section=description" class="ep-snav-item <?= $section === 'description' ? 'active' : '' ?>"><i class="ti ti-file-text"></i> About</a>
                            <a href="edit_company_profile.php?section=mission" class="ep-snav-item <?= $section === 'mission'    ? 'active' : '' ?>"><i class="ti ti-target"></i> Mission</a>
                            <a href="edit_company_profile.php?section=vision" class="ep-snav-item <?= $section === 'vision'     ? 'active' : '' ?>"><i class="ti ti-eye"></i> Vision</a>
                            <a href="edit_company_profile.php?section=specialties" class="ep-snav-item <?= $section === 'specialties' ? 'active' : '' ?>"><i class="ti ti-bulb"></i> Specialties</a>
                            <a href="edit_company_profile.php?section=benefits" class="ep-snav-item <?= $section === 'benefits'   ? 'active' : '' ?>"><i class="ti ti-gift"></i> Benefits</a>
                            <a href="edit_company_profile.php?section=social" class="ep-snav-item <?= $section === 'social'     ? 'active' : '' ?>"><i class="ti ti-share"></i> Social</a>
                        </div>

                        <!-- Desktop: group labels shown via CSS only -->
                        <div class="ep-snav-desktop-nav" style="display:contents;">
                            <!-- rendered same anchors above, hidden on mobile via CSS -->
                        </div>
                    </nav>

                    <div class="ep-card">
                        <?php if ($saved): ?><div class="ep-alert ep-alert-success"><i class="ti ti-circle-check"></i> Changes saved successfully.</div><?php endif; ?>
                        <?php if ($error): ?><div class="ep-alert ep-alert-danger"><i class="ti ti-alert-circle"></i> <?php echo e($error); ?></div><?php endif; ?>

                        <div class="ep-card-header">
                            <h2><i class="ti <?= $sectionMeta[$section]['icon'] ?>"></i> <?= $sectionMeta[$section]['label'] ?></h2>
                            <p><?= $sectionMeta[$section]['desc'] ?></p>
                        </div>

                        <form method="POST" action="edit_company_profile.php?section=<?= $section ?>" enctype="multipart/form-data">

                            <?php if ($section === 'basic'): ?>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label class="ep-field-required">Company Name</label><input type="text" name="company_name" value="<?php echo e($profile['company_name'] ?? ''); ?>" required></div>
                                    <div class="ep-field"><label class="ep-field-required">Industry</label><input type="text" name="industry" value="<?php echo e($profile['industry'] ?? ''); ?>" placeholder="e.g., Information Technology" required></div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label class="ep-field-required">City / Wilaya</label><select name="city" required>
                                            <option value="">— Select your wilaya —</option><?php foreach (getCityOptions() as $opt): ?><option value="<?php echo e($opt); ?>" <?php echo ($profile['city'] ?? '') === $opt ? 'selected' : ''; ?>><?php echo e($opt); ?></option><?php endforeach; ?>
                                        </select></div>
                                    <div class="ep-field"><label>Company Size</label><input type="text" name="size" value="<?php echo e($profile['size'] ?? ''); ?>" placeholder="e.g., 50-100 employees"></div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Founded Year</label><input type="number" name="founded_year" value="<?php echo e($profile['founded_year'] ?? ''); ?>" min="1900" max="<?php echo date('Y'); ?>" placeholder="e.g., 2015"></div>
                                    <div class="ep-field"><label>Employees Count</label><input type="number" name="employees_count" value="<?php echo e($profile['employees_count'] ?? ''); ?>" min="1" placeholder="e.g., 50"></div>
                                </div>
                                <div class="ep-logo-section">
                                    <label style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Company Logo</label>
                                    <div class="ep-logo-row">
                                        <div class="ep-logo-preview" id="logoPreview"><?php if (!empty($profile['logo_url'])): ?><img src="<?php echo e($profile['logo_url']); ?>" alt="Logo"><?php else: ?><?= $initials ?><?php endif; ?></div>
                                        <div class="ep-logo-content">
                                            <h3>Company Logo</h3>
                                            <p>Help candidates recognize your brand.</p>
                                            <label class="ep-file-label"><i class="ti ti-upload"></i> Upload Logo<input type="file" name="logo" accept="image/*" onchange="previewLogo(this)"></label>
                                            <div class="ep-field-hint" style="margin-top:6px;">JPG, PNG · Max 2MB</div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($section === 'contact'): ?>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>" placeholder="+213 XXX XXX XXX"></div>
                                    <div class="ep-field"><label>Website</label><input type="url" name="website" value="<?php echo e($profile['website'] ?? ''); ?>" placeholder="https://example.com"></div>
                                </div>
                                <div class="ep-field"><label>Email</label><input type="email" value="<?php echo e($_SESSION['user']['email']); ?>" disabled>
                                    <div class="ep-field-hint">Primary contact email · cannot be changed</div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:0;">
                                    <div class="ep-field"><label>Address</label><input type="text" name="address" value="<?php echo e($profile['address'] ?? ''); ?>" placeholder="Company address"></div>
                                    <div class="ep-field"><label>Working Hours</label><input type="text" name="working_hours" value="<?php echo e($profile['working_hours'] ?? ''); ?>" placeholder="Sun – Thu · 8AM – 5PM"></div>
                                </div>

                            <?php elseif ($section === 'description'): ?>
                                <div class="ep-field"><label>About the Company</label><textarea name="description" placeholder="Tell candidates about your culture, values and achievements..."><?php echo e($profile['description'] ?? ''); ?></textarea></div>

                            <?php elseif ($section === 'mission'): ?>
                                <div class="ep-field"><label>Our Mission</label><textarea name="mission" placeholder="What drives your company forward..."><?php echo e($profile['mission'] ?? ''); ?></textarea></div>

                            <?php elseif ($section === 'vision'): ?>
                                <div class="ep-field"><label>Our Vision</label><textarea name="vision" placeholder="Where your company is heading..."><?php echo e($profile['vision'] ?? ''); ?></textarea></div>

                            <?php elseif ($section === 'specialties'): ?>
                                <div class="ep-field"><label>Specialties</label><input type="text" name="specialties" value="<?php echo e($profile['specialties'] ?? ''); ?>" placeholder="Web Development, AI, UI/UX ...">
                                    <div class="ep-field-hint">Separate with commas</div>
                                </div>

                            <?php elseif ($section === 'benefits'): ?>
                                <div class="ep-field"><label>Benefits &amp; Perks</label><textarea name="benefits" placeholder="Health insurance, Remote work, Paid leave..."><?php echo e($profile['benefits'] ?? ''); ?></textarea></div>

                            <?php elseif ($section === 'social'): ?>
                                <?php $socials = ['linkedin' => ['ti ti-brand-linkedin', 'LinkedIn', 'https://linkedin.com/company/...'], 'facebook' => ['ti ti-brand-facebook', 'Facebook', 'https://facebook.com/...'], 'twitter' => ['ti ti-brand-twitter', 'Twitter', 'https://twitter.com/...'], 'instagram' => ['ti ti-brand-instagram', 'Instagram', 'https://instagram.com/...'], 'github' => ['ti ti-brand-github', 'GitHub', 'https://github.com/...']];
                                foreach ($socials as $key => [$icon, $label, $ph]): ?>
                                    <div class="social-field-row"><i class="<?= $icon ?> social-field-icon"></i><span class="social-field-label"><?= $label ?></span><input type="url" name="<?= $key ?>" value="<?php echo e($profile[$key] ?? ''); ?>" placeholder="<?= $ph ?>"></div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="ep-actions">
                                <a href="company_profile.php" class="btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                                <button type="submit" class="btn-primary"><i class="ti ti-device-floppy"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>

                </div>

            <?php endif; ?>

        </div>
    </main>

    <script src="js/script.js"></script>
    <script>
        function previewLogo(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('logoPreview').innerHTML =
                    '<img src="' + e.target.result + '" alt="Logo" style="width:100%;height:100%;object-fit:cover;">';
            };
            reader.readAsDataURL(input.files[0]);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = document.querySelector('.ep-snav-scroll-wrapper .ep-snav-item.active');
            if (activeTab) {
                activeTab.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        });
    </script>
</body>

</html>