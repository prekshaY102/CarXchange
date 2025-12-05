<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details from DB (MySQLi)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name, email, phone, role, created_at, address, dob, gender, bio 
                        FROM users 
                        WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - <?php echo htmlspecialchars($user['name']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <?php include './includes/header.php'; ?> <!-- navbar -->

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body text-center p-5">
            
            <!-- Profile Icon -->
            <div class="mb-3">
              <i class="bi bi-person-circle" style="font-size: 5rem; color: #ffc107;"></i>
            </div>

            <!-- User Name -->
            <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h3>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($user['email']); ?></p>

            <!-- Info List -->
            <ul class="list-group list-group-flush text-start mb-4">
              <li class="list-group-item">
                <strong>User ID:</strong> <?php echo $user['id']; ?>
              </li>
              <li class="list-group-item">
                <strong>Phone:</strong> <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not set'; ?>
              </li>
              <li class="list-group-item">
                <strong>Role:</strong> <?php echo !empty($user['role']) ? htmlspecialchars($user['role']) : 'Not set'; ?>
              </li>
              <li class="list-group-item">
                <strong>Member Since:</strong> <?php echo date("F j, Y", strtotime($user['created_at'])); ?>
              </li>
              <li class="list-group-item">
                <strong>Address:</strong> <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not set'; ?>
              </li>
              <li class="list-group-item">
                <strong>Date of Birth:</strong>
                <?php 
                    if (!empty($user['dob'])) {
                        echo date("F j, Y", strtotime($user['dob']));
                    } else {
                        echo 'Not set';
                    }
                ?>
              </li>
              <li class="list-group-item">
                <strong>Gender:</strong> <?php echo !empty($user['gender']) ? htmlspecialchars($user['gender']) : 'Not set'; ?>
              </li>
              <li class="list-group-item">
                <strong>Bio:</strong> <?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'Not set'; ?>
              </li>
            </ul>

            <!-- Actions -->
            <div class="d-flex justify-content-center gap-3 mt-3">
              <a href="edit_profile.php" class="btn btn-primary px-4">
                <i class="bi bi-pencil-square me-2"></i> Edit Profile
              </a>
              <a href="settings.php" class="btn btn-warning px-4">
                <i class="bi bi-gear me-2"></i> Settings
              </a>
              <a href="logout.php" class="btn btn-outline-danger px-4">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include './includes/footer.php'; ?> <!-- footer -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
