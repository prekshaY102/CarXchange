<?php
// reply_inquiry.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$inquiry_id = isset($_POST['inquiry_id']) ? (int) $_POST['inquiry_id'] : 0;
$reply = trim($_POST['reply_message'] ?? '');

if ($inquiry_id <= 0 || $reply === '') {
    $_SESSION['flash_message'] = "Invalid message.";
    header("Location: all_inquiries.php?inquiry_id=".$inquiry_id);
    exit;
}

$stmt = $conn->prepare("INSERT INTO inquiry_replies (inquiry_id, user_id, reply_message, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $inquiry_id, $user_id, $reply);
$stmt->execute();
$stmt->close();

$_SESSION['flash_message'] = "Message sent.";
header("Location: all_inquiries.php?inquiry_id=".$inquiry_id);
exit;
