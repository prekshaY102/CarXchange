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

// Fetch user details
$user = ['name' => 'Guest', 'email' => 'unknown@example.com'];
if ($stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $user = $row;
    $stmt->close();
}

// Quick stats
$listings = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM vehicles WHERE user_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $res = $stmt->get_result();
    $listings = (int) ($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

$favorites = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM favorites WHERE user_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $res = $stmt->get_result();
    $favorites = (int) ($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

$purchases = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM deals WHERE buyer_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute(); $res = $stmt->get_result();
    $purchases = (int) ($res->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

$mode = 'buyer';
$inquiries = [];
$inquiryCount = 0;
$totalInquiries = 0; // 🔹 Added: total inquiries count for "View More"
$visits = [];

// If user has listings, treat as seller dashboard
if ($listings > 0) {
    $mode = 'seller';

    // Inquiry count (for this seller’s vehicles)
    $sql = "
        SELECT COUNT(*) AS total
        FROM inquiries i
        JOIN vehicles v ON i.vehicle_id = v.id
        WHERE v.user_id = ?
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); $res = $stmt->get_result();
        $inquiryCount = (int) ($res->fetch_assoc()['total'] ?? 0);
        $totalInquiries = $inquiryCount; // 🔹 Save total count
        $stmt->close();
    }

    // Inquiries list with buyer info (🔹 LIMIT 2 to show only first two)
    $sql = "
        SELECT i.*, v.brand, v.model, v.year, u.name, u.email
        FROM inquiries i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN users u ON i.user_id = u.id
        WHERE v.user_id = ?
        ORDER BY i.created_at DESC
        LIMIT 2
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $inquiries[] = $row;
        $stmt->close();
    }

} else {
    // Buyer mode: show this user’s inquiries + visits
    $mode = 'buyer';

    // 🔹 LIMIT 2 to show only first two
    $sql = "
        SELECT i.*, v.brand, v.model, v.year, u.name AS seller_name, u.email AS seller_email, u.id AS seller_id
        FROM inquiries i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN users u ON v.user_id = u.id
        WHERE i.user_id = ?
        ORDER BY i.created_at DESC
        LIMIT 2
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $inquiries[] = $row;
        $stmt->close();
    }

    // Count total buyer inquiries (for View More)
    $sqlCount = "SELECT COUNT(*) AS total FROM inquiries WHERE user_id=?";
    if ($stmt = $conn->prepare($sqlCount)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); $res = $stmt->get_result();
        $totalInquiries = (int) ($res->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }
    $inquiryCount = $totalInquiries;

    // Visits
    $sql = "
        SELECT v.id, v.visit_date, v.status, vh.brand, vh.model, vh.year, u.name AS seller_name
        FROM visits v
        JOIN inquiries i ON v.inquiry_id = i.id
        JOIN vehicles vh ON i.vehicle_id = vh.id
        JOIN users u ON vh.user_id = u.id
        WHERE v.buyer_id = ?
        ORDER BY v.created_at DESC
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute(); $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $visits[] = $row;
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars(APP_NAME); ?> - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .dashboard-header {
      background: linear-gradient(135deg, #0d6efd, #6610f2);
      color: white; padding: 2rem; border-radius: .75rem;
    }
    .stat-card { border-radius: 1rem; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    .inquiry-card, .visit-card { border-left: 4px solid #0d6efd; margin-bottom: 1rem; border-radius: .75rem; transition: all 0.3s ease; }
    .inquiry-card:hover, .visit-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    .inquiry-header { font-weight: bold; color: #0d6efd; }
    /* Chat bubbles */
    .chat-wrap { max-width: 720px; }
    .bubble { border-radius: 1rem; padding: .6rem .9rem; display: inline-block; }
    .bubble-me   { background: #0d6efd; color: #fff; }
    .bubble-them { background: #eef3ff; color: #1b1f24; }
    .meta { font-size: .8rem; opacity: .8; }
  </style>
  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

</head>
<body>

<?php include './includes/header.php'; ?>

<div class="container my-5">

  <!-- Flash messages -->
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <!-- Header -->
  <div class="dashboard-header text-center mb-5">
    <h1>Welcome, <?= htmlspecialchars($user['name']); ?> 👋</h1>
    <p class="mb-0">Manage your cars, listings, inquiries and visits all in one place.</p>
  </div>

  <!-- Quick Stats -->
  <div class="row g-4 mb-5">
    <div class="col-md-3">
      <div class="card stat-card shadow-sm text-center p-4">
        <i class="bi bi-car-front display-5 text-primary"></i>
        <h4 class="mt-3"><?= $listings; ?></h4>
        <p class="text-muted">My Listings</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card stat-card shadow-sm text-center p-4">
        <i class="bi bi-heart-fill display-5 text-danger"></i>
        <h4 class="mt-3"><?= $favorites; ?></h4>
        <p class="text-muted">Favorites</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card stat-card shadow-sm text-center p-4">
        <i class="bi bi-bag-check-fill display-5 text-success"></i>
        <h4 class="mt-3"><?= $purchases; ?></h4>
        <p class="text-muted">Purchases</p>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card stat-card shadow-sm text-center p-4">
        <i class="bi bi-envelope-fill display-5 text-info"></i>
        <h4 class="mt-3"><?= $inquiryCount; ?></h4>
        <p class="text-muted">Inquiries</p>
      </div>
    </div>
  </div>
  
     <!-- Chart Section -->
  <div class="card shadow-sm mb-5">
    <div class="card-body">
      <h5 class="card-title mb-4">Activity Overview</h5>
      <canvas id="dashboardChart" height="100"></canvas>
    </div>
  </div>



<!-- Inquiries Section -->
<?php if ($mode === "seller"): ?>

 <div class="d-flex justify-content-between align-items-center mb-4">
  <h3>Inquiries Received (as Seller)</h3>
  <div>
    <a href="add_vehicle.php?user_id=<?php echo $user_id; ?>" class="btn btn-success me-2">
      <i class="bi bi-plus-circle"></i> Add New Car
    </a>
    <a href="my_listings.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-primary me-2">
      <i class="bi bi-card-list"></i> View Listings
    </a>
    <a href="manage_visits.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary me-2">
      <i class="bi bi-calendar-event"></i> Manage Visits
    </a>
    <a href="calendar.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-primary">
      <i class="bi bi-calendar3"></i> View Activity Calendar
    </a>
  </div>
</div>


  <?php if (!empty($inquiries)): ?>
    <?php 
      // Show only first 2 inquiries by default
      $displayInquiries = array_slice($inquiries, 0, 2); 
    ?>
    <?php foreach ($displayInquiries as $inq): ?>
      <div class="card inquiry-card shadow-sm p-3 mb-4">
        <div class="d-flex justify-content-between mb-2">
          <div class="inquiry-header">
            <?= htmlspecialchars($inq['brand']." ".$inq['model']." (".$inq['year'].")") ?>
          </div>
          <small class="text-muted"><?= date("M d, Y H:i", strtotime($inq['created_at'])) ?></small>
        </div>

        <p><strong>From:</strong> <?= htmlspecialchars($inq['name']) ?> (<?= htmlspecialchars($inq['email']) ?>)</p>
        <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($inq['message'])) ?></p>

        <?php
          // Conversation (Seller & Buyer replies)
          $inq_id = (int) $inq['id'];
          $sql = "
            SELECT r.*, u.name AS sender_name
            FROM inquiry_replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.inquiry_id = ?
            ORDER BY r.created_at ASC
          ";
          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $inq_id);
            $stmt->execute(); $res = $stmt->get_result();
        ?>
          <?php if ($res->num_rows > 0): ?>
            <div class="mt-3 chat-wrap">
              <h6 class="text-muted small mb-2">Conversation</h6>
              <?php while ($rep = $res->fetch_assoc()):
                $isMine = ((int)$rep['user_id'] === $user_id);
              ?>
                <div class="d-flex mb-2 <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>">
                  <div class="bubble <?= $isMine ? 'bubble-me' : 'bubble-them' ?>">
                    <div class="fw-semibold small"><?= htmlspecialchars($rep['sender_name']) ?></div>
                    <div><?= nl2br(htmlspecialchars($rep['reply_message'])) ?></div>
                    <div class="meta text-end mt-1"><?= date("M d, Y H:i", strtotime($rep['created_at'])) ?></div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>
        <?php $stmt->close(); } ?>

        <!-- Reply form (Seller) -->
        <form method="post" action="reply_inquiry.php" class="mt-3">
          <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
          <div class="input-group">
            <input type="text" name="reply_message" class="form-control" placeholder="Type your reply..." required>
            <button type="submit" class="btn btn-success">
              <i class="bi bi-send-fill"></i> Send Reply
            </button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>

      <div class="text-center mt-3">
        <a href="all_inquiries.php?mode=seller" class="btn btn-outline-primary">
          <i class="bi bi-chevron-down"></i> View More
        </a>
      </div>

  <?php else: ?>
    <div class="alert alert-info">No inquiries received yet.</div>
  <?php endif; ?>

<?php else: ?><!-- Buyer Section -->

  <h3 class="mb-4">My Inquiries (as Buyer)</h3>

  <?php if (!empty($inquiries)): ?>
    <?php 
      // Show only first 2 inquiries by default
      $displayInquiries = array_slice($inquiries, 0, 2); 
    ?>
    <?php foreach ($displayInquiries as $inq): ?>
      <div class="card inquiry-card shadow-sm p-3 mb-4">
        <div class="d-flex justify-content-between mb-2">
          <div class="inquiry-header">
            <?= htmlspecialchars($inq['brand']." ".$inq['model']." (".$inq['year'].")") ?>
          </div>
          <small class="text-muted"><?= date("M d, Y H:i", strtotime($inq['created_at'])) ?></small>
        </div>

        <p><strong>Seller:</strong> <?= htmlspecialchars($inq['seller_name']) ?> (<?= htmlspecialchars($inq['seller_email']) ?>)</p>
        <p><strong>Your Inquiry:</strong> <?= nl2br(htmlspecialchars($inq['message'])) ?></p>

        <?php
          // Conversation (Buyer & Seller replies)
          $inq_id = (int) $inq['id'];
          $sql = "
            SELECT r.*, u.name AS sender_name
            FROM inquiry_replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.inquiry_id = ?
            ORDER BY r.created_at ASC
          ";
          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $inq_id);
            $stmt->execute(); $res = $stmt->get_result();
        ?>
          <?php if ($res->num_rows > 0): ?>
            <div class="mt-3 chat-wrap">
              <h6 class="text-muted small mb-2">Conversation</h6>
              <?php while ($rep = $res->fetch_assoc()):
                $isMine = ((int)$rep['user_id'] === $user_id);
              ?>
                <div class="d-flex mb-2 <?= $isMine ? 'justify-content-end' : 'justify-content-start' ?>">
                  <div class="bubble <?= $isMine ? 'bubble-me' : 'bubble-them' ?>">
                    <div class="fw-semibold small"><?= htmlspecialchars($rep['sender_name']) ?></div>
                    <div><?= nl2br(htmlspecialchars($rep['reply_message'])) ?></div>
                    <div class="meta text-end mt-1"><?= date("M d, Y H:i", strtotime($rep['created_at'])) ?></div>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>
        <?php $stmt->close(); } ?>

        <!-- Reply form (Buyer) -->
        <form method="post" action="reply_inquiry.php" class="mt-3">
          <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
          <div class="input-group">
            <input type="text" name="reply_message" class="form-control" placeholder="Type your reply..." required>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-send"></i> Reply
            </button>
          </div>
        </form>

      </div>
    <?php endforeach; ?>

  
      <div class="text-center mt-3">
        <a href="all_inquiries.php?mode=buyer" class="btn btn-outline-primary">
          <i class="bi bi-chevron-down"></i> View More
        </a>
      </div>

  <?php else: ?>
    <div class="alert alert-info">You haven’t made any inquiries yet.</div>
  <?php endif; ?>

<?php endif; ?>
    
    
<!-- Visits Section -->
<h3 class="mt-5 mb-4">Visits</h3>

<?php if($mode === 'buyer'): ?>
    <!-- Buyer: Request a Visit -->
    <?php if(!empty($inquiries)): ?>
        <?php
        // Limit to top 2 inquiries for dashboard
        $displayInquiries = array_slice($inquiries, 0, 2);
        ?>
        <?php foreach($displayInquiries as $inq): ?>
            <div class="card visit-card shadow-sm p-3 mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <div class="inquiry-header"><?= htmlspecialchars($inq['brand'].' '.$inq['model'].' ('.$inq['year'].')') ?></div>
                    <small class="text-muted"><?= date("M d, Y H:i", strtotime($inq['created_at'])) ?></small>
                </div>

                <p><strong>Seller:</strong> <?= htmlspecialchars($inq['seller_name']) ?> (<?= htmlspecialchars($inq['seller_email']) ?>)</p>
                <p><strong>Your Inquiry:</strong> <?= nl2br(htmlspecialchars($inq['message'])) ?></p>

                <?php
                // Check if a visit already exists for this inquiry
                $visitExists = false;
                $visitStatus = '';
                $visitDate = '';
                $visitId = 0;

                $stmt = $conn->prepare("SELECT id, visit_date, status FROM visits WHERE inquiry_id = ? AND buyer_id = ? LIMIT 1");
                $stmt->bind_param("ii", $inq['id'], $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if($row = $res->fetch_assoc()){
                    $visitExists = true;
                    $visitStatus = $row['status'];
                    $visitDate = $row['visit_date'];
                    $visitId = $row['id'];
                }
                $stmt->close();
                ?>

                <?php if($visitExists): ?>
                    <p><strong>Visit Status:</strong>
                        <?php if($visitStatus === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif($visitStatus === 'approved'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php elseif($visitStatus === 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Visit Date:</strong> <?= $visitDate ? date("M d, Y H:i", strtotime($visitDate)) : 'Not set' ?></p>

                    <?php if($visitStatus === 'approved'): ?>
                        <a href="visit_receipt.php?visit_id=<?= $visitId ?>" class="btn btn-success mt-2">
                            <i class="bi bi-download"></i> Download Visit Receipt
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Request Visit Form -->
                <?php if(!$visitExists || $visitStatus === 'rejected'): ?>
                    <form action="request_visit.php" method="post" class="mt-3">
                        <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
                        <div class="input-group">
                            <input type="datetime-local" name="visit_date" class="form-control" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-calendar-check"></i> Request Visit
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="text-center mt-3">
            <a href="all_visits.php?mode=buyer" class="btn btn-outline-primary">
                <i class="bi bi-chevron-down"></i> View More
            </a>
        </div>

    <?php else: ?>
        <div class="alert alert-info">You haven’t made any inquiries yet.</div>
    <?php endif; ?>

<?php elseif($mode === 'seller'): ?>
    <!-- Seller: Show Top 2 Visit Requests -->
    <?php
    $stmt = $conn->prepare("SELECT v.id AS visit_id, v.visit_date, v.status, i.message AS inquiry_message,
               u.name AS buyer_name, u.email AS buyer_email,
               vh.brand, vh.model, vh.year
        FROM visits v
        JOIN inquiries i ON v.inquiry_id = i.id
        JOIN users u ON v.buyer_id = u.id
        JOIN vehicles vh ON i.vehicle_id = vh.id
        WHERE vh.user_id = ?
        ORDER BY v.created_at DESC
        LIMIT 2
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $topVisits = [];
    while($row = $result->fetch_assoc()) $topVisits[] = $row;
    $stmt->close();
    ?>

    <?php if(!empty($topVisits)): ?>
        <?php foreach($topVisits as $visit): ?>
            <div class="card visit-card shadow-sm p-3 mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <div class="inquiry-header"><?= htmlspecialchars($visit['brand'].' '.$visit['model'].' ('.$visit['year'].')') ?></div>
                    <small class="text-muted"><?= date("M d, Y H:i", strtotime($visit['visit_date'])) ?></small>
                </div>

                <p><strong>Buyer:</strong> <?= htmlspecialchars($visit['buyer_name']) ?> (<?= htmlspecialchars($visit['buyer_email']) ?>)</p>
                <p><strong>Inquiry Message:</strong> <?= nl2br(htmlspecialchars($visit['inquiry_message'])) ?></p>
                <p><strong>Status:</strong>
                    <?php if($visit['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif($visit['status'] === 'approved'): ?>
                        <span class="badge bg-success">Approved</span>
                    <?php elseif($visit['status'] === 'rejected'): ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                </p>

                <a href="visit_details.php?id=<?= (int)$visit['visit_id'] ?>" class="btn btn-outline-primary btn-sm mt-2">View Details</a>
            </div>
        <?php endforeach; ?>

        <div class="text-center mt-3">
            <a href="manage_visits.php" class="btn btn-outline-primary">
                <i class="bi bi-chevron-down"></i> View More
            </a>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No visit requests yet.</div>
    <?php endif; ?>
<?php endif; ?>



<?php include './includes/footer.php'; ?>
    <!-- <script>
  const ctx = document.getElementById('dashboardChart').getContext('2d');
  const dashboardChart = new Chart(ctx, {
    type: 'bar', // you can change to 'line', 'doughnut', etc.
    data: {
      labels: ['Listings', 'Favorites', 'Purchases', 'Inquiries'],
      datasets: [{
        label: 'Count',
        data: [<?= $listings ?>, <?= $favorites ?>, <?= $purchases ?>, <?= $inquiryCount ?>],
        backgroundColor: [
          'rgba(13, 110, 253, 0.7)',   // Blue
          'rgba(220, 53, 69, 0.7)',    // Red
          'rgba(25, 135, 84, 0.7)',    // Green
          'rgba(13, 202, 240, 0.7)'    // Cyan
        ],
        borderColor: [
          'rgba(13, 110, 253, 1)',
          'rgba(220, 53, 69, 1)',
          'rgba(25, 135, 84, 1)',
          'rgba(13, 202, 240, 1)'
        ],
        borderWidth: 1,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision:0
          }
        }
      }
    }
  });
</script> -->

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->
<script src="assets/js/chart.umd.min.js"></script>
<script>
  const ctx = document.getElementById('dashboardChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Listings', 'Favorites', 'Purchases', 'Inquiries'],
      datasets: [{
        label: 'Count',
        data: [<?= $listings ?>, <?= $favorites ?>, <?= $purchases ?>, <?= $inquiryCount ?>],
        backgroundColor: [
          'rgba(13, 110, 253, 0.7)',
          'rgba(220, 53, 69, 0.7)',
          'rgba(25, 135, 84, 0.7)',
          'rgba(13, 202, 240, 0.7)'
        ],
        borderColor: [
          'rgba(13, 110, 253, 1)',
          'rgba(220, 53, 69, 1)',
          'rgba(25, 135, 84, 1)',
          'rgba(13, 202, 240, 1)'
        ],
        borderWidth: 1,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
</script>

</body>
</html>
