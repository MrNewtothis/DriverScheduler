<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';

// Get review ID
$reviewId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$reviewId) {
    echo '<div style="color:#dc2626;font-weight:600;padding:32px;text-align:center;">Invalid review ID.</div>';
    exit;
}

// Fetch review
$stmt = $pdo->prepare("SELECT e.*, d.name as driver_name FROM driver_performance_evaluations e JOIN drivers d ON e.driver_id = d.id WHERE e.id = ?");
$stmt->execute([$reviewId]);
$eval = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$eval) {
    echo '<div style="color:#dc2626;font-weight:600;padding:32px;text-align:center;">Review not found.</div>';
    exit;
}

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$particulars = [
    'Punctuality',
    'Safe driving. (No Unnecessary phone calls: obedience to traffic rules: no distractions like radios/TV: Courtesy to other motorists)',
    'Courtesy',
    'Personal Attitude',
    'Knowledge of direction to destination',
    'Personal Hygiene',
    'Troubleshooting (Only if the vehicle encounters problems. If not, write "Not Applicable")',
    'Vehicle Cleanliness'
];
$opts = ['Poor','Fair','Good','Excellent'];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Performance Review</title>
    <link rel="stylesheet" href="../css/driverperf.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <style>
    body { background: #f8fafc; }
    .review-container { max-width: 980px; margin: 32px auto; background: #fff; border-radius: 10px; box-shadow: 0 8px 32px rgba(0,0,0,0.10); padding: 0 0 32px 0; }
    .review-header { display:flex; align-items:center; justify-content:space-between; padding: 24px 32px 0 32px; margin-bottom: 18px; }
    .review-header .back-btn { background: #e5e7eb; color: #1e293b; border: none; border-radius: 6px; padding: 7px 18px; font-size: 1em; cursor: pointer; text-decoration:none; font-weight:600; }
    .review-header .print-btn { background: #1e293b; color: #fff; border: none; border-radius: 6px; padding: 7px 18px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0001; font-weight:600; }
    .review-content { padding: 0 32px 0 32px; }
    input[type="radio"] {
        accent-color: #000 !important;
        border: 2px solid #000 !important;
        background: #fff !important;
        /* box-shadow: 0 0 0 1.5px #000 !important; */
        width: 18px;
        height: 18px;
    }
    input[type="radio"]:checked::before {
        content: '';
        display: block;
        width: 10px;
        height: 10px;
        margin: 3px auto;
        border-radius: 50%;
        background: #000;
    }
    /* For Webkit browsers */
    input[type="radio"]::-webkit-radio-inner-circle {
        background-color: #000 !important;
    }
    /* For Firefox */
    input[type="radio"]:checked {
        background-color: #000 !important;
    }
    @media print {
        html, body {
            height: auto !important;
            min-height: 0 !important;
            background: #fff !important;
            color: #000 !important;
            width: 100vw !important;
            overflow: visible !important;
        }
        body, .review-container, .review-content {
            background: #fff !important;
            color: #000 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
            max-width: 100vw !important;
            padding: 0 !important;
            width: 100vw !important;
            min-width: 0 !important;
            overflow: visible !important;
            display: block !important;
        }
        .review-header, .review-header *, .back-btn {
            display: none !important;
        }
        .review-content * {
            color: #000 !important;
            background: #fff !important;
            visibility: visible !important;
            box-shadow: none !important;
        }
        .review-content {
            padding: 0 !important;
            margin: 0 !important;
            width: 100vw !important;
            min-width: 0 !important;
            overflow: visible !important;
        }
        img, table, .perf-title, textarea, input[type="radio"] {
            display: initial !important;
            visibility: visible !important;
            color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background: #fff !important;
            color: #000 !important;
        }
        textarea, input[type="radio"] {
            pointer-events: none !important;
            background: #fff !important;
            color: #000 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .perf-title {
            margin-top: 0 !important;
        }
        table, th, td {
            border-color: #cbd5e1 !important;
            color: #000 !important;
            background: #fff !important;
        }
        .no-border, .no-border td, .no-border th {
            border: none !important;
        }
        * {
            visibility: visible !important;
            color: #000 !important;
            background: #fff !important;
        }
        @page {
            size: auto;
            margin: 18mm 10mm 18mm 10mm;
        }
    }
    </style>
</head>
<body>
<div class="review-container">
    <div class="review-header">
        <a href="driver_performance.php" class="back-btn">&larr; Back</a>
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>
    <div class="review-content">
        <img src="../imgs/topformat.png" alt="Header" style="width:100%;max-width:900px;display:block;margin:0 auto 18px auto;">
        <center>
            <table style="border:none;">
                <tr>
                    <td style="border:none;">
                        <center>
                        <div class="perf-title" style="text-align:center;">DRIVER'S PERFORMANCE EVALUATION </div>
                        </center>
                        <center>
                            <span style="text-align:center;">(To be submitted to the <b>Chief, Engineering and operation Division-PIMO</b> after completion of travel)</span>
                        </center>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center; border:none;">
                        
                    </td>
                </tr>
            </table>
        </center>
        <!-- <div class="perf-title" style="text-align:center;font-weight:700;font-size:1.25em;margin-bottom:8px;">DRIVER'S PERFORMANCE EVALUATION <br><span style="font-size: 0.4em; color:rgb(0, 0, 0);">(To be submitted to the <b>Chief, Engineering and operation Division-PIMO</b> after completion of travel)</span></div> -->
        <table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">
            
            <tr><td style="font-weight:600;width:180px;padding:8px 10px;">Name of Driver:</td><td style="padding:8px 10px;"><?= esc($eval['driver_name']) ?></td><td></td><td></td><td style="font-weight:600;width:80px;padding:8px 10px;">Date</td><td style="padding:8px 10px;"><?= esc(date('M d, Y', strtotime($eval['date'])) ) ?></td></tr>
            <tr><td style="font-weight:600;padding:8px 10px;">Type & Make of Vehicle</td><td style="padding:8px 10px;"><?= esc($eval['vehicle_type']) ?></td><td></td><td></td><td style="font-weight:600;padding:8px 10px;">Vehicle Plate No.</td><td style="padding:8px 10px;"><?= esc($eval['vehicle_plate_no']) ?></td></tr>
            <tr><td style="font-weight:600;padding:8px 10px;">Official Destination</td><td style="padding:8px 10px;"><?= esc($eval['destination']) ?></td></tr>
            <tr><td style="font-weight:600;padding:8px 10px;">Purpose of Travel</td><td style="padding:8px 10px;"><?= esc($eval['purpose_of_travel']) ?></td></tr>
            <tr><td style="font-weight:600;padding:8px 10px;">Duration of Travel</td><td style="padding:8px 10px;"><?= esc($eval['duration_of_travel']) ?></td></tr>
        </table>
        <table class="perf-table" style="width:100%;margin-bottom:14px;border-collapse:collapse;">
            <tr style="background:#f1f5f9;"><th style="padding:8px 10px;">PARTICULARS</th><th style="padding:8px 10px;">Poor</th><th style="padding:8px 10px;">Fair</th><th style="padding:8px 10px;">Good</th><th style="padding:8px 10px;">Excellent</th><th style="padding:8px 10px;">REMARKS</th></tr>
            <?php for ($i=1; $i<=8; $i++): ?>
            <tr>
                <td style="vertical-align:top;padding:8px 10px;"> <?= $i . '. ' . esc($particulars[$i-1]) ?> </td>
                <?php foreach ($opts as $opt): ?>
                    <td class="center" style="padding:8px 10px;"><input type="radio" disabled <?= ($eval['r'.$i] === $opt) ? 'checked' : '' ?> style="pointer-events:none;transform:scale(1.1);margin:0;"></td>
                <?php endforeach; ?>
                <td style="padding:8px 10px;"><textarea rows="1" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:28px;"><?= esc($eval['remarks'.$i]) ?></textarea></td>
            </tr>
            <?php endfor; ?>
        </table>
        <div style="margin:16px 0 0 0;"><b>Comments & Observations:</b><br><textarea rows="2" readonly style="width:100%;resize:none;background:#f1f5f9;border:1px solid #cbd5e1;min-height:38px;"><?= esc($eval['comments']) ?></textarea></div>
        <div style="margin:24px 0 0 0;">
            <table style="width:100%;border-collapse:collapse;border:none;" class="no-border">
                <tr>
                    <td style="font-weight:600;padding:8px 10px;width:160px;text-align:center;border:none;">Evaluated by:</td>
                    <td style="padding:8px 10px;text-align:center;border:none;"><?= esc($eval['evaluated_by']) ?></td>
                </tr>
                <tr><td style="border:none;"></td><td style="text-align:center;border:none;">Official Passenger/s Team Leader</td></tr>
                <tr><td style="border:none;"><br></td></tr>
                <tr>
                    <td style="font-weight:600;padding:8px 10px;text-align:center;border:none;">Noted by:</td>
                    <td style="padding:8px 10px;text-align:center;border:none;"><?= esc($eval['noted_by']) ?></td>
                </tr>
            </table>
        </div>
        <img src="../imgs/bottomformat.png" alt="Footer" style="width:100%;max-width:900px;display:block;margin:24px auto 0 auto;">
        <div style="text-align:left; margin-top:18px;"><font>NIA-PIMO-ENG-EU-INT-Form13 Rev.06</font></div>
    </div>
</div>
</body>
</html>
