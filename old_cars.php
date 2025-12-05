<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);

// --- Base query for old cars ---
$where = ["vehicle_condition IN ('Used','Certified Pre-Owned')"];
$params = [];
$types = "";

// --- Optional filters ---
if (!empty($_GET['brand'])) {
    $where[] = "brand = ?";
    $params[] = $_GET['brand'];
    $types .= "s";
}
if (!empty($_GET['fuel'])) {
    $where[] = "fuel = ?";
    $params[] = $_GET['fuel'];
    $types .= "s";
}
if (!empty($_GET['transmission'])) {
    $where[] = "transmission = ?";
    $params[] = $_GET['transmission'];
    $types .= "s";
}
if (!empty($_GET['min_price'])) {
    $where[] = "price >= ?";
    $params[] = (float)$_GET['min_price'];
    $types .= "d";
}
if (!empty($_GET['max_price'])) {
    $where[] = "price <= ?";
    $params[] = (float)$_GET['max_price'];
    $types .= "d";
}
if (!empty($_GET['max_mileage'])) {
    $where[] = "mileage <= ?";
    $params[] = (float)$_GET['max_mileage'];
    $types .= "d";
}

// --- Build and prepare query ---
$sql = "SELECT id, brand, model, variant, trim, year, fuel, transmission, mileage, price, image1, created_at
        FROM vehicles
        WHERE " . implode(" AND ", $where) . "
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) die("SQL error: " . $conn->error);

// Bind params if available
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- Fetch brands for dropdown ---
$brands = [];
$brandRes = $conn->query("SELECT DISTINCT brand FROM vehicles 
                          WHERE vehicle_condition IN ('Used','Certified Pre-Owned') 
                          AND brand IS NOT NULL AND brand <> '' 
                          ORDER BY brand ASC");
while ($row = $brandRes->fetch_assoc()) $brands[] = $row['brand'];

// --- Fetch user's favorites ---
$favorites = [];
if ($user_id) {
    $favRes = $conn->query("SELECT vehicle_id FROM favorites WHERE user_id = $user_id");
    while ($f = $favRes->fetch_assoc()) $favorites[] = (int)$f['vehicle_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Old Cars - Browse Listings | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  .filter-box {background: #fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
  .car-card img {height:200px; object-fit:cover; border-radius:8px 8px 0 0;}
  .car-card {transition:0.3s;}
  .car-card:hover {transform: translateY(-5px); box-shadow:0 4px 15px rgba(0,0,0,0.2);}
  .fav-btn {border:none; background:none; font-size:20px; cursor:pointer;}
  .fav-btn .fa-heart.fas {color:#e63946;}
  .fav-btn .fa-heart.far {color:#bbb;}
</style>
</head>
<body class="bg-light">

<?php include 'includes/header.php'; ?>
<div class="container py-5">
  <h2 class="mb-4 text-center">🚗 Browse Old Cars</h2>
  <div class="row">
    <div class="col-md-3">
      <div class="filter-box">
        <h5 class="mb-3">🔍 Filters</h5>
        <form method="GET" action="old_cars.php">
          <div class="mb-3">
            <label class="form-label">Brand</label>
            <select name="brand" class="form-select">
              <option value="">All</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= ($_GET['brand']??'')==$b ? 'selected':'' ?>><?= htmlspecialchars($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Fuel</label>
            <select name="fuel" class="form-select">
              <option value="">All</option>
              <?php foreach (['Petrol','Diesel','Hybrid','Electric'] as $fuel): ?>
                <option value="<?= $fuel ?>" <?= ($_GET['fuel']??'')===$fuel ? "selected":"" ?>><?= $fuel ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Transmission</label>
            <select name="transmission" class="form-select">
              <option value="">All</option>
              <?php foreach (['Manual','Automatic','CVT','Dual-Clutch'] as $tr): ?>
                <option value="<?= $tr ?>" <?= ($_GET['transmission']??'')===$tr ? "selected":"" ?>><?= $tr ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Price Range</label>
            <div class="input-group">
              <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?= $_GET['min_price'] ?? "" ?>">
              <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?= $_GET['max_price'] ?? "" ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Max Mileage (km)</label>
            <input type="number" class="form-control" name="max_mileage" value="<?= $_GET['max_mileage'] ?? "" ?>">
          </div>
          <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
          <a href="old_cars.php" class="btn btn-secondary w-100 mt-2">Reset</a>
        </form>
      </div>
    </div>

    <div class="col-md-9">
      <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($car = $result->fetch_assoc()): ?>
            <?php
              $title = trim($car['brand'].' '.$car['model'].''.$car['variant'].''.$car['trim']);
              $img = !empty($car['image1']) ? 'uploads/'.$car['image1'] : 'assets/img/no-car.png';
              $isFav = in_array((int)$car['id'], $favorites);
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="card car-card h-100">
                <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($title) ?>">
                <div class="card-body d-flex flex-column justify-content-between">
                  <div>
                    <h5 class="card-title"><?= htmlspecialchars($title) ?></h5>
                    <p class="card-text text-muted">
                      <?= $car['year'] ?> · <?= htmlspecialchars($car['fuel']) ?> · <?= htmlspecialchars($car['transmission']) ?><br>
                      ₹<?= number_format($car['price']) ?> · <?= $car['mileage'] ?> km
                    </p>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <a href="vehicle_details.php?id=<?= $car['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                    <button class="fav-btn" data-id="<?= $car['id'] ?>" data-fav="<?= $isFav ? '1' : '0' ?>">
                      <i class="<?= $isFav ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-center">No old cars found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on("click", ".fav-btn", function() {
    let btn = $(this);
    let icon = btn.find("i");
    let carId = btn.data("id");
    let isFav = btn.data("fav") == 1;
    let action = isFav ? "remove" : "add";

    $.post("favorite_action.php", {vehicle_id: carId, action: action}, function(res) {
        if (res === "login") alert("Please log in to manage favorites.");
        else if (res === "success") {
            icon.toggleClass("fas far");
            btn.data("fav", isFav ? 0 : 1);
        } else console.error("Error:", res);
    });
});
</script>
</body>
</html>
