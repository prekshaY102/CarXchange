<?php
// vehicles.php

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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM vehicles";
if ($search !== '') {
    $search_sql = $conn->real_escape_string($search);
    $sql .= " WHERE brand LIKE '%{$search_sql}%' OR model LIKE '%{$search_sql}%' OR year LIKE '%{$search_sql}%'";
}
$results = $conn->query($sql);

// Fetch vehicles from database
// $sql = "SELECT * FROM vehicles ORDER BY created_at DESC";
// $result = $conn->query($sql);

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h2>Available Vehicles</h2>
    <div class="vehicles-grid">
        <?php if ($results && $results->num_rows > 0): ?>
            <?php while ($row = $results->fetch_assoc()): ?>
                <div class="vehicle-card">
                    <?php 
                    // Select the first available image or placeholder
                    $image = !empty($row['image1']) ? $row['image1'] : 'images/car-placeholder.jpg'; 
                    ?>
                    <img src="<?= htmlspecialchars($image) ?>" 
                         alt="<?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?>" 
                         class="vehicle-image">

                    <div class="vehicle-info">
                        <h2><?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?></h2>
                        <p>Variant: <?= htmlspecialchars($row['variant']) ?> | Trim: <?= htmlspecialchars($row['trim']) ?></p>
                        <p>Year: <?= htmlspecialchars($row['year']) ?> | Color: <?= htmlspecialchars($row['color']) ?></p>
                        <p>Fuel: <?= htmlspecialchars($row['fuel']) ?> | Transmission: <?= htmlspecialchars($row['transmission']) ?></p>
                        <p>Seats: <?= htmlspecialchars($row['seats']) ?> | Condition: <?= htmlspecialchars($row['vehicle_condition']) ?></p>
                        <p class="price">
                            ₹<?= number_format($row['price'], 2) ?> <?= $row['negotiable'] ? '(Negotiable)' : '' ?>
                        </p>
                        <?php if (!empty($row['description'])): ?>
                            <p class="vehicle-description"><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</p>
                        <?php endif; ?>
                        <a href="vehicle_details.php?vehicle_id=<?= $row['id'] ?>" class="rent-button">Book Now</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No vehicles available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
