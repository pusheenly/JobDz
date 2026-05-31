<?php
session_start();
require 'config.php';
require __DIR__ . '/send_verefication.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please register again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$email  = $_SESSION['email'];

$stmt = $pdo->prepare("SELECT name, is_verified FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if ($user['is_verified']) {
    echo json_encode(['success' => false, 'message' => 'Your account is already verified.']);
    exit;
}

$sent = sendVerificationEmail($pdo, $userId, $email, $user['name']);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Verification email sent!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
}