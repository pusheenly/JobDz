<?php
require_once 'functions.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$search = trim($_GET['search'] ?? '');
$city   = trim($_GET['city'] ?? '');

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 4;
$offset = ($page - 1) * $limit;

global $pdo;

$countSql = '
    SELECT COUNT(DISTINCT cp.user_id)
    FROM companies_profiles cp
    JOIN jobs j ON cp.user_id = j.user_id
    WHERE 1=1
';
$countParams = [];

if ($search !== '') {
    $countSql .= ' AND cp.company_name LIKE ?';
    $countParams[] = "%{$search}%";
}

if ($city !== '') {
    $countSql .= ' AND cp.city LIKE ?';
    $countParams[] = "%{$city}%";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalCompanies = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCompanies / $limit));

$sql = '
    SELECT cp.*, COUNT(j.id) AS job_count
    FROM companies_profiles cp
    JOIN jobs j ON cp.user_id = j.user_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND cp.company_name LIKE ?';
    $params[] = "%{$search}%";
}

if ($city !== '') {
    $sql .= ' AND cp.city LIKE ?';
    $params[] = "%{$city}%";
}

$sql .= '
    GROUP BY cp.user_id
    ORDER BY cp.user_id DESC
    LIMIT ' . $limit . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll();

$pageTitle = 'Companies | JobDZ';

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">

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

        .job-search-results {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 10px);
            background: white;
            border-radius: 18px;
            border: 1px solid #ececf5;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .08);
            overflow: hidden;
            z-index: 100;
        }

        .company-result {
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

        .company-result:hover {
            background: #f8fafc;
            padding-left: 20px;
        }

        .companies-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .company-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 22px;
            padding: 20px;
            transition: .28s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .company-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 24px;
            right: 24px;
            height: 3px;
            border-radius: 0 0 4px 4px;
            background: linear-gradient(90deg, #6366f1, #a5b4fc);
            opacity: 0;
            transition: .28s ease;
        }

        .company-card:hover {
            transform: translateY(-5px);
            border-color: #c7d2fe;
            box-shadow: 0 16px 32px rgba(99, 102, 241, .1);
            text-decoration: none;
        }

        .company-card:hover::before {
            opacity: 1;
        }

        .company-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .company-avatar {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            flex-shrink: 0;
            border: 1px solid rgba(0, 0, 0, .06);
        }

        .company-name {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            margin-bottom: 4px;
        }

        .company-city {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .company-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 14px 0 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
            background: #eef2ff;
            color: #4338ca;
            border-color: #e0e7ff;
        }

        .company-desc {
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.7;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

        .card-time {
            font-size: 11.5px;
            color: #94a3b8;
        }

        .card-view {
            font-size: 11.5px;
            font-weight: 600;
            color: #6366f1;
        }

        .empty-state {
            background: white;
            border: 1.5px dashed #e2e8f0;
            border-radius: 24px;
            padding: 70px 35px;
            text-align: center;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 36px;
            color: #cbd5e1;
            margin-bottom: 18px;
            display: block;
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

        @media (max-width: 768px) {
            .hero-section {
                padding: 28px 22px;
            }

            .hero-section h1 {
                font-size: 28px;
            }

            .companies-grid {
                grid-template-columns: 1fr;
            }

            .company-card {
                padding: 18px;
            }
        }



        @media (max-width: 768px) {

            /* ── HERO ── */
            .hero-section {
                padding: 24px 18px;
                border-radius: 20px;
            }

            .hero-section h1 {
                font-size: 22px;
            }

            .hero-section>p {
                font-size: 13px;
            }

            .hero-stats {
                gap: 16px;
                margin-top: 20px;
                flex-wrap: wrap;
            }

            .stat-num {
                font-size: 18px;
            }

            .stat-label {
                font-size: 11px;
            }

            /* ── SEARCH BAR ── */
            section>form>div[style*="display:flex"] {
                flex-direction: column !important;
                gap: 8px !important;
                border-radius: 16px !important;
                padding: 10px !important;
            }

            section>form>div>div[style*="min-width:175px"] {
                min-width: unset !important;
                width: 100% !important;
            }

            section>form>div>button[type="submit"] {
                width: 100% !important;
                justify-content: center !important;
            }

            .companies-grid {
                grid-template-columns: 1fr !important;
                gap: 14px;
            }

            .company-card {
                padding: 16px;
                border-radius: 18px;
            }

            .company-avatar {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                font-size: 16px;
            }

            .company-name {
                font-size: 15px;
            }

            .company-city {
                font-size: 12px;
            }

            .company-desc {
                font-size: 12px;
                min-height: unset;
                -webkit-line-clamp: 2;
            }

            .company-meta {
                margin: 10px 0 8px;
                gap: 6px;
            }

            .badge {
                font-size: 10.5px;
                padding: 4px 9px;
            }

            .card-footer {
                margin-top: 10px;
                padding-top: 10px;
            }

            .card-time,
            .card-view {
                font-size: 11px;
            }

            /* ── PAGINATION ── */
            .pagination-link {
                padding: 8px 12px;
                font-size: 12px;
                border-radius: 10px;
            }

            /* ── EMPTY STATE ── */
            .empty-state {
                padding: 40px 20px;
                border-radius: 18px;
            }

            /* ── MAIN PADDING ── */
            main.mx-auto {
                padding: 1rem 0.75rem !important;
            }

            .space-y-8 {
                gap: 1.25rem !important;
            }
        }

        @media (max-width: 380px) {

            .hero-section h1 {
                font-size: 19px;
            }

            .hero-stats {
                flex-direction: column;
                gap: 8px;
            }

            .stat-num {
                font-size: 16px;
            }

            .company-name {
                font-size: 14px;
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
                    <i class="fas fa-building" style="font-size:11px"></i>
                    Company directory
                </div>

                <h1>Explore Great <span>Companies</span></h1>

                <p>
                    Find employers, discover open opportunities,
                    and browse companies hiring across Algeria.
                </p>

                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-num"><?php echo number_format($displayJobs); ?>+</span>
                        <span class="stat-label">Active Jobs</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num"><?php echo number_format($displayCompanies); ?>+</span>
                        <span class="stat-label">Companies</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num"><?php echo number_format($displayWilayas); ?></span>
                        <span class="stat-label">Wilayas</span>
                    </div>
                </div>
            </section>

            <!-- SEARCH SECTION -->
            <section style="margin-bottom: 28px;">
                <form method="GET" action="companies.php">

                    <div style="display:flex; gap:10px; background:white; border:1px solid #e8eef6; border-radius:20px; padding:10px; box-shadow:0 2px 12px rgba(99,102,241,.06);">

                        <!-- Company name input -->
                        <div style="flex:1; display:flex; align-items:center; gap:10px; background:#f8fafc; border-radius:13px; padding:0 14px; min-height:48px; position:relative;">
                            <i class="fas fa-search" style="color:#9ca3af; font-size:14px; flex-shrink:0;"></i>
                            <input
                                type="text"
                                id="companySearch"
                                name="search"
                                value="<?php echo e($search); ?>"
                                placeholder="Company name..."
                                autocomplete="off"
                                style="border:none; background:transparent; outline:none; font-size:14px; width:100%; color:#0f172a; font-family:'Poppins',sans-serif;">
                            <div id="companyResults" class="job-search-results hidden"></div>
                        </div>

                        <!-- Wilaya select -->
                        <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; border-radius:13px; padding:0 14px; min-width:175px; min-height:48px;">
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
                        <button type="submit" style="background:#6366f1; color:white; border:none; border-radius:13px; padding:0 24px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap; min-height:48px; font-family:'Poppins',sans-serif; transition:.2s;" onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                            <i class="fas fa-search"></i> Search
                        </button>

                    </div>

                    <!-- Popular tags -->
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px; padding:0 4px;">
                        <span style="font-size:12px; color:#94a3b8; font-weight:500;">Popular:</span>
                        <?php
                        $popularTags = ['Tech', 'Finance', 'Healthcare', 'Engineering', 'Retail', 'Education'];
                        foreach ($popularTags as $tag):
                        ?>
                            <a href="companies.php?search=<?php echo urlencode($tag); ?>"
                                style="font-size:12px; color:#64748b; background:white; border:1px solid #e8eef6; border-radius:99px; padding:3px 13px; text-decoration:none; transition:.15s; font-weight:500;"
                                onmouseover="this.style.borderColor='#c7d2fe';this.style.color='#6366f1'"
                                onmouseout="this.style.borderColor='#e8eef6';this.style.color='#64748b'">
                                <?php echo $tag; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </form>
            </section>

            <section class="companies-grid">
                <?php if (empty($companies)): ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <p class="text-lg font-semibold">No companies found</p>
                        <p class="text-sm text-slate-400 mt-2">Try changing the search name or city.</p>
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
                    <?php foreach ($companies as $company): ?>
                        <?php
                        $avatarStyle = $avatarStyles[$idx % count($avatarStyles)];
                        $initial = strtoupper(substr($company['company_name'] ?? 'C', 0, 1));
                        $idx++;
                        ?>
                        <a href="company.php?id=<?php echo $company['user_id']; ?>" class="company-card">

                            <div class="company-top">
                                <div class="company-avatar" style="<?php echo $avatarStyle; ?>">
                                    <?php echo e($initial); ?>
                                </div>
                            </div>

                            <div>
                                <h3 class="company-name"><?php echo e($company['company_name']); ?></h3>
                                <p class="company-city">
                                    <i class="fas fa-map-marker-alt" style="font-size:10px;"></i>
                                    <?php echo e($company['city'] ?? 'Unknown city'); ?>
                                </p>
                            </div>

                            <div class="company-meta">
                                <span class="badge">
                                    <i class="fas fa-briefcase" style="margin-right:5px;font-size:10px;"></i>
                                    <?php echo (int)$company['job_count']; ?> jobs
                                </span>
                            </div>

                            <p class="company-desc">
                                <?php echo e($company['description'] ?? 'No description available for this company.'); ?>
                            </p>

                            <div class="card-footer">
                                <span class="card-time">
                                    <i class="fas fa-clock" style="margin-right:4px;font-size:10px;"></i>
                                    Recently updated
                                </span>
                                <span class="card-view">
                                    View profile <i class="fas fa-arrow-right" style="font-size:10px;margin-left:4px;"></i>
                                </span>
                            </div>

                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-center gap-2 mt-10 flex-wrap">

                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>"
                            class="pagination-link">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $page + 2);

                    if ($start > 1) {
                        echo '<a class="pagination-link" href="?page=1&search=' . urlencode($search) . '&city=' . urlencode($city) . '">1</a>';
                        if ($start > 2) echo '<span class="px-2 text-slate-400">...</span>';
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        $active = $i === $page ? 'active-page' : '';
                        echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&city=' . urlencode($city) . '" class="pagination-link ' . $active . '">' . $i . '</a>';
                    }

                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) echo '<span class="px-2 text-slate-400">...</span>';
                        echo '<a class="pagination-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&city=' . urlencode($city) . '">' . $totalPages . '</a>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&city=<?php echo urlencode($city); ?>"
                            class="pagination-link">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script>
        /* ───────── COMPANY LIVE SEARCH ───────── */
        const companySearch = document.getElementById('companySearch');
        const companyResults = document.getElementById('companyResults');

        companySearch.addEventListener('keyup', function() {
            if (this.value.trim().length < 1) {
                companyResults.classList.add('hidden');
                return;
            }
            fetch('company-search.php?q=' + encodeURIComponent(this.value))
                .then(r => r.text())
                .then(data => {
                    companyResults.innerHTML = data;
                    companyResults.classList.remove('hidden');
                    document.querySelectorAll('.company-result').forEach(item => {
                        item.addEventListener('click', function() {
                            companySearch.value = this.dataset.name;
                            companyResults.classList.add('hidden');
                        });
                    });
                });
        });

        document.addEventListener('click', function(e) {
            if (!companySearch.contains(e.target) && !companyResults.contains(e.target)) {
                companyResults.classList.add('hidden');
            }
        });
    </script>
</body>

</html>