<?php
session_start();
require_once __DIR__ . '/includes/config.php'; // contains DB credentials
require_once __DIR__ . '/includes/db.php';     // connects $conn (MySQLi)

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $phone  = trim($_POST['phone']);
    $role   = trim($_POST['role']);
    $pass   = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Prepare insert query
    $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // "sssss" means 5 string parameters
        $stmt->bind_param("sssss", $name, $email, $phone, $pass, $role);

        if ($stmt->execute()) {
            $message = "✅ Signup successful! You can now log in.";
        } else {
            if ($conn->errno === 1062) { // duplicate email
                $message = "❌ Email is already registered. Try logging in.";
            } else {
                $message = "❌ Database error: " . $conn->error;
            }
        }
        $stmt->close();
    } else {
        $message = "❌ Failed to prepare statement: " . $conn->error;
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<style>
/* Full height layout */
.signup-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
}

/* Left form section */
.signup-form {
    background: #fff;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

/* Right image section */
.signup-image {
    background: url("https://images.unsplash.com/photo-1502877338535-766e1452684a") no-repeat center center;
    background-size: cover;
    position: relative;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
}

.signup-overlay {
    background: rgba(0,0,50,0.5);
    position: absolute;
    inset: 0;
}

.signup-text {
    position: relative;
    z-index: 2;
    font-size: 2rem;
    font-weight: bold;
    text-align: center;
    animation: fadeInUp 2s ease-in-out infinite alternate;
}

/* Simple text animation */
@keyframes fadeInUp {
    from { transform: translateY(20px); opacity: 0.6; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<div class="container-fluid signup-container">
    <div class="row w-100">
        
        <!-- Left Form -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <div class="signup-form w-75">
                <h2 class="mb-4 text-center">Create Account</h2>
                <?php if ($message): ?>
                  <div class="alert alert-info"><?= $message ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required pattern="[0-9]{10}" title="Enter valid 10-digit number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="buyer" selected>Buyer</option>
                            <option value="seller">Seller</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                    <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
                </form>
            </div>
        </div>

        <!-- Right Image & Animation -->
        <div class="col-md-6 signup-image">
            <div class="signup-overlay"></div>
            <div class="signup-text">
                🚗 Drive Your Dream Car <br>
                Anytime. Anywhere.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
