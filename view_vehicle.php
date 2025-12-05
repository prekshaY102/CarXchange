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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid vehicle ID.");
}

$vehicle_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $vehicle_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();

if (!$vehicle) {
    die("Vehicle not found or you don’t have permission to view it.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - <?php echo htmlspecialchars($vehicle['brand']." ".$vehicle['model']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .carousel-item img { height: 450px; object-fit: cover; border-radius: 12px; }
    .specs-table td { padding: 8px; vertical-align: middle; }
    .badge-custom { font-size: 0.8rem; }
    .price-tag { font-size: 1.8rem; font-weight: bold; color: #0d6efd; }
  </style>
</head>
<body>
<?php include './includes/header.php'; ?>

<div class="container my-5">
  <div class="row g-4">
    <!-- Left Column: Images -->
    <div class="col-lg-6">
      <div id="carouselVehicle" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          <?php 
            $images = [];
            for ($i = 1; $i <= 5; $i++) {
              if (!empty($vehicle["image$i"])) {
                $images[] = $vehicle["image$i"];
              }
            }
            if (empty($images)) {
              $images[] = "assets/images/no-car.png";
            }
            foreach ($images as $index => $img): 
          ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
              <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" alt="Vehicle Image">
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($images) > 1): ?>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselVehicle" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselVehicle" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Column: Info -->
    <div class="col-lg-6">
      <h2><?php echo htmlspecialchars($vehicle['brand']." ".$vehicle['model']." ".$vehicle['variant']); ?></h2>
      <p>
        <span class="price-tag">₹<?php echo number_format($vehicle['price'], 2); ?></span>
        <?php if ($vehicle['negotiable']): ?>
          <span class="badge bg-success badge-custom">Negotiable</span>
        <?php endif; ?>
        <?php if ($vehicle['emi_available']): ?>
          <span class="badge bg-warning text-dark badge-custom">EMI Available</span>
        <?php endif; ?>
      </p>
      <p class="text-muted"><?php echo nl2br(htmlspecialchars($vehicle['description'])); ?></p>

      <div class="mt-3">
        <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-outline-secondary">
          <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="delete_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this vehicle?');">
          <i class="bi bi-trash"></i> Delete
        </a>
      </div>
    </div>
  </div>

  <!-- Vehicle Specs -->
  <div class="mt-5">
    <h4>Specifications</h4>
    <table class="table table-bordered specs-table">
      <tbody>
        <tr><td><strong>Year</strong></td><td><?php echo $vehicle['year']; ?></td></tr>
        <tr><td><strong>Color</strong></td><td><?php echo htmlspecialchars($vehicle['color']); ?></td></tr>
        <tr><td><strong>Fuel</strong></td><td><?php echo $vehicle['fuel']; ?></td></tr>
        <tr><td><strong>Transmission</strong></td><td><?php echo $vehicle['transmission']; ?></td></tr>
        <tr><td><strong>Drivetrain</strong></td><td><?php echo htmlspecialchars($vehicle['drivetrain']); ?></td></tr>
        <tr><td><strong>Seats</strong></td><td><?php echo $vehicle['seats']; ?></td></tr>
        <tr><td><strong>Doors</strong></td><td><?php echo $vehicle['doors']; ?></td></tr>
        <tr><td><strong>Mileage</strong></td><td><?php echo $vehicle['mileage']; ?> km</td></tr>
        <tr><td><strong>Engine</strong></td><td><?php echo htmlspecialchars($vehicle['engine']); ?></td></tr>
        <tr><td><strong>Horsepower</strong></td><td><?php echo $vehicle['horsepower']; ?> HP</td></tr>
        <tr><td><strong>Torque</strong></td><td><?php echo htmlspecialchars($vehicle['torque']); ?></td></tr>
        <tr><td><strong>Condition</strong></td><td><?php echo $vehicle['condition']; ?></td></tr>
        <tr><td><strong>Owners</strong></td><td><?php echo $vehicle['owners']; ?></td></tr>
        <tr><td><strong>Warranty</strong></td><td><?php echo htmlspecialchars($vehicle['warranty']); ?></td></tr>
        <tr><td><strong>Insurance</strong></td><td><?php echo htmlspecialchars($vehicle['insurance']); ?></td></tr>
        <tr><td><strong>Features</strong></td><td><?php echo htmlspecialchars($vehicle['features']); ?></td></tr>
        <?php if (!empty($vehicle['video_url'])): ?>
          <tr>
            <td><strong>Video</strong></td>
            <td><a href="<?php echo htmlspecialchars($vehicle['video_url']); ?>" target="_blank">Watch Video</a></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include './includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
