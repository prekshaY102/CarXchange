<?php
// all_inquiries.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/**
 * 🔹 Helper function for offer action buttons
 */
function actionButtons($offer, $current, $user_id) {
    $html = '';
    $isSeller = ($current['seller_id'] == $user_id);
    $isBuyer  = ($current['buyer_id'] == $user_id);
    $offerMadeByBuyer = ($offer['user_id'] == $current['buyer_id']);

    if ($offer['status'] == 'pending') {
        if ($offerMadeByBuyer && $isSeller) {
            $html .= '<form method="post" action="respond_offer.php" style="display:inline-block;margin-right:6px;">
                        <input type="hidden" name="offer_id" value="'.intval($offer['id']).'">
                        <input type="hidden" name="response" value="accepted">
                        <button class="btn btn-success" type="submit">Accept</button>
                      </form>';
            $html .= '<button class="btn btn-danger" onclick="openCounterForm('.intval($offer['id']).', '.$offer['offer_amount'].')">Reject/Counter</button>';
        } elseif (!$offerMadeByBuyer && $isBuyer) {
            $html .= '<form method="post" action="respond_offer.php" style="display:inline-block;margin-right:6px;">
                        <input type="hidden" name="offer_id" value="'.intval($offer['id']).'">
                        <input type="hidden" name="response" value="accepted">
                        <button class="btn btn-success" type="submit">Accept</button>
                      </form>';
            $html .= '<button class="btn btn-danger" onclick="openCounterForm('.intval($offer['id']).', '.$offer['offer_amount'].')">Reject/Counter</button>';
        }
    }
    return $html;
}

// Fetch inquiries with deal info
$sql = "SELECT MAX(i.id) AS inquiry_id, v.brand AS vehicle_title, 
       u.name AS buyer_name, u.email AS buyer_email, u.id AS buyer_id,
       v.user_id AS seller_id, MAX(i.message) AS message, MAX(i.created_at) AS created_at,
       MAX(d.id) AS deal_id, MAX(d.invoice_generated) AS invoice_generated, MAX(d.final_amount) AS final_amount
FROM inquiries i
JOIN vehicles v ON i.vehicle_id = v.id
JOIN users u ON i.user_id = u.id
LEFT JOIN deals d ON d.inquiry_id = i.id
WHERE i.user_id = ? OR v.user_id = ?
GROUP BY v.id, u.id, v.user_id
ORDER BY MAX(i.id) DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL Error: " . $conn->error);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$current_inquiry_id = isset($_GET['inquiry_id']) ? (int) $_GET['inquiry_id'] : 0;

$messages = [];
$offers = [];

if ($current_inquiry_id > 0) {
    // messages
    $stmt = $conn->prepare("
        SELECT r.id, r.reply_message, r.created_at, r.user_id, u.name
        FROM inquiry_replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.inquiry_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmt->bind_param("i", $current_inquiry_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // offers
    $stmt = $conn->prepare("
        SELECT o.*, u.name
        FROM inquiry_offers o
        JOIN users u ON o.user_id = u.id
        WHERE o.inquiry_id = ?
        ORDER BY o.created_at ASC
    ");
    $stmt->bind_param("i", $current_inquiry_id);
    $stmt->execute();
    $offers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Flash / messages
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>All Inquiries - <?php echo APP_NAME; ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:1100px;margin:18px auto;padding:0 12px;}
.grid{display:flex; gap:16px;}
.col{background:#fff;border-radius:10px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,0.06);}
.left{flex:0 0 320px;}
.right{flex:1;}
.list-item{padding:10px;border-radius:8px;margin-bottom:8px;display:block;text-decoration:none;color:#111;background:#fafafa;}
.list-item.active{background:#0d6efd;color:#fff;}
.chat-box{height:420px;overflow:auto;padding:12px;border-radius:8px;border:1px solid #e6e6e6;background:#fff;}
.msg{margin-bottom:12px;max-width:78%;padding:10px;border-radius:12px;font-size:14px;}
.me{background:#0d6efd;color:#fff;margin-left:auto;text-align:right;}
.other{background:#e9ecef;color:#000;margin-right:auto;text-align:left;}
.offer{background:#fff3cd;border:1px solid #ffeeba;color:#856404;text-align:left;}
.system{background:#f1f3f5;color:#333;font-style:italic;text-align:center;margin:10px auto;max-width:90%;}
.small{display:block;font-size:12px;color:#666;margin-top:6px;}
input[type="text"], input[type="number"]{padding:8px;border-radius:20px;border:1px solid #ccc;flex:1;}
button{padding:8px 12px;border-radius:6px;border:none;cursor:pointer;}
.btn-primary{background:#0d6efd;color:#fff;}
.btn-success{background:#198754;color:#fff;}
.btn-danger{background:#dc3545;color:#fff;}
.invoice-box{background:#f8f9fa;padding:12px;border-radius:8px;margin-top:12px;border:1px solid #ddd;}
</style>
</head>
<body>

<?php include __DIR__.'/includes/header.php'; ?>

<div class="container">
  <?php if ($flash): ?>
    <div style="padding:10px;background:#e9ffe9;border-left:4px solid #28a745;margin-bottom:14px;">
      <?php echo htmlspecialchars($flash); ?>
    </div>
  <?php endif; ?>

  <div class="grid">
    <!-- LEFT PANEL -->
    <div class="col left">
      <h3>📨 Inquiries</h3>
      <?php if (!empty($inquiries)): ?>
        <?php foreach ($inquiries as $inq): ?>
          <a class="list-item <?php echo $inq['inquiry_id']==$current_inquiry_id ? 'active' : ''; ?>"
             href="all_inquiries.php?inquiry_id=<?php echo $inq['inquiry_id']; ?>">
             <strong><?php echo htmlspecialchars($inq['vehicle_title']); ?></strong><br>
             <small>From: <?php echo htmlspecialchars($inq['buyer_name']); ?></small>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div>No inquiries found.</div>
      <?php endif; ?>
    </div>

    <!-- RIGHT PANEL -->
    <div class="col right">
      <?php if ($current_inquiry_id > 0): 
          $current = null;
          foreach ($inquiries as $i) if ($i['inquiry_id']==$current_inquiry_id) { $current = $i; break; }
      ?>
        <!-- Inquiry Header -->
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <h3 style="margin:0;"><?php echo htmlspecialchars($current['vehicle_title']); ?> 
              <small style="color:#666">(#<?php echo $current['inquiry_id']; ?>)</small></h3>
            <div style="color:#555">From: <?php echo htmlspecialchars($current['buyer_name']); ?> (<?php echo htmlspecialchars($current['buyer_email']); ?>)</div>
            <div style="color:#555;margin-top:6px">Message: <?php echo htmlspecialchars($current['message']); ?></div>
          </div>
          <div style="color:#888"><?php echo date("M d, Y H:i", strtotime($current['created_at'])); ?></div>
        </div>

        <hr style="border:none;border-top:1px solid #eee;margin:12px 0">

        <!-- Chat Stream -->
        <div class="chat-box" id="chatBox">
          <?php
            $stream = [];
            foreach ($messages as $m) $stream[] = ['type'=>'message','ts'=>$m['created_at'],'data'=>$m];
            foreach ($offers as $o) $stream[] = ['type'=>'offer','ts'=>$o['created_at'],'data'=>$o];
            usort($stream, fn($a,$b) => strtotime($a['ts']) <=> strtotime($b['ts']));

            if (empty($stream)) {
              echo '<div style="color:#666">No conversation yet. Start by replying or making an offer.</div>';
            } else {
              foreach ($stream as $item) {
                if ($item['type'] === 'message') {
                  $m = $item['data'];
                  $cls = $m['user_id'] == $user_id ? 'me' : 'other';
                  echo '<div class="msg '. $cls .'"><strong>'.htmlspecialchars($m['name']).'</strong>
                        <div style="margin-top:6px">'.nl2br(htmlspecialchars($m['reply_message'])).'</div>
                        <span class="small">'.date("M d, Y H:i", strtotime($m['created_at'])).'</span></div>';
                } else {
                  $o = $item['data'];
                  echo '<div class="msg offer"><strong>'.htmlspecialchars($o['name']).'</strong> offered: ₹'.number_format($o['offer_amount'],2);
                  echo '<span class="small">'.date("M d, Y H:i", strtotime($o['created_at'])).' &nbsp;Status: '.htmlspecialchars($o['status']).'</span>';
                  if ($o['status'] == 'pending') echo actionButtons($o, $current, $user_id);
                  echo '</div>';
                }
              }
            }

            // Invoice section
            if ($current['deal_id']) {
                echo '<div class="invoice-box">';
                echo '<strong>Deal Amount:</strong> ₹'.number_format($current['final_amount'],2).'<br>';
                echo '<strong>Invoice Status:</strong> '.($current['invoice_generated'] ? 'Generated ✅' : 'Not Generated ❌').'<br>';
                if ($current['seller_id']==$user_id && !$current['invoice_generated']) {
                    echo '<a href="generate_invoice.php?deal_id='.$current['deal_id'].'&pdf=1" class="btn btn-success mt-2">🧾 Generate Invoice</a>';
                }
                if ($current['buyer_id']==$user_id && $current['invoice_generated']) {
                    echo '<a href="generate_invoice.php?deal_id='.$current['deal_id'].'&pdf=1" class="btn btn-primary mt-2">🧾 Download Invoice</a>';
                }
                echo '</div>';
            }
          ?>
        </div>

        <!-- Reply Form -->
        <form method="post" action="reply_inquiry.php" style="margin-top:12px;display:flex;gap:8px;">
          <input type="hidden" name="inquiry_id" value="<?php echo $current_inquiry_id; ?>">
          <input type="text" name="reply_message" placeholder="Type your reply..." required>
          <button class="btn-primary" type="submit">Send</button>
        </form>

        <!-- Make Offer (Buyer only) -->
        <?php if ($current['buyer_id'] == $user_id): ?>
          <form method="post" action="make_offer.php" style="margin-top:10px;display:flex;gap:8px;">
             <input type="hidden" name="inquiry_id" value="<?php echo $current_inquiry_id; ?>">
             <input type="number" step="0.01" min="0" name="offer_amount" placeholder="Enter your offer..." required>
             <button class="btn-success" type="submit">Make Offer</button>
          </form>
        <?php endif; ?>

        <!-- Counter Form -->
        <div id="counterForm" style="display:none;margin-top:10px;">
          <form method="post" action="respond_offer.php" onsubmit="return confirm('Send counter offer?');" style="display:flex;gap:8px;">
            <input type="hidden" name="offer_id" id="counter_offer_id" value="">
            <input type="hidden" name="response" value="rejected">
            <input type="number" step="0.01" min="0" name="counter_amount" id="counter_amount_field" placeholder="Counter amount (leave blank to just reject)">
            <button class="btn-danger" type="submit">Submit</button>
            <button type="button" onclick="closeCounter()" style="padding:8px;border-radius:6px;border:1px solid #ccc;background:#fff;margin-left:6px;">Cancel</button>
          </form>
        </div>

        <script>
          function openCounterForm(offerId, originalAmount){
            document.getElementById('counter_offer_id').value = offerId;
            document.getElementById('counter_amount_field').value = '';
            document.getElementById('counterForm').style.display = 'block';
            const chatBox = document.getElementById('chatBox');
            if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
            window.scrollTo(0, document.body.scrollHeight);
          }
          function closeCounter(){
            document.getElementById('counterForm').style.display = 'none';
          }
          setTimeout(()=>{ const cb=document.getElementById('chatBox'); if(cb) cb.scrollTop=cb.scrollHeight; }, 50);
        </script>

      <?php else: ?>
        <div>Select an inquiry from the left to view conversation.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__.'/includes/footer.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
