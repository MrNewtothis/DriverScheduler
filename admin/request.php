<?php
session_start();
// Session and role check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../connection/db.php';

// --- AUTO-UPDATE STATUS OF TRANSPORTATION REQUESTS TO 'accomplished' IF TIMEFRAME IS OVER ---
try {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("UPDATE transportation_requests SET status = 'accomplished' WHERE status = 'approved' AND CONCAT(datetime_used, ' ', time_to) < ? AND status != 'accomplished'");
    $stmt->execute([$now]);
} catch (Exception $e) {
    // Optionally log error
}

// Handle update/save POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $id = $_POST['request_id'];
    $status = $_POST['status'] ?? 'pending';
    $remarks = $_POST['remarks'] ?? '';
    $driver = $_POST['driver'] ?? '';
    $vehicle = $_POST['vehicle'] ?? '';
    $rp_rpt = $_POST['rp_rpt'] ?? '';
    // If status is approved, driver and rp_rpt must not be empty
    if ($status === 'approved' && (empty($driver) || empty($rp_rpt))) {
        $error = 'Driver and RP/RPT are required when approving a request.';
    } else {
        try {
            // Get driver id by name
            $driverId = null;
            if (!empty($driver)) {
                $stmt = $pdo->prepare("SELECT id FROM drivers WHERE name = ? LIMIT 1");
                $stmt->execute([$driver]);
                $driverRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($driverRow) $driverId = $driverRow['id'];
            }
            // If status is accomplished, set driver to Available
            if ($status === 'accomplished' && $driverId) {
                $stmt = $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?");
                $stmt->execute([$driverId]);
            }
            // If status is approved, set driver to On Trip/Occupied
            if ($status === 'approved' && $driverId) {
                $stmt = $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?");
                $stmt->execute([$driverId]);
            }
            $stmt = $pdo->prepare("UPDATE transportation_requests SET status=?, remarks=?, driver=?, vehicle=?, rp_rpt=? WHERE id=?");
            $stmt->execute([$status, $remarks, $driver, $vehicle, $rp_rpt, $id]);
            // Refresh to show updated values
            header('Location: request.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error updating request: ' . $e->getMessage();
        }
    }
}

// Fetch all drivers for dropdown
$driverOptions = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, vehicle, plate_number, status FROM drivers ORDER BY name ASC");
    $stmt->execute();
    $driverOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $driverOptions = [];
}

$dateToday = date('Y-m-d');
$requests = [];
$drivers = [];
try {
    $stmt = $pdo->prepare("SELECT driver, time_from, time_to, requester_name, requesting_unit, purpose FROM transportation_requests WHERE DATE(datetime_used) = ? ORDER BY driver, time_from");
    $stmt->execute([$dateToday]);
    $requests = $stmt->fetchAll();
    // Collect all drivers for today
    foreach ($requests as $req) {
        if (!empty($req['driver'])) {
            $drivers[$req['driver']][] = $req;
        }
    }
} catch (Exception $e) {
    $requests = [];
}

// Filtering logic
$statusFilter = $_GET['status'] ?? '';
$driverFilter = $_GET['driver'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$filterSql = [];
$params = [];
if ($statusFilter !== '') {
    $filterSql[] = 'status = ?';
    $params[] = $statusFilter;
}
if ($driverFilter !== '') {
    $filterSql[] = '(driver = ? OR driver_id = ?)';
    $params[] = $driverFilter;
    $params[] = $driverFilter;
}
if ($dateFilter !== '') {
    $filterSql[] = 'DATE(datetime_used) = ?';
    $params[] = $dateFilter;
}
$where = $filterSql ? ('WHERE ' . implode(' AND ', $filterSql)) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>NIA - Equipment Unit System</title>
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
    .dashboard-header-row {
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
    .dashboard-header-row .dashboard-title {
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
        .dashboard-header-row .dashboard-title {
            font-size: clamp(1em, 4vw, 1.15em);
            margin-bottom: 2px;
        }
    }
    @media (max-width: 500px) {
        .dashboard-header-row .dashboard-title {
            font-size: clamp(0.98em, 5vw, 1.08em);
            margin-bottom: 2px;
        }
    }
    .dashboard-header-row .dashboard-filter-form {
        flex: 1 1 220px;
        min-width: 180px;
        max-width: 100%;
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    @media (max-width: 700px) {
        .dashboard-header-row {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px !important;
            padding-left: 4vw;
            padding-right: 4vw;
        }
        .dashboard-header-row .dashboard-title {
            font-size: clamp(1em, 4vw, 1.15em);
            margin-bottom: 2px;
        }
        .dashboard-header-row .dashboard-filter-form {
            flex-direction: column;
            align-items: stretch !important;
            width: 100%;
            gap: 8px !important;
        }
    }
    @media (max-width: 500px) {
        .dashboard-header-row {
            padding-left: 2vw;
            padding-right: 2vw;
        }
        .dashboard-header-row .dashboard-title {
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
    .dashboard-table td textarea,
    .dashboard-table td select,
    .dashboard-table td input[type="text"] {
        min-width: 0;
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
        font-size: 1em;
        word-break: break-word;
    }
    .dashboard-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }
    .dashboard-table tbody tr:hover {
        background: #e0f2fe;
        transition: background 0.2s;
    }
    .dashboard-table textarea,
    .dashboard-table select,
    .dashboard-table input[type="text"] {
        width: 100%;
        min-width: 80px;
        max-width: 140px;
        font-size: 1em;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        box-sizing: border-box;
    }
    .dashboard-table button.main-btn {
        min-width: 70px;
        font-size: 0.98em;
        padding: 6px 14px;
        border-radius: 6px;
        border: none;
        background: #2563eb;
        color: #fff;
        cursor: pointer;
        transition: background 0.2s;
    }
    .dashboard-table button.main-btn:hover,
    .dashboard-table button.main-btn:focus {
        background: #1d4ed8;
    }
    .no-requests {
        text-align: center;
        color: #64748b;
        font-size: 1.1em;
        padding: 24px 0;
    }
    @media (max-width: 900px) {
        .main-content {
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
        }
        .dashboard-table {
            min-width: 700px;
        }
    }
    @media (max-width: 700px) {
        .main-content {
            padding-left: max(6vw, env(safe-area-inset-left));
            padding-right: max(6vw, env(safe-area-inset-right));
        }
        .dashboard-table {
            min-width: 600px;
            font-size: 0.97em;
        }
        .dashboard-table th, .dashboard-table td {
            padding: 8px 8px;
        }
    }
    @media (max-width: 600px) {
        .dashboard-table {
            min-width: 500px;
            font-size: 0.96em;
        }
        .dashboard-table th, .dashboard-table td {
            padding: 7px 6px;
        }
    }
    @media (max-width: 500px) {
        .main-content {
            padding-left: max(3vw, env(safe-area-inset-left));
            padding-right: max(3vw, env(safe-area-inset-right));
        }
        .dashboard-table {
            min-width: 400px;
            font-size: 0.95em;
        }
        .dashboard-table th, .dashboard-table td {
            padding: 6px 4px;
        }
    }
    @media (max-width: 430px) {
        .dashboard-table-wrapper {
            padding: 0;
        }
        .dashboard-table, .dashboard-table thead, .dashboard-table tbody, .dashboard-table tr {
            display: block;
            width: 100%;
        }
        .dashboard-table thead {
            display: none;
        }
        .dashboard-table tr {
            margin-bottom: 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(30,41,59,0.08);
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 8px 0 8px 0;
        }
        .dashboard-table td {
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 12px;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            font-size: 1em;
            background: #fff;
        }
        .dashboard-table td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #334155;
            min-width: 120px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .dashboard-table td:last-child {
            border-bottom: none;
        }
        .dashboard-table form {
            display: block;
        }
    }
    .dashboard-header-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px 18px;
        width: 100%;
        margin-bottom: 10px;
        padding-top: 18px;
        padding-left: 28px;
        padding-right: 28px;
        box-sizing: border-box;
    }
    .dashboard-header-row .dashboard-title {
        flex: 1 1 180px;
        min-width: 120px;
        margin: 0 8px 0 0;
        font-size: clamp(1.05em, 2.5vw, 1.35em);
        font-weight: 700;
        color: #1e293b;
        word-break: break-word;
        line-height: 1.2;
        padding: 0;
        max-width: 100%;
        white-space: normal;
    }
    @media (max-width: 700px) {
        .dashboard-header-row .dashboard-title {
            font-size: clamp(1em, 4vw, 1.15em);
            margin-bottom: 2px;
        }
    }
    @media (max-width: 500px) {
        .dashboard-header-row .dashboard-title {
            font-size: clamp(0.98em, 5vw, 1.08em);
            margin-bottom: 2px;
        }
    }
    .dashboard-header-row .dashboard-filter-form {
        display: flex;
        gap: 14px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        flex: 2 1 340px;
        min-width: 220px;
        max-width: 100%;
    }
    .dashboard-filter-form label {
        margin-bottom: 0;
        font-weight: 500;
        color: #334155;
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 110px;
        max-width: 100%;
        word-break: break-word;
    }
    .dashboard-filter-form .main-input {
        padding: 8px 12px;
        min-width: 120px;
        font-size: 1em;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        max-width: 100%;
        box-sizing: border-box;
    }
    .dashboard-filter-form .main-btn,
    .dashboard-filter-form .main-btn--primary,
    .dashboard-filter-form .main-btn--secondary {
        height: 38px;
        min-width: 80px;
        font-size: 1em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        line-height: 38px;
        padding: 0 16px;
        max-width: 100%;
        box-sizing: border-box;
    }
    @media (max-width: 1100px) {
        .dashboard-header-row {
            flex-direction: column;
            align-items: stretch;
            gap: 6px 0;
            padding-left: 16px;
            padding-right: 16px;
        }
        .dashboard-header-row .dashboard-title {
            margin-bottom: 0;
        }
        .dashboard-header-row .dashboard-filter-form {
            justify-content: flex-start;
            width: 100%;
            gap: 10px 0;
        }
    }
    @media (max-width: 700px) {
        .dashboard-header-row {
            padding-left: 6vw;
            padding-right: 6vw;
            padding-top: 12px;
        }
    }
    @media (max-width: 600px) {
        .dashboard-header-row {
            flex-direction: column;
            align-items: stretch;
            gap: 4px 0;
            padding-left: 8px;
            padding-right: 8px;
            padding-top: 10px;
        }
        .dashboard-header-row .dashboard-title {
            font-size: 1.1em;
        }
        .dashboard-header-row .dashboard-filter-form {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
            gap: 8px 0;
        }
        .dashboard-header-row .dashboard-filter-form label,
        .dashboard-header-row .dashboard-filter-form button,
        .dashboard-header-row .dashboard-filter-form a {
            width: 100%;
            min-width: 0;
            max-width: 100%;
        }
    }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <?php if (isset($error)): ?>
        <div class="notifier error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)): ?>
        <div class="notifier success">✅ Changes have been applied.</div>
    <?php endif; ?>
    <div class="main-content">
        <!-- <div class="main-card">
            <h1 class="main-title">NIA - Equipment Unit System</h1>
            <div class="main-buttons">
                <a href="driversperf.php" class="main-btn">Driver Performance Feedback</a>
                <a href="transporeq.php" class="main-btn">Transportation Request</a>
            </div>
        </div> -->
        <!-- Driver Dashboard -->
        <div class="dashboard-card">
            <div class="dashboard-header-row">
                <h2 class="dashboard-title">Request of Drivers</h2>
                <form method="get" class="dashboard-filter-form">
                    <label>Status:
                        <select name="status" class="main-input">
                            <option value="">All</option>
                            <option value="pending"<?= $statusFilter==='pending'?' selected':'' ?>>Pending</option>
                            <option value="approved"<?= $statusFilter==='approved'?' selected':'' ?>>Approved</option>
                            <option value="rejected"<?= $statusFilter==='rejected'?' selected':'' ?>>Rejected</option>
                            <option value="accomplished"<?= $statusFilter==='accomplished'?' selected':'' ?>>Accomplished</option>
                        </select>
                    </label>
                    <label>Driver:
                        <select name="driver" class="main-input">
                            <option value="">All</option>
                            <?php foreach ($driverOptions as $drv): ?>
                                <option value="<?= htmlspecialchars($drv['name']) ?>"<?= $driverFilter===$drv['name']?' selected':'' ?>><?= htmlspecialchars($drv['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Date:
                        <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="main-input">
                    </label>
                    <button type="submit" class="main-btn main-btn--primary">Filter</button>
                    <?php if ($statusFilter || $driverFilter || $dateFilter): ?>
                        <a href="request.php" class="main-btn main-btn--secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="dashboard-table-wrapper">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Requester name</th>
                            <th>Occupied (From - To)</th>
                            <th>Unit</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Assigned Driver</th>
                            <th>Vehicle</th>
                            <th>RP/RPT</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM transportation_requests $where ORDER BY datetime_used DESC, dateissued DESC");
                        $stmt->execute($params);
                        $allRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($allRequests) {
                            foreach ($allRequests as $req) {
                                $from = $req['time_from'] ? date('g:i A', strtotime($req['time_from'])) : '-';
                                $to = $req['time_to'] ? date('g:i A', strtotime($req['time_to'])) : '-';
                                echo '<form method="post" action="" style="margin:0;">';
                                echo '<tr style="vertical-align:top;">';
                                echo '<input type="hidden" name="request_id" value="' . htmlspecialchars($req['id']) . '">';
                                // Requester name
                                echo '<td data-label="Requester name">' . htmlspecialchars($req['requester_name']) . '</td>';
                                // Occupied (From - To)
                                echo '<td data-label="Occupied (From - To)">' . $from . ' - ' . $to . '</td>';
                                // Unit
                                echo '<td data-label="Unit">' . htmlspecialchars($req['requesting_unit']) . '</td>';
                                // Purpose
                                echo '<td data-label="Purpose" style="white-space:pre-line;">' . nl2br(htmlspecialchars($req['purpose'])) . '</td>';
                                // Status dropdown
                                echo '<td data-label="Status">';
                                echo '<select name="status" style="width:100%;">';
                                $statuses = ['pending', 'approved', 'rejected', 'accomplished'];
                                foreach ($statuses as $status) {
                                    $selected = ($req['status'] === $status) ? 'selected' : '';
                                    echo '<option value="' . $status . '" ' . $selected . '>' . ucfirst($status) . '</option>';
                                }
                                echo '</select>';
                                echo '</td>';
                                // Remarks textarea
                                echo '<td data-label="Remarks">';
                                echo '<textarea name="remarks" rows="2" style="width:100%; resize:vertical;">' . (isset($req['remarks']) ? htmlspecialchars($req['remarks']) : '') . '</textarea>';
                                echo '</td>';
                                // Assigned Driver dropdown
                                echo '<td data-label="Assigned Driver">';
                                echo '<select name="driver" class="driver-select" data-row="' . $req['id'] . '" style="width:100%;">';
                                echo '<option value="">-- Select Driver --</option>';
                                foreach ($driverOptions as $drv) {
                                    $selected = ($req['driver'] === $drv['name']) ? 'selected' : '';
                                    $label = htmlspecialchars($drv['name']) . ' (' . htmlspecialchars($drv['status']) . ')';
                                    if ($drv['vehicle']) $label .= ' - ' . htmlspecialchars($drv['vehicle']);
                                    if ($drv['plate_number']) $label .= ' [' . htmlspecialchars($drv['plate_number']) . ']';
                                    echo '<option value="' . htmlspecialchars($drv['name']) . '" data-vehicle="' . htmlspecialchars($drv['vehicle']) . '" ' . $selected . '>' . $label . '</option>';
                                }
                                echo '</select>';
                                echo '</td>';
                                // Vehicle input
                                echo '<td data-label="Vehicle">';
                                echo '<input type="text" name="vehicle" class="vehicle-input" data-row="' . $req['id'] . '" value="' . htmlspecialchars($req['vehicle']) . '" style="width:100%;">';
                                echo '</td>';
                                // RP/RPT input
                                echo '<td data-label="RP/RPT">';
                                echo '<input type="text" name="rp_rpt" value="' . (isset($req['rp_rpt']) ? htmlspecialchars($req['rp_rpt']) : '') . '" style="width:100%;">';
                                echo '</td>';
                                // Save/Update button
                                echo '<td data-label="Action" style="text-align:center;">';
                                echo '<button type="submit" class="main-btn" style="padding:6px 16px; width:100%;">Save</button>';
                                echo '</td>';
                                echo '</tr>';
                                echo '</form>';
                            }
                        } else {
                            echo '<tr><td colspan="10" class="no-requests">No transportation requests found.</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="10" class="no-requests">Error loading requests.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal for details -->
    <div id="details-modal" class="modal-overlay">
        <div class="modal-content">
            <button id="close-modal" class="modal-close">&times;</button>
            <h3 class="modal-title">Transportation Request Details</h3>
            <table id="modal-content-table" class="modal-table"></table>
        </div>
    </div>
    <script>
    document.querySelectorAll('.details-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var info = JSON.parse(this.getAttribute('data-info'));
            var html = '';
            html += '<tbody>';
            var order = ['driver', 'time_from', 'time_to', 'requester_name', 'requesting_unit', 'purpose'];
            var labels = {
                driver: 'Driver',
                time_from: 'Time From',
                time_to: 'Time To',
                requester_name: 'Requester',
                requesting_unit: 'Unit',
                purpose: 'Purpose'
            };
            order.forEach(function(key) {
                if (info.hasOwnProperty(key)) {
                    var value = info[key];
                    if ((key === 'time_from' || key === 'time_to') && value) {
                        var d = new Date('1970-01-01T' + value);
                        var hours = d.getHours();
                        var minutes = d.getMinutes();
                        var ampm = hours >= 12 ? 'PM' : 'AM';
                        hours = hours % 12;
                        hours = hours ? hours : 12;
                        var minStr = minutes < 10 ? '0' + minutes : minutes;
                        value = hours + ':' + minStr + ' ' + ampm;
                    }
                    html += '<tr>';
                    html += '<td style="font-weight:600; padding: 6px 12px; text-align:right; width: 40%; background:#f1f5f9;">' + labels[key] + ':</td>';
                    html += '<td style="padding: 6px 12px; text-align:left;">' + (value ? value : '-') + '</td>';
                    html += '</tr>';
                }
            });
            for (var key in info) {
                if (info.hasOwnProperty(key) && order.indexOf(key) === -1) {
                    var label = key.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase() });
                    html += '<tr>';
                    html += '<td style="font-weight:600; padding: 6px 12px; text-align:right; width: 40%; background:#f1f5f9;">' + label + ':</td>';
                    html += '<td style="padding: 6px 12px; text-align:left;">' + (info[key] ? info[key] : '-') + '</td>';
                    html += '</tr>';
                }
            });
            html += '</tbody>';
            document.getElementById('modal-content-table').innerHTML = html;
            document.getElementById('details-modal').classList.add('active');
        });
    });
    document.getElementById('close-modal').onclick = function() {
        document.getElementById('details-modal').classList.remove('active');
    };
    document.getElementById('details-modal').onclick = function(e) {
        if (e.target === this) this.classList.remove('active');
    };
    // Autofill vehicle when driver is selected
    window.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.driver-select').forEach(function(select) {
        select.addEventListener('change', function() {
          var selected = this.options[this.selectedIndex];
          var vehicle = selected.getAttribute('data-vehicle') || '';
          var row = this.getAttribute('data-row');
          var vehicleInput = document.querySelector('.vehicle-input[data-row="' + row + '"]');
          if(vehicleInput) vehicleInput.value = vehicle;
        });
      });
    });
    // Confirmation for setting status to 'accomplished'
    document.querySelectorAll('.dashboard-table form').forEach(function(form) {
      var statusSelect = form.querySelector('select[name="status"]');
      var saveBtn = form.querySelector('button[type="submit"]');
      if (statusSelect && saveBtn) {
        form.addEventListener('submit', function(e) {
          if (statusSelect.value === 'accomplished') {
            var confirmed = confirm('Are you sure you want to set this request status to Accomplished? This action cannot be undone.');
            if (!confirmed) e.preventDefault();
          }
        });
      }
    });
    </script>
</body>
</html>