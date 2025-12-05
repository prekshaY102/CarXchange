<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
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

$user_id = (int) $_SESSION['user_id'];

// Get visit ID from URL
$visit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($visit_id <= 0) {
    $_SESSION['flash_error'] = "Invalid visit ID.";
    header("Location: dashboard.php");
    exit;
}

// Fetch visit details
$stmt = $conn->prepare("
    SELECT v.id AS visit_id, v.visit_date, v.status, v.created_at,
           i.message AS inquiry_message,
           vh.brand, vh.model, vh.year,
           buyer.id AS buyer_id, buyer.name AS buyer_name, buyer.email AS buyer_email,
           seller.id AS seller_id, seller.name AS seller_name, seller.email AS seller_email
    FROM visits v
    JOIN inquiries i ON v.inquiry_id = i.id
    JOIN vehicles vh ON i.vehicle_id = vh.id
    JOIN users buyer ON v.buyer_id = buyer.id
    JOIN users seller ON vh.user_id = seller.id
    WHERE v.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $visit_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$visit = $res->fetch_assoc()) {
    $_SESSION['flash_error'] = "Visit not found.";
    header("Location: dashboard.php");
    exit;
}
$stmt->close();

// Determine user role for this visit
$mode = ($user_id === (int)$visit['seller_id']) ? 'seller' : 'buyer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visit Details - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .visit-card { border-left: 4px solid #0d6efd; border-radius: .75rem; padding: 1.5rem; margin-top: 2rem; }
    </style>
</head>
<body>

<?php include './includes/header.php'; ?>

<div class="container my-5">

    <div class="card visit-card shadow-sm">
        <h3 class="mb-3"><?= htmlspecialchars($visit['brand'].' '.$visit['model'].' ('.$visit['year'].')') ?></h3>
        <p><strong>Visit Date:</strong> <?= date("M d, Y H:i", strtotime($visit['visit_date'])) ?></p>
        <p><strong>Status:</strong>
            <?php if($visit['status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">Pending</span>
            <?php elseif($visit['status'] === 'approved'): ?>
                <span class="badge bg-success">Approved</span>
            <?php elseif($visit['status'] === 'rejected'): ?>
                <span class="badge bg-danger">Rejected</span>
            <?php endif; ?>
        </p>
        <p><strong>Inquiry Message:</strong> <?= nl2br(htmlspecialchars($visit['inquiry_message'])) ?></p>

        <?php if($mode === 'buyer'): ?>
            <p><strong>Seller:</strong> <?= htmlspecialchars($visit['seller_name']) ?> (<?= htmlspecialchars($visit['seller_email']) ?>)</p>
        <?php else: ?>
            <p><strong>Buyer:</strong> <?= htmlspecialchars($visit['buyer_name']) ?> (<?= htmlspecialchars($visit['buyer_email']) ?>)</p>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

