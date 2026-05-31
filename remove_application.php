<?php
require_once 'functions.php';
requireLogin();

if ($_SESSION['user']['role'] !== 'candidate') {
    header('Location: index.php');
    exit;
}

if (isset($_GET['job_id'])) {
    $jobId = (int) $_GET['job_id'];
    $candidateId = $_SESSION['user']['id'];

    removeApplication($jobId, $candidateId);
}

header('Location: applications.php');
exit;
?>