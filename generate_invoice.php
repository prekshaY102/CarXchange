<?php


if (session_status() === PHP_SESSION_NONE) session_start();

//make sure user is logged
$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
    http_response_code(401);
    echo "not logged in";
    exit;
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/TCPDF-main/tcpdf.php';

// use TCPDF;

// Input
$deal_id = filter_input(INPUT_GET, 'deal_id', FILTER_VALIDATE_INT);
$download_pdf = isset($_GET['pdf']) && $_GET['pdf'] == '1';

if (!$deal_id || $deal_id <= 0) {
    http_response_code(400);
    echo "Invalid deal id.";
    exit;
}

// Fetch deal
$sql = "
SELECT 
    d.*, 
    buyer.id AS buyer_id, buyer.name AS buyer_name, buyer.email AS buyer_email, buyer.phone AS buyer_phone, buyer.address AS buyer_address,
    seller.id AS seller_id, seller.name AS seller_name, seller.email AS seller_email, seller.phone AS seller_phone, seller.address AS seller_address,
    v.brand AS vehicle_brand, v.model AS vehicle_model, v.variant, v.year, v.registration_no, v.color
FROM deals d
JOIN users buyer ON buyer.id = d.buyer_id
JOIN users seller ON seller.id = d.seller_id
LEFT JOIN inquiries i ON i.id = d.inquiry_id
LEFT JOIN vehicles v ON v.id = i.vehicle_id
WHERE d.id = ? LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $deal_id);
$stmt->execute();
$deal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$deal) {
    http_response_code(404);
    echo "Deal not found.";
    exit;
}

// Check access
$user_id = $_SESSION['user_id'];
if ($user_id != $deal['seller_id'] && $user_id != $deal['buyer_id']) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Mark invoice generated if seller
if ($user_id == $deal['seller_id'] && !$deal['invoice_generated']) {
    $stmt = $conn->prepare("UPDATE deals SET invoice_generated=1 WHERE id=?");
    $stmt->bind_param("i", $deal_id);
    $stmt->execute();
    $stmt->close();
}

// Calculations
$downpayment = (float)($deal['downpayment_amount'] ?? 0);
$subtotal = (float)$deal['final_amount'];
$remaining = $subtotal - $downpayment;
$tax_rate = 0.18;
$tax = round($subtotal * $tax_rate, 2);
$total = round($subtotal + $tax, 2);

// Metadata
$invoice_number = 'INV-' . str_pad($deal['id'], 6, '0', STR_PAD_LEFT);
$invoice_date = date('d M Y', strtotime($deal['created_at'] ?? 'now'));

$company_name = defined('COMPANY_NAME') ? COMPANY_NAME : 'My Auto Marketplace Pvt Ltd';
$company_address = defined('COMPANY_ADDRESS') ? COMPANY_ADDRESS : '123 Business Park, Mumbai, India';
$company_gst = defined('COMPANY_GST') ? COMPANY_GST : 'GSTIN: 22ABCDE1234F1Z5';
$company_logo = defined('COMPANY_LOGO') ? COMPANY_LOGO : 'https://via.placeholder.com/150x50?text=LOGO';

// Formatting
$subtotal_fmt   = number_format($subtotal, 2);
$downpayment_fmt= number_format($downpayment, 2);
$remaining_fmt  = number_format($remaining, 2);
$tax_fmt        = number_format($tax, 2);
$total_fmt      = number_format($total, 2);
$tax_percent    = $tax_rate * 100;

// Invoice HTML
$invoice_html = <<<HTML
<style>
body { font-family: "dejavusans", sans-serif; font-size: 11px; color:#333; }
.invoice-box { width:100%; }
.header { display:flex; justify-content:space-between; margin-bottom:20px; }
.header img { max-height:50px; }
.company-info { text-align:right; font-size:12px; }
h2 { margin:0; }
.section { margin-top:20px; }
.bill-to, .seller { width:45%; font-size:12px; }
.flex { display:flex; justify-content:space-between; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
table.items th { background:#007BFF; color:#fff; padding:8px; text-align:left; }
table.items td { border-bottom:1px solid #eee; padding:8px; font-size:12px; }
.totals { margin-top:20px; width:40%; float:right; border-collapse:collapse; }
.totals td { padding:6px; font-size:12px; }
.totals tr td:first-child { text-align:left; }
.totals tr td:last-child { text-align:right; }
.totals tr:last-child td { font-weight:bold; font-size:14px; border-top:2px solid #007BFF; }
.footer { clear:both; margin-top:40px; font-size:10px; text-align:center; color:#777; border-top:1px solid #ddd; padding-top:10px; }
</style>

<div class="invoice-box">

<div class="header">
    <div><img src="{$company_logo}" alt="Company Logo"></div>
    <div class="company-info">
        <h2>{$company_name}</h2>
        <div>{$company_address}</div>
        <div>{$company_gst}</div>
    </div>
</div>

<hr>

<div class="flex section">
    <div class="bill-to">
        <strong>Billed To:</strong><br>
        {$deal['buyer_name']}<br>
        {$deal['buyer_email']}<br>
        {$deal['buyer_phone']}<br>
        {$deal['buyer_address']}
    </div>
    <div class="seller">
        <strong>Seller:</strong><br>
        {$deal['seller_name']}<br>
        {$deal['seller_email']}<br>
        {$deal['seller_phone']}<br>
        {$deal['seller_address']}
    </div>
</div>

<div class="section">
<table class="items">
<thead>
<tr>
<th>#</th>
<th>Description</th>
<th>Vehicle</th>
<th>Year</th>
<th>Color</th>
<th style="text-align:right;">Amount</th>
</tr>
</thead>
<tbody>
<tr>
<td>1</td>
<td>{$deal['variant']}</td>
<td>{$deal['vehicle_brand']} {$deal['vehicle_model']} ({$deal['registration_no']})</td>
<td>{$deal['year']}</td>
<td>{$deal['color']}</td>
<td style="text-align:right;">{$subtotal_fmt}</td>
</tr>
</tbody>
</table>
</div>

<table class="totals">
<tr><td>Subtotal</td><td>{$subtotal_fmt}</td></tr>
<tr><td>Downpayment Paid</td><td>{$downpayment_fmt}</td></tr>
<tr><td>Remaining Balance</td><td>{$remaining_fmt}</td></tr>
<tr><td>Tax ({$tax_percent}%)</td><td>{$tax_fmt}</td></tr>
<tr><td>Total</td><td>{$total_fmt}</td></tr>
</table>

<div class="footer">
<p>Invoice Number: {$invoice_number} | Date: {$invoice_date}</p>
<p>Thank you for your business! Please make payment within 15 days.</p>
</div>

</div>
HTML;

// ✅ If PDF requested
if ($download_pdf) {
    if (ob_get_length()) ob_end_clean();

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->writeHTML($invoice_html, true, false, true, false, '');

    $filename = 'invoice_' . $invoice_number . '.pdf';
    $pdf->Output($filename, 'D'); // D = force download, I = inline in browser
    exit;
}

// ✅ Show in browser if not PDF
echo $invoice_html;
