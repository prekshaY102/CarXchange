<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once './includes/db.php';
require_once './includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Handle month/year navigation
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Handle form submission for new activity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $activity_date = $_POST['activity_date'] ?? '';
    $activity_time = $_POST['activity_time'] ?? '';

    if ($title && $activity_date && $activity_time) {
        $stmt = $conn->prepare("INSERT INTO activities (user_id, title, activity_date, activity_time, type) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("isss", $user_id, $title, $activity_date, $activity_time);
        $stmt->execute();
        $stmt->close();

        // Insert notification
        $message = "New activity added: $title at $activity_time";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, is_read, created_at) VALUES (?, 'system', ?, 0, NOW())");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();

        // Refresh page to show new activity
        header("Location: ?month=$month&year=$year");
        exit;
    }
}

// Get start and end of month
$start = "$year-$month-01 00:00:00";
$end = date("Y-m-t 23:59:59", strtotime($start));

// --- Fetch all activities and notifications for the month ---
$activities = [];

// User-added activities
$sql = "SELECT * FROM activities WHERE user_id=? AND activity_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = $row['activity_date'];
    $activities[$date][] = [
        'title' => $row['title'],
        'time' => $row['activity_time'],
        'type' => 'user'
    ];
}
$stmt->close();

// Pre-planned visits
$sql = "SELECT * FROM visits WHERE seller_id=? AND visit_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['visit_date']));
    $activities[$date][] = [
        'title' => "Pre-planned Visit: " . ($row['status'] ?? 'Pending'),
        'time' => date('H:i', strtotime($row['visit_date'])),
        'type' => 'visit'
    ];
}
$stmt->close();

// Helper function to display activities
function displayActivities($date, $activities) {
    if (isset($activities[$date])) {
        foreach ($activities[$date] as $act) {
            $color = $act['type'] === 'visit' ? 'bg-primary' : 'bg-success';
            echo "<div class='activity $color'>{$act['time']} - {$act['title']}</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars(APP_NAME) ?> - Activity Calendar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; }
.calendar-container { max-width: 900px; margin: 40px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);}
.calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.calendar-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; margin-bottom: 5px; }
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
.calendar-day { background: #f1f3f5; padding: 10px; border-radius: 5px; min-height: 80px; position: relative; cursor: pointer; transition: 0.2s; }
.calendar-day:hover { background: #e9ecef; }
.calendar-day .date { font-weight: bold; margin-bottom: 5px; }
.activity { font-size: 12px; padding: 2px 4px; border-radius: 4px; margin-bottom: 2px; color: #fff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bg-primary { background-color: #0d6efd !important; }
.bg-success { background-color: #198754 !important; }
</style>
</head>
<body>

<?php include './includes/header.php'; ?>

<div class="calendar-container">
    <div class="calendar-header">
        <h3><?= date('F Y', strtotime("$year-$month-01")) ?></h3>
        <div>
            <a class="btn btn-sm btn-outline-primary" href="?month=<?= $month-1 ?>&year=<?= $month==1 ? $year-1:$year ?>">&lt;</a>
            <a class="btn btn-sm btn-outline-primary" href="?month=<?= $month+1 ?>&year=<?= $month==12 ? $year+1:$year ?>">&gt;</a>
        </div>
    </div>

    <div class="calendar-days">
        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
        <div>Thu</div><div>Fri</div><div>Sat</div>
    </div>

    <div class="calendar-grid">
        <?php
        $firstDay = date('w', strtotime("$year-$month-01"));
        $daysInMonth = date('t', strtotime("$year-$month-01"));

        for ($i=0;$i<$firstDay;$i++) echo "<div class='calendar-day'></div>";

        for ($day=1;$day<=$daysInMonth;$day++):
            $dateStr = sprintf("%04d-%02d-%02d",$year,$month,$day);
        ?>
        <div class="calendar-day" data-date="<?= $dateStr ?>" data-bs-toggle="modal" data-bs-target="#activityModal">
            <div class="date"><?= $day ?></div>
            <?php displayActivities($dateStr, $activities); ?>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Add Activity Modal -->
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Activity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="activity_date" id="modalActivityDate">
        <div class="mb-3">
          <label>Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Time</label>
          <input type="time" name="activity_time" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Add Activity</button>
      </div>
    </form>
  </div>
</div>

<?php include './includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Set modal date on calendar click
document.querySelectorAll('.calendar-day').forEach(day=>{
    day.addEventListener('click', ()=>{
        const date = day.getAttribute('data-date');
        document.getElementById('modalActivityDate').value = date;
    });
});
</script>
</body>
</html>
