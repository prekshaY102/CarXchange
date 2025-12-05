<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current data
$stmt = $conn->prepare("SELECT name, email, phone, address, dob, gender, bio FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name'];
    $phone   = $_POST['phone'];
    $address = $_POST['address'];
    $dob     = $_POST['dob'];
    $gender  = $_POST['gender'];
    $bio     = $_POST['bio'];

    $stmt = $conn->prepare("UPDATE users 
                            SET name=?, phone=?, address=?, dob=?, gender=?, bio=? 
                            WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $phone, $address, $dob, $gender, $bio, $user_id);

    if ($stmt->execute()) {
        header("Location: profile.php?updated=1");
        exit;
    } else {
        $error = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <?php include './includes/header.php'; ?> <!-- navbar -->

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-5">
            <h3 class="fw-bold mb-4 text-warning">Edit Profile</h3>
            
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['dob']) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                  <option value="">Select</option>
                  <option value="male" <?= $user['gender']=='male'?'selected':'' ?>>Male</option>
                  <option value="female" <?= $user['gender']=='female'?'selected':'' ?>>Female</option>
                  <option value="other" <?= $user['gender']=='other'?'selected':'' ?>>Other</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Bio</label>
                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
              </div>
              <div class="d-flex justify-content-between">
                <a href="profile.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-2"></i> Cancel
                </a>
                <button type="submit" class="btn btn-warning">
                  <i class="bi bi-check-circle me-2"></i> Save Changes
                </button>
                
    <a href="change_password.php" class="btn btn-warning">Change Password</a>
</div>

              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include './includes/footer.php'; ?> <!-- footer -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
