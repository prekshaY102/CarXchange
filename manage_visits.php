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

$seller_id = intval($_SESSION['user_id']); // current seller

// Fetch all visits where the logged-in user is the seller
$query = $conn->prepare("
    SELECT v.id, v.visit_date, v.status, v.created_at,
           b.name AS buyer_name, b.email AS buyer_email,
           vh.brand, vh.model, vh.year
    FROM visits v
    JOIN inquiries i ON v.inquiry_id = i.id
    JOIN users b ON v.buyer_id = b.id
    JOIN vehicles vh ON i.vehicle_id = vh.id
    WHERE v.seller_id = ?
    ORDER BY v.created_at DESC
");
$query->bind_param("i", $seller_id);
$query->execute();
$result = $query->get_result();

$visits = [];
while ($row = $result->fetch_assoc()) {
    $visits[] = $row;
}

$query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Visits - <?php echo APP_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .visit-card {
        border-left: 4px solid #0d6efd;
        margin-bottom: 1rem;
        border-radius: .75rem;
        transition: all 0.3s ease;
    }
    .visit-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>

<?php include './includes/header.php'; ?>

<div class="container my-5">
  <h2 class="mb-4">Manage Visit Requests</h2>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <?php if (count($visits) > 0): ?>
    <?php foreach ($visits as $visit): ?>
      <div class="card visit-card shadow-sm p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">
            <?= htmlspecialchars($visit['brand'] . " " . $visit['model'] . " (" . $visit['year'] . ")") ?>
          </h5>
          <small class="text-muted"><?= date("M d, Y H:i", strtotime($visit['created_at'])) ?></small>
        </div>
        <p class="mb-1"><strong>Buyer:</strong> <?= htmlspecialchars($visit['buyer_name']) ?> (<?= htmlspecialchars($visit['buyer_email']) ?>)</p>
        <p class="mb-1"><strong>Preferred Visit:</strong> <?= date("M d, Y H:i", strtotime($visit['visit_date'])) ?></p>
        <p class="mb-2"><strong>Status:</strong> 
          <?php if ($visit['status'] === 'pending'): ?>
            <span class="badge bg-warning text-dark">Pending</span>
          <?php elseif ($visit['status'] === 'approved'): ?>
            <span class="badge bg-success">Approved</span>
          <?php else: ?>
            <span class="badge bg-danger">Rejected</span>
          <?php endif; ?>
        </p>

        <?php if ($visit['status'] === 'pending'): ?>
          <form method="post" action="update_visit_status.php" class="d-flex gap-2">
            <input type="hidden" name="visit_id" value="<?= $visit['id'] ?>">
            <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">
              <i class="bi bi-check-circle"></i> Approve
            </button>
            <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger">
              <i class="bi bi-x-circle"></i> Reject
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">No visit requests yet.</div>
  <?php endif; ?>
</div>

<?php include './includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
