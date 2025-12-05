<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$car_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// ✅ First fetch car details (only if it belongs to this user)
$stmt = $conn->prepare("SELECT id, brand, model, variant, trim, price, image1 FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $car_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Car not found or you don’t have permission to delete it.");
}

$car = $result->fetch_assoc();

// ✅ If user confirmed deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $del = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $car_id, $user_id);
    if ($del->execute()) {
        header("Location: my_listings.php?msg=deleted");
        exit;
    } else {
        echo "Error deleting vehicle.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete Vehicle</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include './includes/header.php'; ?>

<div class="container py-5">
  <div class="card shadow-sm">
    <div class="card-body text-center">
      <h3 class="mb-3 text-danger">Delete Vehicle</h3>
      <p>Are you sure you want to delete this vehicle?</p>

      <div class="mb-3">
        <?php
        $title = $car['brand'] . " " . $car['model'];
        if (!empty($car['variant'])) $title .= " " . $car['variant'];
        if (!empty($car['trim'])) $title .= " " . $car['trim'];

        $img = "assets/img/no-car.png";
        if (!empty($car['image1'])) {
            if (preg_match('/^https?:\/\//', $car['image1'])) {
                $img = $car['image1'];
            } elseif (strpos($car['image1'], 'uploads/') === 0) {
                $img = $car['image1'];
            } else {
                $img = "uploads/" . $car['image1'];
            }
        }
        ?>
        <img src="<?= $img ?>" alt="<?= htmlspecialchars($title) ?>" class="img-fluid rounded mb-2" style="max-height:200px;">
        <h5><?= htmlspecialchars($title) ?></h5>
        <p class="text-muted">$<?= number_format($car['price']) ?></p>
      </div>

      <form method="post">
        <button type="submit" name="confirm_delete" class="btn btn-danger">Yes, Delete</button>
        <a href="my_listings.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>
    <?php include './includes/footer.php'; ?>
</body>
</html>
