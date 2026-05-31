<?php
require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $subject   = trim($_POST['subject']);
    $message   = trim($_POST['message']);

    $sql = "INSERT INTO contact_messages (full_name, email, subject, message)
            VALUES (?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$full_name, $email, $subject, $message]);

    header("Location: contact.php?sent=1");
    exit;
}
?>