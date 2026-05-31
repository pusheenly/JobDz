<?php
require_once 'config.php';
require_once 'functions.php';

$current_page     = 'profile';
$activeProfileTab = 'profile';

requireLogin();

$viewCandidateId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($viewCandidateId && $_SESSION['user']['role'] === 'company') {
    $userId = $viewCandidateId;
} else {
    if ($_SESSION['user']['role'] !== 'candidate') {
        header('Location: index.php');
        exit;
    }
    $userId = $_SESSION['user']['id'];
}

$profile = getCandidateProfile($userId);

if (!$profile) {
    header('Location: edit_profile.php');
    exit;
}

$experiences = getCandidateExperiences($userId);
$educations  = getCandidateEducations($userId);
$skills      = getCandidateSkills($userId);
$projects    = getCandidateProjects($userId);
$languages   = getCandidateLanguages($userId);
$interests   = getCandidateInterests($userId);
$socialLinks = getCandidateSocialLinks($userId);

if ($_SESSION['user']['role'] === 'candidate') {
    $isProfileIncomplete = !isCandidateProfileComplete($userId);
    if ($isProfileIncomplete) {
        header('Location: edit_profile.php');
        exit;
    }
}

$strength     = calculateProfileStrength($profile);
$strengthInfo = getProfileStrengthLevel($strength);

$initials  = 'U';
$nameParts = array_filter(explode(' ', trim($profile['full_name'] ?? '')));

if (count($nameParts) >= 2) {
    $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
} elseif (count($nameParts) === 1) {
    $initials = strtoupper(mb_substr($nameParts[0], 0, 1));
}

$isOwner =
    isset($_SESSION['user']['id']) &&
    $_SESSION['user']['role'] === 'candidate' &&
    $userId == $_SESSION['user']['id'];

$isCompany = $_SESSION['user']['role'] === 'company';
$candidateApplications = [];
$reviewMessage         = null;

if ($isCompany && $viewCandidateId) {
    $companyId = $_SESSION['user']['id'];
    $jobId     = isset($_GET['job_id']) ? (int) $_GET['job_id'] : null;

    if ($jobId) {
        $stmt = $pdo->prepare("
            SELECT applications.*, jobs.title AS job_title
            FROM applications
            JOIN jobs ON applications.job_id = jobs.id
            WHERE applications.user_id = ?
              AND applications.job_id  = ?
              AND jobs.user_id         = ?
            LIMIT 1
        ");
        $stmt->execute([$viewCandidateId, $jobId, $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $candidateApplications = [$row];
    } else {
        $stmt = $pdo->prepare("
            SELECT applications.*, jobs.title AS job_title
            FROM applications
            JOIN jobs ON applications.job_id = jobs.id
            WHERE applications.user_id = ?
              AND jobs.user_id         = ?
            ORDER BY applications.created_at DESC
        ");
        $stmt->execute([$viewCandidateId, $companyId]);
        $candidateApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['application_id'])) {
        $postAppId = (int) $_POST['application_id'];
        $action    = $_POST['action'] ?? '';

        if (in_array($action, ['accept', 'reject'])) {
            $newStatus     = $action === 'accept' ? 'accepted' : 'rejected';
            $interviewDate = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
            $interviewTime = !empty($_POST['interview_time']) ? $_POST['interview_time'] : null;
            $message       = !empty($_POST['company_message']) ? trim($_POST['company_message']) : null;

            $pdo->prepare("
                UPDATE applications
                SET status          = ?,
                    interview_date  = ?,
                    interview_time  = ?,
                    company_message = ?
                WHERE id = ?
            ")->execute([$newStatus, $interviewDate, $interviewTime, $message, $postAppId]);

            if ($jobId) {
                $stmt->execute([$viewCandidateId, $jobId, $companyId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $candidateApplications = $row ? [$row] : [];
            } else {
                $stmt->execute([$viewCandidateId, $companyId]);
                $candidateApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $reviewMessage = $action === 'accept'
                ? 'Candidate has been accepted successfully.'
                : 'Candidate has been rejected.';
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
    <title>My Profile | JobDZ</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/variables.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        .profile-page * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .profile-page,
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

        .profile-banner {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 24px;
            padding: 1.4rem 1.5rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            position: relative;
            overflow: hidden;
        }

        .profile-banner::before {
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

        .banner-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .prof-avatar-lg {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
            border: 1px solid #e8eef6;
            overflow: hidden;
        }

        .prof-avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 17px;
        }

        .banner-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .banner-sub {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .badge-green {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            white-space: nowrap;
        }

        .banner-meta {
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .banner-meta span {
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .banner-meta i {
            font-size: 13px;
            color: #94a3b8;
        }

        .banner-right {
            display: flex;
            gap: 8px;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .btn-pri {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
            white-space: nowrap;
        }

        .btn-pri:hover {
            background: #4f46e5;
        }

        .btn-sec {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            color: #334155;
            border: 1px solid #e8eef6;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
            white-space: nowrap;
        }

        .btn-sec:hover {
            background: #f8fafc;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0f172a;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
            white-space: nowrap;
        }

        .btn-print:hover {
            background: #1e293b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 1.25rem;
        }

        .sc {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: .9rem 1rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
        }

        .sc-label {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sc-label i {
            font-size: 13px;
        }

        .sc-val {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        .sc-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 99px;
            margin-top: 6px;
            display: inline-block;
        }

        .b-neu {
            background: #EEEDFE;
            color: #3C3489;
        }

        .b-up {
            background: #E1F5EE;
            color: #085041;
        }

        .b-warn {
            background: #FAEEDA;
            color: #633806;
        }

        .b-pink {
            background: #FBEAF0;
            color: #72243E;
        }

        .card {
            background: white;
            border: 1px solid #ececf3;
            border-radius: 24px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .03);
            margin-bottom: 14px;
        }

        .ch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .9rem;
        }

        .ct {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ct i {
            font-size: 16px;
            color: #6366f1;
        }

        .cl {
            font-size: 11px;
            color: #6366f1;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
            cursor: pointer;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .exp-row {
            display: flex;
            gap: 12px;
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            align-items: flex-start;
        }

        .exp-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .exp-ico {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: #ede9fe;
            color: #5b21b6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .exp-ico.edu {
            background: #E1F5EE;
            color: #0F6E56;
        }

        .exp-body {
            flex: 1;
            min-width: 0;
        }

        .exp-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .exp-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
            font-weight: 500;
        }

        .exp-desc {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.5;
        }

        .exp-date {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 99px;
            background: #f1f5f9;
            color: #475569;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .summary-block {
            background: #f5f3ff;
            border-left: 3px solid #6366f1;
            border-radius: 0 12px 12px 0;
            padding: 12px 16px;
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.7;
        }

        .pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 4px 0;
        }

        .pill {
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 500;
            background: #ede9fe;
            color: #3C3489;
            border: none;
        }

        .lang-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
        }

        .lang-name {
            font-size: 12px;
            font-weight: 500;
            color: #111827;
            width: 70px;
            flex-shrink: 0;
        }

        .lang-bar {
            flex: 1;
            height: 5px;
            background: #f1f5f9;
            border-radius: 99px;
            overflow: hidden;
        }

        .lang-fill {
            height: 100%;
            background: #6366f1;
            border-radius: 99px;
        }

        .lang-pct {
            font-size: 11px;
            color: #94a3b8;
            width: 34px;
            text-align: right;
            flex-shrink: 0;
        }

        .proj-row {
            display: flex;
            gap: 12px;
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .proj-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .proj-ico {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: #E1F5EE;
            color: #0F6E56;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .proj-body {
            flex: 1;
            min-width: 0;
        }

        .proj-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .proj-desc {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .proj-link {
            font-size: 11px;
            font-weight: 600;
            color: #6366f1;
            text-decoration: none;
            margin-top: 5px;
            display: inline-block;
        }

        .social-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
            border-radius: 12px;
            transition: background .15s;
        }

        .social-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .social-row:hover {
            background: #f8fafc;
        }

        .social-ico {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .social-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
        }

        .social-url {
            font-size: 11px;
            color: #94a3b8;
        }

        .resume-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            flex-wrap: wrap;
        }

        .resume-ico {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            background: #FBEAF0;
            color: #72243E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .resume-info {
            flex: 1;
            min-width: 120px;
        }

        .resume-name {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .resume-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .dl-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 7px 14px;
            border-radius: 12px;
            background: #6366f1;
            color: white;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            white-space: nowrap;
        }

        .dl-btn:hover {
            background: #4f46e5;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 7px 14px;
            border-radius: 12px;
            background: #0f172a;
            color: white;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            transition: background .2s;
            white-space: nowrap;
        }

        .print-btn:hover {
            background: #1e293b;
        }

        .empty-state {
            text-align: center;
            padding: 1.5rem 1rem;
            background: #fafafe;
            border: 1.5px dashed #e0e7ff;
            border-radius: 16px;
            font-size: 12.5px;
            color: #94a3b8;
            font-weight: 500;
        }

        .empty-state i {
            font-size: 24px;
            color: #c7d2fe;
            margin-bottom: 8px;
            display: block;
        }


        @media (max-width: 700px) {

            .profile-banner {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
                padding: 1.1rem 1rem;
            }

            .banner-left {
                gap: 12px;
            }

            .banner-title {
                font-size: 16px;
            }

            .banner-right {
                width: 100%;
                gap: 8px;
            }

            .banner-right form,
            .banner-right form button {
                width: 100%;
            }

            .btn-pri,
            .btn-sec,
            .btn-print {
                flex: 1;
                justify-content: center;
                padding: 10px 12px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .two-col {
                grid-template-columns: 1fr;
            }

            .resume-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .resume-row>div:last-child {
                width: 100%;
                display: flex;
                gap: 8px;
            }

            .dl-btn,
            .print-btn {
                flex: 1;
                justify-content: center;
            }

            .card {
                padding: .9rem 1rem;
                border-radius: 18px;
            }

            main {
                padding-left: 12px !important;
                padding-right: 12px !important;
                padding-top: 16px !important;
                padding-bottom: 16px !important;
            }
        }

        /* ── REVIEW CARD ── */
        .review-app-card {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 20px;
            padding: 18px 20px;
            margin-top: 14px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .03);
        }

        .review-app-card.is-accepted {
            border-left: 3px solid #059669;
            border-radius: 0 20px 20px 0;
        }

        .review-app-card.is-rejected {
            border-left: 3px solid #DC2626;
            border-radius: 0 20px 20px 0;
        }

        .review-app-card.is-pending {
            border-left: 3px solid #D97706;
            border-radius: 0 20px 20px 0;
        }

        .pill-purple {
            background: #ede9fe;
            color: #3C3489;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-green {
            background: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-amber {
            background: #fffbeb;
            color: #92400e;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-red {
            background: #fef2f2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-gray {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 99px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .ra-form-section {
            background: #f5f3ff;
            border: 1px solid #e9d5ff;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .ra-form-title {
            font-size: 13px;
            font-weight: 600;
            color: #6366f1;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ra-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .ra-form-group {
            display: flex;
            flex-direction: column;
        }

        .ra-form-group label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .ra-form-group input,
        .ra-form-group textarea {
            background: white;
            border: 1px solid #e8eef6;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            color: #111827;
        }

        .ra-form-group input:focus,
        .ra-form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .1);
        }

        .ra-textarea {
            grid-column: 1/-1;
        }

        .ra-textarea textarea {
            min-height: 90px;
            resize: vertical;
        }

        .ra-accepted-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .ra-accepted-title {
            font-size: 13px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ra-accepted-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .ra-accepted-cell {
            background: white;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .ra-accepted-cell label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .ra-accepted-cell p {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
        }

        .ra-company-msg {
            background: #dcfce7;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #14532d;
            line-height: 1.65;
        }

        .ra-company-msg strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .ra-rejected-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
            padding: 14px 16px;
            margin-top: 12px;
        }

        .ra-rejected-title {
            font-size: 13px;
            font-weight: 600;
            color: #991b1b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ra-rejected-msg {
            background: #fecaca;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #7f1d1d;
            line-height: 1.65;
        }

        .ra-rejected-msg strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .ra-success-msg {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 13px 16px;
            color: #065f46;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .ra-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 12px 0;
        }

        .btn-success {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }

        .btn-success:hover {
            background: #d1fae5;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            border-radius: 12px;
            padding: 9px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background .2s;
        }

        .btn-danger:hover {
            background: #FEE2E2;
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

        <!-- BANNER -->
        <div class="profile-banner">
            <div class="banner-left">
                <div class="prof-avatar-lg">
                    <?php if (!empty($profile['image_path'])): ?>
                        <img src="<?php echo e($profile['image_path']); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo e($initials); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="banner-title"><?php echo e($profile['full_name'] ?? 'User'); ?></div>
                    <div class="banner-sub">
                        <span class="badge-green">
                            <i class="ti ti-circle-check-filled"></i> Open to work
                        </span>
                        <?php if (!empty($profile['job_title'])): ?>
                            · <?php echo e($profile['job_title']); ?>
                        <?php endif; ?>
                        <?php if (!empty($profile['city'])): ?>
                            · <i class="ti ti-map-pin" style="font-size:11px"></i> <?php echo e($profile['city']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="banner-meta">
                        <span>
                            <i class="ti ti-mail"></i>
                            <?php echo e($_SESSION['user']['email']); ?>
                        </span>
                        <?php if (!empty($profile['phone'])): ?>
                            <span>
                                <i class="ti ti-phone"></i>
                                <?php echo e($profile['phone']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="banner-right">
                <form action="cv_download.php" method="GET" target="_blank" style="margin:0;">
                    <input type="hidden" name="id" value="<?php echo $userId; ?>">
                    <button type="submit" class="btn-sec">
                        <i class="ti ti-download"></i> Download CV
                    </button>
                </form>

                <?php if ($isCompany): ?>
                    <button class="btn-print" onclick="printCV(<?php echo $userId; ?>)">
                        <i class="ti ti-printer"></i> Print CV
                    </button>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                    <a href="edit_profile.php" class="btn-pri">
                        <i class="ti ti-edit"></i> Edit Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- STATS -->
        <div class="card">
            <div class="ch">
                <span class="ct">
                    <i class="ti ti-user-star"></i> Professional Details
                </span>
                <?php if ($isOwner): ?>
                    <a href="edit_profile.php?section=professional" class="cl">
                        <i class="ti ti-edit"></i> Edit
                    </a>
                <?php endif; ?>
            </div>
            <div class="stats-grid">
                <div class="sc">
                    <div class="sc-label"><i class="ti ti-category"></i> Category</div>
                    <div class="sc-val"><?php echo e($profile['category'] ?? 'Not set'); ?></div>
                    <div class="sc-badge b-neu">Field</div>
                </div>
                <div class="sc">
                    <div class="sc-label"><i class="ti ti-star"></i> Specialty</div>
                    <div class="sc-val"><?php echo e($profile['specialty'] ?? 'Not set'); ?></div>
                    <div class="sc-badge b-up">Focus</div>
                </div>
                <div class="sc">
                    <div class="sc-label"><i class="ti ti-briefcase"></i> Experience</div>
                    <div class="sc-val"><?php echo e($profile['experience_level'] ?? '—'); ?></div>
                    <div class="sc-badge b-warn">Level</div>
                </div>
                <div class="sc">
                    <div class="sc-label"><i class="ti ti-calendar-check"></i> Availability</div>
                    <div class="sc-val"><?php echo e($profile['availability'] ?? '—'); ?></div>
                    <div class="sc-badge b-pink">Status</div>
                </div>
            </div>
        </div>

        <!-- SUMMARY -->
        <div class="card">
            <div class="ch">
                <span class="ct"><i class="ti ti-file-text"></i> Professional Summary</span>
                <?php if ($isOwner): ?>
                    <a href="edit_profile.php?section=summary" class="cl">
                        <i class="ti ti-edit"></i> Edit
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($profile['summary'])): ?>
                <div class="summary-block">
                    <?php echo nl2br(e($profile['summary'])); ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="ti ti-file-text"></i>
                    No professional summary added yet
                </div>
            <?php endif; ?>
        </div>

        <!-- EXPERIENCE + EDUCATION -->
        <div class="two-col">
            <div class="card" style="margin-bottom:0;">
                <div class="ch">
                    <span class="ct"><i class="ti ti-briefcase"></i> Experience</span>
                    <?php if ($isOwner): ?>
                        <a href="edit_profile.php?section=experience" class="cl">
                            <i class="ti ti-plus"></i> Add
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($experiences)): ?>
                    <?php foreach ($experiences as $exp): ?>
                        <div class="exp-row">
                            <div class="exp-ico"><i class="ti ti-code"></i></div>
                            <div class="exp-body">
                                <div class="exp-title"><?php echo e($exp['job_title']); ?></div>
                                <div class="exp-sub"><?php echo e($exp['company_name']); ?></div>
                                <?php if (!empty($exp['description'])): ?>
                                    <div class="exp-desc"><?php echo nl2br(e($exp['description'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="exp-date">
                                <?php echo e($exp['start_date']); ?> –
                                <?php echo !empty($exp['end_date']) ? e($exp['end_date']) : 'Present'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-briefcase"></i>
                        No experience added yet
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="ch">
                    <span class="ct"><i class="ti ti-school"></i> Education</span>
                    <?php if ($isOwner): ?>
                        <a href="edit_profile.php?section=education" class="cl">
                            <i class="ti ti-plus"></i> Add
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($educations)): ?>
                    <?php foreach ($educations as $edu): ?>
                        <div class="exp-row">
                            <div class="exp-ico edu"><i class="ti ti-certificate"></i></div>
                            <div class="exp-body">
                                <div class="exp-title"><?php echo e($edu['degree'] ?? 'No degree'); ?></div>
                                <div class="exp-sub"><?php echo e($edu['school'] ?? 'No school'); ?></div>
                                <?php if (!empty($edu['description'])): ?>
                                    <div class="exp-desc"><?php echo nl2br(e($edu['description'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="exp-date">
                                <?php echo e($edu['start_date'] ?? ''); ?> –
                                <?php echo !empty($edu['end_date']) ? e($edu['end_date']) : 'Present'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-school"></i>
                        No education added yet
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SKILLS + LANGUAGES -->
        <div class="two-col">
            <div class="card" style="margin-bottom:0;">
                <div class="ch">
                    <span class="ct"><i class="ti ti-code"></i> Skills</span>
                    <?php if ($isOwner): ?>
                        <a href="edit_profile.php?section=skills" class="cl">
                            <i class="ti ti-plus"></i> Add
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($skills)): ?>
                    <div class="pills">
                        <?php foreach ($skills as $skill): ?>
                            <span class="pill"><?php echo e($skill['skill_name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-code"></i>
                        No skills added yet
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="ch">
                    <span class="ct"><i class="ti ti-language"></i> Languages</span>
                    <?php if ($isOwner): ?>
                        <a href="edit_profile.php?section=languages" class="cl">
                            <i class="ti ti-plus"></i> Add
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($languages)): ?>
                    <?php foreach ($languages as $lang): ?>
                        <div class="lang-row">
                            <span class="lang-name"><?php echo e($lang['language_name']); ?></span>
                            <div class="lang-bar">
                                <div class="lang-fill" style="width:<?php echo (int)$lang['level']; ?>%"></div>
                            </div>
                            <span class="lang-pct"><?php echo (int)$lang['level']; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-language"></i>
                        No languages added yet
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PROJECTS + INTERESTS & SOCIAL -->
        <div class="two-col">
            <div class="card" style="margin-bottom:0;">
                <div class="ch">
                    <span class="ct"><i class="ti ti-folder"></i> Projects</span>
                    <?php if ($isOwner): ?>
                        <a href="edit_profile.php?section=projects" class="cl">
                            <i class="ti ti-plus"></i> Add
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="proj-row">
                            <div class="proj-ico"><i class="ti ti-world"></i></div>
                            <div class="proj-body">
                                <div class="proj-title"><?php echo e($project['title']); ?></div>
                                <div class="proj-desc"><?php echo e($project['description']); ?></div>
                                <?php if (!empty($project['project_link'])): ?>
                                    <a href="<?php echo e($project['project_link']); ?>" class="proj-link" target="_blank">
                                        <i class="ti ti-external-link" style="font-size:11px"></i> View Project
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="ti ti-folder"></i>
                        No projects added yet
                    </div>
                <?php endif; ?>
            </div>

            <div style="display:flex; flex-direction:column; gap:14px;">

                <div class="card" style="margin-bottom:0;">
                    <div class="ch">
                        <span class="ct"><i class="ti ti-heart"></i> Interests</span>
                        <?php if ($isOwner): ?>
                            <a href="edit_profile.php?section=interests" class="cl">
                                <i class="ti ti-plus"></i> Add
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($interests)): ?>
                        <div class="pills">
                            <?php foreach ($interests as $interest): ?>
                                <span class="pill"><?php echo e($interest['interest_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-heart"></i>
                            No interests added yet
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="margin-bottom:0;">
                    <div class="ch">
                        <span class="ct"><i class="ti ti-share"></i> Social Links</span>
                        <?php if ($isOwner): ?>
                            <a href="edit_profile.php?section=social_links" class="cl">
                                <i class="ti ti-plus"></i> Add
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($socialLinks)): ?>
                        <?php foreach ($socialLinks as $link): ?>
                            <a href="<?php echo e($link['url']); ?>" class="social-row" target="_blank">
                                <div class="social-ico"><i class="ti ti-link"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="social-name"><?php echo e($link['platform']); ?></div>
                                    <div class="social-url"><?php echo e($link['url']); ?></div>
                                </div>
                                <i class="ti ti-external-link" style="font-size:13px;color:#94a3b8;"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-share"></i>
                            No social links added yet
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>


        <!-- ── REVIEW APPLICATION CARDS ── -->
        <?php if (!empty($candidateApplications)): ?>

            <?php if (!empty($reviewMessage)): ?>
                <div class="ra-success-msg" style="margin-top:14px;">
                    <i class="ti ti-circle-check" style="font-size:15px;"></i>
                    <?php echo e($reviewMessage); ?>
                </div>
            <?php endif; ?>

            <?php foreach ($candidateApplications as $candidateApplication): ?>
                <?php
                $appStatus = $candidateApplication['status'] ?? 'pending';
                if ($appStatus === 'accepted') {
                    $statusPill = '<span class="pill-green"><i class="ti ti-check" style="font-size:10px;"></i> Accepted</span>';
                    $cardClass  = 'is-accepted';
                } elseif ($appStatus === 'rejected') {
                    $statusPill = '<span class="pill-red"><i class="ti ti-x" style="font-size:10px;"></i> Rejected</span>';
                    $cardClass  = 'is-rejected';
                } else {
                    $statusPill = '<span class="pill-amber"><i class="ti ti-clock" style="font-size:10px;"></i> Pending</span>';
                    $cardClass  = 'is-pending';
                }
                ?>
                <article class="review-app-card <?php echo $cardClass; ?>">

                    <!-- Top row -->
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                            <span class="pill-purple">
                                <i class="ti ti-user" style="font-size:10px;"></i>
                                <?php echo e($profile['full_name'] ?? 'Candidate'); ?>
                            </span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                            <?php echo $statusPill; ?>
                        </div>
                    </div>

                    <!-- Job title -->
                    <?php if (!empty($candidateApplication['job_title'])): ?>
                        <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:10px;">
                            <i class="ti ti-briefcase" style="font-size:13px;color:#6366f1;margin-right:4px;"></i>
                            <?php echo e($candidateApplication['job_title']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- PENDING: form -->
                    <?php if ($appStatus === 'pending'): ?>
                        <form method="POST" class="ra-form-section">
                            <input type="hidden" name="application_id" value="<?php echo $candidateApplication['id']; ?>">
                            <p class="ra-form-title">
                                <i class="ti ti-edit" style="font-size:13px;"></i>
                                Review this application
                            </p>
                            <div class="ra-form-grid">
                                <div class="ra-form-group">
                                    <label>Interview Date</label>
                                    <input type="date" name="interview_date">
                                </div>
                                <div class="ra-form-group">
                                    <label>Interview Time</label>
                                    <input type="time" name="interview_time">
                                </div>
                            </div>
                            <div class="ra-form-group ra-textarea">
                                <label>Message to Candidate</label>
                                <textarea name="company_message" placeholder="Send a message with your decision..."></textarea>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                                <button type="submit" name="action" value="accept" class="btn-success">
                                    <i class="ti ti-check"></i> Accept
                                </button>
                                <button type="submit" name="action" value="reject" class="btn-danger">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                            </div>
                        </form>

                        <!-- ACCEPTED -->
                    <?php elseif ($appStatus === 'accepted'): ?>
                        <div class="ra-accepted-box">
                            <p class="ra-accepted-title">
                                <i class="ti ti-confetti" style="color:#059669;font-size:15px;"></i>
                                Candidate accepted. Interview scheduled.
                            </p>
                            <?php if (!empty($candidateApplication['interview_date']) || !empty($candidateApplication['interview_time'])): ?>
                                <div class="ra-accepted-grid">
                                    <?php if (!empty($candidateApplication['interview_date'])): ?>
                                        <div class="ra-accepted-cell">
                                            <label>Interview date</label>
                                            <p><?php echo e(date('F d, Y', strtotime($candidateApplication['interview_date']))); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($candidateApplication['interview_time'])): ?>
                                        <div class="ra-accepted-cell">
                                            <label>Interview time</label>
                                            <p><?php echo e(date('g:i A', strtotime($candidateApplication['interview_time']))); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($candidateApplication['company_message'])): ?>
                                <div class="ra-company-msg">
                                    <strong>Your message</strong>
                                    <?php echo e($candidateApplication['company_message']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- REJECTED -->
                    <?php elseif ($appStatus === 'rejected'): ?>
                        <div class="ra-rejected-box">
                            <p class="ra-rejected-title">
                                <i class="ti ti-ban" style="font-size:13px;"></i>
                                Candidate rejected
                            </p>
                            <?php if (!empty($candidateApplication['company_message'])): ?>
                                <div class="ra-rejected-msg">
                                    <strong>Your message</strong>
                                    <?php echo e($candidateApplication['company_message']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ra-divider"></div>

                    <div style="display:flex;align-items:center;justify-content:flex-end;">
                        <p style="font-size:11px;color:#94a3b8;">
                            Applied <?php echo e(date('M d, Y', strtotime($candidateApplication['created_at']))); ?>
                        </p>
                    </div>

                </article>
            <?php endforeach; ?>

        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>

    <script>
        function printCV(userId) {
            var printUrl = 'cv_download.php?id=' + userId + '&print=1';
            var win = window.open(printUrl, '_blank');
            if (win) {
                win.addEventListener('load', function() {
                    setTimeout(function() {
                        win.print();
                    }, 800);
                });
            } else {
                alert('Please allow popups to use the print feature.');
            }
        }
    </script>

</body>

</html>