<?php
ob_start();

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$isPrint = isset($_GET['print']) && $_GET['print'] == '1';

$userId = null;

if ($_SESSION['user']['role'] === 'candidate') {
    $userId = $_SESSION['user']['id'];

    if (!isCandidateProfileComplete($userId)) {
        header('Location: edit_profile.php');
        exit;
    }
} elseif ($_SESSION['user']['role'] === 'company') {
    $userId = intval($_GET['id'] ?? 0);

    if (!$userId) {
        header('Location: company_notifications.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

global $pdo;

try {
    $profile = getCandidateProfile($userId);
    if (!$profile) {
        throw new Exception("الملف الشخصي غير موجود");
    }
} catch (Exception $e) {
    $profile = [];
}

$email = '';
try {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $userData['email'] ?? '';
} catch (PDOException $e) {
    $email = 'N/A';
}

$fullName       = htmlspecialchars($profile['full_name'] ?? 'Candidate', ENT_QUOTES, 'UTF-8');
$jobTitle       = htmlspecialchars($profile['job_title'] ?? '', ENT_QUOTES, 'UTF-8');
$phone          = htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$city           = htmlspecialchars($profile['city'] ?? '', ENT_QUOTES, 'UTF-8');
$summary        = htmlspecialchars($profile['summary'] ?? '', ENT_QUOTES, 'UTF-8');
$availability   = !empty($profile['availability'])
    ? htmlspecialchars($profile['availability'], ENT_QUOTES, 'UTF-8')
    : 'Not specified';
$experienceLvl  = !empty($profile['experience_level'])
    ? htmlspecialchars($profile['experience_level'], ENT_QUOTES, 'UTF-8')
    : 'Not specified';

$profileImage = '';
if (!empty($profile['image_path'])) {
    $imagePath = __DIR__ . '/' . $profile['image_path'];
    if (file_exists($imagePath)) {
        try {
            $imageData = base64_encode(file_get_contents($imagePath));
            $profileImage = 'data:image/jpeg;base64,' . $imageData;
        } catch (Exception $e) {
        }
    }
}

$skillsArray = [];
try {
    $stmt = $pdo->prepare("SELECT skill_name FROM candidate_skills WHERE user_id = ? LIMIT 10");
    $stmt->execute([$userId]);
    $skillsArray = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

$languagesArray = [];
try {
    $stmt = $pdo->prepare("SELECT language_name, level FROM candidate_languages WHERE user_id = ? ORDER BY language_name");
    $stmt->execute([$userId]);
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($languages as $lang) {
        $languagesArray[] = [
            'name'  => $lang['language_name'],
            'level' => intval($lang['level'])
        ];
    }
} catch (PDOException $e) {
}

$experienceArray = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            job_title, 
            company_name, 
            start_date, 
            end_date, 
            is_current, 
            description 
        FROM candidate_experiences 
        WHERE user_id = ? 
        ORDER BY start_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $experienceArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$educationArray = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            degree, 
            school, 
            start_date, 
            end_date, 
            is_current, 
            description 
        FROM candidate_educations 
        WHERE user_id = ? 
        ORDER BY start_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $educationArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}


$projectsArray = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            title, 
            description, 
            demo_link, 
            github_link 
        FROM candidate_projects 
        WHERE user_id = ? 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $projectsArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.5;
            background: #ffffff;
        }

        .cv-wrapper {
            width: 100%;
            display: table;
            min-height: 100vh;
        }

        /* =====================
   SIDEBAR
===================== */

        .sidebar {
            display: table-cell;
            width: 33%;
            background: #1e1b4b;
            padding: 36px 24px;
            vertical-align: top;
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 24px;
            border-bottom: 1px solid #312e81;
        }

        .profile-pic {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #6366f1;
            object-fit: cover;
            margin-bottom: 14px;
        }

        .profile-placeholder {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #312e81;
            border: 3px solid #6366f1;
            margin: 0 auto 14px;
        }

        .name {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .job-title {
            font-size: 9px;
            color: #a5b4fc;
            font-weight: 600;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        /* =====================
   SECTION TITLES (Sidebar)
===================== */

        .section {
            margin-bottom: 26px;
        }

        .section-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #a5b4fc;
            letter-spacing: 2px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #312e81;
        }

        /* =====================
   CONTACT
===================== */

        .contact-item {
            margin-bottom: 12px;
        }

        .contact-label {
            font-size: 8px;
            color: #7c3aed;
            margin-bottom: 2px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .contact-value {
            font-size: 10px;
            color: #e0e7ff;
            font-weight: 500;
        }

        /* =====================
   SKILLS
===================== */

        .skill-tag {
            display: inline-block;
            background: #312e81;
            color: #c7d2fe;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            margin-right: 5px;
            margin-bottom: 6px;
            border: 1px solid #4338ca;
            letter-spacing: 0.5px;
        }

        /* =====================
   LANGUAGES
===================== */

        .language {
            margin-bottom: 11px;
        }

        .language-name {
            font-size: 10px;
            color: #e0e7ff;
            margin-bottom: 3px;
            font-weight: 500;
            display: inline-block;
        }

        .language-level {
            font-size: 9px;
            color: #818cf8;
            float: right;
        }

        .language-bar {
            width: 100%;
            height: 3px;
            background: #312e81;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .language-fill {
            height: 100%;
            background: #6366f1;
            border-radius: 2px;
        }

        /* =====================
   OTHER INFO
===================== */

        .other-item {
            margin-bottom: 12px;
        }

        .other-label {
            font-size: 8px;
            color: #818cf8;
            margin-bottom: 2px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .other-value {
            font-size: 10px;
            font-weight: 600;
            color: #e0e7ff;
        }

        /* =====================
   MAIN CONTENT
===================== */

        .main {
            display: table-cell;
            width: 67%;
            padding: 38px 34px;
            vertical-align: top;
            background: #ffffff;
        }

        /* =====================
   SECTION TITLES (Main)
===================== */

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 16px;
            padding-bottom: 10px;
            position: relative;
        }

        .main .section .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 32px;
            height: 2px;
            background: #6366f1;
        }

        /* =====================
   ABOUT
===================== */

        .about {
            font-size: 10.5px;
            color: #475569;
            line-height: 1.8;
        }

        /* =====================
   TIMELINE
===================== */

        .item {
            margin-bottom: 20px;
            padding-left: 16px;
            position: relative;
        }

        .item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 4px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            border: 2px solid #6366f1;
            background: white;
        }

        .item::after {
            content: '';
            position: absolute;
            left: 3px;
            top: 14px;
            width: 1px;
            height: calc(100% - 8px);
            background: #f1f5f9;
        }

        .item:last-child::after {
            display: none;
        }

        .item-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .item-subtitle {
            font-size: 10px;
            color: #6366f1;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .item-date {
            font-size: 9px;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: 500;
            background: #f1f5f9;
            display: inline-block;
            padding: 2px 7px;
            border-radius: 99px;
            letter-spacing: 0.5px;
        }

        .item-desc {
            font-size: 10px;
            color: #64748b;
            line-height: 1.7;
        }

        /* =====================
   LINKS
===================== */

        .link {
            font-size: 9px;
            color: #6366f1;
            margin-top: 4px;
            font-weight: 600;
            word-break: break-word;
        }

        /* =====================
   DIVIDER
===================== */

        .section-divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 4px 0 24px;
        }

        .section-title-sidebar {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #ffffff;
            letter-spacing: 2px;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #4338ca;
            background: transparent;
        }
    </style>
</head>

<body>

    <div class="cv-wrapper">

        <!-- SIDEBAR -->
        <div class="sidebar">

            <div class="profile">
                <?php if ($profileImage): ?>
                    <img src="<?php echo $profileImage; ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <div class="profile-placeholder"></div>
                <?php endif; ?>

                <div class="name"><?php echo $fullName; ?></div>
                <div class="job-title"><?php echo $jobTitle; ?></div>
            </div>

            <!-- CONTACT -->
            <div class="section">
                <div class="section-title-sidebar">Contact</div>

                <div class="contact-item">
                    <div class="contact-label">EMAIL</div>
                    <div class="contact-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="contact-item">
                    <div class="contact-label">PHONE</div>
                    <div class="contact-value"><?php echo $phone; ?></div>
                </div>

                <div class="contact-item">
                    <div class="contact-label">LOCATION</div>
                    <div class="contact-value"><?php echo $city; ?></div>
                </div>
            </div>

            <!-- SKILLS -->
            <?php if (!empty($skillsArray)): ?>
                <div class="section">
                    <div class="section-title-sidebar">Skills</div>
                    <?php foreach ($skillsArray as $skill): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- LANGUAGES -->
            <?php if (!empty($languagesArray)): ?>
                <div class="section">
                    <div class="section-title-sidebar">Languages</div>
                    <?php foreach ($languagesArray as $lang): ?>
                        <div class="language">
                            <div class="language-name"><?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="language-level"><?php echo $lang['level']; ?>%</div>
                            <div style="clear: both;"></div>
                            <div class="language-bar">
                                <div class="language-fill" style="width: <?php echo $lang['level']; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- OTHER -->
            <div class="section">
                <div class="section-title-sidebar">Other Info</div>

                <div class="other-item">
                    <div class="other-label">AVAILABILITY</div>
                    <div class="other-value"><?php echo $availability; ?></div>
                </div>

                <div class="other-item">
                    <div class="other-label">EXPERIENCE LEVEL</div>
                    <div class="other-value"><?php echo $experienceLvl; ?></div>
                </div>
            </div>

        </div>

        <!-- MAIN CONTENT -->
        <div class="main">

            <!-- ABOUT -->
            <?php if (!empty($summary)): ?>
                <div class="section">
                    <div class="section-title">About Me</div>
                    <div class="about"><?php echo $summary; ?></div>
                </div>
            <?php endif; ?>

            <!-- EXPERIENCE -->
            <?php if (!empty($experienceArray)): ?>
                <div class="section">
                    <div class="section-title">Experience</div>
                    <?php foreach ($experienceArray as $exp): ?>
                        <div class="item">
                            <div class="item-title"><?php echo htmlspecialchars($exp['job_title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="item-subtitle"><?php echo htmlspecialchars($exp['company_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="item-date">
                                <?php echo date('M Y', strtotime($exp['start_date'])); ?>
                                -
                                <?php echo $exp['is_current'] ? 'Present' : date('M Y', strtotime($exp['end_date'])); ?>
                            </div>
                            <div class="item-desc"><?php echo htmlspecialchars($exp['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- EDUCATION -->
            <?php if (!empty($educationArray)): ?>
                <div class="section">
                    <div class="section-title">Education</div>
                    <?php foreach ($educationArray as $edu): ?>
                        <div class="item">
                            <div class="item-title"><?php echo htmlspecialchars($edu['degree'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="item-subtitle"><?php echo htmlspecialchars($edu['school'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="item-date">
                                <?php echo date('Y', strtotime($edu['start_date'])); ?>
                                -
                                <?php echo $edu['is_current'] ? 'Present' : date('Y', strtotime($edu['end_date'])); ?>
                            </div>
                            <div class="item-desc"><?php echo htmlspecialchars($edu['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- PROJECTS -->
            <?php if (!empty($projectsArray)): ?>
                <div class="section">
                    <div class="section-title">Projects</div>
                    <?php foreach ($projectsArray as $project): ?>
                        <div class="item">
                            <div class="item-title"><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="item-desc"><?php echo htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8'); ?></div>

                            <?php if (!empty($project['demo_link'])): ?>
                                <div class="link">Demo: <?php echo htmlspecialchars($project['demo_link'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($project['github_link'])): ?>
                                <div class="link">GitHub: <?php echo htmlspecialchars($project['github_link'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>

</html>
<?php

$html = ob_get_clean();

try {
    $options = new Options();
    $options->set('defaultFont', 'serif');
    $options->set('isPhpEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('isFontSubsettingEnabled', false);
    $options->setChroot(__DIR__);
    $options->setTempDir(sys_get_temp_dir());

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $fileName = 'cv_' . $userId . '_' . date('Y-m-d_H-i-s') . '.pdf';

    $dompdf->stream($fileName, [
        'Attachment' => $isPrint ? false : true
    ]);
} catch (Exception $e) {
    echo "خطأ في إنشاء الـ PDF: " . htmlspecialchars($e->getMessage());
}

exit;
?>