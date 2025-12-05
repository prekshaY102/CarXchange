<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$message = "";

// ✅ Generate CAPTCHA
if (!isset($_SESSION['captcha']) || isset($_GET['refresh_captcha'])) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $_SESSION['captcha'] = substr(str_shuffle($chars), 0, 5);
}

// ✅ Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha  = trim($_POST['captcha']);

    // Check CAPTCHA
    if (strcasecmp($captcha, $_SESSION['captcha']) !== 0) {
        $message = "❌ Wrong CAPTCHA. Try again.";
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $_SESSION['captcha'] = substr(str_shuffle($chars), 0, 5);
    } else {
        // Prepare query
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $message = "❌ Invalid email or password.";
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container-fluid login-container">
    <div class="row w-100">

        <!-- Left Image Section -->
        <div class="col-md-6 d-none d-md-flex login-image">
            <div class="login-overlay"></div>
            <div class="login-text">
                🔑 Welcome Back! <br> Secure Login to Continue
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <div class="login-form">
                <h2 class="mb-4 text-center">Login</h2>

                <?php if ($message): ?>
                    <div class="alert alert-danger"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <!-- CAPTCHA -->
                    <div class="mb-3">
                        <label class="form-label">Enter CAPTCHA</label>
                        <div class="d-flex align-items-center">
                            <div class="captcha-box flex-grow-1">
                                <?= $_SESSION['captcha'] ?>
                            </div>
                            <a href="?refresh_captcha=1" class="btn btn-secondary ms-2">↻</a>
                        </div>
                        <input type="text" name="captcha" class="form-control mt-2" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>

                    <p class="mt-3 text-center">
                        Don’t have an account? <a href="signup.php">Sign Up</a>
                    </p>
                    <p class="mt-3 text-center">
                        Forgot Password? <a href="forgot_password.php">Forgot Password</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
