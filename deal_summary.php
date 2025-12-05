<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$deal_id = isset($_GET['deal_id']) ? (int) $_GET['deal_id'] : 0;

// Fetch deal
$sql = "
SELECT d.*, 
       b.name AS buyer_name, b.email AS buyer_email,
       s.name AS seller_name, s.email AS seller_email,
       v.brand, v.model, v.variant, v.year
FROM deals d
JOIN inquiries i ON d.inquiry_id = i.id
JOIN vehicles v ON i.vehicle_id = v.id
JOIN users b ON d.buyer_id = b.id
JOIN users s ON d.seller_id = s.id
WHERE d.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $deal_id);
$stmt->execute();
$deal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$deal) die("Deal not found.");

// Remaining balance
$downpayment_paid = $deal['downpayment_amount'] ?? 0;
$remaining_balance = $deal['final_amount'] - $downpayment_paid;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Buyer makes downpayment
    if ($action === 'mark_paid') {
        $downpayment_amount = (float)($_POST['downpayment_amount'] ?? 0);
        if ($downpayment_amount > 0 && $downpayment_amount <= $remaining_balance) {
            $new_downpayment = $downpayment_paid + $downpayment_amount;
            $status = ($new_downpayment >= $deal['final_amount']) ? 'handover_pending' : $deal['status'];
            $stmt = $conn->prepare("UPDATE deals SET downpayment_amount=?, status=? WHERE id=?");
            $stmt->bind_param("dsi", $new_downpayment, $status, $deal_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_message'] = "Downpayment of ₹$downpayment_amount made. Deal status updated.";
        }
    }

    // Update deal status
    $status_map = ['handover'=>'completed', 'cancel'=>'cancelled'];
    if (isset($status_map[$action])) {
        $status = $status_map[$action];
        $stmt = $conn->prepare("UPDATE deals SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $deal_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = "Deal status updated to: $status";
    }

    // Seller generates invoice → mark invoice_generated
    if ($action === 'generate_invoice' && $user_id == $deal['seller_id'] && !$deal['invoice_generated']) {
        $stmt = $conn->prepare("UPDATE deals SET invoice_generated=1 WHERE id=?");
        $stmt->bind_param("i", $deal_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = "Invoice has been generated and buyer can download it now.";
        header("Location: deal_summary.php?deal_id=$deal_id");
        exit;
    }

    header("Location: deal_summary.php?deal_id=$deal_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deal Summary - <?php echo APP_NAME; ?></title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.card { border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
.payment-box { background:#f8f9fa; padding:20px; border-radius:10px; margin-top:20px; }
.actions button, .actions a { margin-right:10px; margin-top:5px; }
.badge-status { font-size:0.9rem; padding:0.5em 0.8em; }
</style>
</head>
<body>
<?php include './includes/header.php'; ?>

<main class="container py-4">

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
    </div>
<?php endif; ?>

<div class="card p-4">
    <h3>Deal Summary</h3>
    <hr>
    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars("{$deal['brand']} {$deal['model']} ({$deal['variant']}, {$deal['year']})"); ?></p>
    <p><strong>Buyer:</strong> <?php echo htmlspecialchars($deal['buyer_name']); ?> (<?php echo $deal['buyer_email']; ?>)</p>
    <p><strong>Seller:</strong> <?php echo htmlspecialchars($deal['seller_name']); ?> (<?php echo $deal['seller_email']; ?>)</p>
    <p><strong>Final Amount:</strong> ₹<?php echo number_format($deal['final_amount'],2); ?></p>
    <p><strong>Downpayment Paid:</strong> ₹<?php echo number_format($downpayment_paid,2); ?></p>
    <p><strong>Remaining Balance:</strong> ₹<?php echo number_format($remaining_balance,2); ?></p>
    <p><strong>Status:</strong> <span class="badge bg-info badge-status"><?php echo ucfirst($deal['status']); ?></span></p>
    <p><small><em>Created at: <?php echo date("M d, Y H:i", strtotime($deal['created_at'])); ?></em></small></p>

    <!-- Buyer Downpayment Interface -->
    <?php if ($deal['buyer_id'] == $user_id && $remaining_balance > 0): ?>
    <div class="payment-box">
        <h5>💰 Make Downpayment</h5>
        <p>Remaining Balance: <strong id="remaining_balance">₹<?php echo number_format($remaining_balance,2); ?></strong></p>
        <form method="post" id="downpaymentForm">
            <div class="mb-3">
                <label for="downpayment_amount" class="form-label">Amount to Pay (₹)</label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" 
                           name="downpayment_amount" 
                           id="downpayment_amount" 
                           class="form-control" 
                           min="1" 
                           max="<?php echo $remaining_balance; ?>" 
                           required>
                </div>
            </div>
            <input type="hidden" name="action" value="mark_paid">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal">💳 Pay Now</button>
        </form>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="confirmPaymentModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Confirm downpayment of <strong>₹<span id="confirm_amount">0</span></strong>?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" id="confirmPaymentBtn">Yes, Pay Now</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    const amountInput = document.getElementById('downpayment_amount');
    const remainingBalance = document.getElementById('remaining_balance');
    const confirmAmount = document.getElementById('confirm_amount');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const downpaymentForm = document.getElementById('downpaymentForm');

    amountInput.addEventListener('input', () => {
        let val = parseFloat(amountInput.value) || 0;
        let remaining = <?php echo $remaining_balance; ?> - val;
        remainingBalance.innerText = '₹' + remaining.toFixed(2);
        confirmAmount.innerText = val.toFixed(2);
    });

    confirmPaymentBtn.addEventListener('click', () => downpaymentForm.submit());
    </script>
    <?php endif; ?>

    <!-- Actions -->
    <div class="mt-3 actions">
        <?php if ($deal['status'] == 'handover_pending' && $deal['seller_id'] == $user_id): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="handover">
                <button class="btn btn-primary">🚗 Confirm Vehicle Handover</button>
            </form>
        <?php endif; ?>

        <?php if ($deal['status'] != 'completed' && ($deal['buyer_id']==$user_id || $deal['seller_id']==$user_id)): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="cancel">
                <button class="btn btn-danger">❌ Cancel Deal</button>
            </form>
        <?php endif; ?>

        <!-- Invoice Buttons -->
        <?php if ($deal['seller_id'] == $user_id): ?>
            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="generate_invoice">
                <button class="btn btn-outline-secondary">🧾 Generate / Download Invoice</button>
            </form>
        <?php endif; ?>

        <?php if ($deal['buyer_id'] == $user_id && $deal['invoice_generated']): ?>
            <a href="generate_invoice.php?deal_id=<?php echo $deal_id; ?>&pdf=1" class="btn btn-success">🧾 Download Invoice</a>
        <?php endif; ?>
    </div>

</div>

</main>
<?php include './includes/footer.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
