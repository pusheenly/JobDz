<?php
require_once 'config.php';
require_once 'functions.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) exit;

global $pdo;

// Jobs
$stmt = $pdo->prepare("SELECT j.id, j.title, cp.company_name, j.city FROM jobs j LEFT JOIN companies_profiles cp ON j.user_id = cp.user_id WHERE j.title LIKE ? OR cp.company_name LIKE ? LIMIT 5");
$like = "%$q%";
$stmt->execute([$like, $like]);
$jobs = $stmt->fetchAll();

// Companies
$stmt2 = $pdo->prepare("SELECT u.id, cp.company_name, cp.city FROM users u LEFT JOIN companies_profiles cp ON u.id = cp.user_id WHERE u.role = 'company' AND cp.company_name LIKE ? LIMIT 3");
$stmt2->execute([$like]);
$companies = $stmt2->fetchAll();

if (empty($jobs) && empty($companies)) exit;
?>

<?php if (!empty($companies)): ?>
    <div style="padding:8px 12px 4px; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#94a3b8;">Companies</div>
    <?php foreach ($companies as $co): ?>
        <div class="company-result"
            data-name="<?php echo htmlspecialchars($co['company_name']); ?>"
            data-id="<?php echo $co['id']; ?>"
            style="padding:10px 16px; display:flex; align-items:center; gap:10px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:.15s;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='white'">
            <div style="width:32px; height:32px; border-radius:9px; background:#ede9fe; color:#6d28d9; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0;">
                <?php echo strtoupper(substr($co['company_name'], 0, 1)); ?>
            </div>
            <div>
                <div style="font-size:13px; font-weight:600; color:#0f172a;"><?php echo htmlspecialchars($co['company_name']); ?></div>
                <?php if (!empty($co['city'])): ?>
                    <div style="font-size:11px; color:#94a3b8;"><?php echo htmlspecialchars($co['city']); ?></div>
                <?php endif; ?>
            </div>
            <div style="margin-left:auto; font-size:11px; color:#6366f1; font-weight:600;">View <i class="fas fa-arrow-right" style="font-size:9px;"></i></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($jobs)): ?>
    <div style="padding:8px 12px 4px; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#94a3b8; <?php echo !empty($companies) ? 'margin-top:4px;' : ''; ?>">Jobs</div>
    <?php foreach ($jobs as $job): ?>
        <div class="job-result"
            data-title="<?php echo htmlspecialchars($job['title']); ?>"
            data-id="<?php echo $job['id']; ?>"
            style="padding:10px 16px; display:flex; align-items:center; gap:10px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:.15s;"
            onmouseover="this.style.background='#f8fafc'"
            onmouseout="this.style.background='white'">
            <div style="width:32px; height:32px; border-radius:9px; background:#dbeafe; color:#1e40af; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div>
                <div style="font-size:13px; font-weight:600; color:#0f172a;"><?php echo htmlspecialchars($job['title']); ?></div>
                <div style="font-size:11px; color:#94a3b8;"><?php echo htmlspecialchars($job['company_name']); ?> · <?php echo htmlspecialchars($job['city']); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>