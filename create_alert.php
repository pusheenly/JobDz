<?php
require_once 'config.php';
require_once 'functions.php';
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

$allCategories  = getCategories();
$allSpecialties = getSpecialties();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job Alert | JobDZ</title>
    <?php include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
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

        /* ── PAGE HEADER ── */
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

        /* ── SUCCESS BANNER ── */
        .success-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 14px 18px;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #166534;
            font-weight: 500;
            display: none;
            margin-bottom: 16px;
        }

        .success-banner.show {
            display: flex;
        }

        .success-banner i {
            font-size: 17px;
            color: #16a34a;
        }

        /* ── FORM CARD ── */
        .form-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 28px 28px 24px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        /* ── SECTION ── */
        .form-section {
            display: block;
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
            margin-left: 0;
            padding-left: 0;
            text-transform: uppercase;
            letter-spacing: .05em;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            width: 100%;
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

        /* ── GRID ── */
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

        /* ── LABELS ── */
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

        /* ── BASE INPUTS ── */
        input[type="text"],
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
            direction: ltr;
            text-align: left;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, .12);
            background: white;
        }

        input::placeholder {
            color: #cbd5e1;
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

        /* ── CHIPS ── */
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
        }

        .fchip i {
            font-size: 12px;
        }

        .fchip:hover {
            filter: brightness(.93);
        }

        .fchip.active {
            box-shadow: 0 0 0 2px currentColor;
            filter: brightness(.88);
        }

        /* ── WORK MODE ── */
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
            box-shadow: 0 4px 12px rgba(99, 102, 241, .25);
        }

        /* ── FREQUENCY ── */
        .freq-row {
            display: flex;
            gap: 8px;
        }

        .freq-btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
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
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }

        .freq-btn i {
            font-size: 17px;
        }

        .freq-btn:hover {
            border-color: #818cf8;
            color: #6366f1;
            background: #f5f3ff;
        }

        .freq-btn.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
            box-shadow: 0 4px 12px rgba(99, 102, 241, .25);
        }

        /* ── PREVIEW ── */
        .preview-card {
            background: #f8fafc;
            border: 1.5px dashed #c7d2fe;
            border-radius: 18px;
            padding: 16px 18px;
        }

        .preview-title-row {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .preview-title-row i {
            color: #6366f1;
            font-size: 15px;
        }

        .preview-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .preview-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 4px 11px;
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
        }

        .preview-tag i {
            color: #94a3b8;
            font-size: 12px;
        }

        .preview-empty {
            font-size: 12px;
            color: #94a3b8;
            font-style: italic;
        }

        /* ── FORM ACTIONS ── */
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

        /* ── RESPONSIVE ── */
        @media (max-width: 680px) {
            .page-wrapper {
                padding: 20px 14px 40px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .wt-row,
            .freq-row {
                flex-wrap: wrap;
            }

            .wt-btn,
            .freq-btn {
                flex: 1 1 calc(33% - 8px);
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
    </style>
</head>

<body>



    <div class="page-wrapper">

        <a href="job.php" class="back-link">
            <i class="ti ti-arrow-left"></i> Back to Jobs
        </a>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-banner show">
                <i class="ti ti-circle-check"></i>
                <span>Your job alert has been created successfully! We'll notify you when matching jobs are posted.</span>
            </div>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="ph-left">
                <div class="ph-icon"><i class="ti ti-bell-ringing"></i></div>
                <div>
                    <div class="ph-badge"><i class="ti ti-sparkles"></i> Smart Alert</div>
                    <div class="ph-title">Create a Job Alert</div>
                    <div class="ph-sub">Get notified instantly when matching jobs are posted</div>
                </div>
            </div>
        </div>

        <!-- FORM -->
        <form id="alertForm" method="POST" action="save_alert.php" class="form-card" autocomplete="off">

            <input type="hidden" name="contract" id="contractValue" value="<?php echo htmlspecialchars($_GET['contract']   ?? ''); ?>">
            <input type="hidden" name="experience" id="experienceValue" value="<?php echo htmlspecialchars($_GET['experience'] ?? ''); ?>">
            <input type="hidden" name="worktime" id="worktimeValue" value="<?php echo htmlspecialchars($_GET['worktime']   ?? ''); ?>">
            <input type="hidden" name="city" id="cityHiddenValue" value="<?php echo htmlspecialchars($_GET['city']      ?? ''); ?>">
            <input type="hidden" name="frequency" id="frequencyValue" value="instant">

            <!-- ── KEYWORDS & LOCATION ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-search"></i></span>
                    Keywords & Location
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="ti ti-search"></i> Keywords <span class="required">*</span></label>
                        <div class="ac-wrap">
                            <i class="ti ti-search ac-icon"></i>
                            <input type="text" name="keywords" id="keywordsInput"
                                placeholder="e.g. React developer, designer..."
                                value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                oninput="updatePreview()">
                        </div>
                        <div class="form-hint">Job title, skill, or company name</div>
                    </div>

                    <div class="form-group">
                        <label><i class="ti ti-map-pin"></i> Wilaya</label>
                        <div class="ac-wrap" id="wrap-wilaya">
                            <i class="ti ti-map-pin ac-icon"></i>
                            <input type="text" id="wilayaDisplay"
                                placeholder="All wilayas (Algeria)"
                                value="<?php echo htmlspecialchars($_GET['city'] ?? ''); ?>"
                                autocomplete="off" readonly
                                onclick="document.getElementById('drop-wilaya').classList.toggle('open')">
                            <div class="ac-dropdown" id="drop-wilaya">
                                <div class="ac-item" data-val="" onclick="pickWilaya(this)">
                                    <i class="ti ti-world"></i> All wilayas
                                </div>
                                <?php
                                $algeriaWilayas = ['Adrar', 'Chlef', 'Laghouat', 'Oum El Bouaghi', 'Batna', 'Béjaïa', 'Biskra', 'Béchar', 'Blida', 'Bouira', 'Tamanrasset', 'Tébessa', 'Tlemcen', 'Tiaret', 'Tizi Ouzou', 'Alger', 'Djelfa', 'Jijel', 'Sétif', 'Saïda', 'Skikda', 'Sidi Bel Abbès', 'Annaba', 'Guelma', 'Constantine', 'Médéa', 'Mostaganem', "M'Sila", 'Mascara', 'Ouargla', 'Oran', 'El Bayadh', 'Illizi', 'Bordj Bou Arréridj', 'Boumerdès', 'El Tarf', 'Tindouf', 'Tissemsilt', 'El Oued', 'Khenchela', 'Souk Ahras', 'Tipaza', 'Mila', 'Aïn Defla', 'Naâma', 'Aïn Témouchent', 'Ghardaïa', 'Relizane'];
                                foreach ($algeriaWilayas as $w): ?>
                                    <div class="ac-item" data-val="<?php echo htmlspecialchars($w); ?>" onclick="pickWilaya(this)">
                                        <i class="ti ti-map-pin"></i> <?php echo htmlspecialchars($w); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-hint">Leave empty to cover all of Algeria</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="ti ti-category"></i> Category</label>
                        <div class="ac-wrap">
                            <i class="ti ti-tag ac-icon"></i>
                            <input type="text" name="category" id="input-category"
                                placeholder="e.g. IT / Development"
                                value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>"
                                autocomplete="off"
                                oninput="buildAC('input-category','drop-category',AC_CATEGORIES)"
                                onfocus="buildAC('input-category','drop-category',AC_CATEGORIES)"
                                onkeydown="navAC(event,'drop-category','input-category')">
                            <div class="ac-dropdown" id="drop-category"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="ti ti-briefcase"></i> Specialty</label>
                        <div class="ac-wrap">
                            <i class="ti ti-sparkles ac-icon"></i>
                            <input type="text" name="specialty" id="input-specialty"
                                placeholder="e.g. Frontend, UX Design..."
                                value="<?php echo htmlspecialchars($_GET['specialty'] ?? ''); ?>"
                                autocomplete="off"
                                oninput="buildAC('input-specialty','drop-specialty',AC_SPECIALTIES)"
                                onfocus="buildAC('input-specialty','drop-specialty',AC_SPECIALTIES)"
                                onkeydown="navAC(event,'drop-specialty','input-specialty')">
                            <div class="ac-dropdown" id="drop-specialty"></div>
                        </div>
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
                    <!-- Contract Type -->
                    <div class="form-group">
                        <label><i class="ti ti-file-text"></i> Contract Type</label>
                        <?php
                        $contractOptions = [
                            'CDI'        => ['label' => 'CDI',        'icon' => 'ti-file-invoice',   'color' => '#6d28d9', 'bg' => '#ede9fe', 'border' => '#ddd6fe'],
                            'CDD'        => ['label' => 'CDD',        'icon' => 'ti-file-text',      'color' => '#be185d', 'bg' => '#fce7f3', 'border' => '#fbcfe8'],
                            'Full_Time'  => ['label' => 'Full Time',  'icon' => 'ti-briefcase',      'color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                            'Part_Time'  => ['label' => 'Part Time',  'icon' => 'ti-clock',          'color' => '#065f46', 'bg' => '#ecfdf5', 'border' => '#a7f3d0'],
                            'Freelance'  => ['label' => 'Freelance',  'icon' => 'ti-device-laptop',  'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                            'Internship' => ['label' => 'Internship', 'icon' => 'ti-school',         'color' => '#166534', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
                            'Temporary'  => ['label' => 'Temporary',  'icon' => 'ti-calendar-event', 'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                        ];
                        $selectedContract = $_GET['contract'] ?? '';
                        ?>
                        <div class="chip-grid" style="margin-top:4px">
                            <?php foreach ($contractOptions as $val => $opt): ?>
                                <span class="fchip <?php echo $selectedContract === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo $val; ?>"
                                    data-group="contract"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleFChip(this,'contractValue')">
                                    <i class="ti <?php echo $opt['icon']; ?>"></i>
                                    <?php echo $opt['label']; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint">Select one or leave empty for any</div>
                    </div>

                    <!-- Experience Level -->
                    <div class="form-group">
                        <label><i class="ti ti-trending-up"></i> Experience Level</label>
                        <?php
                        $experienceOptions = [
                            'Junior Level'  => ['label' => 'Junior Level',  'color' => '#1e40af', 'bg' => '#dbeafe', 'border' => '#bfdbfe'],
                            'Mid Level'     => ['label' => 'Mid Level',     'color' => '#166534', 'bg' => '#dcfce7', 'border' => '#bbf7d0'],
                            'Senior Level'  => ['label' => 'Senior Level',  'color' => '#7e22ce', 'bg' => '#fdf4ff', 'border' => '#e9d5ff'],
                            'Expert Level'  => ['label' => 'Expert Level',  'color' => '#92400e', 'bg' => '#fef3c7', 'border' => '#fde68a'],
                            'Internship'    => ['label' => 'Internship',    'color' => '#0c4a6e', 'bg' => '#e0f2fe', 'border' => '#bae6fd'],
                        ];
                        $selectedExp = $_GET['experience'] ?? '';
                        ?>
                        <div class="chip-grid" style="margin-top:4px">
                            <?php foreach ($experienceOptions as $val => $opt): ?>
                                <span class="fchip <?php echo $selectedExp === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo $val; ?>"
                                    data-group="experience"
                                    style="background:<?php echo $opt['bg']; ?>;color:<?php echo $opt['color']; ?>;border-color:<?php echo $opt['border']; ?>;"
                                    onclick="toggleFChip(this,'experienceValue')">
                                    <?php echo $opt['label']; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint">Select one or leave empty for any</div>
                    </div>
                </div>

                <!-- Work Mode -->
                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-building"></i> Work Mode</label>
                        <?php
                        $worktimeOptions = [
                            'On_Site' => ['label' => 'On-site', 'icon' => 'ti-building'],
                            'Remote'  => ['label' => 'Remote',  'icon' => 'ti-home'],
                            'Hybrid'  => ['label' => 'Hybrid',  'icon' => 'ti-arrows-shuffle'],
                        ];
                        $selectedWT = $_GET['worktime'] ?? '';
                        ?>
                        <div class="wt-row">
                            <?php foreach ($worktimeOptions as $val => $opt): ?>
                                <div class="wt-btn <?php echo $selectedWT === $val ? 'active' : ''; ?>"
                                    data-val="<?php echo $val; ?>"
                                    onclick="toggleWT(this,'worktimeValue')">
                                    <i class="ti <?php echo $opt['icon']; ?>"></i>
                                    <?php echo $opt['label']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-hint">Click again to deselect — leave empty for all modes</div>
                    </div>
                </div>
            </div>

            <!-- ── NOTIFICATION FREQUENCY ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-bell"></i></span>
                    Notification Frequency
                </div>

                <div class="form-row single">
                    <div class="form-group">
                        <label><i class="ti ti-clock"></i> How often to notify you</label>
                        <div class="freq-row">
                            <div class="freq-btn active" data-val="instant" onclick="toggleFreq(this)">
                                <i class="ti ti-bolt"></i>
                                Instant
                                <span style="font-size:10px;font-weight:400;opacity:.75">As posted</span>
                            </div>
                            <div class="freq-btn" data-val="daily" onclick="toggleFreq(this)">
                                <i class="ti ti-sun"></i>
                                Daily
                                <span style="font-size:10px;font-weight:400;opacity:.75">Once a day</span>
                            </div>
                            <div class="freq-btn" data-val="weekly" onclick="toggleFreq(this)">
                                <i class="ti ti-calendar-week"></i>
                                Weekly
                                <span style="font-size:10px;font-weight:400;opacity:.75">Once a week</span>
                            </div>
                        </div>
                        <div class="form-hint">Instant alerts are recommended for competitive roles</div>
                    </div>
                </div>
            </div>

            <!-- ── ALERT PREVIEW ── -->
            <div class="form-section">
                <div class="section-title">
                    <span class="s-ico"><i class="ti ti-eye"></i></span>
                    Alert Preview
                </div>

                <div class="form-group">
                    <label><i class="ti ti-bell"></i> What your alert will look for</label>
                    <div class="preview-card">
                        <div class="preview-title-row">
                            <i class="ti ti-bell"></i>
                            <span id="previewName">Your alert</span>
                        </div>
                        <div class="preview-tags" id="previewTags">
                            <span class="preview-empty">No filters set yet. Fill in the form above.</span>
                        </div>
                    </div>
                    <div class="form-hint">Updates live as you fill in the form</div>
                </div>
            </div>

            <!-- ── ACTIONS ── -->
            <div class="form-actions">
                <a href="job.php" class="btn btn-cancel">
                    <i class="ti ti-x"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit" onclick="handleSubmit(event)">
                    <i class="ti ti-bell"></i> Create Alert
                </button>
            </div>

        </form>

    </div>



    <script>
        const AC_CATEGORIES = <?php echo json_encode(array_values($allCategories)); ?>;
        const AC_SPECIALTIES = <?php echo json_encode(array_values($allSpecialties)); ?>;

        /* ── AUTOCOMPLETE ── */
        function buildAC(inputId, dropId, dataList) {
            const input = document.getElementById(inputId);
            const drop = document.getElementById(dropId);
            const q = input.value.trim().toLowerCase();

            let results = q ? dataList.filter(v => v.toLowerCase().includes(q)) : dataList;
            if (q && !dataList.some(v => v.toLowerCase() === q)) results = [input.value, ...results];
            results = results.slice(0, 10);

            function highlight(text) {
                if (!q) return text;
                const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return text.replace(re, '<mark>$1</mark>');
            }

            drop.innerHTML = results.length ?
                results.map(item => {
                    const isNew = q && item === input.value && !dataList.some(v => v.toLowerCase() === q);
                    return `<div class="ac-item" data-val="${item.replace(/"/g,'&quot;')}">
                        <i class="ti ${isNew ? 'ti-pencil-plus' : 'ti-tag'}"></i>
                        ${isNew ? '<em style="color:#6366f1;font-style:normal;font-weight:600">Use: </em>' : ''}
                        ${highlight(item)}
                    </div>`;
                }).join('') :
                '<div style="padding:10px 14px;font-size:12px;color:#94a3b8;font-style:italic">No suggestions</div>';

            drop.classList.add('open');

            drop.querySelectorAll('.ac-item').forEach(item => {
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    input.value = item.dataset.val;
                    drop.classList.remove('open');
                    updatePreview();
                });
            });
        }

        function navAC(e, dropId, inputId) {
            const drop = document.getElementById(dropId);
            const items = [...drop.querySelectorAll('.ac-item')];
            if (!items.length || !drop.classList.contains('open')) return;
            const cur = drop.querySelector('.ac-item.focused');
            let idx = items.indexOf(cur);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (cur) cur.classList.remove('focused');
                items[Math.min(idx + 1, items.length - 1)].classList.add('focused');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (cur) cur.classList.remove('focused');
                items[Math.max(idx - 1, 0)].classList.add('focused');
            } else if (e.key === 'Enter') {
                const f = drop.querySelector('.ac-item.focused');
                if (f) {
                    e.preventDefault();
                    document.getElementById(inputId).value = f.dataset.val;
                    drop.classList.remove('open');
                    updatePreview();
                }
            } else if (e.key === 'Escape') {
                drop.classList.remove('open');
            }
        }

        /* ── WILAYA ── */
        function pickWilaya(el) {
            document.getElementById('wilayaDisplay').value = el.dataset.val;
            document.getElementById('cityHiddenValue').value = el.dataset.val;
            document.getElementById('drop-wilaya').classList.remove('open');
            updatePreview();
        }

        /* Close all dropdowns on outside click */
        document.addEventListener('click', e => {
            document.querySelectorAll('.ac-dropdown').forEach(drop => {
                const wrap = drop.closest('.ac-wrap');
                if (wrap && !wrap.contains(e.target)) drop.classList.remove('open');
            });
        });

        /* ── CHIPS ── */
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
            updatePreview();
        }

        /* ── WORK MODE ── */
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
            updatePreview();
        }

        /* ── FREQUENCY ── */
        function toggleFreq(btn) {
            document.querySelectorAll('.freq-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('frequencyValue').value = btn.dataset.val;
        }

        /* ── LIVE PREVIEW ── */
        function updatePreview() {
            const keywords = document.getElementById('keywordsInput').value.trim();
            const city = document.getElementById('wilayaDisplay').value.trim();
            const category = document.getElementById('input-category').value.trim();
            const specialty = document.getElementById('input-specialty').value.trim();
            const contract = document.getElementById('contractValue').value;
            const experience = document.getElementById('experienceValue').value;
            const worktime = document.getElementById('worktimeValue').value;

            document.getElementById('previewName').textContent = keywords || 'Your alert';

            const tags = [];
            if (keywords) tags.push({
                icon: 'ti-search',
                label: keywords
            });
            if (city) tags.push({
                icon: 'ti-map-pin',
                label: city
            });
            if (category) tags.push({
                icon: 'ti-category',
                label: category
            });
            if (specialty) tags.push({
                icon: 'ti-briefcase',
                label: specialty
            });
            if (contract) tags.push({
                icon: 'ti-file-text',
                label: contract.replaceAll('_', ' ')
            });
            if (experience) tags.push({
                icon: 'ti-trending-up',
                label: experience.replaceAll('_', ' ')
            });
            if (worktime) tags.push({
                icon: 'ti-building',
                label: worktime.replaceAll('_', ' ')
            });

            const container = document.getElementById('previewTags');
            container.innerHTML = tags.length ?
                tags.map(t => `<span class="preview-tag"><i class="ti ${t.icon}"></i>${t.label}</span>`).join('') :
                '<span class="preview-empty">No filters set yet. Fill in the form above.</span>';
        }

        updatePreview();

        /* ── SUBMIT VALIDATION ── */
        function handleSubmit(e) {
            const keywords = document.getElementById('keywordsInput').value.trim();
            if (!keywords) {
                e.preventDefault();
                document.getElementById('keywordsInput').focus();
            }
        }
    </script>

</body>

</html>