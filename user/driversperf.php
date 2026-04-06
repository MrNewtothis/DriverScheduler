<?php
session_start();
require_once '../connection/db.php';
// Handle form submission
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_POST['driver_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $vehicle_type = $_POST['vehicle_type'] ?? '';
    $vehicle_plate_no = $_POST['vehicle_plate_no'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $purpose_of_travel = $_POST['purpose_of_travel'] ?? '';
    $duration_of_travel = $_POST['duration_of_travel'] ?? '';
    $comments = $_POST['comments'] ?? '';
    $evaluated_by = $_POST['evaluated_by'] ?? '';
    $noted_by = $_POST['noted_by'] ?? '';

    // Radio fields
    $ratings = [];
    for ($i = 1; $i <= 8; $i++) {
        $ratings["r$i"] = $_POST["r$i"] ?? '';
    }
    // Remarks fields
    $remarks = [];
    for ($i = 1; $i <= 8; $i++) {
        $remarks["remark$i"] = $_POST["remark$i"] ?? '';
    }

    // Validate required fields
    $missing = [];
    if (!$driver_id) $missing[] = 'Name of Driver';
    if (!$vehicle_type) $missing[] = 'Type & Make of Vehicle';
    if (!$vehicle_plate_no) $missing[] = 'Vehicle Plate No.';
    if (!$destination) $missing[] = 'Official Destination';
    if (!$purpose_of_travel) $missing[] = 'Purpose of Travel';
    if (!$duration_of_travel) $missing[] = 'Duration of Travel';
    if (!$date) $missing[] = 'Date';
    // Ratings required except #7
    foreach ([1,2,3,4,5,6,8] as $n) {
        if (empty($ratings["r$n"])) $missing[] = "Rating #$n";
    }
    if (!$evaluated_by) $missing[] = 'Evaluated By';
    if (!$noted_by) $missing[] = 'Noted By';

    if (empty($missing)) {
        // Get driver name from drivers table
        $driver_stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $driver_stmt->execute([$driver_id]);
        $driver_row = $driver_stmt->fetch();
        $driver_name = $driver_row ? $driver_row['name'] : '';

        $stmt = $pdo->prepare("INSERT INTO driver_performance_evaluations (
            driver_id, driver_name, date, vehicle_type, vehicle_plate_no, destination, purpose_of_travel, duration_of_travel,
            r1, remarks1,
            r2, remarks2,
            r3, remarks3,
            r4, remarks4,
            r5, remarks5,
            r6, remarks6,
            r7, remarks7,
            r8, remarks8,
            comments, evaluated_by, noted_by
        ) VALUES (
            :driver_id, :driver_name, :date, :vehicle_type, :vehicle_plate_no, :destination, :purpose_of_travel, :duration_of_travel,
            :r1, :remark1,
            :r2, :remark2,
            :r3, :remark3,
            :r4, :remark4,
            :r5, :remark5,
            :r6, :remark6,
            :r7, :remark7,
            :r8, :remark8,
            :comments, :evaluated_by, :noted_by
        )");
        try {
            $stmt->execute([
                'driver_id' => $driver_id,
                'driver_name' => $driver_name,
                'date' => $date,
                'vehicle_type' => $vehicle_type,
                'vehicle_plate_no' => $vehicle_plate_no,
                'destination' => $destination,
                'purpose_of_travel' => $purpose_of_travel,
                'duration_of_travel' => $duration_of_travel,
                'r1' => $ratings['r1'],
                'remark1' => $remarks['remark1'],
                'r2' => $ratings['r2'],
                'remark2' => $remarks['remark2'],
                'r3' => $ratings['r3'],
                'remark3' => $remarks['remark3'],
                'r4' => $ratings['r4'],
                'remark4' => $remarks['remark4'],
                'r5' => $ratings['r5'],
                'remark5' => $remarks['remark5'],
                'r6' => $ratings['r6'],
                'remark6' => $remarks['remark6'],
                'r7' => $ratings['r7'],
                'remark7' => $remarks['remark7'],
                'r8' => $ratings['r8'],
                'remark8' => $remarks['remark8'],
                'comments' => $comments,
                'evaluated_by' => $evaluated_by,
                'noted_by' => $noted_by
            ]);
            if ($stmt->rowCount() > 0) {
                $successMsg = 'Evaluation submitted successfully!';
                // Clear form after success
                $_POST = [];
                $driver_id = $date = $vehicle_type = $vehicle_plate_no = $destination = $purpose_of_travel = $duration_of_travel = $comments = $evaluated_by = $noted_by = '';
                $ratings = $remarks = [];
            } else {
                $errorMsg = 'Submission failed: No rows affected.';
            }
        } catch (PDOException $e) {
            $errorMsg = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errorMsg = 'General error: ' . $e->getMessage();
        }
    } else {
        $errorMsg = 'Please fill in the following required fields:<br><ul style="margin:0 0 0 18px;">';
        foreach ($missing as $m) {
            $errorMsg .= '<li>' . htmlspecialchars($m) . '</li>';
        }
        $errorMsg .= '</ul>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver's Performance Evaluation</title>
    <link rel="stylesheet" href="../css/usermain.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <link rel="stylesheet" href="../css/driverperf.css">
    <style>
        /* All CSS moved to driverperf.css */
    </style>
</head>
<body>
    <div id="notif-container" style="position:fixed;top:32px;right:32px;z-index:9999;min-width:280px;max-width:350px;"></div>
    <div class="perf-container" id="perfContainer">
        <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
            <a href="main.php" style="text-decoration: none; display: inline-flex; align-items: center;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 4px;">
                    <path d="M15 19l-7-7 7-7" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span style="color:#2563eb; font-weight:600; font-size:1.05em;">Back</span>
            </a>
            <button type="button" id="printBtn" class="submit-btn" style="background:#1e293b; margin-bottom:0;">Print</button>
        </div>
        <img src="../imgs/topformat.png" alt="Header" style="width:100%;max-width:900px;display:block;margin:0 auto 18px auto;">
        <div class="perf-title">DRIVER'S PERFORMANCE EVALUATION <br>
            <span style="font-size: 0.4em; color:rgb(0, 0, 0);">(To be submitted to the <b>Chief, Engineering and operation Division-PIMO</b> after completion of travel)</span>
        </div>
        <?php
        require_once '../connection/db.php';
        $drivers = [];
        $stmt = $pdo->prepare("SELECT id, name FROM drivers");
        $stmt->execute();
        $drivers = $stmt->fetchAll();
        ?>
        <form method="post" autocomplete="off">
            <table class="perf-table">
                <tr>
                    <td>Name of Driver:</td>
                    <td>
                        <select name="driver_id" required>
                            <option value="">Select Driver</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= $driver['id'] ?>" <?= (isset($_POST['driver_id']) && $_POST['driver_id'] == $driver['id']) ? 'selected' : '' ?>><?= htmlspecialchars($driver['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td></td>
                    <td></td>
                    <td>Date</td>
                    <td><input type="date" name="date" value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>"></td>
                </tr>
                <tr>
                    <td>Type & Make of Vehicle</td>
                    <td><input type="text" name="vehicle_type" value="<?= isset($_POST['vehicle_type']) ? htmlspecialchars($_POST['vehicle_type']) : '' ?>"></td>
                    <td></td>
                    <td></td>
                    <td>Vehicle Plate No.</td>
                    <td><input type="text" name="vehicle_plate_no" value="<?= isset($_POST['vehicle_plate_no']) ? htmlspecialchars($_POST['vehicle_plate_no']) : '' ?>"></td>
                </tr>
                <tr>
                    <td>Official Destination</td>
                    <td><input type="text" name="destination" value="<?= isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : '' ?>"></td>
                </tr>
                <tr>
                    <td>Purpose of Travel</td>
                    <td><input type="text" name="purpose_of_travel" value="<?= isset($_POST['purpose_of_travel']) ? htmlspecialchars($_POST['purpose_of_travel']) : '' ?>"></td>
                </tr>
                <tr>
                    <td>Duration of Travel</td>
                    <td><input type="text" name="duration_of_travel" value="<?= isset($_POST['duration_of_travel']) ? htmlspecialchars($_POST['duration_of_travel']) : '' ?>"></td>
                </tr>
            </table>
            <table class="perf-table">
                <tr>
                    <th>PARTICULARS</th>
                    <th>Poor</th>
                    <th>Fair</th>
                    <th>Good</th>
                    <th>Excellent</th>
                    <th>REMARKS</th>
                </tr>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <tr>
                    <td><?= $i ?>. <?= [
                        1 => 'Punctuality',
                        2 => 'Safe driving. (No Unnecessary phone calls: obedience <br>to traffic rules: no distractions like radios/TV: Courtesy <br> to other motorists)',
                        3 => 'Courtesy',
                        4 => 'Personal Attitude',
                        5 => 'Knowledge of direction to destination',
                        6 => 'Personal Hygiene',
                        7 => 'Troubleshooting (Only if the vehicle encounters problems. If not, write "Not Applicable")',
                        8 => 'Vehicle Cleanliness',
                    ][$i] ?></td>
                    <?php for ($j = 0, $opts = ['Poor','Fair','Good','Excellent']; $j < 4; $j++): ?>
                        <td class="center"><input type="radio" name="r<?= $i ?>" value="<?= $opts[$j] ?>" <?= (isset($_POST['r'.$i]) && $_POST['r'.$i] == $opts[$j]) ? 'checked' : '' ?> <?= $i == 7 ? '' : 'required' ?>></td>
                    <?php endfor; ?>
                    <td><textarea name="remark<?= $i ?>" rows="1"><?= isset($_POST['remark'.$i]) ? htmlspecialchars($_POST['remark'.$i]) : '' ?></textarea></td>
                </tr>
                <?php endfor; ?>
            </table>
            <br>
            <center>
                <h5><b>NOTE: 1. Driver's Performance Evaluation is a based strictly on services rendered as per Approved Travel Order.</b></h5>
            </center>
            <br>
            Comments & Observations: <textarea name="comments" id=""><?= isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : '' ?></textarea><br>
            <center>
                <table>
                    <tr>
                        <td>Evaluated by:</td>
                        <td><input type="text" name="evaluated_by" id="" placeholder="Name of Evaluator" value="<?= isset($_POST['evaluated_by']) ? htmlspecialchars($_POST['evaluated_by']) : (isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : '') ?>" readonly></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                                Official Passenger/s Team Leader 
                        </td>
                    </tr>
                    <tr>
                        <td><br></td>
                    </tr>
                    <tr>
                        <td>Noted by:</td>
                        <td><input type="text" name="noted_by" id="" placeholder="Name of Noted By" value="<?= isset($_POST['noted_by']) ? htmlspecialchars($_POST['noted_by']) : '' ?>"></td>
                    </tr>
                </table>
            </center>
            <img src="../imgs/bottomformat.png" alt="Footer" style="width:100%;max-width:900px;display:block;margin:18px auto 0 auto;">
            <div style="text-align:left; margin-top:18px;">
                <font>NIA-PIMO-ENG-EU-INT-Form13 Rev.06</font>
            </div>
            <div style="text-align:right;">
                <button class="submit-btn" type="submit">Submit Evaluation</button>
            </div>
        </form>
    </div>
    <img src="../imgs/topformat.png" id="printHeaderImg" alt="Header" style="display:none;">
    <img src="../imgs/bottomformat.png" id="printFooterImg" alt="Footer" style="display:none;">
    <script>
    // Notification logic
    (function() {
        var notif = '';
        <?php if ($successMsg): ?>
            notif = '<div style="background:#d1fae5;color:#065f46;padding:14px 22px;border-radius:8px;margin-bottom:12px;font-weight:600;box-shadow:0 2px 8px #0001;">'+<?= json_encode($successMsg) ?>+'</div>';
        <?php elseif ($errorMsg): ?>
            notif = '<div style="background:#fee2e2;color:#991b1b;padding:14px 22px;border-radius:8px;margin-bottom:12px;font-weight:600;box-shadow:0 2px 8px #0001;">'+<?= json_encode($errorMsg) ?>+'</div>';
        <?php endif; ?>
        if (notif) {
            document.getElementById('notif-container').innerHTML = notif;
            setTimeout(function(){
                document.getElementById('notif-container').innerHTML = '';
            }, 5000);
        }
    })();
    document.getElementById('printBtn').onclick = function() {
        document.getElementById('printHeaderImg').style.display = 'block';
        document.getElementById('printFooterImg').style.display = 'block';
        window.print();
        setTimeout(function() {
            document.getElementById('printHeaderImg').style.display = 'none';
            document.getElementById('printFooterImg').style.display = 'none';
        }, 500);
    };
    </script>
</body>
</html>