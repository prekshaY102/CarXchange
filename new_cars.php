<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

/* ------------------ Handle Favourites ------------------ */
if (isset($_GET['fav'])) {
    $vehicle_id = (int)$_GET['fav'];

    // Check if already in favourites
    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id=? AND vehicle_id=?");
    $check->bind_param("ii", $user_id, $vehicle_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Remove from favourites
        $del = $conn->prepare("DELETE FROM favorites WHERE user_id=? AND vehicle_id=?");
        $del->bind_param("ii", $user_id, $vehicle_id);
        $del->execute();
    } else {
        // Add to favourites
        $ins = $conn->prepare("INSERT INTO favorites (user_id, vehicle_id) VALUES (?, ?)");
        $ins->bind_param("ii", $user_id, $vehicle_id);
        $ins->execute();
    }

    // Redirect without fav param to avoid duplicate actions
    $redirectUrl = strtok($_SERVER["REQUEST_URI"], '?'); 
    $query = $_GET;
    unset($query['fav']);
    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }
    header("Location: " . $redirectUrl);
    exit;
}

/* ------------------ Build Filters ------------------ */
$where = ["v.vehicle_condition='New'"];
$params = [];
$types = "i"; // always include user_id first

if (!empty($_GET['brand'])) {
    $where[] = "v.brand = ?";
    $params[] = $_GET['brand'];
    $types .= "s";
}
if (!empty($_GET['fuel'])) {
    $where[] = "v.fuel = ?";
    $params[] = $_GET['fuel'];
    $types .= "s";
}
if (!empty($_GET['transmission'])) {
    $where[] = "v.transmission = ?";
    $params[] = $_GET['transmission'];
    $types .= "s";
}
if (!empty($_GET['min_price'])) {
    $where[] = "v.price >= ?";
    $params[] = (float)$_GET['min_price'];
    $types .= "d";
}
if (!empty($_GET['max_price'])) {
    $where[] = "v.price <= ?";
    $params[] = (float)$_GET['max_price'];
    $types .= "d";
}
if (!empty($_GET['max_mileage'])) {
    $where[] = "v.mileage <= ?";
    $params[] = (float)$_GET['max_mileage'];
    $types .= "d";
}

/* ------------------ Query Cars ------------------ */
$sql = "SELECT v.*, f.id AS fav_id
        FROM vehicles v
        LEFT JOIN favorites f ON f.vehicle_id = v.id AND f.user_id = ?
        WHERE " . implode(" AND ", $where) . "
        ORDER BY v.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL Error: " . $conn->error);

// Bind params: first user_id then filters
$bindValues = [$user_id];
if (!empty($params)) {
    $bindValues = array_merge([$user_id], $params);
    $stmt->bind_param($types, ...$bindValues);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$cars = [];
while ($row = $result->fetch_assoc()) {
    $cars[] = $row;
}

/* ------------------ Fetch Brands for Dropdown ------------------ */
$brands = [];
$res = $conn->query("SELECT DISTINCT brand FROM vehicles WHERE vehicle_condition='New' ORDER BY brand ASC");
while ($b = $res->fetch_assoc()) {
    $brands[] = $b['brand'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Cars | <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f8f9fa; }
.filter-box { background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.car-card img { height:200px; object-fit:cover; border-radius:8px 8px 0 0; }
.car-card { transition:0.3s ease; position:relative; }
.car-card:hover { transform:translateY(-5px); box-shadow:0 4px 15px rgba(0,0,0,0.2);}
.heart-btn { position:absolute; top:10px; right:10px; font-size:2rem; text-decoration:none; }
.heart-red { color:red; }
.heart-gray { color:#aaa; }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4 text-center">🚗 Browse New Cars</h2>
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-md-3">
            <div class="filter-box">
                <form method="GET" action="new_cars.php">
                    <div class="mb-3">
                        <label>Brand</label>
                        <select name="brand" class="form-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" <?= ($_GET['brand']??"")==$b?"selected":"" ?>>
                                    <?= htmlspecialchars($b) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Fuel</label>
                        <select name="fuel" class="form-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach(['Petrol','Diesel','Hybrid','Electric'] as $f): ?>
                                <option value="<?= $f ?>" <?= ($_GET['fuel']??"")==$f?"selected":"" ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Transmission</label>
                        <select name="transmission" class="form-select" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach(['Manual','Automatic','CVT','Dual-Clutch'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($_GET['transmission']??"")==$t?"selected":"" ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Price Range</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?= $_GET['min_price']??"" ?>">
                            <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?= $_GET['max_price']??"" ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Max Mileage (km)</label>
                        <input type="number" class="form-control" name="max_mileage" value="<?= $_GET['max_mileage']??"" ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <a href="new_cars.php" class="btn btn-secondary w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>

        <!-- Cars List -->
        <div class="col-md-9">
            <div class="row g-4">
                <?php if ($cars): ?>
                    <?php foreach ($cars as $car): 
                        $title = $car['brand']." ".$car['model'];
                        if ($car['variant']) $title .= " ".$car['variant'];
                        if ($car['trim']) $title .= " ".$car['trim'];
                        $img = (!empty($car['image1']) && file_exists(__DIR__."/".$car['image1']))
                            ? $car['image1']
                            : "assets/img/no-car.png";
                        $isFav = !empty($car['fav_id']);
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card car-card h-100">
                                <a href="?<?= http_build_query(array_merge($_GET,['fav'=>$car['id']])) ?>" 
                                   class="heart-btn <?= $isFav?'heart-red':'heart-gray' ?>">♥</a>
                                <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($title) ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($title) ?></h5>
                                    <p class="card-text text-muted">
                                        <?= $car['year'] ?> · <?= $car['fuel'] ?> · <?= $car['transmission'] ?><br>
                                        ₹<?= number_format($car['price']) ?> · <?= $car['mileage'] ?> km
                                    </p>
                                    <a href="vehicle_details.php?id=<?= $car['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">No new cars found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
