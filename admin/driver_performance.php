<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';

// Fetch all drivers for filter dropdown
$allDrivers = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM drivers ORDER BY name ASC");
    $stmt->execute();
    $allDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allDrivers = [];
}

// Filtering
$driverId = $_GET['driver_id'] ?? '';
$where = '';
$params = [];
if ($driverId !== '') {
    $where = 'WHERE driver_id = ?';
    $params[] = $driverId;
}

// Fetch evaluations
$evaluations = [];
try {
    $sql = "SELECT e.*, d.name as driver_name, u.unit as evaluator_unit FROM driver_performance_evaluations e 
            JOIN drivers d ON e.driver_id = d.id 
            LEFT JOIN users u ON e.evaluated_by = u.name ";
    if ($where) $sql .= $where;
    $sql .= " ORDER BY e.date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $evaluations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Performance Dashboard - NIA Equipment Unit System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <style>
    .review-modal-bg {
        display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35); z-index:1000; align-items:center; justify-content:center;
    }
    .review-modal-bg.active { display:flex; }
    .review-modal {
        background:#fff; border-radius:8px; max-width:520px; width:95vw; padding:32px 24px 24px 24px; box-shadow:0 8px 32px rgba(0,0,0,0.18);
        position:relative;
    }
    .review-modal h3 { margin-top:0; margin-bottom:18px; color:#1e293b; }
    .review-modal .close-btn {
        position:absolute; top:12px; right:16px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;
    }
    .review-modal .review-row { margin-bottom:10px; }
    .review-modal label { font-weight:600; color:#334155; }
    .review-modal .review-value { margin-left:8px; color:#0f172a; }

    /* Responsive table styles */
    @media (max-width: 1100px) {
        .dashboard-table-wrapper {
            overflow-x: auto;
            width: 100%;
            margin-left: 10px;
            margin-right: 10px;
        }
        .dashboard-table {
            min-width: 900px;
        }
    }
    @media (max-width: 900px) {
        .dashboard-table-wrapper {
            margin-left: 6px;
            margin-right: 6px;
        }
        .dashboard-table {
            min-width: 700px;
        }
        .dashboard-title {
            font-size: 1.1em;
        }
    }
    @media (max-width: 700px) {
        .dashboard-table {
            font-size: 0.92em;
        }
        .dashboard-table th, .dashboard-table td {
            padding: 6px 6px;
        }
        .dashboard-table {
            min-width: 600px;
        }
        .dashboard-title {
            font-size: 1em;
        }
        .dashboard-card form {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }
        .dashboard-card form > * {
            width: 100% !important;
            min-width: 0 !important;
            margin-left: 0 !important;
        }
        .main-btn {
            width: 100%;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }
    @media (max-width: 500px) {
        .dashboard-table {
            font-size: 0.85em;
            min-width: 500px;
        }
        .dashboard-title {
            font-size: 0.95em;
        }
        .dashboard-card form {
            gap: 6px;
        }
    }
    .dashboard-table-wrapper {
        width: 100%;
        overflow-x: auto;
        padding-left: max(16px, env(safe-area-inset-left));
        padding-right: max(16px, env(safe-area-inset-right));
        box-sizing: border-box;
        margin-bottom: 0;
    }
    @media (max-width: 900px) {
        .dashboard-table-wrapper {
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
        }
    }
    @media (max-width: 700px) {
        .dashboard-table-wrapper {
            padding-left: max(6vw, env(safe-area-inset-left));
            padding-right: max(6vw, env(safe-area-inset-right));
        }
    }
    @media (max-width: 500px) {
        .dashboard-table-wrapper {
            padding-left: max(3vw, env(safe-area-inset-left));
            padding-right: max(3vw, env(safe-area-inset-right));
        }
    }
    .main-content {
        padding-left: max(16px, env(safe-area-inset-left));
        padding-right: max(16px, env(safe-area-inset-right));
        box-sizing: border-box;
        max-width: 100vw;
    }
    @media (max-width: 900px) {
        .main-content {
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
        }
    }
    @media (max-width: 700px) {
        .main-content {
            padding-left: max(6vw, env(safe-area-inset-left));
            padding-right: max(6vw, env(safe-area-inset-right));
        }
    }
    @media (max-width: 500px) {
        .main-content {
            padding-left: max(3vw, env(safe-area-inset-left));
            padding-right: max(3vw, env(safe-area-inset-right));
        }
    }
    /* Responsive dashboard header and table styles - update for wrapping */
    .dashboard-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 10px;
        padding-left: 16px;
        padding-right: 16px;
        box-sizing: border-box;
        flex-wrap: wrap;
        gap: 12px;
    }
    .dashboard-header-row > * {
        flex: 1 1 auto;
        min-width: 180px;
    }
    .dashboard-header-row form {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
        margin: 0;
        width: auto;
        justify-content: flex-end;
    }
    .dashboard-header-row form > * {
        flex: 0 1 auto;
        min-width: 120px;
    }
    @media (max-width: 700px) {
        .dashboard-header-row form {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px !important;
            width: 100%;
            justify-content: flex-start;
        }
        .dashboard-header-row form > * {
            width: 100% !important;
            min-width: 0 !important;
            margin-left: 0 !important;
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
    .dashboard-card {
        margin-bottom: 32px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10);
        padding: 28px 0 18px 0;
        overflow-x: visible;
        margin-left: auto;
        margin-right: auto;
        max-width: 100%;
    }
    @media (min-width: 600px) {
        .dashboard-card {
            max-width: 1200px;
        }
    }
    </style>
</head>
<body>
<?php include 'admin_header.php'; ?>
<div class="main-content">
    <div class="dashboard-card">
        <div class="dashboard-header-row">
            <h2 class="dashboard-title" style="margin:0;">Driver Performance Dashboard</h2>
            <form method="get">
                <label for="driver_id" style="font-weight:600; color:#334155; margin-bottom:0;">Driver:</label>
                <select name="driver_id" id="driver_id" class="main-input" style="padding:8px 12px; min-width:180px;">
                    <option value="">All Drivers</option>
                    <?php foreach ($allDrivers as $drv): ?>
                        <option value="<?= $drv['id'] ?>" <?= $driverId==$drv['id']?'selected':'' ?>><?= htmlspecialchars($drv['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="main-btn main-btn--primary" style="height:38px; min-width:80px; font-size:1em;">Filter</button>
                <?php if ($driverId): ?>
                    <a href="driver_performance.php" class="main-btn main-btn--secondary" style="height:38px; min-width:80px; font-size:1em; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; line-height:38px; padding:0 16px;">Reset</a>
                <?php endif; ?>
                <button type="button" id="printAllBtn" class="main-btn main-btn--secondary" style="height:38px; min-width:100px; font-size:1em; margin-left:12px;">Print All</button>
                <!-- <button type="button" id="printAllReviewsBtn" class="main-btn main-btn--secondary" style="height:38px; min-width:140px; font-size:1em; margin-left:12px;">Print All Reviews</button> -->
            </form>
        </div>
        <div class="dashboard-table-wrapper">
            <table class="dashboard-table" style="width:100%; border-collapse:collapse;">
                <thead style="background:#f1f5f9;">
                    <tr>
                        <th>Date</th>
                        <th>Driver</th>
                        <th>Evaluator</th>
                        <th>Unit</th>
                        <th>Vehicle</th>
                        <th>Destination</th>
                        <th>Ratings</th>
                        <th>Comments</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($evaluations): ?>
                    <?php foreach ($evaluations as $eval): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($eval['date']))) ?></td>
                            <td><?= htmlspecialchars($eval['driver_name']) ?></td>
                            <td><?= htmlspecialchars($eval['evaluated_by']) ?></td>
                            <td><?= htmlspecialchars($eval['evaluator_unit'] ?? '') ?></td>
                            <td><?= htmlspecialchars($eval['vehicle_type']) ?> (<?= htmlspecialchars($eval['vehicle_plate_no']) ?>)</td>
                            <td><?= htmlspecialchars($eval['destination']) ?></td>
                            <td>
                                <?php
                                $ratings = [];
                                for ($i=1; $i<=8; $i++) {
                                    if (!empty($eval['r'.$i])) $ratings[] = $eval['r'.$i];
                                }
                                echo htmlspecialchars(implode(', ', $ratings));
                                ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($eval['comments'])) ?></td>
                            <td>
                                <a href="driver_performance_view.php?id=<?= $eval['id'] ?>" class="main-btn main-btn--secondary" style="padding:4px 14px; font-size:0.98em; text-decoration:none;">View Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center; color:#dc2626; font-weight:600; padding:32px 0; font-size:1.1em;">No evaluations found for the selected driver.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="review-modal-bg" id="reviewModalBg">
    <div class="review-modal" id="reviewModal" style="max-width:980px;width:98vw;padding:0;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.18);display:flex;flex-direction:column;height:90vh;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 32px 0 32px;gap:12px;position:sticky;top:0;z-index:3;background:#fff;">
            <button id="printReviewBtn" style="background:#1e293b;color:#fff;border:none;padding:7px 18px;border-radius:6px;font-size:1em;cursor:pointer;box-shadow:0 2px 8px #0001;">Print</button>
            <button class="close-btn" onclick="closeReviewModal()" style="z-index:2;font-size:2em;background:none;border:none;color:#64748b;">&times;</button>
        </div>
        <div id="reviewDetails" style="padding:0 32px 24px 32px;overflow-y:auto;flex:1;min-height:0;"></div>
    </div>
</div>
<script>
function closeReviewModal() {
    document.getElementById('reviewModalBg').classList.remove('active');
}
document.querySelectorAll('.view-review-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var data = JSON.parse(this.getAttribute('data-review'));
        var particulars = [
            'Punctuality',
            'Safe driving. (No Unnecessary phone calls: obedience to traffic rules: no distractions like radios/TV: Courtesy to other motorists)',
            'Courtesy',
            'Personal Attitude',
            'Knowledge of direction to destination',
            'Personal Hygiene',
            'Troubleshooting (Only if the vehicle encounters problems. If not, write "Not Applicable")',
            'Vehicle Cleanliness'
        ];
        var opts = ['Poor','Fair','Good','Excellent'];
        var html = '';
        html += '<div id="printableReview" style="background:#fff;">';
        html += '<img src="../imgs/topformat.png" alt="Header" style="width:100%;max-width:900px;display:block;margin:0 auto 18px auto;">';
        html += '<div class="perf-title" style="text-align:center;font-weight:700;font-size:1.25em;margin-bottom:8px;">DRIVER\'S PERFORMANCE EVALUATION <br><span style="font-size: 0.4em; color:rgb(0, 0, 0);">(To be submitted to the <b>Chief, Engineering and operation Division-PIMO</b> after completion of travel)</span></div>';
        html += '<table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">';
        html += '<tr><td style="font-weight:600;width:180px;padding:8px 10px;">Name of Driver:</td><td style="padding:8px 10px;">'+data.driver_name+'</td><td></td><td></td><td style="font-weight:600;width:80px;padding:8px 10px;">Date</td><td style="padding:8px 10px;">'+data.date+'</td></tr>';
        html += '<tr><td style="font-weight:600;padding:8px 10px;">Type & Make of Vehicle</td><td style="padding:8px 10px;">'+(data.vehicle_type||'')+'</td><td></td><td></td><td style="font-weight:600;padding:8px 10px;">Vehicle Plate No.</td><td style="padding:8px 10px;">'+(data.vehicle_plate_no||'')+'</td></tr>';
        html += '<tr><td style="font-weight:600;padding:8px 10px;">Official Destination</td><td style="padding:8px 10px;">'+(data.destination||'')+'</td></tr>';
        html += '<tr><td style="font-weight:600;padding:8px 10px;">Purpose of Travel</td><td style="padding:8px 10px;">'+(data.purpose_of_travel||'')+'</td></tr>';
        html += '<tr><td style="font-weight:600;padding:8px 10px;">Duration of Travel</td><td style="padding:8px 10px;">'+(data.duration_of_travel||'')+'</td></tr>';
        html += '</table>';
        html += '<table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">';
        html += '<tr style="background:#f1f5f9;"><th style="padding:8px 10px;">PARTICULARS</th><th style="padding:8px 10px;">Poor</th><th style="padding:8px 10px;">Fair</th><th style="padding:8px 10px;">Good</th><th style="padding:8px 10px;">Excellent</th><th style="padding:8px 10px;">REMARKS</th></tr>';
        for (let i=1; i<=8; i++) {
            html += '<tr>';
            html += '<td style="vertical-align:top;padding:8px 10px;">' + i + '. ' + particulars[i-1] + '</td>';
            for (let j=0; j<4; j++) {
                let checked = (data['r'+i] === opts[j]) ? 'checked' : '';
                html += '<td class="center" style="padding:8px 10px;"><input type="radio" disabled '+checked+' style="pointer-events:none;transform:scale(1.1);margin:0;"></td>';
            }
            html += '<td style="padding:8px 10px;"><textarea rows="1" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:28px;">'+(data['remarks'+i]||'')+'</textarea></td>';
            html += '</tr>';
        }
        html += '</table>';
        html += '<div style="margin:16px 0 0 0;"><b>Comments & Observations:</b><br><textarea rows="2" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:38px;">'+(data.comments||'')+'</textarea></div>';
        html += '<div style="margin:24px 0 0 0;">';
        html += '<table style="width:100%;border-collapse:collapse;">';
        html += '<tr><td style="font-weight:600;padding:8px 10px;width:160px;">Evaluated by:</td><td style="padding:8px 10px;">'+(data.evaluated_by||'')+'</td></tr>';
        html += '<tr><td></td><td>Official Passenger/s Team Leader</td></tr>';
        html += '<tr><td><br></td></tr>';
        html += '<tr><td style="font-weight:600;padding:8px 10px;">Noted by:</td><td style="padding:8px 10px;">'+(data.noted_by||'')+'</td></tr>';
        html += '</table>';
        html += '</div>';
        html += '<img src="../imgs/bottomformat.png" alt="Footer" style="width:100%;max-width:900px;display:block;margin:24px auto 0 auto;">';
        html += '<div style="text-align:left; margin-top:18px;"><font>NIA-PIMO-ENG-EU-INT-Form13 Rev.06</font></div>';
        html += '</div>';
        document.getElementById('reviewDetails').innerHTML = html;
        document.getElementById('reviewModalBg').classList.add('active');
        setTimeout(function(){
            var printBtn = document.getElementById('printReviewBtn');
            if (printBtn) {
                printBtn.onclick = function() {
                    // Clone the printable content to a hidden iframe for printing
                    var printContents = document.getElementById('printableReview').outerHTML;
                    var iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = 'none';
                    document.body.appendChild(iframe);
                    var doc = iframe.contentWindow.document;
                    doc.open();
                    doc.write('<!DOCTYPE html><html><head><title>Print Review</title>');
                    doc.write('<meta charset="utf-8">');
                    doc.write('<link rel="stylesheet" href="../css/driverperf.css">');
                    doc.write('<style>body{background:#fff!important;margin:0;padding:0;} .perf-table{border-collapse:collapse;width:100%;margin-bottom:14px;} .perf-table td,.perf-table th{border:1px solid #cbd5e1;padding:8px 10px;} .perf-title{text-align:center;font-weight:700;font-size:1.25em;margin-bottom:8px;} textarea[readonly]{border:none;background:#f1f5f9;resize:none;} @media print{body{margin:0!important;padding:0!important;} #printableReview{margin:0!important;padding:0!important;}}</style>');
                    doc.write('</head><body>');
                    doc.write(printContents);
                    doc.write('</body></html>');
                    doc.close();
                    // Wait for images to load before printing
                    function doPrintIframe() {
                        var imgs = doc.images;
                        if (imgs.length > 0) {
                            var loaded = 0;
                            for (var i = 0; i < imgs.length; i++) {
                                if (imgs[i].complete) {
                                    loaded++;
                                } else {
                                    imgs[i].onload = imgs[i].onerror = function() {
                                        loaded++;
                                        if (loaded === imgs.length) setTimeout(function(){iframe.contentWindow.focus();iframe.contentWindow.print();document.body.removeChild(iframe);}, 100);
                                    };
                                }
                            }
                            if (loaded === imgs.length) setTimeout(function(){iframe.contentWindow.focus();iframe.contentWindow.print();document.body.removeChild(iframe);}, 100);
                        } else {
                            setTimeout(function(){iframe.contentWindow.focus();iframe.contentWindow.print();document.body.removeChild(iframe);}, 100);
                        }
                    }
                    doPrintIframe();
                };
            }
        }, 200);
    });
});
document.getElementById('reviewModalBg').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
document.getElementById('printAllBtn').onclick = function() {
    // Clone the table for printing
    var table = document.querySelector('.dashboard-table-wrapper').innerHTML;
    var win = window.open('', '', 'width=1200,height=800');
    win.document.write('<html><head><title>Print All Evaluations</title>');
    win.document.write('<link rel="stylesheet" href="../css/admin.css">');
    win.document.write('<style>@media print { body { margin:0; } .dashboard-table { width:100%; border-collapse:collapse; } .dashboard-table th, .dashboard-table td { border:1px solid #cbd5e1; padding:8px 10px; } }</style>');
    win.document.write('</head><body>');
    win.document.write('<h2 style="text-align:center;">Driver Performance Evaluations</h2>');
    win.document.write(table);
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function(){ win.print(); win.close(); }, 500);
};
document.getElementById('printAllReviewsBtn').onclick = function() {
    var evaluations = <?php echo json_encode($evaluations); ?>;
    if (!evaluations.length) {
        alert('No evaluations to print.');
        return;
    }
    var particulars = [
        'Punctuality',
        'Safe driving. (No Unnecessary phone calls: obedience to traffic rules: no distractions like radios/TV: Courtesy to other motorists)',
        'Courtesy',
        'Personal Attitude',
        'Knowledge of direction to destination',
        'Personal Hygiene',
        'Troubleshooting (Only if the vehicle encounters problems. If not, write "Not Applicable")',
        'Vehicle Cleanliness'
    ];
    var opts = ['Poor','Fair','Good','Excellent'];
    var win = window.open('', '', 'width=1200,height=800');
    win.document.write('<html><head><title>Print All Driver Reviews</title>');
    win.document.write('<link rel="stylesheet" href="../css/driverperf.css">');
    win.document.write('<style>body{background:#fff!important;margin:0;padding:0;} .perf-table{border-collapse:collapse;width:100%;margin-bottom:14px;} .perf-table td,.perf-table th{border:1px solid #cbd5e1;padding:8px 10px;} .perf-title{text-align:center;font-weight:700;font-size:1.25em;margin-bottom:8px;} textarea[readonly]{border:none;background:#f1f5f9;resize:none;} @media print{body{margin:0!important;padding:0!important;} .printableReview{margin:0!important;padding:0!important;page-break-after:always;} .printableReview:last-child{page-break-after:auto;}}</style>');
    win.document.write('</head><body>');
    win.document.write('<h2 style="text-align:center;">Driver\'s Performance Evaluations</h2>');
    evaluations.forEach(function(data, idx) {
        win.document.write('<div class="printableReview" style="background:#fff;">');
        win.document.write('<img src="../imgs/topformat.png" alt="Header" style="width:100%;max-width:900px;display:block;margin:0 auto 18px auto;">');
        win.document.write('<div class="perf-title" style="text-align:center;font-weight:700;font-size:1.25em;margin-bottom:8px;">DRIVER\'S PERFORMANCE EVALUATION <br><span style="font-size: 0.4em; color:rgb(0, 0, 0);">(To be submitted to the <b>Chief, Engineering and operation Division-PIMO</b> after completion of travel)</span></div>');
        win.document.write('<table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">');
        win.document.write('<tr><td style="font-weight:600;width:180px;padding:8px 10px;">Name of Driver:</td><td style="padding:8px 10px;">'+(data.driver_name||'')+'</td><td></td><td></td><td style="font-weight:600;width:80px;padding:8px 10px;">Date</td><td style="padding:8px 10px;">'+(data.date||'')+'</td></tr>');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;">Type & Make of Vehicle</td><td style="padding:8px 10px;">'+(data.vehicle_type||'')+'</td><td></td><td></td><td style="font-weight:600;padding:8px 10px;">Vehicle Plate No.</td><td style="padding:8px 10px;">'+(data.vehicle_plate_no||'')+'</td></tr>');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;">Official Destination</td><td style="padding:8px 10px;">'+(data.destination||'')+'</td></tr>');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;">Purpose of Travel</td><td style="padding:8px 10px;">'+(data.purpose_of_travel||'')+'</td></tr>');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;">Duration of Travel</td><td style="padding:8px 10px;">'+(data.duration_of_travel||'')+'</td></tr>');
        win.document.write('</table>');
        win.document.write('<table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">');
        win.document.write('<tr style="background:#f1f5f9;"><th style="padding:8px 10px;">PARTICULARS</th><th style="padding:8px 10px;">Poor</th><th style="padding:8px 10px;">Fair</th><th style="padding:8px 10px;">Good</th><th style="padding:8px 10px;">Excellent</th><th style="padding:8px 10px;">REMARKS</th></tr>');
        for (var i=1; i<=8; i++) {
            win.document.write('<tr>');
            win.document.write('<td style="vertical-align:top;padding:8px 10px;">' + i + '. ' + particulars[i-1] + '</td>');
            for (var j=0; j<4; j++) {
                var checked = (data['r'+i] === opts[j]) ? 'checked' : '';
                win.document.write('<td class="center" style="padding:8px 10px;"><input type="radio" disabled '+checked+' style="pointer-events:none;transform:scale(1.1);margin:0;"></td>');
            }
            win.document.write('<td style="padding:8px 10px;"><textarea rows="1" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:28px;">'+(data['remarks'+i]||'')+'</textarea></td>');
            win.document.write('</tr>');
        }
        win.document.write('</table>');
        win.document.write('<div style="margin:16px 0 0 0;"><b>Comments & Observations:</b><br><textarea rows="2" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:38px;">'+(data.comments||'')+'</textarea></div>');
        win.document.write('<div style="margin:24px 0 0 0;">');
        win.document.write('<table style="width:100%;border-collapse:collapse;">');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;width:160px;">Evaluated by:</td><td style="padding:8px 10px;">'+(data.evaluated_by||'')+'</td></tr>');
        win.document.write('<tr><td></td><td>Official Passenger/s Team Leader</td></tr>');
        win.document.write('<tr><td><br></td></tr>');
        win.document.write('<tr><td style="font-weight:600;padding:8px 10px;">Noted by:</td><td style="padding:8px 10px;">'+(data.noted_by||'')+'</td></tr>');
        win.document.write('</table>');
        win.document.write('</div>');
        win.document.write('<img src="../imgs/bottomformat.png" alt="Footer" style="width:100%;max-width:900px;display:block;margin:24px auto 0 auto;">');
        win.document.write('<div style="text-align:left; margin-top:18px;"><font>NIA-PIMO-ENG-EU-INT-Form13 Rev.06</font></div>');
        win.document.write('</div>');
    });
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function(){ win.print(); win.close(); }, 800);
};
</script>
</body>
</html>
