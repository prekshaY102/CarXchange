<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Get vehicle ID from URL
$vehicle_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($vehicle_id <= 0) {
    die("Invalid vehicle ID.");
}

// Fetch vehicle details + seller info
$sql = "SELECT v.*, u.name AS seller_name, u.email AS seller_email, 
               u.phone AS seller_phone, u.address AS seller_address 
        FROM vehicles v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    die("Vehicle not found.");
}

// Helper function for safe output
function safe($val, $default = '') {
    return htmlspecialchars($val ?? $default);
}

// Helper for images
function getImage($filename) {
    if ($filename && file_exists(__DIR__ . "/" . $filename)) {
        return $filename; // already includes uploads/vehicles/...
    }
    return "uploads/no-image.png";
}

// Handle inquiry form submission
$success_msg = $error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        if (!isset($_SESSION['user_id'])) {
            $error_msg = "⚠️ You must be logged in to send an inquiry.";
        } else {
            $user_id = (int) $_SESSION['user_id'];

            // Prevent user from sending inquiry on their own vehicle
            if ($user_id === (int) $vehicle['user_id']) {
                $error_msg = "⚠️ You cannot send an inquiry on your own vehicle.";
            } else {
                $stmt = $conn->prepare("INSERT INTO inquiries 
                    (user_id, vehicle_id, name, email, message) 
                    VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iisss", $user_id, $vehicle_id, $name, $email, $message);
                    if ($stmt->execute()) {
                        $success_msg = "✅ Your inquiry has been sent successfully!";
                    } else {
                        $error_msg = "❌ Failed to send inquiry. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $error_msg = "❌ Database error. Please try again later.";
                }
            }
        }
    } else {
        $error_msg = "⚠️ All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= safe($vehicle['brand']) . " " . safe($vehicle['model']) ?> - Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { background-color: #f9fafb; }
      .vehicle-img { width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px; }
      .thumb-img { width: 80px; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid #ddd; border-radius: 8px; transition: all .2s; }
      .thumb-img:hover { border-color: #0d6efd; transform: scale(1.05); }
      .accordion-button:not(.collapsed) { background-color: #0d6efd; color: white; }
  </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container my-5">
  <div class="row g-4">
    
    <!-- Left Column: Vehicle Details -->
    <div class="col-lg-8">
      <div class="card shadow-lg p-4">
        <h2 class="fw-bold"><?= safe($vehicle['brand']) . " " . safe($vehicle['model']) ?> (<?= safe($vehicle['year']) ?>)</h2>
        <p class="text-muted"><?= safe($vehicle['color']) ?> • <?= safe($vehicle['vehicle_condition']) ?></p>
        <h3 class="text-success fw-bold mb-3">$<?= number_format((float)$vehicle['price'], 2) ?></h3>

        <!-- Main Image -->
        <div class="mb-3">
          <img id="mainImage" src="<?= getImage($vehicle['image1']) ?>" class="vehicle-img shadow" alt="Vehicle Image">
        </div>

        <!-- Thumbnails -->
        <div class="d-flex gap-2 flex-wrap mb-4">
          <?php 
          for ($i=1; $i<=5; $i++): 
              $imgFile = $vehicle["image$i"];
              $imgPath = getImage($imgFile);
              if ($imgFile): ?>
                  <img src="<?= $imgPath ?>" class="thumb-img" onclick="changeImage(this.src)" alt="Thumbnail <?= $i ?>">
          <?php endif; endfor; ?>
        </div>

        <!-- Accordion -->
        <div class="accordion" id="vehicleAccordion">
          <!-- Specifications -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSpecs">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSpecs" aria-expanded="true">
                Specifications
              </button>
            </h2>
            <div id="collapseSpecs" class="accordion-collapse collapse show" data-bs-parent="#vehicleAccordion">
              <div class="accordion-body">
                <table class="table table-striped">
                  <tr><th>Brand</th><td><?= safe($vehicle['brand']) ?></td></tr>
                  <tr><th>Model</th><td><?= safe($vehicle['model']) ?></td></tr>
                  <tr><th>Variant</th><td><?= safe($vehicle['variant']) ?></td></tr>
                  <tr><th>Trim</th><td><?= safe($vehicle['trim']) ?></td></tr>
                  <tr><th>Year</th><td><?= safe($vehicle['year']) ?></td></tr>
                  <tr><th>VIN</th><td><?= safe($vehicle['vin']) ?></td></tr>
                  <tr><th>Registration No</th><td><?= safe($vehicle['registration_no']) ?></td></tr>
                  <tr><th>Color</th><td><?= safe($vehicle['color']) ?></td></tr>
                  <tr><th>Engine</th><td><?= safe($vehicle['engine']) ?></td></tr>
                  <tr><th>Horsepower</th><td><?= safe($vehicle['horsepower']) ?> HP</td></tr>
                  <tr><th>Torque</th><td><?= safe($vehicle['torque']) ?></td></tr>
                  <tr><th>Fuel</th><td><?= safe($vehicle['fuel']) ?></td></tr>
                  <tr><th>Fuel Capacity</th><td><?= safe($vehicle['fuel_capacity']) ?> L</td></tr>
                  <tr><th>Transmission</th><td><?= safe($vehicle['transmission']) ?></td></tr>
                  <tr><th>Drivetrain</th><td><?= safe($vehicle['drivetrain']) ?></td></tr>
                  <tr><th>Seats</th><td><?= safe($vehicle['seats']) ?></td></tr>
                  <tr><th>Doors</th><td><?= safe($vehicle['doors']) ?></td></tr>
                  <tr><th>Mileage</th><td><?= safe($vehicle['mileage']) ?> km</td></tr>
                  <tr><th>Condition</th><td><?= safe($vehicle['vehicle_condition']) ?></td></tr>
                  <tr><th>Owners</th><td><?= safe($vehicle['owners']) ?></td></tr>
                  <tr><th>Warranty</th><td><?= safe($vehicle['warranty']) ?></td></tr>
                  <tr><th>Insurance</th><td><?= safe($vehicle['insurance']) ?></td></tr>
                </table>
              </div>
            </div>
          </div>

          <!-- Features -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingFeatures">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFeatures">
                Features
              </button>
            </h2>
            <div id="collapseFeatures" class="accordion-collapse collapse" data-bs-parent="#vehicleAccordion">
              <div class="accordion-body">
                <?= nl2br(safe($vehicle['features'])) ?>
              </div>
            </div>
          </div>

          <!-- Description -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingDesc">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesc">
                Description
              </button>
            </h2>
            <div id="collapseDesc" class="accordion-collapse collapse" data-bs-parent="#vehicleAccordion">
              <div class="accordion-body">
                <?= nl2br(safe($vehicle['description'])) ?>
              </div>
            </div>
          </div>

          <!-- Seller Info -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSeller">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeller">
                Seller Information
              </button>
            </h2>
            <div id="collapseSeller" class="accordion-collapse collapse" data-bs-parent="#vehicleAccordion">
              <div class="accordion-body">
                <p><strong>Name:</strong> <?= safe($vehicle['seller_name']) ?></p>
                <p><strong>Email:</strong> <?= safe($vehicle['seller_email']) ?></p>
                <p><strong>Phone:</strong> <?= safe($vehicle['seller_phone']) ?></p>
                <p><strong>Address:</strong> <?= safe($vehicle['seller_address']) ?></p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Right Column: Inquiry Form -->
    <div class="col-lg-4">
      <div class="card shadow-lg p-4">
        <h4>Send Inquiry</h4>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?= $success_msg ?></div>
        <?php elseif ($error_msg): ?>
            <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Your Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Your Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">Send Inquiry</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function changeImage(src) {
      document.getElementById("mainImage").src = src;
  }
</script>

</body>
</html>
