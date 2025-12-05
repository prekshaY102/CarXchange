<?php
session_start();
require __DIR__ . '/includes/db.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$mode = isset($_GET['mode']) && $_GET['mode'] === 'seller' ? 'seller' : 'buyer';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4"><?= ucfirst($mode) ?> Visits</h2>

    <?php if($mode === 'buyer'): ?>
        <?php
        $stmt = $conn->prepare("
            SELECT v.id AS visit_id, v.visit_date, v.status, i.message AS inquiry_message,
                   u.name AS seller_name, u.email AS seller_email,
                   vh.brand, vh.model, vh.year
            FROM visits v
            JOIN inquiries i ON v.inquiry_id = i.id
            JOIN vehicles vh ON i.vehicle_id = vh.id
            JOIN users u ON vh.user_id = u.id
            WHERE v.buyer_id = ?
            ORDER BY v.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <?php if($result->num_rows > 0): ?>
            <div class="row">
            <?php while($visit = $result->fetch_assoc()): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span><?= htmlspecialchars($visit['brand'].' '.$visit['model'].' ('.$visit['year'].')') ?></span>
                            <small class="text-muted"><?= date("M d, Y H:i", strtotime($visit['visit_date'])) ?></small>
                        </div>
                        <div class="card-body">
                            <p><strong>Seller:</strong> <?= htmlspecialchars($visit['seller_name']) ?> (<?= htmlspecialchars($visit['seller_email']) ?>)</p>
                            <p><strong>Inquiry:</strong> <?= nl2br(htmlspecialchars($visit['inquiry_message'])) ?></p>
                            <p><strong>Status:</strong>
                                <?php if($visit['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($visit['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif($visit['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </p>
                           <?php if($visit['status'] === 'approved'): ?>
    <a href="visit_receipt.php?visit_id=<?= $visit['visit_id'] ?>" class="btn btn-success btn-sm">
        <i class="bi bi-download"></i> Download Receipt
    </a>
<?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>

            <?php
            $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM visits WHERE buyer_id = ?");
            $stmtCount->bind_param("i", $user_id);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result();
            $totalVisits = $resCount->fetch_assoc()['total'];
            $totalPages = ceil($totalVisits / $limit);
            $stmtCount->close();
            ?>

            <!-- Pagination -->
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                            <a class="page-link" href="?mode=buyer&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

        <?php else: ?>
            <div class="alert alert-info">No visits found.</div>
        <?php endif; ?>

    <?php elseif($mode === 'seller'): ?>
        <?php
        $stmt = $conn->prepare("
            SELECT v.id AS visit_id, v.visit_date, v.status, i.message AS inquiry_message,
                   u.name AS buyer_name, u.email AS buyer_email,
                   vh.brand, vh.model, vh.year
            FROM visits v
            JOIN inquiries i ON v.inquiry_id = i.id
            JOIN vehicles vh ON i.vehicle_id = vh.id
            JOIN users u ON v.buyer_id = u.id
            WHERE vh.user_id = ?
            ORDER BY v.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <?php if($result->num_rows > 0): ?>
            <div class="row">
            <?php while($visit = $result->fetch_assoc()): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between">
                            <span><?= htmlspecialchars($visit['brand'].' '.$visit['model'].' ('.$visit['year'].')') ?></span>
                            <small class="text-muted"><?= date("M d, Y H:i", strtotime($visit['visit_date'])) ?></small>
                        </div>
                        <div class="card-body">
                            <p><strong>Buyer:</strong> <?= htmlspecialchars($visit['buyer_name']) ?> (<?= htmlspecialchars($visit['buyer_email']) ?>)</p>
                            <p><strong>Inquiry:</strong> <?= nl2br(htmlspecialchars($visit['inquiry_message'])) ?></p>
                            <p><strong>Status:</strong>
                                <?php if($visit['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($visit['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif($visit['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </p>
                            <a href="visit_details.php?id=<?= $visit['visit_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>

            <?php
            $stmtCount = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM visits v
                JOIN inquiries i ON v.inquiry_id = i.id
                JOIN vehicles vh ON i.vehicle_id = vh.id
                WHERE vh.user_id = ?
            ");
            $stmtCount->bind_param("i", $user_id);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result();
            $totalVisits = $resCount->fetch_assoc()['total'];
            $totalPages = ceil($totalVisits / $limit);
            $stmtCount->close();
            ?>

            <!-- Pagination -->
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <li class="page-item <?= $i==$page?'active':'' ?>">
                            <a class="page-link" href="?mode=seller&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

        <?php else: ?>
            <div class="alert alert-info">No visit requests found.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
