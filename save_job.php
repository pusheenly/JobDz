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
            'error' => 'Please login as a candidate to save jobs.'
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
    if (hasSavedJob($jobId, $userId)) {
        $removed = removeSavedJob($jobId, $userId);
        if (!$removed) {
            throw new Exception('Could not remove saved job.');
        }

        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'saved' => false,
                'message' => 'Job removed from saved jobs.'
            ]);
        } else {
            header('Location: ' . $referer);
        }
        exit;
    }

    $saved = saveJob($jobId, $userId);
    if (!$saved) {
        throw new Exception('Could not save job.');
    }

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'saved' => true,
            'message' => 'Job saved successfully.'
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
