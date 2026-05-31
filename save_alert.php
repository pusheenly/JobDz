<?php

require_once 'config.php';
require_once 'functions.php';

requireLogin();

if (empty($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_alert.php');
    exit;
}


$user_id    = $_SESSION['user']['id'];
$alert_name = !empty($_POST['keywords']) ? trim($_POST['keywords']) : 'My Alert';
$keywords   = trim($_POST['keywords']   ?? '');
$city       = trim($_POST['city']       ?? '');
$category   = trim($_POST['category']   ?? '');
$specialty  = trim($_POST['specialty']  ?? '');
$contract   = trim($_POST['contract']   ?? '');
$experience = trim($_POST['experience'] ?? '');
$worktime   = trim($_POST['worktime']   ?? '');
$frequency  = trim($_POST['frequency']  ?? 'daily');

if (empty($keywords)) {
    header('Location: create_alert.php');
    exit;
}

try {

    $sql = "
        INSERT INTO job_alerts
        (
            user_id,
            alert_name,
            keywords,
            city,
            category,
            specialty,
            contract_type,
            experience_level,
            work_type,
            frequency,
            created_at
        )
        VALUES
        (
            :user_id,
            :alert_name,
            :keywords,
            :city,
            :category,
            :specialty,
            :contract_type,
            :experience_level,
            :work_type,
            :frequency,
            NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ':user_id'           => $user_id,
        ':alert_name'        => $alert_name,
        ':keywords'          => $keywords,
        ':city'              => $city,
        ':category'          => $category,
        ':specialty'         => $specialty,
        ':contract_type'     => $contract,
        ':experience_level'  => $experience,
        ':work_type'         => $worktime,
        ':frequency'         => $frequency

    ]);

    header('Location: create_alert.php?success=1');
    exit;
} catch (PDOException $e) {

    die("Database Error: " . $e->getMessage());
}
