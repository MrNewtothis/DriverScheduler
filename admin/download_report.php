<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';
?>
<header class="header-modern">
    <div class="header-modern-left">
        <a href="main.php" class="header-modern-logo">
            <img src="../imgs/nialogo.png" alt="NIA Logo" class="header-modern-logo-img">
            <span class="header-modern-title">NIA Equipment Unit System</span>
        </a>
    </div>
    <nav class="header-modern-nav" id="headerModernNav">
        <a href="driverlog.php" class="header-modern-link">Driver List/Log</a>
        <a href="request.php" class="header-modern-link">Transpo Requests</a>
        <a href="report.php" class="header-modern-link" style="background:#0ea5e9; color:#fff; border-radius:8px; margin-left:12px;">Report</a>
        <a href="#" class="header-modern-link header-modern-profile" title="Profile" id="profileIconBtn">
            <img src="../imgs/nialogo.png" alt="Profile" class="header-modern-profile-img">
        </a>
    </nav>
    <button class="header-modern-burger" id="headerModernBurger" aria-label="Open menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
</header>
<script>
// Responsive header menu toggle
const burger = document.getElementById('headerModernBurger');
const nav = document.getElementById('headerModernNav');
burger.addEventListener('click', function() {
    nav.classList.toggle('open');
    burger.classList.toggle('open');
});
window.addEventListener('resize', function() {
    if (window.innerWidth > 900) {
        nav.classList.remove('open');
        burger.classList.remove('open');
    }
});
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 900 && !nav.contains(e.target) && !burger.contains(e.target)) {
        nav.classList.remove('open');
        burger.classList.remove('open');
    }
});
</script>
<?php
$driverId = $_POST['driver_id'] ?? null;
$month = $_POST['month'] ?? date('Y-m');
if (!$driverId) {
    die('No driver selected.');
}
$monthStart = $month . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$logs = [];
$stmt = $pdo->prepare("SELECT l.*, d.name as driver_name FROM driver_travel_logs l JOIN drivers d ON l.driver_id = d.id WHERE l.driver_id = ? AND l.log_date BETWEEN ? AND ? ORDER BY l.log_date DESC, l.time_from DESC");
$stmt->execute([$driverId, $monthStart, $monthEnd]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$logs) {
    $stmt = $pdo->prepare("SELECT driver as driver_name, time_from, time_to, requester_name, requesting_unit, purpose, DATE(datetime_used) as log_date FROM transportation_requests WHERE driver = (SELECT name FROM drivers WHERE id = ?) AND DATE(datetime_used) BETWEEN ? AND ? ORDER BY datetime_used DESC, dateissued DESC");
    $stmt->execute([$driverId, $monthStart, $monthEnd]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$filename = 'driver_report_' . $driverId . '_' . $month . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Driver', 'Time From', 'Time To', 'Requester', 'Unit', 'Purpose']);
foreach ($logs as $log) {
    fputcsv($output, [
        $log['log_date'] ? date('M d, Y', strtotime($log['log_date'])) : '-',
        $log['driver_name'],
        $log['time_from'] ? date('g:i A', strtotime($log['time_from'])) : '-',
        $log['time_to'] ? date('g:i A', strtotime($log['time_to'])) : '-',
        $log['requester_name'],
        $log['requesting_unit'],
        $log['purpose']
    ]);
}
fclose($output);
exit;
