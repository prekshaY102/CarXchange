<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Handle marking a notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $nid = (int) $_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $nid, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit;
}

// Fetch notifications for the user
$sql = "
    SELECT id, type, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";
$notifications = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars(APP_NAME) ?> - Notifications</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #f8f9fa; }
    .notification-card { margin-bottom: 0.5rem; }
    .unread { background: #e7f1ff; font-weight: bold; }
</style>
</head>
<body>
<?php include './includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">🔔 Notifications</h2>

    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">You have no notifications.</div>
    <?php else: ?>
        <?php foreach ($notifications as $note): ?>
            <div class="card notification-card <?= $note['is_read'] ? '' : 'unread' ?>">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($note['type']) ?></span>
                        <?= htmlspecialchars($note['message']) ?>
                        <div class="text-muted small"><?= date("F j, Y, g:i A", strtotime($note['created_at'])) ?></div>
                    </div>
                    <?php if (!$note['is_read']): ?>
                        <a href="?mark_read=<?= $note['id'] ?>" class="btn btn-sm btn-outline-success">Mark Read</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include './includes/footer.php'; ?>
</body>
</html>
