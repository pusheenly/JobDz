<?php
require_once 'functions.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
}

$jobId = intval($_POST['job_id'] ?? $_GET['id'] ?? 0);
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (!isLoggedIn() || $_SESSION['user']['role'] !== 'candidate') {
    if ($isAjax) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Please login as a candidate to apply for jobs.'
        ]);
    } else {
        header('Location: login.php');
    }
    exit;
}

if ($jobId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid job selected.'
        ]);
    } else {
        header('Location: ' . $referer);
    }
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $action = $_POST['action'] ?? null;
    $existing = hasAppliedJob($jobId, $userId);

    if ($action === 'remove') {
        if (!$existing) {
            if ($isAjax) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'No application found to remove.'
                ]);
            } else {
                header('Location: ' . $referer);
            }
            exit;
        }

        $removed = removeApplication($jobId, $userId);
        if (!$removed) {
            throw new Exception('Could not remove application.');
        }

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'removed' => true,
                'message' => 'Application removed.'
            ]);
        } else {
            header('Location: ' . $referer);
        }
        exit;
    }

    if ($existing) {
        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'applied' => true,
                'status' => $existing['status'],
                'message' => 'Application already exists.'
            ]);
        } else {
            header('Location: ' . $referer);
        }
        exit;
    }

    // Check if job is open and can accept applications
    if (!canApplyForJob($jobId)) {
        $jobStatus = getJobPositionsSummary($jobId);

        if (!$isAjax) {
            header('Location: ' . $referer);
            exit;
        }

        if ($jobStatus && $jobStatus['status'] === 'closed') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'This job posting is closed. All positions have been filled.'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'This job is no longer accepting applications.'
            ]);
        }
        exit;
    }

    $applied = applyJob($jobId, $userId);

    if (!$applied) {
        throw new Exception('Could not apply for job.');
    }

    if ($applied) {

        global $pdo;

        $updateApplicants = $pdo->prepare("
        UPDATE jobs
        SET applications_count = applications_count + 1
        WHERE id = ?
    ");

        $updateApplicants->execute([$jobId]);
    }

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'applied' => true,
            'status' => 'pending',
            'message' => 'Successfully applied for job.'
        ]);
    } else {
        header('Location: ' . $referer);
    }
    exit;
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        header('Location: ' . $referer);
    }
    exit;
}
