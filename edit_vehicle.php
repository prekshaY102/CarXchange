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

// Get vehicle ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_vehicles.php");
    exit;
}
$vehicle_id = intval($_GET['id']);
$user_id    = intval($_SESSION['user_id']);

// Fetch vehicle
$vehicle = $conn->query("SELECT * FROM vehicles WHERE id=$vehicle_id AND user_id=$user_id")->fetch_assoc();
if (!$vehicle) {
    $error = "❌ Vehicle not found or access denied!";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $vehicle) {

    // Collect updated data
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

    // Handle image updates
    $targetDir = "uploads/vehicles/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $images = [
        $vehicle['image1'], $vehicle['image2'], $vehicle['image3'],
        $vehicle['image4'], $vehicle['image5']
    ];

    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_FILES["image$i"]["name"])) {
            $fileTmp  = $_FILES["image$i"]["tmp_name"];
            $fileName = $_FILES["image$i"]["name"];
            $safeName = time() . "_{$i}_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($fileName));
            $targetFile = $targetDir . $safeName;

            if (move_uploaded_file($fileTmp, $targetFile)) {
                // Delete old image if exists
                if (!empty($images[$i-1]) && file_exists($images[$i-1])) {
                    unlink($images[$i-1]);
                }
                $images[$i-1] = $targetFile;
            }
        }
    }

    // Update query
    $sql = "UPDATE vehicles SET 
        brand='$brand', model='$model', variant='$variant', trim='$trim', year=$year, vin='$vin', registration_no='$reg_no', color='$color',
        engine='$engine', horsepower=$hp, torque='$torque', fuel='$fuel', fuel_capacity=$capacity, transmission='$transmission', drivetrain='$drivetrain',
        seats=$seats, doors=$doors, mileage=$mileage,
        vehicle_condition='$condition', owners=$owners, warranty='$warranty', insurance='$insurance',
        price=$price, negotiable=$negotiable, emi_available=$emi,
        features='$features', description='$description', video_url='$video_url',
        image1='{$images[0]}', image2='{$images[1]}', image3='{$images[2]}', image4='{$images[3]}', image5='{$images[4]}'
        WHERE id=$vehicle_id AND user_id=$user_id";

    if ($conn->query($sql)) {
        $success = "✅ Vehicle updated successfully!";
        // Refresh vehicle data
        $vehicle = $conn->query("SELECT * FROM vehicles WHERE id=$vehicle_id AND user_id=$user_id")->fetch_assoc();
    } else {
        $error = "❌ Error updating vehicle: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - Edit Vehicle</title>
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
    .img-preview { max-height: 150px; margin-top: 5px; display: block; }
  </style>
</head>
<body>

<?php include './includes/header.php'; ?>

<div class="container my-5" style="max-width: 1000px;">
    <h2 class="mb-4 text-center">✏️ Edit Vehicle</h2>

    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($vehicle): ?>
    <form method="POST" enctype="multipart/form-data">

        <!-- Basic Info -->
        <div class="form-section">
            <h4 class="section-title">Basic Information</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Brand</label><input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($vehicle['brand']); ?>" required></div>
                <div class="col-md-4"><label class="form-label">Model</label><input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required></div>
                <div class="col-md-4"><label class="form-label">Variant</label><input type="text" name="variant" class="form-control" value="<?php echo htmlspecialchars($vehicle['variant']); ?>"></div>
                <div class="col-md-4"><label class="form-label">Trim</label><input type="text" name="trim" class="form-control" value="<?php echo htmlspecialchars($vehicle['trim']); ?>"></div>
                <div class="col-md-2"><label class="form-label">Year</label><input type="number" name="year" class="form-control" value="<?php echo $vehicle['year']; ?>" required></div>
                <div class="col-md-3"><label class="form-label">VIN</label><input type="text" name="vin" class="form-control" value="<?php echo htmlspecialchars($vehicle['vin']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Registration No</label><input type="text" name="registration" class="form-control" value="<?php echo htmlspecialchars($vehicle['registration_no']); ?>"></div>
                <div class="col-md-2"><label class="form-label">Color</label><input type="text" name="color" class="form-control" value="<?php echo htmlspecialchars($vehicle['color']); ?>"></div>
            </div>
        </div>

        <!-- Technical Specs -->
        <div class="form-section">
            <h4 class="section-title">Technical Specifications</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Engine</label><input type="text" name="engine" class="form-control" value="<?php echo htmlspecialchars($vehicle['engine']); ?>"></div>
                <div class="col-md-2"><label class="form-label">HP</label><input type="number" name="horsepower" class="form-control" value="<?php echo $vehicle['horsepower']; ?>"></div>
                <div class="col-md-2"><label class="form-label">Torque</label><input type="text" name="torque" class="form-control" value="<?php echo htmlspecialchars($vehicle['torque']); ?>"></div>
                <div class="col-md-2"><label class="form-label">Fuel</label>
                    <select name="fuel" class="form-select">
                        <?php foreach (["Petrol","Diesel","Hybrid","Electric"] as $f): ?>
                        <option <?php if($vehicle['fuel']==$f) echo "selected"; ?>><?php echo $f; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Fuel Capacity</label><input type="number" step="0.01" name="fuel_capacity" class="form-control" value="<?php echo $vehicle['fuel_capacity']; ?>"></div>
                <div class="col-md-3"><label class="form-label">Transmission</label>
                    <select name="transmission" class="form-select">
                        <?php foreach (["Manual","Automatic","CVT","Dual-Clutch"] as $t): ?>
                        <option <?php if($vehicle['transmission']==$t) echo "selected"; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Drivetrain</label><input type="text" name="drivetrain" class="form-control" value="<?php echo htmlspecialchars($vehicle['drivetrain']); ?>"></div>
                <div class="col-md-2"><label class="form-label">Seats</label><input type="number" name="seats" class="form-control" value="<?php echo $vehicle['seats']; ?>"></div>
                <div class="col-md-2"><label class="form-label">Doors</label><input type="number" name="doors" class="form-control" value="<?php echo $vehicle['doors']; ?>"></div>
                <div class="col-md-2"><label class="form-label">Mileage (km)</label><input type="number" step="0.01" name="mileage" class="form-control" value="<?php echo $vehicle['mileage']; ?>"></div>
            </div>
        </div>

        <!-- Ownership -->
        <div class="form-section">
            <h4 class="section-title">Ownership & Condition</h4>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Condition</label>
                    <select name="condition" class="form-select">
                        <?php foreach (["New","Used","Certified Pre-Owned"] as $c): ?>
                        <option <?php if($vehicle['vehicle_condition']==$c) echo "selected"; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">No. of Owners</label><input type="number" name="owners" class="form-control" value="<?php echo $vehicle['owners']; ?>"></div>
                <div class="col-md-3"><label class="form-label">Warranty</label><input type="text" name="warranty" class="form-control" value="<?php echo htmlspecialchars($vehicle['warranty']); ?>"></div>
                <div class="col-md-3"><label class="form-label">Insurance</label><input type="text" name="insurance" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance']); ?>"></div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="form-section">
            <h4 class="section-title">Pricing & Availability</h4>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Price ($)</label><input type="number" step="0.01" name="price" class="form-control" value="<?php echo $vehicle['price']; ?>" required></div>
                <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="negotiable" value="1" <?php if($vehicle['negotiable']) echo "checked"; ?>> <label class="form-check-label">Negotiable</label></div></div>
                <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="emi" value="1" <?php if($vehicle['emi_available']) echo "checked"; ?>> <label class="form-check-label">EMI</label></div></div>
            </div>
        </div>

        <!-- Features -->
        <div class="form-section">
            <h4 class="section-title">Features</h4>
            <?php $selected_features = explode(", ", $vehicle['features']); ?>
            <div class="row">
                <div class="col-md-3">
                    <h6>Safety</h6>
                    <?php foreach(["Airbags","ABS","ESP"] as $f): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $f; ?>" <?php if(in_array($f, $selected_features)) echo "checked"; ?>> <?php echo $f; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-3">
                    <h6>Interior</h6>
                    <?php foreach(["Leather Seats","Sunroof","Climate Control"] as $f): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $f; ?>" <?php if(in_array($f, $selected_features)) echo "checked"; ?>> <?php echo $f; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-3">
                    <h6>Entertainment</h6>
                    <?php foreach(["Touchscreen","Bluetooth","Premium Sound"] as $f): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $f; ?>" <?php if(in_array($f, $selected_features)) echo "checked"; ?>> <?php echo $f; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-3">
                    <h6>Exterior</h6>
                    <?php foreach(["Alloy Wheels","Fog Lights","LED Headlights"] as $f): ?>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="<?php echo $f; ?>" <?php if(in_array($f, $selected_features)) echo "checked"; ?>> <?php echo $f; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Media -->
        <div class="form-section">
    <h4 class="section-title">Media</h4>
    <label class="form-label">Upload Images (replace to update)</label>

    <?php for ($i = 1; $i <= 5; $i++): ?>
        <?php 
            $col = "image$i"; 
            $imgPath = !empty($vehicle[$col]) ? "/" . htmlspecialchars($vehicle[$col]) : "";
        ?>
        <div class="mb-3">
            <input type="file" name="image<?php echo $i; ?>" class="form-control mb-2" accept="image/*">
            <?php if (!empty($vehicle[$col])): ?>
                <div class="mt-2">
                    <img src="<?php echo $imgPath; ?>" alt="Image <?php echo $i; ?>" style="max-height:120px; border-radius:8px;">
                    <p class="small text-muted mb-0"><?php echo basename($vehicle[$col]); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endfor; ?>

            <label class="form-label">Video URL (YouTube)</label>
            <input type="url" name="video" class="form-control" value="<?php echo htmlspecialchars($vehicle['video_url']); ?>">
        </div>

        <!-- Description -->
        <div class="form-section">
            <h4 class="section-title">Description</h4>
            <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Update Vehicle</button>
    </form>
    <?php endif; ?>
</div>

<?php include './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
