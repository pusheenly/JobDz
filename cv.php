<?php
require_once 'functions.php';
requireLogin();
if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}
if (!isCandidateProfileComplete($_SESSION['user']['id'])) {
    header('Location: edit_profile.php');
    exit;
}
$profile = getCandidateProfile($_SESSION['user']['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/tailwind-head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
<header>
    <div class="container">
        <?php include 'includes/navbar.php'; ?>
    </div>
</header>
<main class="container profile-page" style="padding: 60px 0;">
    <div class="card profile-card" style="max-width: 760px; margin: 0 auto;">
        <div class="section-title" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <h3>Resume</h3>
            <a class="btn secondary" href="cv_download.php">Download Resume PDF</a>
            <button class="btn secondary" onclick="window.print()">Print Preview</button>
        </div>
        <div style="display:grid; grid-template-columns: 220px 1fr; gap:24px; align-items:start;">
            <div style="text-align:center;">
                <img class="upload-preview" src="<?php echo e($profile['image_path'] ?: 'https://via.placeholder.com/180?text=CV'); ?>" alt="Profile Image">
                <p class="small-note" style="margin-top:12px;"><?php echo e($profile['full_name'] ?: 'Candidate'); ?></p>
            </div>
            <div>
                <h2><?php echo e($profile['full_name'] ?? 'Full Name'); ?></h2>
                <p class="small-note"><?php echo e($profile['job_title'] ?? 'Professional Title'); ?></p>
                <p class="small-note"><?php echo e($_SESSION['user']['email']); ?></p>
                <p class="small-note"><?php echo e($profile['phone'] ?? 'Phone not specified'); ?>  <?php echo e($profile['city'] ?? 'City not specified'); ?></p>

                <div style="margin-top: 24px;">
                    <h4>Professional Summary</h4>
                    <p><?php echo nl2br(e($profile['summary'] ?? 'Add a strong career summary here.')); ?></p>
                </div>

                <div style="margin-top: 20px;">
                    <h4>Experience</h4>
                    <p><?php echo nl2br(e($profile['experience'] ?? 'Add your work experience here.')); ?></p>
                </div>

                <div style="margin-top: 20px;">
                    <h4>Skills</h4>
                    <p><?php echo nl2br(e($profile['skills'] ?? 'Add the skills you possess.')); ?></p>
                </div>

                <div style="margin-top: 20px;">
                    <h4>Projects & Achievements</h4>
                    <p><?php echo nl2br(e($profile['projects'] ?? 'Add some important projects or achievements.')); ?></p>
                </div>

                <div style="margin-top: 20px;">
                    <h4>Languages</h4>
                    <p><?php echo nl2br(e($profile['languages'] ?? 'Add the languages you speak.')); ?></p>
                </div>

                <div style="margin-top: 20px;">
                    <h4>Interests</h4>
                    <p><?php echo nl2br(e($profile['interests'] ?? 'Add interests or career goals.')); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
