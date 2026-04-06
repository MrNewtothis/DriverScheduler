<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';
$month = $_GET['month'] ?? date('Y-m');
$driverId = $_GET['driver_id'] ?? null;
$allDrivers = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM drivers ORDER BY name ASC");
    $stmt->execute();
    $allDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allDrivers = [];
}
$logs = [];
if ($driverId) {
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $stmt = $pdo->prepare("SELECT l.*, d.name as driver_name FROM driver_travel_logs l JOIN drivers d ON l.driver_id = d.id WHERE l.driver_id = ? AND l.log_date BETWEEN ? AND ? ORDER BY l.log_date DESC, l.time_from DESC");
    $stmt->execute([$driverId, $monthStart, $monthEnd]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$logs) {
        $stmt = $pdo->prepare("SELECT driver as driver_name, time_from, time_to, requester_name, requesting_unit, purpose, DATE(datetime_used) as log_date FROM transportation_requests WHERE driver = (SELECT name FROM drivers WHERE id = ?) AND DATE(datetime_used) BETWEEN ? AND ? ORDER BY datetime_used DESC, dateissued DESC");
        $stmt->execute([$driverId, $monthStart, $monthEnd]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
function monthOptions($selected) {
    $out = '';
    for ($i = 0; $i < 12; $i++) {
        $m = date('Y-m', strtotime("-{$i} months"));
        $out .= '<option value="' . $m . '"' . ($selected == $m ? ' selected' : '') . '>' . date('F Y', strtotime($m)) . '</option>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Report - NIA Equipment Unit System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <style>
        .main-content {
            box-sizing: border-box;
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 24px;
            padding-right: 24px;
        }
        @media (max-width: 1100px) {
            .main-content {
                max-width: 98vw;
                padding-left: 16px;
                padding-right: 16px;
            }
        }
        @media (max-width: 900px) {
            .main-content {
                max-width: 100vw;
                padding-left: 10px;
                padding-right: 10px;
            }
        }
        @media (max-width: 700px) {
            .main-content {
                padding-left: 4vw;
                padding-right: 4vw;
            }
        }
        @media (max-width: 500px) {
            .main-content {
                padding-left: 2vw;
                padding-right: 2vw;
            }
        }
        .dashboard-card {
            margin-bottom: 32px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10);
            padding: 28px 0 18px 0;
            overflow-x: visible;
        }
        .dashboard-header-row, .dashboard-header-row-report {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px 12px;
            width: 100%;
            margin-bottom: 10px;
            padding-top: 18px;
            padding-left: 16px;
            padding-right: 16px;
            box-sizing: border-box;
        }
        .dashboard-title {
            flex: 0 0 auto !important;
            min-width: unset !important;
            max-width: 100%;
            margin: 0 8px 0 0;
            font-size: clamp(1.05em, 2.5vw, 1.35em);
            font-weight: 700;
            color: #1e293b;
            word-break: break-word;
            line-height: 1.2;
            padding: 0;
            white-space: normal;
            display: inline-block;
        }
        @media (max-width: 700px) {
            .dashboard-header-row, .dashboard-header-row-report {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px !important;
                padding-left: 4vw;
                padding-right: 4vw;
            }
            .dashboard-title {
                font-size: clamp(1em, 4vw, 1.15em);
                margin-bottom: 2px;
            }
        }
        @media (max-width: 500px) {
            .dashboard-header-row, .dashboard-header-row-report {
                padding-left: 2vw;
                padding-right: 2vw;
            }
            .dashboard-title {
                font-size: clamp(0.98em, 5vw, 1.08em);
                margin-bottom: 2px;
            }
        }
        .dashboard-table-wrapper {
            width: 100%;
            overflow-x: auto;
            padding-left: 16px;
            padding-right: 16px;
            box-sizing: border-box;
            margin-bottom: 0;
        }
        @media (max-width: 900px) {
            .dashboard-table-wrapper {
                padding-left: 8px;
                padding-right: 8px;
            }
        }
        @media (max-width: 700px) {
            .dashboard-table-wrapper {
                padding-left: 4vw;
                padding-right: 4vw;
            }
        }
        @media (max-width: 500px) {
            .dashboard-table-wrapper {
                padding-left: 2vw;
                padding-right: 2vw;
            }
        }
        .dashboard-table {
            min-width: 950px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(30,41,59,0.08);
        }
        @media (max-width: 1100px) {
            .dashboard-table {
                min-width: 800px;
            }
        }
        @media (max-width: 700px) {
            .dashboard-table {
                min-width: 600px;
                font-size: 0.97em;
            }
        }
        @media (max-width: 500px) {
            .dashboard-table {
                min-width: 480px;
                font-size: 0.93em;
            }
        }
        .dashboard-table th, .dashboard-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1.5px solid #e5e7eb;
            background: #fff;
            word-break: break-word;
            white-space: normal;
            min-width: 80px;
            max-width: 220px;
            overflow-wrap: break-word;
        }
        .dashboard-table td {
            vertical-align: top;
        }
        @media (max-width: 900px) {
            .dashboard-table th, .dashboard-table td {
                padding: 8px 8px;
                min-width: 70px;
                max-width: 180px;
            }
        }
        @media (max-width: 700px) {
            .dashboard-table th, .dashboard-table td {
                padding: 7px 6px;
                min-width: 60px;
                max-width: 140px;
                font-size: 0.97em;
            }
        }
        @media (max-width: 500px) {
            .dashboard-table th, .dashboard-table td {
                padding: 6px 4px;
                min-width: 50px;
                max-width: 110px;
                font-size: 0.93em;
            }
        }
    </style>
</head>
<body>
<?php include 'admin_header.php'; ?>
<div class="main-content">
    <div class="dashboard-card">
        <div class="dashboard-header-row-report">
            <h2 class="dashboard-title">Driver Report Generation</h2>
            <form method="get" action="report.php" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <label for="driver_id" style="font-weight:600; color:#334155; margin-bottom:0;">Driver:</label>
                <select name="driver_id" id="driver_id" required class="main-input" style="padding:8px 12px; border-radius:8px; border:1.5px solid #cbd5e1; min-width:120px;">
                    <option value="">Select Driver</option>
                    <?php foreach ($allDrivers as $drv): ?>
                        <option value="<?= $drv['id'] ?>" <?= $driverId==$drv['id']?'selected':'' ?>><?= htmlspecialchars($drv['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="month" style="font-weight:600; color:#334155; margin-bottom:0;">Month:</label>
                <select name="month" id="month" class="main-input" style="padding:8px 12px; border-radius:8px; border:1.5px solid #cbd5e1; min-width:100px;">
                    <?= monthOptions($month) ?>
                </select>
                <button type="submit" class="main-btn main-btn--primary" style="height:38px; min-width:80px; font-size:1em;">Generate</button>
            </form>
        </div>
        <?php if ($driverId && $logs): ?>
        <div style="margin-bottom:18px; display:flex; justify-content:left; padding-right:16px; padding-left:16px;">
            <form method="post" action="download_report.php" style="margin:0;">
                <input type="hidden" name="driver_id" value="<?= htmlspecialchars($driverId) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($month) ?>">
                <button type="submit" class="main-btn" style="background:#22c55e; color:#fff; border-radius:8px;">Download Report</button>
            </form>
        </div>
        <div class="dashboard-table-wrapper">
        <table class="dashboard-table">
            <thead style="background:#f1f5f9;">
                <tr>
                    <th>Date</th>
                    <th>Driver</th>
                    <th>Time From</th>
                    <th>Time To</th>
                    <th>Requester</th>
                    <th>Unit</th>
                    <th>Purpose</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['log_date'] ? date('M d, Y', strtotime($log['log_date'])) : '-' ?></td>
                    <td><?= htmlspecialchars($log['driver_name']) ?></td>
                    <td><?= $log['time_from'] ? date('g:i A', strtotime($log['time_from'])) : '-' ?></td>
                    <td><?= $log['time_to'] ? date('g:i A', strtotime($log['time_to'])) : '-' ?></td>
                    <td><?= htmlspecialchars($log['requester_name']) ?></td>
                    <td><?= htmlspecialchars($log['requesting_unit']) ?></td>
                    <td><?= htmlspecialchars($log['purpose']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php elseif ($driverId): ?>
            <div style="margin-top:18px; color:#ef4444; font-weight:600;">No logs found for this driver and month.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
