<?php
require_once 'functions.php';

global $pdo;

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    exit;
}

$stmt = $pdo->prepare("
    SELECT DISTINCT company_name
    FROM companies_profiles
    WHERE company_name LIKE ?
    ORDER BY company_name ASC
    LIMIT 8
");

$stmt->execute(["%$q%"]);

$companies = $stmt->fetchAll();

if (!$companies) {
    echo '
        <div class="px-5 py-3 text-sm text-slate-400">
            No companies found
        </div>
    ';
    exit;
}

foreach ($companies as $company):
?>

<div
    class="company-result cursor-pointer px-5 py-3 hover:bg-slate-50 text-sm text-slate-700 border-b border-slate-100 last:border-b-0"
    data-name="<?php echo htmlspecialchars($company['company_name']); ?>">

    <i class="fas fa-building mr-2 text-slate-400"></i>

    <?php echo htmlspecialchars($company['company_name']); ?>

</div>

<?php endforeach; ?>