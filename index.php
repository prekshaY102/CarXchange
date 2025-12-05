<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$searchCondition = '';
if ($search !== '') {
  $searchEscaped = $conn->real_escape_string($search);
  $searchCondition = " AND (brand LIKE '%{$searchEscaped}%' OR model LIKE '%{$searchEscaped}%' OR variant LIKE '%{$searchEscaped}%' OR year LIKE '%{$searchEscaped}%')";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - Buy & Sell Cars</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f8f9fa;
    }

    .hero-carousel img {
      height: 80vh;
      object-fit: cover;
      filter: brightness(0.6);
    }

    .hero-content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: #fff;
      z-index: 10;
    }

    .search-box {
      display: none;
      margin-top: 1.5rem;
    }

    .card img {
      height: 220px;
      object-fit: cover;
    }

    .section-heading {
      font-weight: 700;
      margin-bottom: 2rem;
    }
  </style>
</head>

<body>

  <?php include './includes/header.php'; ?>

  <!-- Hero Carousel -->
  <div id="heroCarousel" class="carousel slide position-relative" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="https://images.unsplash.com/photo-1503736334956-4c8f8e92946d" class="d-block w-100" alt="Cars">
      </div>
      <div class="carousel-item">
        <img src="https://images.unsplash.com/photo-1502877338535-766e1452684a" class="d-block w-100" alt="Cars">
      </div>
      <div class="carousel-item">
        <img src="https://images.unsplash.com/photo-1502877338535-766e1452684a" class="d-block w-100" alt="Cars">
      </div>
    </div>

    <!-- Overlay Content -->
    <div class="hero-content text-center">
      <h1 class="fw-bold display-4"><?php echo APP_NAME; ?></h1>
      <p class="lead">Your trusted marketplace to buy, sell & manage vehicles.</p>

      <!-- Start Now button -->
      <button id="startNowBtn" class="btn btn-warning btn-lg">🚗 Start Now</button>

      <!-- Hidden search box (only shown if logged in) -->
      <form class="search-box d-flex justify-content-center mt-3" id="searchBox" action="vehicles.php" method="get"
        style="display:none;">
        <input class="form-control w-50 me-2" type="search" placeholder="Search cars by model, brand, year"
          name="search">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
      </form>
    </div>

    <!-- Carousel controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>
  </div>

  <!-- Featured Vehicles with Tabs -->
  <section class="py-5">
    <div class="container">
      <h2 class="section-heading text-center">Featured Vehicles</h2>

      <!-- Nav Tabs -->
      <ul class="nav nav-pills justify-content-center mb-4" id="carTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="new-tab" data-bs-toggle="pill" data-bs-target="#newCars" type="button"
            role="tab">New Cars</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="others-tab" data-bs-toggle="pill" data-bs-target="#otherCars" type="button"
            role="tab">Old Cars</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="old-tab" data-bs-toggle="pill" data-bs-target="#oldCars" type="button"
            role="tab">My Cars</button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="carTabsContent">

        <!-- New Cars -->
        <div class="tab-pane fade show active" id="newCars" role="tabpanel">
          <div class="row g-4">
            <?php
            if (isset($_SESSION['user_id'])) {
              $uid = intval($_SESSION['user_id']);

              // Fetch latest 3 new cars not posted by the current user (with search)
              $cars = $conn->query("
        SELECT id, brand, model, variant, trim, price, mileage, transmission, image1
        FROM vehicles
        WHERE user_id != $uid AND vehicle_condition = 'New' $searchCondition
        ORDER BY created_at DESC
        LIMIT 3
      ");

              if ($cars && $cars->num_rows > 0) {
                while ($car = $cars->fetch_assoc()) {
                  // Build car title
                  $title = trim($car['brand'] . ' ' . $car['model'] . ' ' . $car['variant'] . ' ' . $car['trim']);
                  if (empty($title))
                    $title = "Car #" . $car['id'];

                  // Determine image path
                  $img = 'assets/img/no-car.png';
                  if (!empty($car['image1'])) {
                    if (preg_match('/^https?:\/\//', $car['image1'])) {
                      $img = $car['image1'];
                    } elseif (strpos($car['image1'], 'uploads/') === 0) {
                      $img = $car['image1'];
                    } else {
                      $img = 'uploads/' . $car['image1'];
                    }
                  }

                  // Output card
                  echo "
          <div class='col-lg-4 col-md-6'>
            <div class='card shadow-sm h-100'>
              <img src='{$img}' class='card-img-top' alt='" . htmlspecialchars($title) . "'>
              <div class='card-body'>
                <h5 class='card-title'>" . htmlspecialchars($title) . "</h5>
                <p class='card-text text-muted'>
                  ₹" . number_format($car['price']) . " · " . htmlspecialchars($car['transmission']) . " · " . htmlspecialchars($car['mileage']) . " km
                </p>
                <a href='vehicle_details.php?id={$car['id']}' class='btn btn-primary btn-sm'>View Details</a>
              </div>
            </div>
          </div>";
                }

                // Add View More link
                echo "<div class='col-12 text-center mt-3'>
          <a href='new_cars.php" . ($search ? "?search=" . urlencode($search) : "") . "' class='btn btn-outline-primary'>View More New Cars</a>
        </div>";
              } else {
                echo "<p class='text-center'>No new cars available" . ($search ? " matching your search" : "") . ".</p>";
              }
            }
            ?>
          </div>
        </div>

        <!-- Other Users' Cars (Old/Used) -->
        <div class="tab-pane fade" id="otherCars" role="tabpanel">
          <div class="row g-4">
            <?php
            if (isset($_SESSION['user_id'])) {
              $uid = intval($_SESSION['user_id']);
              $otherCars = $conn->query("
        SELECT id, brand, model, variant, trim, price, mileage, transmission, image1 
        FROM vehicles 
        WHERE user_id != $uid AND vehicle_condition = 'used' $searchCondition
        ORDER BY created_at DESC 
        LIMIT 3
      ");

              if ($otherCars && $otherCars->num_rows > 0) {
                while ($car = $otherCars->fetch_assoc()) {
                  $title = $car['brand'] . ' ' . $car['model'];
                  if (!empty($car['variant']))
                    $title .= ' ' . $car['variant'];
                  if (!empty($car['trim']))
                    $title .= ' ' . $car['trim'];

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

                  echo "
        <div class='col-lg-4 col-md-6'>
          <div class='card shadow-sm h-100'>
            <img src='$img' class='card-img-top' alt='$title'>
            <div class='card-body'>
              <h5 class='card-title'>$title</h5>
              <p class='card-text text-muted'>
                ₹" . number_format($car['price']) . " · {$car['transmission']} · {$car['mileage']} km
              </p>
              <a href='vehicle_details.php?id={$car['id']}' class='btn btn-primary btn-sm'>View Details</a>
            </div>
          </div>
        </div>";
                }

                // Add View More link
                echo "<div class='col-12 text-center mt-3'>
            <a href='old_cars.php" . ($search ? "?search=" . urlencode($search) : "") . "' class='btn btn-outline-primary'>View More Old Cars</a>
          </div>";
              } else {
                echo "<p class='text-center'>No old cars available" . ($search ? " matching your search" : "") . ".</p>";
              }
            } else {
              echo "<p class='text-center'>Please <a href='login.php'>log in</a> to see other users' cars.</p>";
            }
            ?>
          </div>
        </div>

        <!-- My Cars -->
        <div class="tab-pane fade" id="oldCars" role="tabpanel">
          <div class="row g-4">
            <?php
            if (isset($_SESSION['user_id'])) {
              $uid = intval($_SESSION['user_id']);

              $myCars = $conn->query("
        SELECT id, brand, model, variant, trim, price, mileage, transmission, image1 
        FROM vehicles 
        WHERE user_id=$uid $searchCondition
        ORDER BY created_at DESC 
        LIMIT 3
      ");

              if ($myCars && $myCars->num_rows > 0) {
                while ($car = $myCars->fetch_assoc()) {
                  $title = $car['brand'] . ' ' . $car['model'];
                  if (!empty($car['variant']))
                    $title .= ' ' . $car['variant'];
                  if (!empty($car['trim']))
                    $title .= ' ' . $car['trim'];

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

                  echo "
        <div class='col-lg-4 col-md-6'>
          <div class='card shadow-sm h-100'>
            <img src='$img' class='card-img-top' alt='$title'>
            <div class='card-body'>
              <h5 class='card-title'>$title</h5>
              <p class='card-text text-muted'>
                ₹" . number_format($car['price']) . " · {$car['transmission']} · {$car['mileage']} km
              </p>
              <a href='vehicle_details.php?id={$car['id']}' class='btn btn-primary btn-sm'>View Details</a>
              <a href='edit_vehicle.php?id={$car['id']}' class='btn btn-warning btn-sm ms-2'>Edit</a>
              <a href='delete_vehicle.php?id={$car['id']}' onclick='return confirm(\"Delete this car?\");' class='btn btn-danger btn-sm ms-2'>Delete</a>
            </div>
          </div>
        </div>";
                }

                // Add View More link
                echo "<div class='col-12 text-center mt-3'>
            <a href='my_listings.php" . ($search ? "?search=" . urlencode($search) : "") . "' class='btn btn-outline-primary'>View More My Cars</a>
          </div>";
              } else {
                echo "<p class='text-center'>You haven't added any cars" . ($search ? " matching your search" : "") . " yet.</p>";
              }
            } else {
              echo "<p class='text-center'>Please <a href='login.php'>log in</a> to see your cars.</p>";
            }
            ?>
          </div>
        </div>


      </div>
    </div>
  </section>

  <!-- Blog/News -->
  <section class="py-5 bg-light">
    <div class="container">
      <h2 class="section-heading text-center">Latest from Auto World</h2>
      <div class="row g-4 mt-3">
        <div class="col-md-4">
          <div class="card shadow-sm"><img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70"
              class="card-img-top">
            <div class="card-body">
              <h5>Top 10 Cars of 2025</h5>
              <p>See which cars made the list this year.</p><a href="#" class="btn btn-link">Read More</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm"><img src="https://images.unsplash.com/photo-1511919884226-fd3cad34687c"
              class="card-img-top">
            <div class="card-body">
              <h5>Electric Cars Rising</h5>
              <p>Why EVs are becoming more popular worldwide.</p><a href="#" class="btn btn-link">Read More</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm"><img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d"
              class="card-img-top">
            <div class="card-body">
              <h5>Tips for Used Car Buyers</h5>
              <p>How to check if a car is worth the price.</p><a href="#" class="btn btn-link">Read More</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- App Download CTA -->
  <section class="py-5 text-center bg-dark text-light">
    <div class="container">
      <h2 class="mb-3">Get the App</h2>
      <p class="mb-4">Buy, sell & browse vehicles on the go with our mobile app.</p>
      <a href="#" class="btn btn-warning btn-lg me-2"><i class="bi bi-apple"></i> App Store</a>
      <a href="#" class="btn btn-success btn-lg"><i class="bi bi-android2"></i> Google Play</a>
    </div>
  </section>

  <?php include './includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById("startNowBtn").addEventListener("click", function () {
      <?php if (!isset($_SESSION['user_id'])) { ?>
        window.location.href = "login.php";
      <?php } else { ?>
        let box = document.getElementById("searchBox");
        box.style.display = "flex";
        this.style.display = "none";
        box.scrollIntoView({ behavior: "smooth" });
      <?php } ?>
    });
  </script>
</body>

</html>