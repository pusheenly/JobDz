<?php
require_once 'config.php';

$alertId = (int) $_GET['id'];
$userId  = $_SESSION['user']['id'];

$stmt = $pdo->prepare("DELETE FROM job_alerts WHERE id = ? AND user_id = ?");
$stmt->execute([$alertId, $userId]);

header('Location: candidate_dashboard.php');
exit;