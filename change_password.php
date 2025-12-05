<?php
session_start();
require './includes/config.php'; // database connection
require './includes/db.php'; // database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "⚠ All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $message = "⚠ New password and confirm password do not match.";
    } else {
        // Fetch user’s current password from DB
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_password);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $db_password)) {
            $message = "❌ Current password is incorrect.";
        } else {
            // Update with new password (hashed)
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);

            if ($stmt->execute()) {
                $message = "✅ Password updated successfully.";
            } else {
                $message = "⚠ Something went wrong.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - <?php echo APP_NAME; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include './includes/header.php'; ?>  

<div class="container" style="margin-top: 120px; max-width: 600px;">
  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-body p-4">
      <h3 class="mb-4 text-center"><i class="bi bi-key me-2"></i> Change Password</h3>

      <?php if ($message): ?>
        <div class="alert alert-info text-center">
          <?= htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label for="current_password" class="form-label">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="new_password" class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-circle me-1"></i> Update Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include './includes/footer.php'; ?>  

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
