<?php
// favorite_action.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    echo 'login'; // AJAX handler understands this
    exit;
}

$vehicle_id = isset($_POST['vehicle_id']) ? (int) $_POST['vehicle_id'] : 0;
$action = $_POST['action'] ?? '';

if ($vehicle_id <= 0 || !in_array($action, ['add', 'remove'], true)) {
    echo 'invalid';
    exit;
}

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, vehicle_id, created_at) VALUES (?, ?, NOW())");
    if ($stmt === false) { echo 'prepare_error'; exit; }
    $stmt->bind_param("ii", $user_id, $vehicle_id);
    if ($stmt->execute()) { echo 'success'; } else { echo 'db_error:' . $stmt->error; }
    exit;
}

if ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND vehicle_id = ?");
    if ($stmt === false) { echo 'prepare_error'; exit; }
    $stmt->bind_param("ii", $user_id, $vehicle_id);
    if ($stmt->execute()) { echo 'success'; } else { echo 'db_error:' . $stmt->error; }
    exit;
}
?>