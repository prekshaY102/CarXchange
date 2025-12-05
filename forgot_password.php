<?php
session_start();
require './includes/config.php'; 
require './includes/db.php'; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $message = "⚠ All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $message = "⚠ New password and confirm password do not match.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);

            if ($update->execute()) {
                $message = "✅ Password updated successfully. You can now <a href='login.php'>login</a>.";
            } else {
                $message = "⚠ Something went wrong while updating password.";
            }
            $update->close();
        } else {
            $message = "❌ No account found with that email.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - <?php echo APP_NAME; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include './includes/header.php'; ?>  

<div class="container" style="margin-top: 120px; max-width: 600px;">
  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-body p-4">
      <h3 class="mb-4 text-center"><i class="bi bi-key me-2"></i> Forgot Password</h3>

      <?php if ($message): ?>
        <div class="alert alert-info text-center">
          <?= $message; ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="mb-3">
          <label for="email" class="form-label">Registered Email</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
        </div>

        <div class="mb-3">
          <label for="new_password" class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
        </div>

        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-circle me-1"></i> Change Password
          </button>
        </div>

        <div class="text-center mt-3">
          <a href="login.php" class="text-decoration-none">Back to Login</a>
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
