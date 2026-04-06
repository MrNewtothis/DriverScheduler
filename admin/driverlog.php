<?php
session_start();
// Session and role check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../connection/db.php';
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

// Fetch all drivers and their info
$allDrivers = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM drivers ORDER BY name ASC");
    $stmt->execute();
    $allDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allDrivers = [];
}

// --- AUTO-UPDATE DRIVER STATUS BASED ON ACTIVE REQUESTS ---
foreach ($allDrivers as &$drv) {
    $driverName = $drv['name'];
    $driverId = $drv['id'];
    $now = date('Y-m-d H:i:s');
    // Only consider requests for today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM transportation_requests WHERE driver = ? AND status = 'approved' AND DATE(datetime_used) = ? AND CONCAT(datetime_used, ' ', time_to) > ? AND status != 'accomplished' LIMIT 1");
    $stmt->execute([$driverName, $today, $now]);
    $activeRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($activeRequest) {
        // If not already On Trip, update status
        if ($drv['status'] !== 'On Trip') {
            $update = $pdo->prepare("UPDATE drivers SET status = 'On Trip' WHERE id = ?");
            $update->execute([$driverId]);
            $drv['status'] = 'On Trip';
        }
    } else {
        // If no active trip, set to Available (unless Inactive)
        if ($drv['status'] !== 'Inactive' && $drv['status'] !== 'Available') {
            $update = $pdo->prepare("UPDATE drivers SET status = 'Available' WHERE id = ?");
            $update->execute([$driverId]);
            $drv['status'] = 'Available';
        }
    }
}
unset($drv); // Unset reference to avoid issues in later foreach loops

// Handle Add Driver POST
if (isset($_POST['add_driver_submit'])) {
    $add_name = trim($_POST['add_name'] ?? '');
    $add_vehicle = trim($_POST['add_vehicle'] ?? '');
    $add_plate = trim($_POST['add_plate'] ?? '');
    $add_phone = trim($_POST['add_phone'] ?? '');
    $add_email = trim($_POST['add_email'] ?? '');
    if ($add_name && $add_vehicle && $add_plate && $add_phone) {
        // Check for duplicate driver (by name and plate number or phone number)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM drivers WHERE (name = ? OR plate_number = ? OR phone_number = ?)");
        $stmt->execute([$add_name, $add_plate, $add_phone]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            echo '<script>alert("Driver with the same name, plate number, or phone number already exists.");</script>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO drivers (name, vehicle, plate_number, phone_number, email, status) VALUES (?, ?, ?, ?, ?, 'Available')");
            $stmt->execute([$add_name, $add_vehicle, $add_plate, $add_phone, $add_email]);
            header('Location: driverlog.php');
            exit;
        }
    } else {
        echo '<script>alert("Please fill in all required fields.");</script>';
    }
}

// Handle Edit Driver POST
if (isset($_POST['edit_driver_id'])) {
    $edit_id = $_POST['edit_driver_id'];
    $edit_name = trim($_POST['edit_name'] ?? '');
    $edit_vehicle = trim($_POST['edit_vehicle'] ?? '');
    $edit_plate = trim($_POST['edit_plate_number'] ?? '');
    $edit_status = trim($_POST['edit_status'] ?? 'Available');
    if ($edit_id && $edit_name && $edit_vehicle && $edit_plate) {
        $stmt = $pdo->prepare("UPDATE drivers SET name=?, vehicle=?, plate_number=?, status=? WHERE id=?");
        $stmt->execute([$edit_name, $edit_vehicle, $edit_plate, $edit_status, $edit_id]);
        header('Location: driverlog.php');
        exit;
    }
}

// Remove AJAX handler and implement server-side filtering
$search = trim($_GET['search'] ?? '');
$filteredDrivers = $allDrivers;
if ($search !== '' || (isset($_GET['status_filter']) && $_GET['status_filter'] !== '')) {
    $filteredDrivers = [];
    $statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    foreach ($allDrivers as $drv) {
        $haystack = strtolower(
            $drv['name'] . ' ' .
            $drv['vehicle'] . ' ' .
            $drv['plate_number'] . ' ' .
            ($drv['phone_number'] ?? '') . ' ' .
            ($drv['email'] ?? '')
        );
        $matchesSearch = ($search === '' || strpos($haystack, strtolower($search)) !== false);
        $matchesStatus = ($statusFilter === '' || $drv['status'] === $statusFilter);
        if ($matchesSearch && $matchesStatus) {
            $filteredDrivers[] = $drv;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"></html></html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>NIA - Equipment Unit System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <style>
        .main-btn--primary {
            background: #2563eb;
            color: #fff;
            border: none;
        }
        .main-btn--secondary {
            background: #f1f5f9;
            color: #222;
            border: 1.5px solid #cbd5e1;
        }
        .main-btn--info {
            background: #0ea5e9;
            color: #fff;
            border: none;
        }
        .main-btn--accent {
            background: #22c55e;
            color: #fff;
            border: none;
        }
        .main-btn--danger {
            background: #ef4444;
            color: #fff;
            border: none;
        }
        .main-btn {
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
            cursor: pointer;
        }
        .main-btn:active {
            opacity: 0.92;
        }
        .main-input {
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 1em;
            background: #f8fafc;
        }
        .dashboard-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 10px;
            padding-left: 24px;
            padding-right: 24px;
            
            box-sizing: border-box;
            gap: 0;
        }
        @media (max-width: 900px) {
            .dashboard-header-row {
                padding-left: 12px;
                padding-right: 12px;
            }
        }
        @media (max-width: 700px) {
            .dashboard-header-row {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px !important;
                padding-left: 4vw;
                padding-right: 4vw;
            }
            .dashboard-header-row > div {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
                width: 100%;
            }
            .dashboard-header-row form {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px !important;
                width: 100%;
            }
            .main-btn, .main-btn--primary, .main-btn--secondary, .main-btn--accent {
                width: 100%;
                min-width: 0;
                margin-bottom: 6px;
            }
        }
        @media (max-width: 500px) {
            .dashboard-header-row {
                padding-left: 2vw;
                padding-right: 2vw;
            }
        }
        .dashboard-table-wrapper {
            width: 100%;
            overflow-x: auto !important;
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
            box-sizing: border-box;
            margin-bottom: 0;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
            min-width: 0;
        }
        .dashboard-table {
            min-width: 1400px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
        }
        .dashboard-table-wrapper::-webkit-scrollbar {
            height: 10px;
        }
        .dashboard-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        .dashboard-table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        .dashboard-table-wrapper::-webkit-scrollbar {
            height: 10px;
        }
        .dashboard-table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        .dashboard-table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        @media (max-width: 900px) {
            .dashboard-table-wrapper {
                padding-left: max(8px, env(safe-area-inset-left));
                padding-right: max(8px, env(safe-area-inset-right));
            }
        }
        @media (max-width: 700px) {
            .dashboard-table-wrapper {
                padding-left: max(4vw, env(safe-area-inset-left));
                padding-right: max(4vw, env(safe-area-inset-right));
            }
        }
        @media (max-width: 500px) {
            .dashboard-table-wrapper {
                padding-left: max(2vw, env(safe-area-inset-left));
                padding-right: max(2vw, env(safe-area-inset-right));
            }
        }        .dashboard-table {
            min-width: 1500px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            background: #fff;
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
        }
        .dashboard-table th {
            background: #f1f5f9;
            font-weight: 700;
            font-size: 1em;
        }
        .dashboard-table tr:last-child td {
            border-bottom: none;
        }
        .main-btn, .main-btn--primary, .main-btn--secondary, .main-btn--accent {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            min-width: 80px;
            min-height: 38px;
            box-sizing: border-box;
            margin-right: 0;
            margin-left: 0;
        }
        .main-btn--primary {
            background: #2563eb;
            color: #fff;
        }
        .main-btn--secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1.5px solid #cbd5e1;
        }
        .main-btn--accent {
            background: #22c55e;
            color: #fff;
        }
        .main-btn:active, .main-btn--primary:active, .main-btn--secondary:active, .main-btn--accent:active {
            filter: brightness(0.95);
        }
        .action-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
        }
        @media (max-width: 900px) {
            .action-cell {
                flex-direction: row;
                gap: 8px;
            }
        }
        @media (max-width: 700px) {
            .action-cell {
                flex-direction: row;
                gap: 8px;
            }
        }
        @media (max-width: 600px) {
            .action-cell {
                flex-direction: column;
                gap: 8px;
                align-items: stretch;
            }
        }
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
            width: 100%;
            margin-bottom: 10px;
            padding-left: 16px;
            padding-right: 16px;
            box-sizing: border-box;
        }
        @media (max-width: 900px) {
            .dashboard-header-row {
                padding-left: 8px;
                padding-right: 8px;
            }
        }
        @media (max-width: 700px) {
            .dashboard-header-row {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px !important;
                padding-left: 4vw;
                padding-right: 4vw;
            }
        }
        @media (max-width: 500px) {
            .dashboard-header-row {
                padding-left: 2vw;
                padding-right: 2vw;
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
        }
        .dashboard-table th {
            background: #f1f5f9;
            font-weight: 700;
            font-size: 1em;
        }
        .dashboard-table tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <div class="main-content">
        <div class="dashboard-card">
            <div class="dashboard-header-row">
                <h2 class="dashboard-title" style="margin:0;">Drivers List</h2>
                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <form method="get" action="driverlog.php" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <input type="text" id="driver-search-input" name="search" placeholder="Search driver, vehicle, plate, phone" value="<?= htmlspecialchars($search) ?>" class="main-input" style="padding:8px 12px; border-radius:8px; border:1.5px solid #cbd5e1; min-width:200px; font-size:1em;">
                        <select name="status_filter" id="status-filter" class="main-input" style="padding:8px 12px; border-radius:8px; border:1.5px solid #cbd5e1; font-size:1em; min-width:120px;">
                            <option value="">All Statuses</option>
                            <option value="Available" <?= (isset($_GET['status_filter']) && $_GET['status_filter']==='Available') ? 'selected' : '' ?>>Available</option>
                            <option value="On Trip" <?= (isset($_GET['status_filter']) && $_GET['status_filter']==='On Trip') ? 'selected' : '' ?>>Occupied</option>
                            <option value="Inactive" <?= (isset($_GET['status_filter']) && $_GET['status_filter']==='Inactive') ? 'selected' : '' ?>>Unavailable</option>
                        </select>
                        <button type="submit" id="driver-search-btn" class="main-btn main-btn--primary" style="height:38px; min-width:80px; font-size:1em;">Search</button>
                        <a href="driverlog.php" class="main-btn main-btn--secondary" style="height:38px; min-width:80px; font-size:1em; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; line-height:38px; padding:0 16px;">Reset</a>
                    </form>
                    <button type="button" class="main-btn main-btn--accent" style="height:38px; min-width:120px; font-size:1em;" onclick="document.getElementById('add-driver-modal').style.display='block';">+ Add Driver</button>
                </div>
            </div>
            <!-- Add Driver Modal -->
            <div id="add-driver-modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
                <div style="background:#fff; border-radius:18px; box-shadow:0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10); max-width:420px; width:96vw; margin:60px auto; padding:36px 32px 26px 32px; position:relative; top:40px; border:1.5px solid #e5e7eb;">
                    <button type="button" onclick="document.getElementById('add-driver-modal').style.display='none';" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;">&times;</button>
                    <h3 style="margin-top:0; margin-bottom:22px; font-size:1.35em; color:#0f172a; letter-spacing:0.5px; font-weight:700;">Add New Driver</h3>
                    <form method="post" action="" autocomplete="off">
                        <div style="margin-bottom:18px;">
                            <label for="add_name" style="font-weight:600; color:#334155;">Name<span style="color:#ef4444;">*</span></label><br>
                            <input type="text" id="add_name" name="add_name" required class="main-input" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px;">
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="add_vehicle" style="font-weight:600; color:#334155;">Vehicle<span style="color:#ef4444;">*</span></label><br>
                            <input type="text" id="add_vehicle" name="add_vehicle" required class="main-input" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px;">
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="add_plate" style="font-weight:600; color:#334155;">Plate Number<span style="color:#ef4444;">*</span></label><br>
                            <input type="text" id="add_plate" name="add_plate" required class="main-input" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px;">
                        </div>
                        <div style="margin-bottom:18px;">
                            <label for="add_phone" style="font-weight:600; color:#334155;">Phone Number<span style="color:#ef4444;">*</span></label><br>
                            <input type="text" id="add_phone" name="add_phone" required class="main-input" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px;">
                        </div>
                        <div style="margin-bottom:24px;">
                            <label for="add_email" style="font-weight:600; color:#334155;">Email (Optional)</label><br>
                            <input type="email" id="add_email" name="add_email" class="main-input" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px;">
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap:12px;">
                            <button type="button" class="main-btn main-btn--secondary" style="background:#f1f5f9; color:#222; border:1.5px solid #cbd5e1; border-radius:8px; font-weight:600;" onclick="document.getElementById('add-driver-modal').style.display='none';">Cancel</button>
                            <button type="submit" class="main-btn main-btn--primary" name="add_driver_submit" style="background:#2563eb; color:#fff; border-radius:8px; font-weight:600;">Add Driver</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="dashboard-table-wrapper" id="driver-table-wrapper" style="overflow-x:auto;">
                <table class="dashboard-table" style="min-width:1500px; border-collapse:separate; border-spacing:0;">
                    <thead>
                        <tr>
                            <th style="min-width:140px;">Driver Name</th>
                            <th style="min-width:180px;">Info</th>
                            <th style="min-width:120px;">Vehicle</th>
                            <th style="min-width:120px;">License Plate</th>
                            <th style="min-width:120px;">Travel Log</th>
                            <th style="min-width:110px;">Status</th>
                            <th style="min-width:300px; white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="driver-table-body">
                    <?php if (empty($filteredDrivers)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#dc2626; font-weight:600; padding:32px 0; font-size:1.1em;">No drivers found matching your search.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filteredDrivers as $drv): ?>
                            <form method="post" action="">
                            <tr style="vertical-align:top;">
                                <td style="padding:10px 8px; word-break:normal; white-space:normal; max-width:180px;"><?= htmlspecialchars($drv['name']) ?></td>
                                <td style="padding:10px 8px; word-break:normal; white-space:normal; max-width:220px; vertical-align:middle;">
                                    <div style="display:inline-flex; align-items:center; justify-content:center; gap:10px; width:100%;">
                                        <button type="button" class="main-btn main-btn--info info-btn" data-driver-id="<?= htmlspecialchars($drv['id']) ?>" style="height:36px; min-width:110px; margin:0; display:inline-flex; align-items:center; justify-content:center;">View Info</button>
                                    </div>
                                    <div class="info-modal" id="info-modal-<?= htmlspecialchars($drv['id']) ?>" style="display:none; position:fixed; z-index:20; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:18px; min-width:320px; max-width:90vw; left:50%; top:50%; transform:translate(-50%,-50%);">
                                        <h4 style="margin-top:0; margin-bottom:18px; font-size:1.2em; color:#0f172a; font-weight:700; text-align:center;">Driver Info</h4>
                                        <table style="width:100%; font-size:1em; border-collapse:collapse; margin-bottom:18px;" id="info-view-<?= htmlspecialchars($drv['id']) ?>">
                                            <tbody>
                                                <tr>
                                                    <td style="font-weight:600; color:#334155; padding:8px 6px; width:120px;">Name:</td>
                                                    <td style="color:#222; padding:8px 6px;"><?= htmlspecialchars($drv['name']) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="font-weight:600; color:#334155; padding:8px 6px;">Vehicle:</td>
                                                    <td style="color:#222; padding:8px 6px;"><?= htmlspecialchars($drv['vehicle']) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="font-weight:600; color:#334155; padding:8px 6px;">Plate Number:</td>
                                                    <td style="color:#222; padding:8px 6px;"><?= htmlspecialchars($drv['plate_number']) ?></td>
                                                </tr>
                                                <tr>
                                                    <td style="font-weight:600; color:#334155; padding:8px 6px;">Status:</td>
                                                    <td style="color:#222; padding:8px 6px;"><?= htmlspecialchars($drv['status']) ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;" id="info-action-btns-<?= htmlspecialchars($drv['id']) ?>">
                                            <button type="button" class="main-btn main-btn--primary edit-info-btn" data-driver-id="<?= htmlspecialchars($drv['id']) ?>" style="border-radius:8px;">Edit</button>
                                            <button type="button" class="main-btn main-btn--secondary close-info-btn" data-driver-id="<?= htmlspecialchars($drv['id']) ?>" style="border-radius:8px;">Close</button>
                                        </div>
                                        <div id="edit-modal-container-<?= htmlspecialchars($drv['id']) ?>"></div>
                                    </div>
                                </td>
                                <td style="padding:10px 8px; word-break:normal; white-space:normal; max-width:120px;"><?= htmlspecialchars($drv['vehicle']) ?></td>
                                <td style="padding:10px 8px; word-break:normal; white-space:normal; max-width:120px;"><?= htmlspecialchars($drv['plate_number']) ?></td>
                                <td style="padding:10px 8px; text-align:center; vertical-align:middle;">
                                    <button type="button" class="main-btn main-btn--info travel-log-btn" data-driver="<?= htmlspecialchars($drv['name']) ?>" style="height:36px; min-width:110px; margin:0; display:inline-flex; align-items:center; justify-content:center;">View Log</button>
                                    <div class="travel-log-modal" id="travel-log-<?= htmlspecialchars($drv['id']) ?>" style="display:none; position:fixed; z-index:10; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:18px; min-width:340px; max-width:95vw; left:50%; top:50%; transform:translate(-50%,-50%);">
                                        <h4 style="margin-top:0;">Travel Log for <?= htmlspecialchars($drv['name']) ?></h4>
                                        <table style="width:100%; font-size:0.97em; border-collapse:collapse;">
                                            <thead style="background:#f1f5f9;">
                                                <tr>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Date</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Driver</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Time From</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Time To</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Requester</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Unit</th>
                                                    <th style="padding:8px 6px; border-bottom:1.5px solid #e5e7eb; text-align:left;">Purpose</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $driverId = $drv['id'];
                                            $monthStart = date('Y-m-01');
                                            $monthEnd = date('Y-m-t');
                                            $stmt = $pdo->prepare("SELECT l.*, d.name as driver_name FROM driver_travel_logs l JOIN drivers d ON l.driver_id = d.id WHERE l.driver_id = ? AND l.log_date BETWEEN ? AND ? ORDER BY l.log_date DESC, l.time_from DESC");
                                            $stmt->execute([$driverId, $monthStart, $monthEnd]);
                                            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            if (!$logs) {
                                                $stmt = $pdo->prepare("SELECT driver as driver_name, time_from, time_to, requester_name, requesting_unit, purpose, DATE(datetime_used) as log_date FROM transportation_requests WHERE driver = ? AND DATE(datetime_used) BETWEEN ? AND ? ORDER BY datetime_used DESC, dateissued DESC");
                                                $stmt->execute([$drv['name'], $monthStart, $monthEnd]);
                                                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            }
                                            if ($logs) {
                                                foreach ($logs as $log) {
                                                    echo '<tr>';
                                                    echo '<td>' . ($log['log_date'] ? date('M d, Y', strtotime($log['log_date'])) : '-') . '</td>';
                                                    echo '<td>' . htmlspecialchars($log['driver_name']) . '</td>';
                                                    echo '<td>' . ($log['time_from'] ? date('g:i A', strtotime($log['time_from'])) : '-') . '</td>';
                                                    echo '<td>' . ($log['time_to'] ? date('g:i A', strtotime($log['time_to'])) : '-') . '</td>';
                                                    echo '<td>' . htmlspecialchars($log['requester_name']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($log['requesting_unit']) . '</td>';
                                                    echo '<td>' . htmlspecialchars($log['purpose']) . '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {    
                                                echo '<tr><td colspan="7">No travel logs found for this month.</td></tr>';
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                        <button type="button" class="main-btn main-btn--secondary close-log-btn" style="margin-top:12px;">Close</button>
                                    </div>
                                </td>
                                <td style="padding:10px 8px; text-align:center;">
                                    <?php
                                    $status = strtolower($drv['status']);
                                    $color = '#0ea5e9';
                                    if ($status === 'available') $color = '#22c55e';
                                    elseif ($status === 'occupied' || $status === 'on trip') $color = '#f59e42';
                                    elseif ($status === 'unavailable' || $status === 'inactive') $color = '#ef4444';
                                    ?>
                                    <span style="display:inline-block; padding:4px 14px; border-radius:12px; font-weight:600; color:#fff; background:<?= $color ?>; min-width:80px; text-align:center;">
                                        <?= ucfirst($drv['status']) ?>
                                    </span>
                                </td>
                                <td style="padding:10px 8px; text-align:center; min-width:180px; vertical-align:middle;">
    <form method="post" action="" style="margin:0; padding:0;">
        <input type="hidden" name="driver_id" value="<?= htmlspecialchars($drv['id']) ?>">
        <select name="status" class="main-input" style="padding:4px 8px; min-width:120px; width:100%; height:38px; margin-bottom:8px;">
            <option value="Available" <?= $drv['status']==='Available'?'selected':'' ?>>Available</option>
            <option value="On Trip" <?= $drv['status']==='On Trip'?'selected':'' ?>>Occupied</option>
            <option value="Inactive" <?= $drv['status']==='Inactive'?'selected':'' ?>>Unavailable</option>
        </select>
        <br>
        <button type="submit" class="main-btn main-btn--primary" style="padding:8px 22px; height:38px; min-width:100px; font-size:1em; width:100%;">Save</button>
    </form>
</td>
                            </tr>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    function attachDriverTableEventListeners() {
        document.querySelectorAll('.travel-log-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var driverName = this.getAttribute('data-driver');
                var modal = document.getElementById('travel-log-' + this.closest('tr').querySelector('input[name="driver_id"]').value);
                if (modal) {
                    modal.style.display = 'block';
                    var rect = this.getBoundingClientRect();
                    modal.style.top = (window.scrollY + rect.bottom + 8) + 'px';
                    modal.style.left = (window.scrollX + rect.left - 40) + 'px';
                }
            });
        });
        document.querySelectorAll('.close-log-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.travel-log-modal').style.display = 'none';
            });
        });
        document.querySelectorAll('.info-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var driverId = this.getAttribute('data-driver-id');
                var modal = document.getElementById('info-modal-' + driverId);
                if (modal) {
                    modal.style.display = 'block';
                    var rect = this.getBoundingClientRect();
                    modal.style.top = (window.scrollY + rect.bottom + 8) + 'px';
                    modal.style.left = (window.scrollX + rect.left - 40) + 'px';
                }
            });
        });
        document.querySelectorAll('.edit-info-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-driver-id');
                document.getElementById('info-view-' + id).style.display = 'none';
                var btns = document.getElementById('info-action-btns-' + id);
                if (btns) btns.style.display = 'none';
                var container = document.getElementById('edit-modal-container-' + id);
                container.innerHTML = `
                    <form class="driver-info-edit" id="info-edit-${id}" method="post">
                        <input type="hidden" name="edit_driver_id" value="${id}">
                        <div style="margin-bottom:16px;"><label style="font-weight:600; color:#334155;">Name:</label><input type="text" name="edit_name" value="${document.querySelector('#info-modal-' + id + ' td:nth-child(2)').innerText}" required class="main-input" style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:1em; background:#f8fafc; margin-top:4px;"></div>
                        <div style="margin-bottom:16px;"><label style="font-weight:600; color:#334155;">Vehicle:</label><input type="text" name="edit_vehicle" value="${document.querySelector('#info-modal-' + id + ' tr:nth-child(2) td:nth-child(2)').innerText}" required class="main-input" style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:1em; background:#f8fafc; margin-top:4px;"></div>
                        <div style="margin-bottom:16px;"><label style="font-weight:600; color:#334155;">Plate Number:</label><input type="text" name="edit_plate_number" value="${document.querySelector('#info-modal-' + id + ' tr:nth-child(3) td:nth-child(2)').innerText}" required class="main-input" style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:1em; background:#f8fafc; margin-top:4px;"></div>
                        <div style="margin-bottom:16px;"><label style="font-weight:600; color:#334155;">Status:</label>
                            <select name="edit_status" class="main-input" style="width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:1em; background:#f8fafc; margin-top:4px;">
                                <option value="Available">Available</option>
                                <option value="On Trip">On Trip</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
                            <button type="button" class="main-btn main-btn--secondary cancel-edit-info-btn" data-driver-id="${id}" style="background:#f1f5f9; color:#222; border:1.5px solid #cbd5e1; border-radius:8px;">Cancel</button>
                            <button type="submit" class="main-btn main-btn--primary" style="background:#22c55e; color:#fff; border-radius:8px;">Save</button>
                        </div>
                    </form>
                `;
                var status = document.querySelector('#info-modal-' + id + ' tr:nth-child(4) td:nth-child(2)').innerText;
                var select = container.querySelector('select[name="edit_status"]');
                if (select) select.value = status;
                var firstInput = container.querySelector('input[type="text"]');
                if (firstInput) firstInput.focus();
            });
        });
        document.querySelectorAll('.close-info-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.closest('.info-modal').style.display = 'none';
                var id = this.getAttribute('data-driver-id');
                if (id) {
                    var editForm = document.getElementById('info-edit-' + id);
                    if (editForm) editForm.style.display = 'none';
                    document.getElementById('info-view-' + id).style.display = 'block';
                }
            });
        });
        document.querySelectorAll('.report-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var driverId = this.getAttribute('data-driver-id');
                window.location.href = 'report.php?driver_id=' + driverId;
            });
        });
    }

    // Initial attach on page load
    attachDriverTableEventListeners();
    </script>
</body>
</html>