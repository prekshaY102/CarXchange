<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$offer_id  = (int) ($_POST['offer_id'] ?? 0);
$response  = $_POST['response'] ?? '';
$counter   = isset($_POST['counter_amount']) && $_POST['counter_amount'] !== '' ? (float) $_POST['counter_amount'] : null;

// Fetch the offer details
$stmt = $conn->prepare("SELECT * FROM inquiry_offers WHERE id = ?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$offer) {
    $_SESSION['flash_message'] = "Offer not found.";
    header("Location: all_inquiries.php");
    exit;
}

$inquiry_id   = $offer['inquiry_id'];
$offer_amount = $offer['offer_amount'];
$offer_user   = $offer['user_id'];

// Handle counter-offer
if ($response === 'rejected' && $counter !== null) {
    $stmt = $conn->prepare("INSERT INTO inquiry_offers (inquiry_id, user_id, offer_amount, status) VALUES (?,?,?,'pending')");
    $stmt->bind_param("iid", $inquiry_id, $user_id, $counter);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_message'] = "Counter offer submitted.";
    header("Location: all_inquiries.php?inquiry_id=" . $inquiry_id);
    exit;
}

// Update existing offer
$stmt = $conn->prepare("UPDATE inquiry_offers SET status = ? WHERE id = ?");
$stmt->bind_param("si", $response, $offer_id);
$stmt->execute();
$stmt->close();

if ($response === 'accepted') {
    // Fetch inquiry details to get buyer/seller
    $stmt = $conn->prepare("
        SELECT i.user_id AS buyer_id, v.user_id AS seller_id
        FROM inquiries i
        JOIN vehicles v ON i.vehicle_id = v.id
        WHERE i.id = ?
    ");
    $stmt->bind_param("i", $inquiry_id);
    $stmt->execute();
    $inq = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Create deal
    $stmt = $conn->prepare("INSERT INTO deals (inquiry_id, buyer_id, seller_id, final_amount, status) VALUES (?,?,?,?, 'initiated')");
    $stmt->bind_param("iiid", $inquiry_id, $inq['buyer_id'], $inq['seller_id'], $offer_amount);
    $stmt->execute();
    $deal_id = $stmt->insert_id;
    $stmt->close();

    //new added notification code

    $vehicleTitle = $inq['brand'] . " " . $inq['model'];
    $notifType = 'offer';
    $notifMessage = "Your offer for $vehicleTitle has been accepted! Please view your deal summary.";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->bind_param("iss", $inq['buyer_id'], $notifType, $notifMessage);
    $stmt->execute();
    $stmt->close();

    //ends here

    // Redirect to deal summary
    header("Location: deal_summary.php?deal_id=" . $deal_id);
    exit;
}

$_SESSION['flash_message'] = "Offer " . ucfirst($response) . ".";
header("Location: all_inquiries.php?inquiry_id=" . $inquiry_id);
exit;
