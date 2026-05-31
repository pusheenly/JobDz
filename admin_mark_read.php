<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

if (!isAdmin()) { http_response_code(403); exit; }

global $pdo;
$id = intval($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")
        ->execute([$id]);
}
echo json_encode(['success' => true]);