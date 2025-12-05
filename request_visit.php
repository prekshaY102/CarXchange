<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Validate POST
if (!isset($_POST['inquiry_id'], $_POST['visit_date'])) {
    $_SESSION['flash_error'] = "Invalid request.";
    header("Location: dashboard.php");
    exit;
}

$inquiry_id = (int) $_POST['inquiry_id'];
$visit_date = $_POST['visit_date'];

// Check if this inquiry belongs to the buyer
$stmt = $conn->prepare("SELECT id FROM inquiries WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $inquiry_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['flash_error'] = "You cannot request a visit for this inquiry.";
    header("Location: dashboard.php");
    exit;
}
$stmt->close();

// Insert into visits table
$stmt = $conn->prepare("INSERT INTO visits (inquiry_id, buyer_id, seller_id, visit_date, status, created_at) 
                        SELECT i.id, ?, v.user_id, ?, 'pending', NOW() 
                        FROM inquiries i
                        JOIN vehicles v ON i.vehicle_id=v.id
                        WHERE i.id=?");
$stmt->bind_param("isi", $user_id, $visit_date, $inquiry_id);
if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Visit requested successfully!";
} else {
    $_SESSION['flash_error'] = "Failed to request visit. Try again.";
}
$stmt->close();

//notification code

// Fetch vehicle and user info for notifications
$stmt_v = $conn->prepare("SELECT v.user_id AS sellerId, v.brand, v.model, i.user_id AS buyerId FROM inquiries i JOIN vehicles v ON i.vehicle_id = v.id WHERE i.id=?");
$stmt_v->bind_param("i", $inquiry_id);
$stmt_v->execute();
$res = $stmt_v->get_result();
if ($row = $res->fetch_assoc()) {
    $sellerId = $row['sellerId'];
    $buyerId = $row['buyerId'];
    $vehicleTitle = $row['brand'] . " " . $row['model'];

    // Notify seller
    $notifType = 'visit';
    $notifMessage = "A visit was requested for your vehicle: $vehicleTitle on $visit_date.";
    $stmt_n = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt_n->bind_param("iss", $sellerId, $notifType, $notifMessage);
    $stmt_n->execute();
    $stmt_n->close();

    // Notify buyer
    $notifMessage = "Your request for a visit for $vehicleTitle on $visit_date was submitted.";
    $stmt_n = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt_n->bind_param("iss", $buyerId, $notifType, $notifMessage);
    $stmt_n->execute();
    $stmt_n->close();
}
$stmt_v->close();


//ends here

header("Location: dashboard.php");
exit;
