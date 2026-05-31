<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();

if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$profile = getCandidateProfile($userId);
$isProfileIncomplete = !isCandidateProfileComplete($userId);

if ($isProfileIncomplete && !isset($_GET['step']) && !isset($_GET['section'])) {
    header('Location: edit_profile.php?step=1');
    exit;
}

$section = trim($_GET['section'] ?? 'all');
$validSections = ['personal', 'professional', 'summary', 'experience', 'education', 'skills', 'languages', 'projects', 'interests', 'social_links', 'all'];
if (!in_array($section, $validSections)) $section = 'all';

$sectionMeta = [
    'personal'     => ['label' => 'Personal Info',         'icon' => 'ti-user',         'desc' => 'Basic information about you'],
    'professional' => ['label' => 'Professional Info',      'icon' => 'ti-user-star',  'desc' =>  'Career details and work preferences'],
    'summary'      => ['label' => 'Professional Summary',   'icon' => 'ti-file-text',    'desc' => 'Your professional summary'],
    'experience'   => ['label' => 'Experience',             'icon' => 'ti-briefcase',    'desc' => 'Work experience'],
    'education'    => ['label' => 'Education',              'icon' => 'ti-school',       'desc' => 'Educational background'],
    'skills'       => ['label' => 'Skills',                 'icon' => 'ti-code',         'desc' => 'Technical and soft skills'],
    'languages'    => ['label' => 'Languages',              'icon' => 'ti-language',     'desc' => 'Languages you speak'],
    'projects'     => ['label' => 'Projects',               'icon' => 'ti-folder',       'desc' => 'Notable projects'],
    'interests'    => ['label' => 'Interests',              'icon' => 'ti-heart',        'desc' => 'Professional interests'],
    'social_links' => ['label' => 'Social Links',           'icon' => 'ti-share',        'desc' => 'Social media links'],
];

$currentStep  = intval($_GET['step'] ?? 1);
$totalSteps   = 3;
$isSectionEdit = ($section !== 'all');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $uploadedPath = $profile['image_path'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $newPath = uploadImage($_FILES['image']);
        if ($newPath) $uploadedPath = $newPath;
    }

    $data = [
        'full_name'  => trim($_POST['full_name']  ?? $profile['full_name']  ?? ''),
        'job_title'  => trim($_POST['job_title']   ?? $profile['job_title']  ?? ''),
        'summary'    => trim($_POST['summary']     ?? $profile['summary']    ?? ''),
        'phone'      => trim($_POST['phone']       ?? $profile['phone']      ?? ''),
        'city'       => trim($_POST['city']        ?? $profile['city']       ?? ''),
        'category'   => trim($_POST['category']    ?? $profile['category']   ?? ''),
        'specialty'  => trim($_POST['specialty']   ?? $profile['specialty']  ?? ''),
        'experience_level' => trim($_POST['experience_level'] ?? $profile['experience_level'] ?? ''),
        'availability' => trim($_POST['availability'] ?? $profile['availability'] ?? ''),
        'image_path' => $uploadedPath,
    ];
    updateCandidateProfile($userId, $data);

    if (isset($_POST['experience']) && is_array($_POST['experience'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_experiences WHERE user_id = ?");
        $stmt->execute([$userId]);
        foreach ($_POST['experience'] as $exp) {
            if (!empty(trim($exp['job_title'] ?? '')) || !empty(trim($exp['company_name'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO candidate_experiences (user_id,job_title,company_name,start_date,end_date,description) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$userId, trim($exp['job_title'] ?? ''), trim($exp['company_name'] ?? ''), trim($exp['start_date'] ?? ''), trim($exp['end_date'] ?? ''), trim($exp['description'] ?? '')]);
            }
        }
    }

    if (isset($_POST['education']) && is_array($_POST['education'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_educations WHERE user_id = ?");
        $stmt->execute([$userId]);
        foreach ($_POST['education'] as $edu) {
            if (!empty(trim($edu['degree'] ?? '')) || !empty(trim($edu['school'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO candidate_educations (user_id,degree,school,start_date,end_date,description) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$userId, trim($edu['degree'] ?? ''), trim($edu['school'] ?? ''), trim($edu['start_date'] ?? ''), trim($edu['end_date'] ?? ''), trim($edu['description'] ?? '')]);
            }
        }
    }

    if (isset($_POST['skills'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_skills WHERE user_id = ?");
        $stmt->execute([$userId]);
        $skillsList = is_array($_POST['skills']) ? $_POST['skills'] : explode(',', $_POST['skills']);
        foreach ($skillsList as $skill) {
            $skill = trim($skill);
            if (!empty($skill)) {
                $stmt = $pdo->prepare("INSERT INTO candidate_skills (user_id,skill_name) VALUES (?,?)");
                $stmt->execute([$userId, $skill]);
            }
        }
    }

    if (isset($_POST['languages'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_languages WHERE user_id = ?");
        $stmt->execute([$userId]);
        $langs  = $_POST['languages'] ?? [];
        $levels = $_POST['language_levels'] ?? [];
        foreach ($langs as $i => $lang) {
            $lang = trim($lang);
            $level = isset($levels[$i]) ? intval($levels[$i]) : 80;
            if (!empty($lang)) {
                $stmt = $pdo->prepare("INSERT INTO candidate_languages (user_id,language_name,level) VALUES (?,?,?)");
                $stmt->execute([$userId, $lang, $level]);
            }
        }
    }

    if (isset($_POST['projects']) && is_array($_POST['projects'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_projects WHERE user_id = ?");
        $stmt->execute([$userId]);
        foreach ($_POST['projects'] as $proj) {
            if (!empty(trim($proj['title'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO candidate_projects (user_id,title,description,demo_link,github_link) VALUES (?,?,?,?,?)");
                $stmt->execute([$userId, trim($proj['title'] ?? ''), trim($proj['description'] ?? ''), trim($proj['project_link'] ?? ''), '']);
            }
        }
    }

    if (isset($_POST['interests'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_interests WHERE user_id = ?");
        $stmt->execute([$userId]);
        $interestsList = is_array($_POST['interests']) ? $_POST['interests'] : explode(',', $_POST['interests']);
        foreach ($interestsList as $interest) {
            $interest = trim($interest);
            if (!empty($interest)) {
                $stmt = $pdo->prepare("INSERT INTO candidate_interests (user_id,interest_name) VALUES (?,?)");
                $stmt->execute([$userId, $interest]);
            }
        }
    }

    if (isset($_POST['social_links']) && is_array($_POST['social_links'])) {
        $stmt = $pdo->prepare("DELETE FROM candidate_social_links WHERE user_id = ?");
        $stmt->execute([$userId]);
        foreach ($_POST['social_links'] as $link) {
            if (!empty(trim($link['platform'] ?? '')) && !empty(trim($link['url'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO candidate_social_links (user_id,platform,url) VALUES (?,?,?)");
                $stmt->execute([$userId, trim($link['platform'] ?? ''), trim($link['url'] ?? '')]);
            }
        }
    }

    if ($isSectionEdit) {
        header('Location: edit_profile.php?section=' . $section . '&saved=1');
        exit;
    }
    if ($currentStep < $totalSteps) {
        header('Location: edit_profile.php?step=' . ($currentStep + 1));
        exit;
    }
    header('Location: profile.php?id=' . (int)($_SESSION['user']['id'] ?? 0));
    exit;
}

$experiences = getCandidateExperiences($userId);
$educations  = getCandidateEducations($userId);
$skills      = getCandidateSkills($userId);
$languages   = getCandidateLanguages($userId);
$projects    = getCandidateProjects($userId);
$interests   = getCandidateInterests($userId);
$socialLinks = getCandidateSocialLinks($userId);
$saved       = isset($_GET['saved']);

$stmt = $pdo->query("SELECT DISTINCT category FROM candidates_profiles WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT job_title FROM candidates_profiles WHERE job_title IS NOT NULL AND job_title != '' ORDER BY job_title ASC");
$jobTitles = $stmt->fetchAll(PDO::FETCH_COLUMN);

$algeriaWilayas = ['01 - Adrar', '02 - Chlef', '03 - Laghouat', '04 - Oum El Bouaghi', '05 - Batna', '06 - Béjaïa', '07 - Biskra', '08 - Béchar', '09 - Blida', '10 - Bouira', '11 - Tamanrasset', '12 - Tébessa', '13 - Tlemcen', '14 - Tiaret', '15 - Tizi Ouzou', '16 - Alger', '17 - Djelfa', '18 - Jijel', '19 - Sétif', '20 - Saïda', '21 - Skikda', '22 - Sidi Bel Abbès', '23 - Annaba', '24 - Guelma', '25 - Constantine', '26 - Médéa', '27 - Mostaganem', '28 - M\'Sila', '29 - Mascara', '30 - Ouargla', '31 - Oran', '32 - El Bayadh', '33 - Illizi', '34 - Bordj Bou Arréridj', '35 - Boumerdès', '36 - El Tarf', '37 - Tindouf', '38 - Tissemsilt', '39 - El Oued', '40 - Khenchela', '41 - Souk Ahras', '42 - Tipaza', '43 - Mila', '44 - Aïn Defla', '45 - Naâma', '46 - Aïn Témouchent', '47 - Ghardaïa', '48 - Relizane', '49 - Timimoun', '50 - Bordj Badji Mokhtar', '51 - Ouled Djellal', '52 - Béni Abbès', '53 - In Salah', '54 - In Guezzam', '55 - Touggourt', '56 - Djanet', '57 - El M\'Ghair', '58 - El Meniaa'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isSectionEdit ? 'Edit ' . $sectionMeta[$section]['label'] : 'Complete Your Profile'; ?> | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

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

        main {
            padding: 32px 16px 64px;
        }

        .ep-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ── BACK ── */
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
            margin-bottom: 1.5rem;
        }

        .ep-back-btn:hover {
            color: #6366f1;
        }

        /* ── PROGRESS ── */
        .ep-progress-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 0;
            margin-bottom: 40px;
        }

        .ep-progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            gap: 8px;
            position: relative;
        }

        .ep-progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 24px;
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
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 2px solid #e8eef6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
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
            font-size: 10px;
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
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-align: center;
        }

        .ep-progress-step.active .ep-progress-name {
            color: #0f172a;
        }

        .ep-progress-step.done .ep-progress-name {
            color: #1D9E75;
        }

        /* ── WIZARD LAYOUT ── */
        .ep-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* ── WIZARD SIDEBAR ── */
        .ep-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: sticky;
            top: 100px;
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

        /* ── SECTION EDIT LAYOUT ── */
        .ep-section-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* ── SECTION SIDEBAR NAV ── */
        .ep-section-nav {
            position: sticky;
            top: 24px;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 10px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .ep-section-nav-title {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: 8px 12px 10px;
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

        /* ── MOBILE SECTION NAV (horizontal scrollable) ── */
        .ep-section-nav-mobile {
            display: none;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            gap: 6px;
            padding-bottom: 2px;
            margin-bottom: 16px;
        }

        .ep-section-nav-mobile::-webkit-scrollbar {
            display: none;
        }

        .ep-snav-mobile-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            background: white;
            border: 1px solid #e8eef6;
            text-decoration: none;
            white-space: nowrap;
            transition: all .15s;
            flex-shrink: 0;
        }

        .ep-snav-mobile-item.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .ep-snav-mobile-item i {
            font-size: 13px;
        }

        /* ── CARD ── */
        .ep-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .ep-card-header {
            margin-bottom: 28px;
        }

        .ep-card-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ep-card-header h2 i {
            font-size: 22px;
            color: #6366f1;
        }

        .ep-card-header p {
            font-size: 13px;
            color: #64748b;
        }

        /* ── FIELD ── */
        .ep-field {
            margin-bottom: 20px;
        }

        .ep-field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 8px;
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
            padding: 11px 14px;
            font-size: 13.5px;
            color: #0f172a;
            outline: none;
            transition: all .15s;
            font-family: inherit;
        }

        .ep-field>input:focus,
        .ep-field>textarea:focus,
        .ep-field>select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
            background: white;
        }

        .ep-field>textarea {
            min-height: 110px;
            resize: vertical;
            line-height: 1.6;
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
            gap: 16px;
        }

        /* ── AVATAR ── */
        .ep-avatar-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e8eef6;
        }

        .ep-avatar-row {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .ep-avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            background: #ede9fe;
            border: 2px solid #6366f1;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            color: #6366f1;
            flex-shrink: 0;
        }

        .ep-avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ep-avatar-content h3 {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .ep-avatar-content p {
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
            padding: 9px 16px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
        }

        .ep-file-label:hover {
            background: #4f46e5;
        }

        .ep-file-label input {
            display: none;
        }

        /* ── ITEM GROUPS ── */
        .ep-item-group {
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 10px;
        }

        .ep-item-group input,
        .ep-item-group textarea,
        .ep-item-group select {
            width: 100%;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            color: #0f172a;
            outline: none;
            transition: all .15s;
            font-family: inherit;
            display: block;
            margin-bottom: 8px;
        }

        .ep-item-group input:focus,
        .ep-item-group textarea:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
            background: white;
        }

        .ep-item-group textarea {
            min-height: 72px;
            resize: vertical;
            line-height: 1.6;
        }

        .ep-item-group .ep-field-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 8px;
        }

        .ep-item-group .ep-field-group input {
            margin-bottom: 0;
        }

        .ep-item-remove {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 11px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all .2s;
        }

        .ep-item-remove:hover {
            background: #fca5a5;
        }

        .ep-add-item-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ede9fe;
            color: #6366f1;
            border: 1px solid #c4b5fd;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all .2s;
        }

        .ep-add-item-btn:hover {
            background: #ddd6fe;
        }

        /* ── ACTIONS ── */
        .ep-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e8eef6;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 14px;
            padding: 11px 22px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-1px);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #334155;
            border: 1px solid #e8eef6;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #6366f1;
            color: #6366f1;
        }

        /* ── ALERTS ── */
        .ep-alert {
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
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

        /* ── AUTOCOMPLETE ── */
        .ac-wrap {
            position: relative;
        }

        .ac-wrap>i.ac-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }

        .ac-wrap input {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 11px 14px 11px 36px;
            font-size: 13.5px;
            color: #0f172a;
            outline: none;
            font-family: inherit;
            transition: all .15s;
        }

        .ac-wrap input:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
            background: white;
        }

        .ac-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .10);
            z-index: 9999;
            max-height: 220px;
            overflow-y: auto;
            display: none;
        }

        .ac-dropdown.open {
            display: block;
        }

        .ac-item {
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background .12s;
            border-radius: 8px;
            margin: 3px 4px;
        }

        .ac-item:hover {
            background: #f5f3ff;
            color: #5b21b6;
        }

        .ac-item mark {
            background: none;
            color: #6366f1;
            font-weight: 700;
        }

        /* ── CHIPS ── */
        .chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 4px;
        }

        .fchip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: .18s;
            user-select: none;
            font-family: 'Poppins', sans-serif;
        }

        .fchip i {
            font-size: 12px;
        }

        .fchip:hover {
            filter: brightness(.93);
        }

        .fchip.active {
            box-shadow: 0 0 0 2.5px currentColor;
            filter: brightness(.88);
        }

        @media (max-width: 968px) {
            .ep-layout {
                grid-template-columns: 1fr;
            }

            .ep-sidebar {
                display: none;
            }

            .ep-section-layout {
                grid-template-columns: 1fr;
            }

            .ep-section-nav {
                display: none;
            }

            .ep-section-nav-mobile {
                display: flex;
            }
        }

        @media (max-width: 640px) {

            main {
                padding: 16px 12px 56px;
            }

            /* ── Progress bar ── */
            .ep-progress-wrapper {
                margin-bottom: 24px;
            }

            .ep-progress-circle {
                width: 36px;
                height: 36px;
                font-size: 13px;
            }

            .ep-progress-step:not(:last-child)::after {
                top: 18px;
            }

            .ep-progress-label {
                font-size: 9px;
            }

            .ep-progress-name {
                font-size: 10px;
            }

            /* ── Card ── */
            .ep-card {
                padding: 18px 14px;
                border-radius: 18px;
            }

            .ep-card-header {
                margin-bottom: 20px;
            }

            .ep-card-header h2 {
                font-size: 17px;
                gap: 8px;
            }

            .ep-card-header h2 i {
                font-size: 18px;
            }

            .ep-card-header p {
                font-size: 12px;
            }

            /* ── Fields: single column on mobile ── */
            .ep-field-group {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .ep-field>input,
            .ep-field>textarea,
            .ep-field>select {
                font-size: 13px;
                padding: 10px 12px;
            }

            .ep-field label {
                font-size: 10px;
            }

            /* ── Item groups ── */
            .ep-item-group {
                padding: 12px;
                border-radius: 14px;
            }

            .ep-item-group .ep-field-group {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .ep-item-group input,
            .ep-item-group textarea {
                font-size: 13px;
                padding: 9px 12px;
            }

            /* ── Avatar section ── */
            .ep-avatar-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .ep-avatar-preview {
                width: 80px;
                height: 80px;
                border-radius: 16px;
                font-size: 28px;
            }

            /* ── Actions: full-width buttons ── */
            .ep-actions {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 10px;
            }

            .ep-actions>div {
                width: 100%;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
                padding: 13px 16px;
                border-radius: 14px;
            }

            /* ── Autocomplete dropdown: larger touch targets ── */
            .ac-item {
                padding: 11px 14px;
                font-size: 13px;
            }

            .ac-wrap input {
                font-size: 13px;
                padding: 10px 12px 10px 34px;
            }

            /* ── Add item button: full width ── */
            .ep-add-item-btn {
                width: 100%;
                justify-content: center;
                padding: 11px 16px;
            }

            /* ── Alert ── */
            .ep-alert {
                font-size: 12px;
                padding: 10px 12px;
            }

            /* ── Back button ── */
            .ep-back-btn {
                font-size: 12px;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 375px) {
            .ep-progress-circle {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }

            .ep-progress-step:not(:last-child)::after {
                top: 15px;
            }

            .ep-progress-name {
                display: none;
            }

            .ep-card {
                padding: 14px 10px;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="ep-container">

            <a href="profile.php?id=<?= (int)$_SESSION['user']['id'] ?>" class="ep-back-btn">
                <i class="ti ti-arrow-left"></i> Back to profile
            </a>

            <?php if (!$isSectionEdit): ?>

                <!-- WIZARD PROGRESS -->
                <div class="ep-progress-wrapper">
                    <?php
                    $steps = [
                        1 => ['label' => 'Step 1', 'name' => 'Personal Info',          'icon' => 'ti ti-user'],
                        2 => ['label' => 'Step 2', 'name' => 'Education & Experience', 'icon' => 'ti ti-school'],
                        3 => ['label' => 'Step 3', 'name' => 'Skills & Projects',      'icon' => 'ti ti-code'],
                    ];
                    for ($i = 1; $i <= $totalSteps; $i++):
                        $cls = $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : '');
                    ?>
                        <div class="ep-progress-step <?= $cls ?>">
                            <div class="ep-progress-circle">
                                <?php if ($i < $currentStep): ?>
                                    <i class="ti ti-check"></i>
                                <?php else: ?>
                                    <?= $i ?>
                                <?php endif; ?>
                            </div>
                            <div class="ep-progress-label"><?= $steps[$i]['label'] ?></div>
                            <div class="ep-progress-name"><?= $steps[$i]['name'] ?></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- WIZARD LAYOUT -->
                <div class="ep-layout">
                    <aside class="ep-sidebar">
                        <div class="ep-sidebar-card">
                            <div class="ep-sidebar-title">Complete Your Profile</div>
                            <div class="ep-sidebar-text">Just 3 steps to stand out to recruiters.</div>
                            <div style="margin-top:14px;">
                                <?php
                                $stepDescs = [
                                    1 => ['icon' => 'ti ti-user',      'title' => 'Personal Info',          'desc' => 'Basic information'],
                                    2 => ['icon' => 'ti ti-school',    'title' => 'Education & Experience', 'desc' => 'Background'],
                                    3 => ['icon' => 'ti ti-briefcase', 'title' => 'Skills & Projects',      'desc' => 'Your expertise'],
                                ];
                                for ($i = 1; $i <= $totalSteps; $i++):
                                    $cls = $i < $currentStep ? 'done' : ($i === $currentStep ? 'active' : 'pending');
                                ?>
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
                            <div style="font-size:28px;margin-bottom:10px;">📋</div>
                            <div class="ep-sidebar-title">Why complete your profile?</div>
                            <div class="ep-sidebar-text">Complete profiles get significantly more views from recruiters across Algeria.</div>
                        </div>
                    </aside>

                    <div class="ep-card">
                        <?php if ($saved): ?>
                            <div class="ep-alert ep-alert-success"><i class="ti ti-circle-check"></i> Changes saved successfully.</div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="ep-alert ep-alert-danger"><i class="ti ti-alert-circle"></i> Please complete all required sections before finishing your profile.</div>
                        <?php endif; ?>

                        <div class="ep-card-header">
                            <h2><i class="ti <?= $steps[$currentStep]['icon'] ?>"></i> <?= $steps[$currentStep]['name'] ?></h2>
                            <p>
                                <?php if ($currentStep === 1): ?>Tell us about yourself so recruiters can identify and contact you.
                                <?php elseif ($currentStep === 2): ?>Share your academic background and professional journey.
                                <?php else: ?>Help recruiters understand what you can do and what drives you.<?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" action="edit_profile.php?step=<?= $currentStep ?>" enctype="multipart/form-data">

                            <?php if ($currentStep === 1): ?>
                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Full Name</label>
                                        <input type="text" name="full_name" value="<?php echo e($profile['full_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Job Title</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-briefcase ac-icon"></i>
                                            <input type="text" name="job_title" id="input-jobtitle-sec"
                                                value="<?php echo e($profile['job_title'] ?? ''); ?>"
                                                placeholder="e.g. Software Engineer" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-jobtitle-sec"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Email</label>
                                        <input type="email" value="<?php echo e($_SESSION['user']['email']); ?>" disabled style="opacity:.55;">
                                        <div class="ep-field-hint">Cannot be changed</div>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Phone</label>
                                        <input type="tel" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="ep-field">
                                    <label class="ep-field-required">City / Wilaya</label>
                                    <div class="ac-wrap">
                                        <i class="ti ti-map-pin ac-icon"></i>
                                        <input type="text" name="city" id="input-city-w1"
                                            value="<?php echo e($profile['city'] ?? ''); ?>"
                                            placeholder="e.g. 05 - Batna" autocomplete="off">
                                        <div class="ac-dropdown" id="drop-city-w1"></div>
                                    </div>
                                </div>

                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Category</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-tag ac-icon"></i>
                                            <input type="text" name="category" id="input-category-w1"
                                                value="<?php echo e($profile['category'] ?? ''); ?>"
                                                placeholder="e.g. IT / Development" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-category-w1"></div>
                                        </div>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Specialty</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-sparkles ac-icon"></i>
                                            <input type="text" name="specialty" id="input-specialty-w1"
                                                value="<?php echo e($profile['specialty'] ?? ''); ?>"
                                                placeholder="e.g. React Developer" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-specialty-w1"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Experience Level</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-chart-bar ac-icon"></i>
                                            <input type="text" name="experience_level" id="input-explevel-w1"
                                                value="<?php echo e($profile['experience_level'] ?? ''); ?>"
                                                placeholder="e.g. Junior" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-explevel-w1"></div>
                                        </div>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Availability</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-clock ac-icon"></i>
                                            <input type="text" name="availability" id="input-avail-w1"
                                                value="<?php echo e($profile['availability'] ?? ''); ?>"
                                                placeholder="e.g. Available" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-avail-w1"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="ep-avatar-section">
                                    <label class="ep-field-required" style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Profile Picture</label>
                                    <div class="ep-avatar-row">
                                        <div class="ep-avatar-preview" id="avatarPreview">
                                            <?php if (!empty($profile['image_path'])): ?>
                                                <img src="<?php echo e($profile['image_path']); ?>" alt="Profile">
                                            <?php else: ?>
                                                <?php echo e(strtoupper(mb_substr($profile['full_name'] ?? 'U', 0, 1))); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ep-avatar-content">
                                            <h3>Add a profile picture</h3>
                                            <p>Make your profile more memorable.</p>
                                            <label class="ep-file-label"><i class="ti ti-upload"></i> Upload Photo<input type="file" name="image" accept="image/*" onchange="previewAvatar(this)"></label>
                                            <div class="ep-field-hint" style="margin-top:6px;">JPG, PNG · Max 2MB</div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($currentStep === 2): ?>
                                <div class="ep-field">
                                    <label>Professional Summary</label>
                                    <textarea name="summary" placeholder="Briefly describe yourself..."><?php echo e($profile['summary'] ?? ''); ?></textarea>
                                    <div class="ep-field-hint">Optional</div>
                                </div>
                                <div class="ep-field">
                                    <label>Education</label>
                                    <div id="educations-container">
                                        <?php if (empty($educations)): ?>
                                            <div class="ep-item-group">
                                                <div class="ep-field-group">
                                                    <input type="text" name="education[0][degree]" placeholder="Degree">
                                                    <input type="text" name="education[0][school]" placeholder="School / University">
                                                </div>
                                                <div class="ep-field-group">
                                                    <input type="date" name="education[0][start_date]">
                                                    <input type="date" name="education[0][end_date]">
                                                </div>
                                                <textarea name="education[0][description]" placeholder="Description (optional)"></textarea>
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($educations as $idx => $edu): ?>
                                                <div class="ep-item-group">
                                                    <div class="ep-field-group">
                                                        <input type="text" name="education[<?= $idx ?>][degree]" value="<?php echo e($edu['degree'] ?? ''); ?>" placeholder="Degree">
                                                        <input type="text" name="education[<?= $idx ?>][school]" value="<?php echo e($edu['school'] ?? ''); ?>" placeholder="School">
                                                    </div>
                                                    <div class="ep-field-group">
                                                        <input type="date" name="education[<?= $idx ?>][start_date]" value="<?php echo e($edu['start_date'] ?? ''); ?>">
                                                        <input type="date" name="education[<?= $idx ?>][end_date]" value="<?php echo e($edu['end_date'] ?? ''); ?>">
                                                    </div>
                                                    <textarea name="education[<?= $idx ?>][description]" placeholder="Description"><?php echo e($edu['description'] ?? ''); ?></textarea>
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addEducation()"><i class="ti ti-plus"></i> Add Education</button>
                                </div>
                                <div class="ep-field">
                                    <label>Experience</label>
                                    <div id="experiences-container">
                                        <?php if (empty($experiences)): ?>
                                            <div class="ep-item-group">
                                                <div class="ep-field-group">
                                                    <input type="text" name="experience[0][job_title]" placeholder="Job Title">
                                                    <input type="text" name="experience[0][company_name]" placeholder="Company">
                                                </div>
                                                <div class="ep-field-group">
                                                    <input type="date" name="experience[0][start_date]">
                                                    <input type="date" name="experience[0][end_date]">
                                                </div>
                                                <textarea name="experience[0][description]" placeholder="Description (optional)"></textarea>
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($experiences as $idx => $exp): ?>
                                                <div class="ep-item-group">
                                                    <div class="ep-field-group">
                                                        <input type="text" name="experience[<?= $idx ?>][job_title]" value="<?php echo e($exp['job_title'] ?? ''); ?>" placeholder="Job Title">
                                                        <input type="text" name="experience[<?= $idx ?>][company_name]" value="<?php echo e($exp['company_name'] ?? ''); ?>" placeholder="Company">
                                                    </div>
                                                    <div class="ep-field-group">
                                                        <input type="date" name="experience[<?= $idx ?>][start_date]" value="<?php echo e($exp['start_date'] ?? ''); ?>">
                                                        <input type="date" name="experience[<?= $idx ?>][end_date]" value="<?php echo e($exp['end_date'] ?? ''); ?>">
                                                    </div>
                                                    <textarea name="experience[<?= $idx ?>][description]" placeholder="Description"><?php echo e($exp['description'] ?? ''); ?></textarea>
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addExperience()"><i class="ti ti-plus"></i> Add Experience</button>
                                </div>

                            <?php elseif ($currentStep === 3): ?>
                                <div class="ep-field">
                                    <label>Skills</label>
                                    <textarea name="skills" placeholder="JavaScript, Python, React..."><?php echo e(implode(', ', array_column($skills, 'skill_name'))); ?></textarea>
                                    <div class="ep-field-hint">Comma-separated</div>
                                </div>
                                <div class="ep-field">
                                    <label>Languages</label>
                                    <div id="languages-wrapper">
                                        <?php if (!empty($languages)): ?>
                                            <?php foreach ($languages as $lang): ?>
                                                <div class="ep-field-group" style="margin-bottom:10px;">
                                                    <div class="ep-field" style="margin-bottom:0;"><input type="text" name="languages[]" placeholder="English" value="<?php echo e($lang['language_name']); ?>"></div>
                                                    <div class="ep-field" style="margin-bottom:0;"><input type="number" name="language_levels[]" min="0" max="100" placeholder="Level %" value="<?php echo e($lang['level'] ?? 80); ?>"></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="ep-field-group" style="margin-bottom:10px;">
                                                <div class="ep-field" style="margin-bottom:0;"><input type="text" name="languages[]" placeholder="English"></div>
                                                <div class="ep-field" style="margin-bottom:0;"><input type="number" name="language_levels[]" min="0" max="100" placeholder="Level %"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addLanguageField()"><i class="ti ti-plus"></i> Add Language</button>
                                </div>
                                <div class="ep-field">
                                    <label>Projects</label>
                                    <div id="projects-container">
                                        <?php if (empty($projects)): ?>
                                            <div class="ep-item-group">
                                                <input type="text" name="projects[0][title]" placeholder="Project Title">
                                                <textarea name="projects[0][description]" placeholder="Description (optional)"></textarea>
                                                <input type="url" name="projects[0][project_link]" placeholder="Project Link (https://...)">
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($projects as $idx => $proj): ?>
                                                <div class="ep-item-group">
                                                    <input type="text" name="projects[<?= $idx ?>][title]" value="<?php echo e($proj['title'] ?? ''); ?>" placeholder="Project Title">
                                                    <textarea name="projects[<?= $idx ?>][description]" placeholder="Description"><?php echo e($proj['description'] ?? ''); ?></textarea>
                                                    <input type="url" name="projects[<?= $idx ?>][project_link]" value="<?php echo e($proj['project_link'] ?? $proj['demo_link'] ?? ''); ?>" placeholder="Project Link (https://...)">
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addProject()"><i class="ti ti-plus"></i> Add Project</button>
                                </div>
                               
                                <div class="ep-field">
                                    <label>Social Links</label>
                                    <div id="social-container">
                                        <?php if (empty($socialLinks)): ?>
                                            <div class="ep-item-group">
                                                <input type="text" name="social_links[0][platform]" placeholder="Platform (GitHub, LinkedIn…)">
                                                <input type="url" name="social_links[0][url]" placeholder="URL (https://...)">
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($socialLinks as $idx => $link): ?>
                                                <div class="ep-item-group">
                                                    <input type="text" name="social_links[<?= $idx ?>][platform]" value="<?php echo e($link['platform'] ?? ''); ?>" placeholder="Platform">
                                                    <input type="url" name="social_links[<?= $idx ?>][url]" value="<?php echo e($link['url'] ?? ''); ?>" placeholder="URL">
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addSocialLink()"><i class="ti ti-plus"></i> Add Link</button>
                                </div>
                                 <div class="ep-field">
                                    <label>Interests</label>
                                    <textarea name="interests" placeholder="UI Design, Machine Learning..."><?php echo e(implode(', ', array_column($interests, 'interest_name'))); ?></textarea>
                                    <div class="ep-field-hint">Comma-separated</div>
                                </div>
                            <?php endif; ?>

                            <div class="ep-actions">
                                <div><?php if ($currentStep > 1): ?><a href="edit_profile.php?step=<?= $currentStep - 1 ?>" class="btn-secondary"><i class="ti ti-arrow-left"></i> Back</a><?php endif; ?></div>
                                <div><?php if ($currentStep < $totalSteps): ?><button type="submit" class="btn-primary">Continue <i class="ti ti-arrow-right"></i></button><?php else: ?><button type="submit" class="btn-primary"><i class="ti ti-check"></i> Complete Profile</button><?php endif; ?></div>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>

                <div class="ep-section-nav-mobile">
                    <?php foreach ($sectionMeta as $key => $meta): ?>
                        <a href="edit_profile.php?section=<?= $key ?>" class="ep-snav-mobile-item <?= ($section === $key) ? 'active' : '' ?>">
                            <i class="ti <?= $meta['icon'] ?>"></i><?= $meta['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="ep-section-layout">
                    <!-- Desktop sidebar nav -->
                    <nav class="ep-section-nav">
                        <div class="ep-section-nav-title">Edit Section</div>
                        <?php
                        $navGroups = [
                            'personal' => 'account',
                            'professional' => 'account',
                            'summary' => 'account',
                            'experience' => 'career',
                            'education' => 'career',
                            'skills' => 'extras',
                            'languages' => 'extras',
                            'projects' => 'extras',
                            'interests' => 'extras',
                            'social_links' => 'extras',
                        ];
                        $lastGroup = '';
                        foreach ($sectionMeta as $key => $meta):
                            $group = $navGroups[$key] ?? '';
                            if ($group !== $lastGroup && $lastGroup !== ''):
                        ?>
                                <div class="ep-snav-sep"></div>
                            <?php endif;
                            $lastGroup = $group; ?>
                            <a href="edit_profile.php?section=<?= $key ?>" class="ep-snav-item <?= ($section === $key) ? 'active' : '' ?>">
                                <i class="ti <?= $meta['icon'] ?>"></i><?= $meta['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <div class="ep-card">
                        <?php if ($saved): ?>
                            <div class="ep-alert ep-alert-success"><i class="ti ti-circle-check"></i> Changes saved successfully.</div>
                        <?php endif; ?>

                        <div class="ep-card-header">
                            <h2><i class="ti <?= $sectionMeta[$section]['icon'] ?>"></i> <?= $sectionMeta[$section]['label'] ?></h2>
                            <p><?= $sectionMeta[$section]['desc'] ?></p>
                        </div>

                        <form method="POST" action="edit_profile.php?section=<?= $section ?>" enctype="multipart/form-data">

                            <?php if ($section === 'personal'): ?>
                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Full Name</label>
                                        <input type="text" name="full_name" value="<?php echo e($profile['full_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Job Title</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-briefcase ac-icon"></i>
                                            <input type="text" name="job_title" id="input-jobtitle-w1"
                                                value="<?php echo e($profile['job_title'] ?? ''); ?>"
                                                placeholder="e.g. Software Engineer" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-jobtitle-w1"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Email</label>
                                        <input type="email" value="<?php echo e($_SESSION['user']['email']); ?>" disabled style="opacity:.55;">
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label class="ep-field-required">Phone</label>
                                        <input type="tel" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="ep-field">
                                    <label class="ep-field-required">City / Wilaya</label>
                                    <div class="ac-wrap">
                                        <i class="ti ti-map-pin ac-icon"></i>
                                        <input type="text" name="city" id="input-city-sec"
                                            value="<?php echo e($profile['city'] ?? ''); ?>"
                                            placeholder="e.g. 05 - Batna" autocomplete="off">
                                        <div class="ac-dropdown" id="drop-city-sec"></div>
                                    </div>
                                </div>
                                <div class="ep-avatar-section">
                                    <label style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Profile Picture</label>
                                    <div class="ep-avatar-row">
                                        <div class="ep-avatar-preview" id="avatarPreview">
                                            <?php if (!empty($profile['image_path'])): ?>
                                                <img src="<?php echo e($profile['image_path']); ?>" alt="Profile">
                                            <?php else: ?>
                                                <?php echo e(strtoupper(mb_substr($profile['full_name'] ?? 'U', 0, 1))); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ep-avatar-content">
                                            <h3>Add a profile picture</h3>
                                            <p>Make your profile more memorable.</p>
                                            <label class="ep-file-label"><i class="ti ti-upload"></i> Upload Photo<input type="file" name="image" accept="image/*" onchange="previewAvatar(this)"></label>
                                            <div class="ep-field-hint" style="margin-top:6px;">JPG, PNG · Max 2MB</div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($section === 'professional'): ?>
                                <div class="ep-field-group" style="margin-bottom:20px;">
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Category</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-tag ac-icon"></i>
                                            <input type="text" name="category" id="input-category"
                                                value="<?php echo e($profile['category'] ?? ''); ?>"
                                                placeholder="e.g. IT / Development" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-category"></div>
                                        </div>
                                    </div>
                                    <div class="ep-field" style="margin-bottom:0;">
                                        <label>Specialty</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-sparkles ac-icon"></i>
                                            <input type="text" name="specialty" id="input-specialty"
                                                value="<?php echo e($profile['specialty'] ?? ''); ?>"
                                                placeholder="e.g. Frontend Development" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-specialty"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ep-field-group">
                                    <div class="ep-field">
                                        <label>Experience Level</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-chart-bar ac-icon"></i>
                                            <input type="text" name="experience_level" id="input-explevel-sec"
                                                value="<?php echo e($profile['experience_level'] ?? ''); ?>"
                                                placeholder="e.g. Junior" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-explevel-sec"></div>
                                        </div>
                                    </div>
                                    <div class="ep-field">
                                        <label>Availability</label>
                                        <div class="ac-wrap">
                                            <i class="ti ti-clock ac-icon"></i>
                                            <input type="text" name="availability" id="input-avail-sec"
                                                value="<?php echo e($profile['availability'] ?? ''); ?>"
                                                placeholder="e.g. Available" autocomplete="off">
                                            <div class="ac-dropdown" id="drop-avail-sec"></div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($section === 'summary'): ?>
                                <div class="ep-field">
                                    <label>Professional Summary</label>
                                    <textarea name="summary" placeholder="Briefly describe yourself..."><?php echo e($profile['summary'] ?? ''); ?></textarea>
                                </div>

                            <?php elseif ($section === 'experience'): ?>
                                <div class="ep-field">
                                    <label>Experience</label>
                                    <div id="experiences-container">
                                        <?php if (empty($experiences)): ?>
                                            <div class="ep-item-group">
                                                <div class="ep-field-group">
                                                    <input type="text" name="experience[0][job_title]" placeholder="Job Title">
                                                    <input type="text" name="experience[0][company_name]" placeholder="Company">
                                                </div>
                                                <div class="ep-field-group">
                                                    <input type="date" name="experience[0][start_date]">
                                                    <input type="date" name="experience[0][end_date]">
                                                </div>
                                                <textarea name="experience[0][description]" placeholder="Description"></textarea>
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($experiences as $idx => $exp): ?>
                                                <div class="ep-item-group">
                                                    <div class="ep-field-group">
                                                        <input type="text" name="experience[<?= $idx ?>][job_title]" value="<?php echo e($exp['job_title'] ?? ''); ?>" placeholder="Job Title">
                                                        <input type="text" name="experience[<?= $idx ?>][company_name]" value="<?php echo e($exp['company_name'] ?? ''); ?>" placeholder="Company">
                                                    </div>
                                                    <div class="ep-field-group">
                                                        <input type="date" name="experience[<?= $idx ?>][start_date]" value="<?php echo e($exp['start_date'] ?? ''); ?>">
                                                        <input type="date" name="experience[<?= $idx ?>][end_date]" value="<?php echo e($exp['end_date'] ?? ''); ?>">
                                                    </div>
                                                    <textarea name="experience[<?= $idx ?>][description]" placeholder="Description"><?php echo e($exp['description'] ?? ''); ?></textarea>
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addExperience()"><i class="ti ti-plus"></i> Add Experience</button>
                                </div>

                            <?php elseif ($section === 'education'): ?>
                                <div class="ep-field">
                                    <label>Education</label>
                                    <div id="educations-container">
                                        <?php if (empty($educations)): ?>
                                            <div class="ep-item-group">
                                                <div class="ep-field-group">
                                                    <input type="text" name="education[0][degree]" placeholder="Degree">
                                                    <input type="text" name="education[0][school]" placeholder="School">
                                                </div>
                                                <div class="ep-field-group">
                                                    <input type="date" name="education[0][start_date]">
                                                    <input type="date" name="education[0][end_date]">
                                                </div>
                                                <textarea name="education[0][description]" placeholder="Description"></textarea>
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($educations as $idx => $edu): ?>
                                                <div class="ep-item-group">
                                                    <div class="ep-field-group">
                                                        <input type="text" name="education[<?= $idx ?>][degree]" value="<?php echo e($edu['degree'] ?? ''); ?>" placeholder="Degree">
                                                        <input type="text" name="education[<?= $idx ?>][school]" value="<?php echo e($edu['school'] ?? ''); ?>" placeholder="School">
                                                    </div>
                                                    <div class="ep-field-group">
                                                        <input type="date" name="education[<?= $idx ?>][start_date]" value="<?php echo e($edu['start_date'] ?? ''); ?>">
                                                        <input type="date" name="education[<?= $idx ?>][end_date]" value="<?php echo e($edu['end_date'] ?? ''); ?>">
                                                    </div>
                                                    <textarea name="education[<?= $idx ?>][description]" placeholder="Description"><?php echo e($edu['description'] ?? ''); ?></textarea>
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addEducation()"><i class="ti ti-plus"></i> Add Education</button>
                                </div>

                            <?php elseif ($section === 'skills'): ?>
                                <div class="ep-field">
                                    <label>Skills</label>
                                    <textarea name="skills" placeholder="JavaScript, Python, React..."><?php echo e(implode(', ', array_column($skills, 'skill_name'))); ?></textarea>
                                    <div class="ep-field-hint">Comma-separated</div>
                                </div>

                            <?php elseif ($section === 'languages'): ?>
                                <div class="ep-field">
                                    <label>Languages</label>
                                    <div id="languages-wrapper">
                                        <?php if (!empty($languages)): ?>
                                            <?php foreach ($languages as $lang): ?>
                                                <div class="ep-field-group" style="margin-bottom:12px;">
                                                    <div class="ep-field" style="margin-bottom:0;"><input type="text" name="languages[]" placeholder="English" value="<?php echo e($lang['language_name']); ?>"></div>
                                                    <div class="ep-field" style="margin-bottom:0;"><input type="number" name="language_levels[]" min="0" max="100" placeholder="Level %" value="<?php echo e($lang['level'] ?? 80); ?>"></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="ep-field-group" style="margin-bottom:12px;">
                                                <div class="ep-field" style="margin-bottom:0;"><input type="text" name="languages[]" placeholder="English"></div>
                                                <div class="ep-field" style="margin-bottom:0;"><input type="number" name="language_levels[]" min="0" max="100" placeholder="Level %"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addLanguageField()"><i class="ti ti-plus"></i> Add Language</button>
                                </div>

                            <?php elseif ($section === 'projects'): ?>
                                <div class="ep-field">
                                    <label>Projects</label>
                                    <div id="projects-container">
                                        <?php if (empty($projects)): ?>
                                            <div class="ep-item-group">
                                                <input type="text" name="projects[0][title]" placeholder="Project Title">
                                                <textarea name="projects[0][description]" placeholder="Description (optional)"></textarea>
                                                <input type="url" name="projects[0][project_link]" placeholder="Project Link (https://...)">
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($projects as $idx => $proj): ?>
                                                <div class="ep-item-group">
                                                    <input type="text" name="projects[<?= $idx ?>][title]" value="<?php echo e($proj['title'] ?? ''); ?>" placeholder="Project Title">
                                                    <textarea name="projects[<?= $idx ?>][description]" placeholder="Description"><?php echo e($proj['description'] ?? ''); ?></textarea>
                                                    <input type="url" name="projects[<?= $idx ?>][project_link]" value="<?php echo e($proj['project_link'] ?? $proj['demo_link'] ?? ''); ?>" placeholder="Project Link (https://...)">
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addProject()"><i class="ti ti-plus"></i> Add Project</button>
                                </div>

                            <?php elseif ($section === 'interests'): ?>
                                <div class="ep-field">
                                    <label>Interests</label>
                                    <textarea name="interests" placeholder="UI Design, Machine Learning..."><?php echo e(implode(', ', array_column($interests, 'interest_name'))); ?></textarea>
                                    <div class="ep-field-hint">Comma-separated</div>
                                </div>

                            <?php elseif ($section === 'social_links'): ?>
                                <div class="ep-field">
                                    <label>Social Links</label>
                                    <div id="social-container">
                                        <?php if (empty($socialLinks)): ?>
                                            <div class="ep-item-group">
                                                <input type="text" name="social_links[0][platform]" placeholder="Platform (GitHub, LinkedIn…)">
                                                <input type="url" name="social_links[0][url]" placeholder="URL (https://...)">
                                                <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($socialLinks as $idx => $link): ?>
                                                <div class="ep-item-group">
                                                    <input type="text" name="social_links[<?= $idx ?>][platform]" value="<?php echo e($link['platform'] ?? ''); ?>" placeholder="Platform">
                                                    <input type="url" name="social_links[<?= $idx ?>][url]" value="<?php echo e($link['url'] ?? ''); ?>" placeholder="URL">
                                                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="ep-add-item-btn" onclick="addSocialLink()"><i class="ti ti-plus"></i> Add Link</button>
                                </div>
                            <?php endif; ?>

                            <div class="ep-actions">
                                <a href="profile.php?id=<?= (int)$_SESSION['user']['id'] ?>" class="btn-secondary"><i class="ti ti-x"></i> Cancel</a>
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
        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
            };
            reader.readAsDataURL(input.files[0]);
        }

        let expCount = document.querySelectorAll('#experiences-container .ep-item-group').length || 1;
        let eduCount = document.querySelectorAll('#educations-container .ep-item-group').length || 1;
        let projCount = document.querySelectorAll('#projects-container .ep-item-group').length || 1;
        let socialCount = document.querySelectorAll('#social-container .ep-item-group').length || 1;

        function addExperience() {
            document.getElementById('experiences-container').insertAdjacentHTML('beforeend', `
                <div class="ep-item-group">
                    <div class="ep-field-group">
                        <input type="text" name="experience[${expCount}][job_title]" placeholder="Job Title">
                        <input type="text" name="experience[${expCount}][company_name]" placeholder="Company">
                    </div>
                    <div class="ep-field-group">
                        <input type="date" name="experience[${expCount}][start_date]">
                        <input type="date" name="experience[${expCount}][end_date]">
                    </div>
                    <textarea name="experience[${expCount}][description]" placeholder="Description"></textarea>
                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                </div>`);
            expCount++;
        }

        function addEducation() {
            document.getElementById('educations-container').insertAdjacentHTML('beforeend', `
                <div class="ep-item-group">
                    <div class="ep-field-group">
                        <input type="text" name="education[${eduCount}][degree]" placeholder="Degree">
                        <input type="text" name="education[${eduCount}][school]" placeholder="School">
                    </div>
                    <div class="ep-field-group">
                        <input type="date" name="education[${eduCount}][start_date]">
                        <input type="date" name="education[${eduCount}][end_date]">
                    </div>
                    <textarea name="education[${eduCount}][description]" placeholder="Description"></textarea>
                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                </div>`);
            eduCount++;
        }

        function addProject() {
            document.getElementById('projects-container').insertAdjacentHTML('beforeend', `
                <div class="ep-item-group">
                    <input type="text" name="projects[${projCount}][title]" placeholder="Project Title">
                    <textarea name="projects[${projCount}][description]" placeholder="Description (optional)"></textarea>
                    <input type="url" name="projects[${projCount}][project_link]" placeholder="Project Link (https://...)">
                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                </div>`);
            projCount++;
        }

        function addSocialLink() {
            document.getElementById('social-container').insertAdjacentHTML('beforeend', `
                <div class="ep-item-group">
                    <input type="text" name="social_links[${socialCount}][platform]" placeholder="Platform (GitHub, LinkedIn…)">
                    <input type="url" name="social_links[${socialCount}][url]" placeholder="URL (https://...)">
                    <button type="button" class="ep-item-remove" onclick="this.parentElement.remove();">Remove</button>
                </div>`);
            socialCount++;
        }

        function addLanguageField() {
            document.getElementById('languages-wrapper').insertAdjacentHTML('beforeend', `
                <div class="ep-field-group" style="margin-bottom:12px;">
                    <div class="ep-field" style="margin-bottom:0;"><input type="text" name="languages[]" placeholder="English"></div>
                    <div class="ep-field" style="margin-bottom:0;"><input type="number" name="language_levels[]" min="0" max="100" placeholder="Level %"></div>
                </div>`);
        }

        // Scroll active mobile nav item into view
        (function() {
            const activeItem = document.querySelector('.ep-snav-mobile-item.active');
            if (activeItem) {
                activeItem.scrollIntoView({
                    inline: 'center',
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        })();

        // ── AUTOCOMPLETE DATA ──
        const EP_AC_DATA = {
            job_title: <?php echo json_encode(array_values($jobTitles)); ?>,
            category: <?php echo json_encode(array_values($categories)); ?>,
            specialty: [],
            city: <?php echo json_encode(array_values($algeriaWilayas)); ?>,
            experience_level: ['Junior', 'Mid-Level', 'Senior', 'Lead'],
            availability: ['Available', 'Open to Work', 'Full Time', 'Part Time', 'Freelance']
        };

        function buildEpAutocomplete(inputId, dropId, dataKey, icon = 'ti-hash', hiddenId = null, strictMatch = false) {
            const input = document.getElementById(inputId);
            const drop = document.getElementById(dropId);
            if (!input || !drop) return;
            const hidden = hiddenId ? document.getElementById(hiddenId) : null;
            let focusedIdx = -1;

            function highlight(text, query) {
                if (!query) return text;
                const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return text.replace(re, '<mark>$1</mark>');
            }

            function render(query) {
                const q = query.trim().toLowerCase();
                const list = EP_AC_DATA[dataKey] || [];
                let results = q ? list.filter(v => v.toLowerCase().includes(q)) : list;
                if (!strictMatch && q && !list.some(v => v.toLowerCase() === q))
                    results = [query, ...results];
                results = results.slice(0, 12);
                focusedIdx = -1;
                drop.innerHTML = results.length === 0 ?
                    '<div style="padding:12px;font-size:12px;color:#94a3b8;text-align:center">No results</div>' :
                    results.map(item => `<div class="ac-item" data-val="${item.replace(/"/g,'&quot;')}"><i class="ti ${icon}"></i>${highlight(item, q)}</div>`).join('');
                drop.classList.add('open');
                drop.querySelectorAll('.ac-item').forEach(el => {
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        input.value = el.dataset.val;
                        if (hidden) hidden.value = el.dataset.val;
                        drop.classList.remove('open');
                    });
                });
            }

            function close() {
                drop.classList.remove('open');
                focusedIdx = -1;
                if (strictMatch && hidden) {
                    const list = EP_AC_DATA[dataKey] || [];
                    if (!list.some(v => v.toLowerCase() === input.value.trim().toLowerCase())) {
                        input.value = '';
                        hidden.value = '';
                    }
                }
            }

            input.addEventListener('input', () => render(input.value));
            input.addEventListener('focus', () => render(input.value));
            input.addEventListener('keydown', e => {
                if (!drop.classList.contains('open')) return;
                const items = [...drop.querySelectorAll('.ac-item')];
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    items.forEach(i => i.classList.remove('focused'));
                    focusedIdx = (focusedIdx + 1) % items.length;
                    items[focusedIdx]?.classList.add('focused');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    items.forEach(i => i.classList.remove('focused'));
                    focusedIdx = (focusedIdx - 1 + items.length) % items.length;
                    items[focusedIdx]?.classList.add('focused');
                } else if (e.key === 'Enter') {
                    const f = drop.querySelector('.ac-item.focused');
                    if (f) {
                        e.preventDefault();
                        input.value = f.dataset.val;
                        if (hidden) hidden.value = f.dataset.val;
                        close();
                    }
                } else if (e.key === 'Escape') close();
            });
            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !drop.contains(e.target)) close();
            });
        }

        buildEpAutocomplete('input-category', 'drop-category', 'category', 'ti-tag', null, false);
        buildEpAutocomplete('input-specialty', 'drop-specialty', 'specialty', 'ti-code', null, false);
        buildEpAutocomplete('input-explevel-sec', 'drop-explevel-sec', 'experience_level', 'ti-chart-bar', null, true);
        buildEpAutocomplete('input-avail-sec', 'drop-avail-sec', 'availability', 'ti-clock', null, true);
        buildEpAutocomplete('input-city-sec', 'drop-city-sec', 'city', 'ti-map-pin', null, true);
        buildEpAutocomplete('input-category-w1', 'drop-category-w1', 'category', 'ti-tag', null, false);
        buildEpAutocomplete('input-specialty-w1', 'drop-specialty-w1', 'specialty', 'ti-code', null, false);
        buildEpAutocomplete('input-city-w1', 'drop-city-w1', 'city', 'ti-map-pin', null, true);
        buildEpAutocomplete('input-explevel-w1', 'drop-explevel-w1', 'experience_level', 'ti-chart-bar', null, true);
        buildEpAutocomplete('input-avail-w1', 'drop-avail-w1', 'availability', 'ti-clock', null, true);
        buildEpAutocomplete('input-jobtitle-w1', 'drop-jobtitle-w1', 'job_title', 'ti-briefcase', null, false);
        buildEpAutocomplete('input-jobtitle-sec', 'drop-jobtitle-sec', 'job_title', 'ti-briefcase', null, false);
    </script>
</body>

</html>