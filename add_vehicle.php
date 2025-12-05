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

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = intval($_SESSION['user_id']);
    
    // Collect data
    $brand     = $conn->real_escape_string($_POST['brand']);
    $model     = $conn->real_escape_string($_POST['model']);
    $variant   = $conn->real_escape_string($_POST['variant']);
    $trim      = $conn->real_escape_string($_POST['trim']);
    $year      = intval($_POST['year']);
    $vin       = $conn->real_escape_string($_POST['vin']);
    $reg_no    = $conn->real_escape_string($_POST['registration']);
    $color     = $conn->real_escape_string($_POST['color']);

    $engine    = $conn->real_escape_string($_POST['engine']);
    $hp        = intval($_POST['horsepower']);
    $torque    = $conn->real_escape_string($_POST['torque']);
    $fuel      = $conn->real_escape_string($_POST['fuel']);
    $capacity  = floatval($_POST['fuel_capacity']);
    $transmission = $conn->real_escape_string($_POST['transmission']);
    $drivetrain= $conn->real_escape_string($_POST['drivetrain']);
    $seats     = intval($_POST['seats']);
    $doors     = intval($_POST['doors']);
    $mileage   = floatval($_POST['mileage']);

    $condition = $conn->real_escape_string($_POST['condition']);
    $owners    = intval($_POST['owners']);
    $warranty  = $conn->real_escape_string($_POST['warranty']);
    $insurance = $conn->real_escape_string($_POST['insurance']);

    $price     = floatval($_POST['price']);
    $negotiable= isset($_POST['negotiable']) ? 1 : 0;
    $emi       = isset($_POST['emi']) ? 1 : 0;

    $features  = isset($_POST['features']) ? implode(", ", $_POST['features']) : "";
    $description = $conn->real_escape_string($_POST['description']);
    $video_url   = $conn->real_escape_string($_POST['video']);

    // Upload up to 5 images (separate file inputs)
    $targetDir = "uploads/vehicles/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $images = ["", "", "", "", ""]; // placeholders

    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_FILES["image$i"]["name"])) {
            $fileTmp  = $_FILES["image$i"]["tmp_name"];
            $fileName = $_FILES["image$i"]["name"];
            $safeName = time() . "_{$i}_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($fileName));
            $targetFile = $targetDir . $safeName;

            if (move_uploaded_file($fileTmp, $targetFile)) {
                $images[$i-1] = $targetFile;
            }
        }
    }

    // Insert query
    $sql = "INSERT INTO vehicles 
        (user_id, brand, model, variant, trim, year, vin, registration_no, color,
        engine, horsepower, torque, fuel, fuel_capacity, transmission, drivetrain, seats, doors, mileage,
        vehicle_condition, owners, warranty, insurance,
        price, negotiable, emi_available,
        features, description, video_url,
        image1, image2, image3, image4, image5, created_at)
        VALUES
        ($user_id, '$brand', '$model', '$variant', '$trim', $year, '$vin', '$reg_no', '$color',
        '$engine', $hp, '$torque', '$fuel', $capacity, '$transmission', '$drivetrain', $seats, $doors, $mileage,
        '$condition', $owners, '$warranty', '$insurance',
        $price, $negotiable, $emi,
        '$features', '$description', '$video_url',
        '{$images[0]}', '{$images[1]}', '{$images[2]}', '{$images[3]}', '{$images[4]}', NOW())";

    if ($conn->query($sql)) {
        $success = "✅ Vehicle added successfully!";
    } else {
        $error = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - Add Vehicle</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .form-section {
        background: #fff;
        padding: 2rem;
        border-radius: .75rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    .section-title {
        border-bottom: 2px solid #eee;
        padding-bottom: .5rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
        color: #0d6efd;
    }
  </style>
</head>
<body>

<?php include './includes/header.php'; ?>

<div class="container my-5" style="max-width: 1000px;">
    <h2 class="mb-4 text-center">🚘 Add a New Vehicle</h2>

    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- Basic Info -->
        <div class="form-section">
            <h4 class="section-title">Basic Information</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Model</label><input type="text" name="model" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Variant</label><input type="text" name="variant" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Trim</label><input type="text" name="trim" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Year</label><input type="number" name="year" class="form-control" min="1900" max="2099" required></div>
                <div class="col-md-3"><label class="form-label">VIN</label><input type="text" name="vin" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Registration No</label><input type="text" name="registration" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Color</label><input type="text" name="color" class="form-control"></div>
            </div>
        </div>

        <!-- Technical Specs -->
        <div class="form-section">
            <h4 class="section-title">Technical Specifications</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Engine</label><input type="text" name="engine" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">HP</label><input type="number" name="horsepower" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Torque</label><input type="text" name="torque" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Fuel</label>
                    <select name="fuel" class="form-select"><option>Petrol</option><option>Diesel</option><option>Hybrid</option><option>Electric</option></select>
                </div>
                <div class="col-md-2"><label class="form-label">Fuel Capacity</label><input type="number" step="0.01" name="fuel_capacity" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Transmission</label>
                    <select name="transmission" class="form-select"><option>Manual</option><option>Automatic</option><option>CVT</option><option>Dual-Clutch</option></select>
                </div>
                <div class="col-md-3"><label class="form-label">Drivetrain</label><input type="text" name="drivetrain" class="form-control" placeholder="FWD, RWD, AWD"></div>
                <div class="col-md-2"><label class="form-label">Seats</label><input type="number" name="seats" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Doors</label><input type="number" name="doors" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Mileage (km)</label><input type="number" step="0.01" name="mileage" class="form-control"></div>
            </div>
        </div>

        <!-- Ownership -->
        <div class="form-section">
            <h4 class="section-title">Ownership & Condition</h4>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Condition</label>
                    <select name="condition" class="form-select"><option>New</option><option>Used</option><option>Certified Pre-Owned</option></select>
                </div>
                <div class="col-md-3"><label class="form-label">No. of Owners</label><input type="number" name="owners" class="form-control" min="1"></div>
                <div class="col-md-3"><label class="form-label">Warranty</label><input type="text" name="warranty" class="form-control" placeholder="e.g. 2 Years"></div>
                <div class="col-md-3"><label class="form-label">Insurance</label><input type="text" name="insurance" class="form-control" placeholder="Valid till..."></div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="form-section">
            <h4 class="section-title">Pricing & Availability</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Price ($)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="negotiable" value="1"> <label class="form-check-label">Negotiable</label></div></div>
                <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="emi" value="1"> <label class="form-check-label">EMI</label></div></div>
            </div>
        </div>

        <!-- Features -->
        <div class="form-section">
            <h4 class="section-title">Features</h4>
            <div class="row">
                <div class="col-md-3">
                    <h6>Safety</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Airbags"> Airbags</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="ABS"> ABS</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="ESP"> ESP</div>
                </div>
                <div class="col-md-3">
                    <h6>Interior</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Leather Seats"> Leather Seats</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Sunroof"> Sunroof</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Climate Control"> Climate Control</div>
                </div>
                <div class="col-md-3">
                    <h6>Entertainment</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Touchscreen"> Touchscreen</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Bluetooth"> Bluetooth</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Premium Sound"> Premium Sound</div>
                </div>
                <div class="col-md-3">
                    <h6>Exterior</h6>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Alloy Wheels"> Alloy Wheels</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="Fog Lights"> Fog Lights</div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="LED Headlights"> LED Headlights</div>
                </div>
            </div>
        </div>

         <!-- Media -->
        <div class="form-section">
            <h4 class="section-title">Media</h4>
            <label class="form-label">Upload Images (5 separate)</label>
            <input type="file" name="image1" class="form-control mb-2" accept="image/*">
            <input type="file" name="image2" class="form-control mb-2" accept="image/*">
            <input type="file" name="image3" class="form-control mb-2" accept="image/*">
            <input type="file" name="image4" class="form-control mb-2" accept="image/*">
            <input type="file" name="image5" class="form-control mb-3" accept="image/*">

            <label class="form-label">Video URL (YouTube)</label>
            <input type="url" name="video" class="form-control" placeholder="https://youtube.com/...">
        </div>
        <!-- Description -->
        <div class="form-section">
            <h4 class="section-title">Description</h4>
            <textarea name="description" class="form-control" rows="6" placeholder="Write a detailed description of your car"></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Submit Vehicle</button>
    </form>
</div>

<?php include './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
