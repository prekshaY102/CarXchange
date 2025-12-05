<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seller_id = intval($_SESSION['user_id']);
    $visit_id = intval($_POST['visit_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    // Validate request
    if ($visit_id <= 0 || !in_array($status, ['approved', 'rejected'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: manage_visits.php");
        exit;
    }

    // Update visit status (only for logged-in seller)
    $stmt = $conn->prepare("UPDATE visits SET status = ? WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("sii", $status, $visit_id, $seller_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Visit request has been updated.";

            //notification code starts here

            // Fetch visit info to get buyer and visit date/title
            $stmt_v = $conn->prepare("SELECT buyer_id, seller_id, visit_date, inquiry_id FROM visits WHERE id = ?");
            $stmt_v->bind_param("i", $visit_id);
            $stmt_v->execute();
            $res = $stmt_v->get_result();
            if ($row = $res->fetch_assoc()) {
                $buyerId = $row['buyer_id'];
                $sellerId = $row['seller_id'];
                $visitDate = $row['visit_date'];
                // Optionally, fetch vehicle info for prettier message
                $vehicleTitle = "";
                $inqId = $row['inquiry_id'];
                $stmt_inq = $conn->prepare("SELECT v.brand, v.model FROM inquiries i JOIN vehicles v ON i.vehicle_id = v.id WHERE i.id = ?");
                $stmt_inq->bind_param("i", $inqId);
                $stmt_inq->execute();
                $res_inq = $stmt_inq->get_result();
                if ($res_inq && $r2 = $res_inq->fetch_assoc()) {
                    $vehicleTitle = $r2['brand'] . " " . $r2['model'];
                }
                $stmt_inq->close();

                // Notify BUYER
                $notifType = 'visit';
                if ($status === "approved") {
                    $notifMessage = "Your visit request for $vehicleTitle on $visitDate was accepted!";
                } else if ($status === "rejected") {
                    $notifMessage = "Your visit request for $vehicleTitle on $visitDate was rejected.";
                } else {
                    $notifMessage = "Your visit request for $vehicleTitle was updated.";
                }
                $stmt_n = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt_n->bind_param("iss", $buyerId, $notifType, $notifMessage);
                $stmt_n->execute();
                $stmt_n->close();

                // Notify SELLER
                if ($status === "approved") {
                    $notifMessage = "You accepted a visit for $vehicleTitle on $visitDate.";
                } else if ($status === "rejected") {
                    $notifMessage = "You rejected a visit for $vehicleTitle on $visitDate.";
                } else {
                    $notifMessage = "You updated a visit for $vehicleTitle.";
                }
                $stmt_n = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt_n->bind_param("iss", $sellerId, $notifType, $notifMessage);
                $stmt_n->execute();
                $stmt_n->close();
            }
            $stmt_v->close();


            //ends here
        } else {
            // No row changed: either wrong seller_id/visit_id, or status already set
            $_SESSION['info'] = "No changes made (maybe status already set).";
        }
    } else {
        $_SESSION['error'] = "Database error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: manage_visits.php");
    exit;

} else {
    header("Location: manage_visits.php");
    exit;
}
?>