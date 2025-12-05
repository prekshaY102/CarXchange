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

$user_id = intval($_SESSION['user_id']); // buyer id

// Fetch inquiries made by this buyer
$query = $conn->query("
    SELECT i.*, v.brand, v.model, v.year 
    FROM inquiries i
    JOIN vehicles v ON i.vehicle_id = v.id
    WHERE i.user_id = $user_id
    ORDER BY i.created_at DESC
");

$inquiries = [];
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $inquiries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - My Inquiries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include './includes/header.php'; ?>

<div class="container my-5">
  <h2 class="mb-4">My Inquiries</h2>

  <?php if (count($inquiries) > 0): ?>
    <?php foreach ($inquiries as $inq): ?>
      <div class="card shadow-sm p-3 mb-3">
        <h5><?= htmlspecialchars($inq['brand'] . " " . $inq['model'] . " (" . $inq['year'] . ")") ?></h5>
        <p class="mb-1"><strong>Your Message:</strong> <?= nl2br(htmlspecialchars($inq['message'])) ?></p>
        <small class="text-muted">Sent on <?= date("M d, Y H:i", strtotime($inq['created_at'])) ?></small>

        <!-- Seller Replies -->
        <?php
        $inq_id = intval($inq['id']);
        $repliesRes = $conn->query("SELECT * FROM inquiry_replies WHERE inquiry_id = $inq_id ORDER BY created_at ASC");
        if ($repliesRes && $repliesRes->num_rows > 0): ?>
          <div class="mt-3 ps-3 border-start">
            <h6 class="text-muted small mb-2">Seller Replies</h6>
            <?php while ($rep = $repliesRes->fetch_assoc()): ?>
              <p class="small text-primary mb-1">
                <strong>Seller:</strong> <?= nl2br(htmlspecialchars($rep['reply_message'])) ?>
                <br><small class="text-muted"><?= date("M d, Y H:i", strtotime($rep['created_at'])) ?></small>
              </p>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-secondary mt-2">No replies yet from seller.</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">You haven’t made any inquiries yet.</div>
  <?php endif; ?>
</div>

<?php include './includes/footer.php'; ?>
</body>
</html>
