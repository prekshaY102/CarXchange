<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
} else {
    echo "<div class='alert alert-danger'>User not logged in.</div>";
    exit;
}

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    $check = $conn->prepare("SELECT id FROM vehicles WHERE id=? AND user_id=?");
    $check->bind_param("ii", $delete_id, $user_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM vehicles WHERE id=? AND user_id=?");
        $delete->bind_param("ii", $delete_id, $user_id);
        $delete->execute();
        $delete->close();
        echo "<div class='alert alert-success'>Vehicle deleted successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>Invalid delete request.</div>";
    }
    $check->close();
}

$sql = "SELECT * FROM vehicles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vehicles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Include header
include 'includes/header.php';
?>

<!-- Bootstrap container starts -->
<div class="container mt-5">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Vehicle Listings</h2>
    <a href="add_vehicle.php?user_id=<?php echo $user_id; ?>" class="btn btn-success">
      <i class="bi bi-plus-circle"></i> Add New Vehicle
    </a>
  </div>

  <?php if (empty($vehicles)): ?>
    <div class="alert alert-info">
      No vehicles found for this user. <a href="add_vehicle.php?user_id=<?php echo $user_id; ?>">Add your first vehicle</a>.
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($vehicles as $car): ?>
        <div class="col-md-4 mb-4">
          <div class="card shadow-sm h-100">

            <!-- Carousel for multiple images -->
            <div id="carousel-<?php echo $car['id']; ?>" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php
                $images = [];
                for ($i=1; $i<=5; $i++) { 
                    if (!empty($car["image$i"])) $images[] = $car["image$i"];
                }
                if (empty($images)) $images[] = 'uploads/default-car.jpg';
                foreach ($images as $index => $img):
                ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                  <img src="<?php echo htmlspecialchars($img); ?>" class="d-block w-100" style="height:200px; object-fit:cover;" alt="Car Image">
                </div>
                <?php endforeach; ?>
              </div>
              <?php if (count($images) > 1): ?>
              <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $car['id']; ?>" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $car['id']; ?>" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
              </button>
              <?php endif; ?>
            </div>

            <div class="card-body">
              <h5 class="card-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h5>
              <p class="card-text">
                Year: <?php echo htmlspecialchars($car['year']); ?><br>
                Fuel: <?php echo htmlspecialchars($car['fuel']); ?><br>
                Price: ₹<?php echo number_format($car['price'], 2); ?>
              </p>
              <div class="d-flex justify-content-between">
                <a href="vehicle_details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">View</a>
                <a href="edit_vehicle.php?id=<?php echo $car['id']; ?>&user_id=<?php echo $user_id; ?>" class="btn btn-warning btn-sm">Edit</a>
                <a href="my_listings.php?user_id=<?php echo $user_id; ?>&delete_id=<?php echo $car['id']; ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Are you sure you want to delete this vehicle?');">Delete</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<!-- Bootstrap container ends -->

<?php include 'includes/footer.php'; ?>
