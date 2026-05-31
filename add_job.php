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

function getJobTitles()
{
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT title FROM jobs WHERE title IS NOT NULL AND title != '' ORDER BY title ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getCategories()
{
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT category FROM jobs WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getSpecialties()
{
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT specialty FROM jobs WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$cities = [
    'Adrar',
    'Aïn Beïda',
    'Aïn Defla',
    'Aïn Témouchent',
    'Algiers',
    'Annaba',
    'Batna',
    'Béchar',
    'Béjaïa',
    'Biskra',
    'Blida',
    'Bordj Bou Arréridj',
    'Bouira',
    'Boumerdès',
    'Chlef',
    'Constantine',
    'Djanet',
    'Djelfa',
    'El Bayadh',
    'El Oued',
    'El Tarf',
    'Ghardaïa',
    'Guelma',
    'Illizi',
    'Jijel',
    'Khenchela',
    'Laghouat',
    'Mascara',
    'Médéa',
    'Mila',
    'Mostaganem',
    'Msila',
    'Naâma',
    'Oran',
    'Ouargla',
    'Oum El Bouaghi',
    'Relizane',
    'Saïda',
    'Sétif',
    'Sidi Bel Abbès',
    'Skikda',
    'Souk Ahras',
    'Tamanrasset',
    'Tébessa',
    'Tiaret',
    'Tindouf',
    'Tipaza',
    'Tissemsilt',
    'Tizi Ouzou',
    'Tlemcen',
];

$experienceLevels = ['Junior Level', 'Mid Level', 'Senior Level', 'Expert Level', 'Internship'];
$educationLevels  = ['High School', 'Bachelor Degree', 'Master Degree', 'Engineer Degree', 'Doctorate'];

$allTitles      = getJobTitles();
$allCategories  = getCategories();
$allSpecialties = getSpecialties();

$staticCategories = ['IT / Development', 'Marketing', 'Design', 'Finance', 'Human Resources', 'Business', 'Engineering', 'Customer Support', 'Sales', 'Legal', 'Healthcare', 'Education', 'Logistics', 'Architecture', 'Accounting'];
$allCategories = array_unique(array_merge($allCategories, $staticCategories));
sort($allCategories);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        'title'             => trim($_POST['title']             ?? ''),
        'category'          => trim($_POST['category']          ?? ''),
        'specialty'         => trim($_POST['specialty']         ?? ''),
        'city'              => trim($_POST['city']              ?? ''),
        'salary'            => trim($_POST['salary']            ?? ''),
        'contract_type'     => trim($_POST['contract_type']     ?? ''),
        'work_mode'         => trim($_POST['work_mode']         ?? ''),
        'experience_level'  => trim($_POST['experience_level']  ?? ''),
        'description'       => trim($_POST['description']       ?? ''),
        'skills'            => trim($_POST['skills']            ?? ''),
        'duration_days'     => intval($_POST['duration_days']   ?? 7),
        'requirements'      => trim($_POST['requirements']      ?? ''),
        'responsibilities'  => trim($_POST['responsibilities']  ?? ''),
        'benefits'          => trim($_POST['benefits']          ?? ''),
        'experience'        => trim($_POST['experience']        ?? ''),
        'education_level'   => trim($_POST['education_level']   ?? ''),
        'language_required' => trim($_POST['language_required'] ?? ''),
    ];

    if (empty($data['title']) || empty($data['category']) || empty($data['city'])) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $newJobId = addJob($_SESSION['user']['id'], $data);

            $title            = trim(strtolower($data['title']));
            $category         = trim(strtolower($data['category']));
            $specialty        = trim(strtolower($data['specialty']));
            $city             = trim(strtolower($data['city']));
            $contract_type    = trim(strtolower($data['contract_type']));
            $experience_level = trim(strtolower($data['experience_level']));
            $worktime         = trim(strtolower(str_replace(['_', '-'], ' ', $data['work_mode'])));

            $normalizeMatchValue = function ($value) {
                $normalized = trim(strtolower((string)$value));
                return preg_replace('/[\s_\-]+/', ' ', $normalized);
            };

            $keywordsMatch = function ($jobTitle, $keywords) use ($normalizeMatchValue) {
                $keywords = trim((string)$keywords);
                if ($keywords === '') return true;
                $jobTitle = $normalizeMatchValue($jobTitle);
                $keywords = $normalizeMatchValue($keywords);
                $terms    = preg_split('/\s+/', $keywords, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($terms as $term) {
                    if (stripos($jobTitle, $term) === false) return false;
                }
                return true;
            };

            $companyStmt = $pdo->prepare("SELECT id FROM companies_profiles WHERE user_id = ? LIMIT 1");
            $companyStmt->execute([$_SESSION['user']['id']]);
            $companyProfile  = $companyStmt->fetch(PDO::FETCH_ASSOC);
            $senderCompanyId = $companyProfile['id'] ?? 0;

            $alertStmt = $pdo->prepare("SELECT * FROM job_alerts");
            $alertStmt->execute();
            $alerts = $alertStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($alerts as $alert) {
                $match = true;

                $alertKeywords   = trim($alert['keywords']          ?? '');
                $alertCategory   = $normalizeMatchValue($alert['category']         ?? '');
                $alertSpecialty  = $normalizeMatchValue($alert['specialty']        ?? '');
                $alertCity       = $normalizeMatchValue($alert['city']             ?? '');
                $alertContract   = $normalizeMatchValue($alert['contract_type']    ?? '');
                $alertExperience = $normalizeMatchValue($alert['experience_level'] ?? '');
                $alertWorktime   = $normalizeMatchValue($alert['work_type']        ?? '');

                if (!empty($alertKeywords)   && !$keywordsMatch($title, $alertKeywords))                       $match = false;
                if (!empty($alertCategory)   && $alertCategory   !== $normalizeMatchValue($category))         $match = false;
                if (!empty($alertSpecialty)  && $alertSpecialty  !== $normalizeMatchValue($specialty))        $match = false;
                if (!empty($alertCity)       && $alertCity       !== $normalizeMatchValue($city))             $match = false;
                if (!empty($alertContract)   && $alertContract   !== $normalizeMatchValue($contract_type))    $match = false;
                if (!empty($alertExperience) && $alertExperience !== $normalizeMatchValue($experience_level)) $match = false;
                if (!empty($alertWorktime)   && $alertWorktime   !== $normalizeMatchValue($worktime))         $match = false;

                if ($match) {
                    $message   = "New matching job: " . $data['title'];
                    $notifStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, sender_id, title, type, message, related_id, is_read, created_at)
                        VALUES (:user_id, :sender_id, :title, 'job_alert', :message, :related_id, 0, NOW())
                    ");
                    $notifStmt->execute([
                        ':user_id'    => $alert['user_id'],
                        ':sender_id'  => $senderCompanyId,
                        ':title'      => 'New Job Alert',
                        ':message'    => $message,
                        ':related_id' => $newJobId,
                    ]);
                }
            }

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a New Job | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0;
        }

        body {
            background: #f8fafc !important;
            color: #0f172a;
            line-height: 1.6;
        }

        .page-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: color .2s;
        }

        .back-link:hover {
            color: #4f46e5;
        }

        .page-header {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 1.4rem 1.5rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
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

        .ph-left {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .ph-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
        }

        .ph-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
            border: 1px solid #ddd6fe;
            margin-bottom: 5px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .ph-title {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }

        .ph-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }

        .form-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 28px 28px 24px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .form-section {
            margin-bottom: 26px;
            padding-bottom: 26px;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: .05em;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: flex-start;
        }

        .section-title .s-ico {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-row.single {
            grid-template-columns: 1fr;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .05em;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .required {
            color: #ef4444;
            margin-left: 2px;
        }

        .form-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 1px;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 10px 13px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            color: #0f172a;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, .12);
            background: white;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2.5'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
            cursor: pointer;
        }

        textarea {
            resize: vertical;
            min-height: 110px;
        }

      
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
            padding-left: 36px !important;
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

        .ac-dropdown::-webkit-scrollbar {
            width: 4px;
        }

        .ac-dropdown::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 99px;
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

        .ac-item i {
            font-size: 12px;
            color: #94a3b8;
        }

        .ac-item:hover,
        .ac-item.focused {
            background: #f5f3ff;
            color: #5b21b6;
        }

        .ac-item:hover i,
        .ac-item.focused i {
            color: #5b21b6;
        }

        .ac-item mark {
            background: none;
            color: #6366f1;
            font-weight: 700;
        }

        .ac-empty {
            padding: 12px 14px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
            font-style: italic;
        }

      
        .wt-row {
            display: flex;
            gap: 8px;
        }

        .wt-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 11px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #e8eef6;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: .18s;
            user-select: none;
            font-family: 'Poppins', sans-serif;
        }

        .wt-btn i {
            font-size: 18px;
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
        }

        #work_mode_select {
            display: none;
        }

        .file-upload-wrap {
            position: relative;
            border: 1.5px dashed #e0e7ff;
            border-radius: 14px;
            background: #fafafe;
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: .2s;
        }

        .file-upload-wrap:hover {
            border-color: #818cf8;
            background: #f5f3ff;
        }

        .file-upload-wrap input[type="file"] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .fu-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin: 0 auto 8px;
        }

        .fu-text {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .fu-text span {
            color: #6366f1;
            font-weight: 700;
        }

        .fu-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        #fileNameDisplay {
            font-size: 12px;
            color: #6366f1;
            margin-top: 8px;
            font-weight: 600;
            display: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 24px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-family: 'Poppins', sans-serif;
            transition: all .2s;
            text-decoration: none;
        }

        .btn-cancel {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e8eef6;
        }

        .btn-cancel:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-submit {
            background: #6366f1;
            color: white;
            box-shadow: 0 8px 20px rgba(99, 102, 241, .22);
        }

        .btn-submit:hover {
            background: #4f46e5;
            box-shadow: 0 12px 28px rgba(99, 102, 241, .30);
            transform: translateY(-1px);
        }

        @media (max-width: 680px) {
            .page-wrapper {
                padding: 20px 14px 40px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .wt-btn {
                flex: 1 1 calc(33% - 6px);
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }

        .chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
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

        .ac-dropdown *,
        .ac-item,
        .ac-item span,
        .ac-item em,
        .ac-item mark {
            letter-spacing: 0 !important;
            word-spacing: 0 !important;
        }

        .ac-dropdown,
        .ac-dropdown .ac-item,
        .ac-dropdown .ac-item * {
            font-family: 'Poppins', sans-serif !important;
            letter-spacing: 0 !important;
            word-spacing: 0 !important;
        }

        .ac-item i,
        .ac-item i::before,
        .ac-item i::after {
            font-family: 'tabler-icons' !important;
            letter-spacing: 0 !important;
        }
    </style>
</head>

<body>


    <div class="page-wrapper">

        <a href="index.php" class="back-link">
            <i class="ti ti-arrow-left"></i> Back to Dashboard
        </a>

        <div class="page-header">
            <div class="ph-left">
                <div class="ph-icon"><i class="ti ti-briefcase"></i></div>
                <div>
                    <div class="ph-badge"><i class="ti ti-sparkles"></i> New Listing</div>
                    <div class="ph-title">Post a New Job</div>
                    <div class="ph-sub">Create an engaging listing and reach qualified candidates</div>
                </div>
            </div>
        </div>

        <form method="POST" class="form-card" autocomplete="off">

            <?php if ($error): ?>
                <div class="alert error">
                    <i class="ti ti-alert-circle"></i>
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- ── BASIC INFORMATION ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-info-circle"></i></span>
                    Basic Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="ti ti-briefcase"></i> Job Title <span class="required">*</span></label>
                        <div class="ac-wrap" id="wrap-title">
                            <i class="ti ti-search ac-icon"></i>
                            <input type="text" name="title" id="input-title"
                                value="<?php echo e($_POST['title'] ?? ''); ?>"
                                placeholder="e.g. Senior Frontend Developer" required autocomplete="off">
                            <div class="ac-dropdown" id="drop-title"></div>
                        </div>
                        <div class="form-hint">Choose a clear, specific job title</div>
                    </div>

                    <div class="form-group">
                        <label><i class="ti ti-category"></i> Category <span class="required">*</span></label>
                        <div class="ac-wrap" id="wrap-category">
                            <i class="ti ti-tag ac-icon"></i>
                            <input type="text" name="category" id="input-category"
                                value="<?php echo e($_POST['category'] ?? ''); ?>"
                                placeholder="e.g. IT / Development" required autocomplete="off">
                            <div class="ac-dropdown" id="drop-category"></div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="ti ti-code"></i> Specialty</label>
                        <div class="ac-wrap" id="wrap-specialty">
                            <i class="ti ti-sparkles ac-icon"></i>
                            <input type="text" name="specialty" id="input-specialty"
                                value="<?php echo e($_POST['specialty'] ?? ''); ?>"
                                placeholder="e.g. Frontend Development, React" autocomplete="off">
                            <div class="ac-dropdown" id="drop-specialty"></div>
                        </div>
                        <div class="form-hint">Specific area or technology</div>
                    </div>

                    <!-- ── CITY  ── -->
                    <div class="form-group">
                        <label><i class="ti ti-map-pin"></i> City <span class="required">*</span></label>
                        <div class="ac-wrap" id="wrap-city">
                            <i class="ti ti-map-pin ac-icon"></i>
                            <input type="text" id="cityDisplay"
                                placeholder="Search city..."
                                value="<?php echo e($_POST['city'] ?? ''); ?>"
                                autocomplete="off">
                            <input type="hidden" name="city" id="cityHidden"
                                value="<?php echo e($_POST['city'] ?? ''); ?>">
                            <div class="ac-dropdown" id="drop-city"></div>
                        </div>
                        <div class="form-hint">Type to search Algerian cities</div>
                    </div>
                </div>
            </div>

            <!-- ── JOB DETAILS ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-list-details"></i></span>
                    Job Details
                </div>

                <div class="form-row">
                    <!-- Experience Level — chips -->
                    <div class="form-group">
                        <label><i class="ti ti-trending-up"></i> Experience Level <span class="required">*</span></label>
                        <div class="chip-grid" id="expLevelChips" style="margin-top:4px">
                            <?php
                            $expChips = [
                                'Junior Level'  => ['color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                                'Mid Level'     => ['color' => '#166534', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
                                'Senior Level'  => ['color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                                'Expert Level'  => ['color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                                'Internship'    => ['color' => '#0c4a6e', 'bg' => '#e0f2fe', 'border' => '#bae6fd'],
                            ];
                            $selExp = $_POST['experience_level'] ?? '';
                            foreach ($expChips as $val => $opt): ?>
                                <span class="fchip <?php echo $selExp === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo e($val); ?>"
                                    data-group="exp_level"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleJobChip(this,'exp_level_hidden')">
                                    <?php echo e($val); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="experience_level" id="exp_level_hidden"
                            value="<?php echo e($_POST['experience_level'] ?? ''); ?>">
                        <div class="form-hint">Select one or leave empty</div>
                    </div>

                    <!-- Years of Experience — autocomplete + free type -->
                    <div class="form-group">
                        <label><i class="ti ti-clock"></i> Years of Experience</label>
                        <div class="ac-wrap" id="wrap-experience">
                            <i class="ti ti-clock ac-icon"></i>
                            <input type="text" name="experience" id="input-experience"
                                value="<?php echo e($_POST['experience'] ?? ''); ?>"
                                placeholder="e.g. 2-5 years" autocomplete="off">
                            <div class="ac-dropdown" id="drop-experience"></div>
                        </div>
                        <div class="form-hint">Pick a range or type freely</div>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Contract Type — chips -->
                    <div class="form-group">
                        <label><i class="ti ti-file-text"></i> Contract Type</label>
                        <?php
                        $contractChips = [
                            'CDI'        => ['label' => 'CDI',        'icon' => 'ti-file-invoice',  'color' => '#6d28d9', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
                            'CDD'        => ['label' => 'CDD',        'icon' => 'ti-file-text',     'color' => '#be185d', 'bg' => '#fce7f3', 'border' => '#fbcfe8'],
                            'Full_Time'  => ['label' => 'Full Time',  'icon' => 'ti-briefcase',     'color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                            'Part_Time'  => ['label' => 'Part Time',  'icon' => 'ti-clock',         'color' => '#065f46', 'bg' => '#ecfdf5', 'border' => '#a7f3d0'],
                            'Freelance'  => ['label' => 'Freelance',  'icon' => 'ti-device-laptop', 'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                            'Internship' => ['label' => 'Internship', 'icon' => 'ti-school',        'color' => '#166534', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
                            'Temporary'  => ['label' => 'Temporary',  'icon' => 'ti-calendar-event', 'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                        ];
                        $selContract = $_POST['contract_type'] ?? '';
                        ?>
                        <div class="chip-grid" style="margin-top:4px">
                            <?php foreach ($contractChips as $val => $opt): ?>
                                <span class="fchip <?php echo $selContract === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo e($val); ?>"
                                    data-group="contract_type"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleJobChip(this,'contract_type_hidden')">
                                    <i class="ti <?php echo $opt['icon']; ?>"></i>
                                    <?php echo e($opt['label']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="contract_type" id="contract_type_hidden"
                            value="<?php echo e($_POST['contract_type'] ?? ''); ?>">
                        <div class="form-hint">Select one or leave empty</div>
                    </div>

                    <!-- Work Mode — existing toggle buttons (unchanged) -->
                    <div class="form-group">
                        <label><i class="ti ti-building"></i> Work Mode</label>
                        <div class="wt-row" id="wtRow">
                            <div class="wt-btn <?php echo (($_POST['work_mode'] ?? '') === 'On-site') ? 'active' : ''; ?>" data-val="On-site">
                                <i class="ti ti-building"></i> On-site
                            </div>
                            <div class="wt-btn <?php echo (($_POST['work_mode'] ?? '') === 'Remote') ? 'active' : ''; ?>" data-val="Remote">
                                <i class="ti ti-home"></i> Remote
                            </div>
                            <div class="wt-btn <?php echo (($_POST['work_mode'] ?? '') === 'Hybrid') ? 'active' : ''; ?>" data-val="Hybrid">
                                <i class="ti ti-arrows-shuffle"></i> Hybrid
                            </div>
                        </div>
                        <select name="work_mode" id="work_mode_select">
                            <option value=""></option>
                            <option value="On-site" <?php echo (($_POST['work_mode'] ?? '') === 'On-site') ? 'selected' : ''; ?>>On-site</option>
                            <option value="Remote" <?php echo (($_POST['work_mode'] ?? '') === 'Remote')  ? 'selected' : ''; ?>>Remote</option>
                            <option value="Hybrid" <?php echo (($_POST['work_mode'] ?? '') === 'Hybrid')  ? 'selected' : ''; ?>>Hybrid</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Salary -->
                    <div class="form-group">
                        <label><i class="ti ti-coin"></i> Salary</label>
                        <input type="text" name="salary"
                            value="<?php echo e($_POST['salary'] ?? ''); ?>"
                            placeholder="e.g. 80,000 - 120,000 DZD">
                    </div>

                    <!-- Posting Duration — chips -->
                    <div class="form-group">
                        <label><i class="ti ti-calendar"></i> Posting Duration <span class="required">*</span></label>
                        <?php
                        $durationChips = [
                            '3'  => ['label' => '3 Days',  'icon' => 'ti-calendar',       'color' => '#0c4a6e', 'bg' => '#e0f2fe', 'border' => '#bae6fd'],
                            '7'  => ['label' => '7 Days',  'icon' => 'ti-calendar-week',  'color' => '#166534', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
                            '15' => ['label' => '15 Days', 'icon' => 'ti-calendar-month', 'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                            '30' => ['label' => '30 Days', 'icon' => 'ti-calendar-stats', 'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                        ];
                        $selDuration = $_POST['duration_days'] ?? '7';
                        ?>
                        <div class="chip-grid" style="margin-top:4px">
                            <?php foreach ($durationChips as $val => $opt): ?>
                                <span class="fchip <?php echo $selDuration == $val ? 'active' : ''; ?>"
                                    data-val="<?php echo e($val); ?>"
                                    data-group="duration"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleJobChipRequired(this,'duration_hidden')">
                                    <i class="ti <?php echo $opt['icon']; ?>"></i>
                                    <?php echo e($opt['label']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="duration_days" id="duration_hidden"
                            value="<?php echo e($selDuration); ?>">
                        <div class="form-hint">Select listing duration</div>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Education Level — chips -->
                    <div class="form-group">
                        <label><i class="ti ti-school"></i> Education Level</label>
                        <?php
                        $eduChips = [
                            'High School'     => ['color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                            'Bachelor Degree' => ['color' => '#166534', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
                            'Master Degree'   => ['color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                            'Engineer Degree' => ['color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                            'Doctorate'       => ['color' => '#be185d', 'bg' => '#fce7f3', 'border' => '#fbcfe8'],
                        ];
                        $selEdu = $_POST['education_level'] ?? '';
                        ?>
                        <div class="chip-grid" style="margin-top:4px">
                            <?php foreach ($eduChips as $val => $opt): ?>
                                <span class="fchip <?php echo $selEdu === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo e($val); ?>"
                                    data-group="education"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleJobChip(this,'education_hidden')">
                                    <?php echo e($val); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="education_level" id="education_hidden"
                            value="<?php echo e($_POST['education_level'] ?? ''); ?>">
                        <div class="form-hint">Select one or leave empty</div>
                    </div>

                    <!-- Required Languages -->
                    <div class="form-group">
                        <label><i class="ti ti-language"></i> Required Languages</label>
                        <div class="ac-wrap" id="wrap-language">
                            <i class="ti ti-language ac-icon"></i>
                            <input type="text" name="language_required" id="input-language"
                                value="<?php echo e($_POST['language_required'] ?? ''); ?>"
                                placeholder="e.g. Arabic, English, French..."
                                autocomplete="off">
                            <div class="ac-dropdown" id="drop-language"></div>
                        </div>
                        <div class="form-hint">Type or pick — separate multiple with commas</div>
                    </div>
                </div>

            </div>

            <!-- ── JOB DESCRIPTION ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-file-description"></i></span>
                    Job Description
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-align-left"></i> Job Description</label>
                        <textarea name="description"
                            placeholder="Provide a comprehensive overview of the position..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                        <div class="form-hint">Include company background and role overview</div>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-list-check"></i> Responsibilities</label>
                        <textarea name="responsibilities"
                            placeholder="List key responsibilities and duties..."><?php echo e($_POST['responsibilities'] ?? ''); ?></textarea>
                        <div class="form-hint">One per line is recommended</div>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-checklist"></i> Requirements</label>
                        <textarea name="requirements"
                            placeholder="Specify essential and nice-to-have requirements..."><?php echo e($_POST['requirements'] ?? ''); ?></textarea>
                        <div class="form-hint">Be specific about what you're looking for</div>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-gift"></i> Benefits & Perks</label>
                        <textarea name="benefits"
                            placeholder="e.g. Health insurance&#10;Remote work&#10;Flexible hours"><?php echo e($_POST['benefits'] ?? ''); ?></textarea>
                        <div class="form-hint">One benefit per line</div>
                    </div>
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-tools"></i> Required Skills</label>
                        <textarea name="skills"
                            placeholder="e.g. React, Node.js, MongoDB, REST APIs"><?php echo e($_POST['skills'] ?? ''); ?></textarea>
                        <div class="form-hint">List key skills (separated by commas)</div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-cancel">
                    <i class="ti ti-x"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="ti ti-send"></i> Post Job
                </button>
            </div>

        </form>
    </div>



    <script src="js/script.js"></script>

    <script>
        const AC_DATA = {
            title: <?php echo json_encode(array_values($allTitles)); ?>,
            category: <?php echo json_encode(array_values($allCategories)); ?>,
            specialty: <?php echo json_encode(array_values($allSpecialties)); ?>,
            city: <?php echo json_encode(array_values($cities)); ?>
        };
    </script>

    <script>
        /* ── GENERIC AUTOCOMPLETE ENGINE ── */
        function buildAutocomplete(inputId, dropId, dataKey, icon = 'ti-hash', hiddenId = null, strictMatch = false) {
            const input = document.getElementById(inputId);
            const drop = document.getElementById(dropId);
            const hidden = hiddenId ? document.getElementById(hiddenId) : null;
            let focusedIdx = -1;

            function highlight(text, query) {
                if (!query) return text;
                const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return text.replace(re, '<mark>$1</mark>');
            }

            function render(query) {
                const q = query.trim().toLowerCase();
                const list = AC_DATA[dataKey];
                let results = q ? list.filter(v => v.toLowerCase().includes(q)) : list;

                if (!strictMatch && q && !list.some(v => v.toLowerCase() === q)) {
                    results = [query, ...results];
                }

                results = results.slice(0, 12);
                focusedIdx = -1;

                drop.innerHTML = results.length === 0 ?
                    '<div class="ac-empty">No results found</div>' :
                    results.map((item, i) => {
                        const isNew = !strictMatch && q && item === query && !list.some(v => v.toLowerCase() === q);
                        return `<div class="ac-item" data-val="${item.replace(/"/g,'&quot;')}" data-idx="${i}">
                        <i class="ti ${isNew ? 'ti-pencil-plus' : icon}"></i>
                        ${isNew ? '<em style="color:#6366f1;font-style:normal;font-weight:600">Use: </em>' : ''}
                        ${highlight(item, q)}
                    </div>`;
                    }).join('');

                drop.classList.add('open');
                attachEvents();
            }

            function attachEvents() {
                drop.querySelectorAll('.ac-item').forEach(item => {
                    item.addEventListener('mousedown', e => {
                        e.preventDefault();
                        input.value = item.dataset.val;
                        if (hidden) hidden.value = item.dataset.val;
                        close();
                    });
                });
            }

            function close() {
                drop.classList.remove('open');
                focusedIdx = -1;
                if (strictMatch && hidden) {
                    const list = AC_DATA[dataKey];
                    if (!list.some(v => v.toLowerCase() === input.value.trim().toLowerCase())) {
                        input.value = '';
                        hidden.value = '';
                    }
                }
            }

            function moveFocus(dir) {
                const items = drop.querySelectorAll('.ac-item');
                if (!items.length) return;
                items.forEach(i => i.classList.remove('focused'));
                focusedIdx = (focusedIdx + dir + items.length) % items.length;
                items[focusedIdx].classList.add('focused');
                items[focusedIdx].scrollIntoView({
                    block: 'nearest'
                });
            }

            input.addEventListener('input', () => render(input.value));
            input.addEventListener('focus', () => render(input.value));

            input.addEventListener('keydown', e => {
                if (!drop.classList.contains('open')) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    moveFocus(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    moveFocus(-1);
                } else if (e.key === 'Enter') {
                    const focused = drop.querySelector('.ac-item.focused');
                    if (focused) {
                        e.preventDefault();
                        input.value = focused.dataset.val;
                        if (hidden) hidden.value = focused.dataset.val;
                        close();
                    }
                } else if (e.key === 'Escape') close();
            });

            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !drop.contains(e.target)) close();
            });
        }

        buildAutocomplete('input-title', 'drop-title', 'title', 'ti-briefcase');
        buildAutocomplete('input-category', 'drop-category', 'category', 'ti-category');
        buildAutocomplete('input-specialty', 'drop-specialty', 'specialty', 'ti-code');
        buildAutocomplete('cityDisplay', 'drop-city', 'city', 'ti-map-pin', 'cityHidden', true);

        /* ── WORK MODE TOGGLE ── */
        document.querySelectorAll('#wtRow .wt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const sel = document.getElementById('work_mode_select');
                if (btn.classList.contains('active')) {
                    btn.classList.remove('active');
                    sel.value = '';
                } else {
                    document.querySelectorAll('#wtRow .wt-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    sel.value = btn.dataset.val;
                }
            });
        });

        /* ── CITY VALIDATION ── */
        document.querySelector('form').addEventListener('submit', function(e) {
            const cityHidden = document.getElementById('cityHidden').value.trim();
            if (!cityHidden) {
                e.preventDefault();
                const cityInput = document.getElementById('cityDisplay');
                cityInput.style.borderColor = '#ef4444';
                cityInput.style.boxShadow = '0 0 0 3px rgba(239,68,68,.12)';
                cityInput.focus();
                setTimeout(() => {
                    cityInput.style.borderColor = '';
                    cityInput.style.boxShadow = '';
                }, 2000);
            }
        });

        /* ── CHIP TOGGLE (optional) ── */
        function toggleJobChip(chip, hiddenId) {
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

        /* ── CHIP TOGGLE (required) ── */
        function toggleJobChipRequired(chip, hiddenId) {
            const group = chip.dataset.group;
            const hidden = document.getElementById(hiddenId);
            document.querySelectorAll(`.fchip[data-group="${group}"]`).forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            hidden.value = chip.dataset.val;
        }

        /* ── YEARS OF EXPERIENCE ── */
        (function() {
            const EXP_RANGES = [
                'Less than 1 year', '1 year', '1-2 years', '2-3 years',
                '3-5 years', '5-7 years', '7-10 years', '10+ years'
            ];
            const input = document.getElementById('input-experience');
            const drop = document.getElementById('drop-experience');
            let focusedIdx = -1;

            function render() {
                const q = input.value.trim().toLowerCase();
                const results = q ? EXP_RANGES.filter(r => r.toLowerCase().includes(q)) : EXP_RANGES;
                if (!results.length) {
                    drop.classList.remove('open');
                    return;
                }

                focusedIdx = -1;
                drop.innerHTML = results.map((r, i) => `
                <div class="ac-item" data-val="${r}" data-idx="${i}">
                    <i class="ti ti-clock"></i>
                    ${r.replace(new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'),'<mark>$1</mark>')}
                </div>`).join('');
                drop.classList.add('open');

                drop.querySelectorAll('.ac-item').forEach(item => {
                    item.addEventListener('mousedown', e => {
                        e.preventDefault();
                        input.value = item.dataset.val;
                        drop.classList.remove('open');
                    });
                });
            }

            function moveFocus(dir) {
                const items = [...drop.querySelectorAll('.ac-item')];
                if (!items.length) return;
                items.forEach(i => i.classList.remove('focused'));
                focusedIdx = (focusedIdx + dir + items.length) % items.length;
                items[focusedIdx].classList.add('focused');
                items[focusedIdx].scrollIntoView({
                    block: 'nearest'
                });
            }

            input.addEventListener('input', render);
            input.addEventListener('focus', render);
            input.addEventListener('keydown', e => {
                if (!drop.classList.contains('open')) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    moveFocus(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    moveFocus(-1);
                } else if (e.key === 'Enter') {
                    const f = drop.querySelector('.ac-item.focused');
                    if (f) {
                        e.preventDefault();
                        input.value = f.dataset.val;
                        drop.classList.remove('open');
                    }
                } else if (e.key === 'Escape') drop.classList.remove('open');
            });
            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !drop.contains(e.target)) drop.classList.remove('open');
            });
        })();

       
        (function() {
            const LANGUAGES = ['Arabic', 'English', 'French', 'Tamazight', 'Spanish', 'German', 'Italian', 'Chinese'];
            const input = document.getElementById('input-language');
            const drop = document.getElementById('drop-language');
            let focusedIdx = -1;

            function getCurrentToken() {
                const parts = input.value.split(',');
                return parts[parts.length - 1].trim();
            }

            function replaceLastToken(chosen) {
                const parts = input.value.split(',').map(p => p.trim()).filter(Boolean);
                const unique = [...new Set([...parts.slice(0, -1), chosen])];
                input.value = unique.join(', ') + ', ';
                drop.classList.remove('open');
                input.focus();
            }

            function render() {
                const q = getCurrentToken().toLowerCase();
                const already = input.value.split(',').map(p => p.trim().toLowerCase()).filter(Boolean);
                const results = q ?
                    LANGUAGES.filter(l => l.toLowerCase().includes(q) && !already.includes(l.toLowerCase())) :
                    LANGUAGES.filter(l => !already.includes(l.toLowerCase()));

                if (!results.length) {
                    drop.classList.remove('open');
                    return;
                }

                focusedIdx = -1;
                drop.innerHTML = results.map((lang, i) => `
                <div class="ac-item" data-val="${lang}" data-idx="${i}">
                    <i class="ti ti-language"></i>
                    ${lang.replace(new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'),'<mark>$1</mark>')}
                </div>`).join('');
                drop.classList.add('open');

                drop.querySelectorAll('.ac-item').forEach(item => {
                    item.addEventListener('mousedown', e => {
                        e.preventDefault();
                        replaceLastToken(item.dataset.val);
                    });
                });
            }

            function moveFocus(dir) {
                const items = [...drop.querySelectorAll('.ac-item')];
                if (!items.length) return;
                items.forEach(i => i.classList.remove('focused'));
                focusedIdx = (focusedIdx + dir + items.length) % items.length;
                items[focusedIdx].classList.add('focused');
                items[focusedIdx].scrollIntoView({
                    block: 'nearest'
                });
            }

            input.addEventListener('input', render);
            input.addEventListener('focus', render);
            input.addEventListener('keydown', e => {
                if (!drop.classList.contains('open')) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    moveFocus(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    moveFocus(-1);
                } else if (e.key === 'Enter') {
                    const f = drop.querySelector('.ac-item.focused');
                    if (f) {
                        e.preventDefault();
                        replaceLastToken(f.dataset.val);
                    }
                } else if (e.key === 'Escape') drop.classList.remove('open');
            });
            document.addEventListener('click', e => {
                if (!input.contains(e.target) && !drop.contains(e.target)) drop.classList.remove('open');
            });
        })();
    </script>
</body>

</html>