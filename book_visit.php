<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']); // logged-in buyer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiry_id = intval($_POST['inquiry_id'] ?? 0);
    $seller_id  = intval($_POST['seller_id'] ?? 0);
    $visit_date = trim($_POST['visit_date'] ?? '');

    if ($inquiry_id <= 0 || $seller_id <= 0 || empty($visit_date)) {
        $_SESSION['flash_error'] = "Invalid data. Please try again.";
        header("Location: dashboard.php");
        exit;
    }

    // Prevent duplicate booking
    $stmt = $conn->prepare("SELECT id FROM visits WHERE inquiry_id = ? AND visit_date = ?");
    $stmt->bind_param("is", $inquiry_id, $visit_date);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['flash_error'] = "You already booked a visit for this date.";
        header("Location: dashboard.php");
        exit;
    }
    $stmt->close();

    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO visits (inquiry_id, buyer_id, seller_id, visit_date) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $inquiry_id, $user_id, $seller_id, $visit_date);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Your visit request has been submitted!";
    } else {
        $_SESSION['flash_error'] = "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: dashboard.php");
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}
