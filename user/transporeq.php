<?php
session_start();
require_once '../connection/db.php';

$success = false;
$error = '';
$conflict = false;
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateissued = $_POST['dateissued'] ?? null;
    $name = $_POST['name'] ?? ($_SESSION['user_name'] ?? null);
    $unit = $_POST['unit'] ?? null;
    $purpose = $_POST['purpose'] ?? null;
    $destination = $_POST['destination'] ?? null;
    $datetime_used = $_POST['datetime_used'] ?? null;
    $from = $_POST['from'] ?? null;
    $to = $_POST['to'] ?? null;
    $vehicle = $_POST['vehicle'] ?? null;
    $driver = $_POST['driver'] ?? null;

    // Prevent requests for past date/time
    if ($datetime_used && strtotime($datetime_used) < time()) {
        $error = 'You cannot request transportation for a past date/time.';
    } else {
        // Check for time conflict (only for the same driver)
        try {
            $stmt = $pdo->prepare("SELECT * FROM transportation_requests WHERE driver = ? AND DATE(datetime_used) = DATE(?) AND ((time_from < ? AND time_to > ?) OR (time_from < ? AND time_to > ?) OR (time_from >= ? AND time_to <= ?))");
            $stmt->execute([
                $driver,
                $datetime_used,
                $to, $from, // Overlap: existing start < new end AND existing end > new start
                $from, $to, // Overlap: existing start < new start AND existing end > new end
                $from, $to  // Fully inside
            ]);
            $conflictRequest = $stmt->fetch();
            if ($conflictRequest) {
                $conflict = true;
            } else {
                $stmt = $pdo->prepare("INSERT INTO transportation_requests (dateissued, requester_name, requesting_unit, purpose, destination, datetime_used, time_from, time_to, vehicle, driver, requested_by, requested_by_user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $dateissued,
                    $name,
                    $unit,
                    $purpose,
                    $destination,
                    $datetime_used,
                    $from,
                    $to,
                    $vehicle,
                    $driver,
                    $name, // requested_by (legacy)
                    $userId, // requested_by_user_id (foreign key)
                    'pending'
                ]);
                $success = true;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Auto-update accomplished status in DB for user's requests
if ($userId) {
    $stmt = $pdo->prepare("SELECT id, datetime_used, time_to, status FROM transportation_requests WHERE requested_by_user_id = ?");
    $stmt->execute([$userId]);
    $userRequests = $stmt->fetchAll();
    foreach ($userRequests as $req) {
        if ($req['status'] !== 'accomplished' && !empty($req['datetime_used']) && !empty($req['time_to'])) {
            $windowEnd = strtotime(date('Y-m-d', strtotime($req['datetime_used'])) . ' ' . $req['time_to']);
            if ($windowEnd && $windowEnd < time()) {
                $updateStmt = $pdo->prepare("UPDATE transportation_requests SET status = 'accomplished' WHERE id = ?");
                $updateStmt->execute([$req['id']]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportation Request</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/usermain.css">
    <link rel="stylesheet" href="../css/transporeq.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
</head>
<body>
<center>
<?php if ($success): ?>
    <div id="success-popup" class="popup-success">Request submitted successfully!</div>
<?php elseif ($conflict): ?>
    <div id="conflict-popup" class="popup-conflict">Time conflict: There is already a request for this date and time.</div>
<?php elseif ($error): ?>
    <div style="color: red; font-weight: bold; margin-bottom: 18px;">Error: <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<!-- Table of user's transport requests -->
<div class="dashboard-card" style="margin: 48px auto 32px auto; width: 100%; max-width: 98vw; min-width: 0; box-sizing: border-box; padding: 0 32px 32px 32px; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px 0 rgba(0,0,0,0.07);">
  <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 10px; margin-top: 24px;">
    <span style="cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:1.4em; color:#374151; width:38px; height:38px; border-radius:50%; background:#f3f4f6; box-shadow:0 1px 2px rgba(0,0,0,0.04); transition:background 0.2s;" onclick="window.history.back(); return false;" title="Back" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">&#8592;</span>
    <button class="main-btn" id="open-request-modal" type="button" style="background-color: #22c55e; color: #fff; font-weight:600; border: none;">+ New Transportation Request</button>
  </div>
  <h2 class="dashboard-title">My Transportation Requests</h2>
  <div class="dashboard-table-wrapper" style="overflow-x: unset;">
    <table class="dashboard-table">
      <thead>
        <tr>
          <th>Date Issued</th>
          <th>Requester</th>
          <th>Unit</th>
          <th>Purpose</th>
          <th>Destination</th>
          <th>Date/Time Used</th>
          <th>From</th>
          <th>To</th>
          <th>Vehicle</th>
          <th>Driver</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($userId) {
          $stmt = $pdo->prepare("SELECT * FROM transportation_requests WHERE requested_by_user_id = ? ORDER BY dateissued DESC, datetime_used DESC");
          $stmt->execute([$userId]);
          $userRequests = $stmt->fetchAll();
          if ($userRequests) {
            $hasRows = false;
            foreach ($userRequests as $req) {
                $hasRows = true;
                // Format date and time fields
                $dateissued = $req['dateissued'] ? date('M d, Y', strtotime($req['dateissued'])) : '-';
                $datetime_used = $req['datetime_used'] ? date('M d, Y g:i A', strtotime($req['datetime_used'])) : '-';
                $from = $req['time_from'] ? date('g:i A', strtotime($req['time_from'])) : '-';
                $to = $req['time_to'] ? date('g:i A', strtotime($req['time_to'])) : '-';

                // Determine status: auto-accomplished if window is over
                $status = $req['status'] ?? 'pending';
                // Always use the DB value for status (do not override with auto-accomplished logic)

                echo '<tr>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($dateissued) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($req['requester_name']) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($req['requesting_unit']) . '</td>';
                echo '<td style="padding: 10px 8px; white-space: pre-line;">' . nl2br(htmlspecialchars($req['purpose'])) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($req['destination']) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($datetime_used) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($from) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($to) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($req['vehicle']) . '</td>';
                echo '<td style="padding: 10px 8px;">' . htmlspecialchars($req['driver']) . '</td>';
                echo '<td style="padding: 10px 8px;">' . '<span class="status-badge status-' . htmlspecialchars($status) . '">' . ucfirst(htmlspecialchars($status)) . '</span>' . '</td>';
                echo '<td style="padding: 10px 8px;"><button class="main-btn open-request-modal" data-request="' . htmlspecialchars(json_encode($req), ENT_QUOTES, 'UTF-8') . '">Edit</button></td>';
                echo '</tr>';
            }
            if (!$hasRows) {
                echo '<tr><td colspan="12" class="no-requests">No transportation requests found.</td></tr>';
            }
          } else {
            echo '<tr><td colspan="12" class="no-requests">No transportation requests found.</td></tr>';
          }
        } else {
          echo '<tr><td colspan="12" class="no-requests">Please log in to view your requests.</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Modal for request form -->
<div id="request-modal" class="modal-overlay">
  <div class="modal-content" style="max-width: 600px;">
    <button id="close-request-modal" class="modal-close">&times;</button>
    <h2 class="modal-title">Transportation Request</h2>
    <form method="post" autocomplete="off" id="request-form">
      <table class="request-table" style="width: auto; min-width: 420px; max-width: 98vw; margin: 0 auto; border-spacing: 0 14px;">
        <tr>
          <td colspan="3" style="text-align: right; padding-bottom: 18px; padding-top: 8px;">Date:
            <input type="date" name="dateissued" style="margin-left:8px;">
          </td>
        </tr>
        <tr>
          <td class="label" style="width: 180px; padding-bottom: 12px;">1. To be used by:</td>
          <td colspan="2" style="padding-bottom: 12px;">
            <input type="text" name="name" style="width: 180px; margin-right:8px;" placeholder="Requester name"> of 
            <input type="text" name="unit" style="width: 180px;" placeholder="Requesting unit">
          </td>
        </tr>
        <tr>
          <td class="label" style="padding-bottom: 12px;">2. Purpose</td>
          <td class="textarea-cell" colspan="2" style="padding-bottom: 12px;">
            <textarea name="purpose" placeholder="Purpose of request" style="width: 100%; min-height: 48px; padding: 8px 10px; border-radius: 6px; border: 1px solid #e5e7eb;"></textarea>
          </td>
        </tr>
        <tr>
          <td style="padding-bottom: 12px;">3. Destination</td>
          <td colspan="2" style="padding-bottom: 12px;">
            <input type="text" name="destination" placeholder="Location" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #e5e7eb;">
          </td>
        </tr>
        <tr>
          <td style="padding-bottom: 12px;">4. Date & Time used:</td>
          <td style="padding-bottom: 12px;">
            <input type="datetime-local" name="datetime_used" style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #e5e7eb;">
          </td>
          <td style="vertical-align: top; padding-bottom: 12px;">
            <div style="display: flex; flex-direction: row; gap: 16px;">
              <div>
                <label for="from" style="font-weight: 500;">From:</label>
                <input type="time" id="from" name="from" style="width: 110px; margin-left: 6px; padding: 6px 8px; border-radius: 6px; border: 1px solid #e5e7eb;">
              </div>
              <div>
                <label for="to" style="font-weight: 500;">To:</label>
                <input type="time" id="to" name="to" style="width: 110px; margin-left: 6px; padding: 6px 8px; border-radius: 6px; border: 1px solid #e5e7eb;">
              </div>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="3" style="padding: 0; text-align:center;">
            <hr style="border: 1px solid #e5e7eb; margin: 18px auto 10px auto; width: 95%; max-width: 600px; min-width: 200px;">
          </td>
        </tr>
        <tr>
          <td colspan="3" style="padding-top: 18px;">
            <center>
              <div style="max-width: 98vw;">
                <button class="main-btn" type="submit">Submit Request</button>
              </div>
            </center>
          </td>
        </tr>
        <tr>
          <td colspan="3" style="text-align:right; padding-top:12px;">
            <button type="button" id="cancel-request-modal" class="main-btn" style="background:#e5e7eb; color:#374151; margin-right:8px;">Cancel</button>
          </td>
        </tr>
      </table>
    </form>
  </div>
</div>
<script>
// Modal open/close logic for each row and for new request
const modal = document.getElementById('request-modal');
const closeBtn = document.getElementById('close-request-modal');
const openBtns = document.querySelectorAll('.open-request-modal');
const openNewBtn = document.getElementById('open-request-modal');
const form = document.getElementById('request-form');

// Helper to set form values
function setFormValues(data) {
  form.reset();
  if (!data) return;
  form.elements['dateissued'].value = data.dateissued || '';
  form.elements['name'].value = data.requester_name || '';
  form.elements['unit'].value = data.requesting_unit || '';
  form.elements['purpose'].value = data.purpose || '';
  form.elements['destination'].value = data.destination || '';
  form.elements['datetime_used'].value = data.datetime_used ? data.datetime_used.replace(' ', 'T') : '';
  form.elements['from'].value = data.time_from || '';
  form.elements['to'].value = data.time_to || '';
  // vehicle and driver fields can be added if present in the form
  if (form.elements['vehicle']) form.elements['vehicle'].value = data.vehicle || '';
  if (form.elements['driver']) form.elements['driver'].value = data.driver || '';
}

if (openNewBtn) {
  openNewBtn.onclick = () => {
    modal.classList.add('active');
    setFormValues(null); // blank form for new request
    // Autofill dateissued with today's date
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var dd = String(today.getDate()).padStart(2, '0');
    var dateStr = yyyy + '-' + mm + '-' + dd;
    form.elements['dateissued'].value = dateStr;
  };
}
openBtns.forEach(btn => {
  btn.onclick = () => {
    modal.classList.add('active');
    // Populate form fields for editing
    const req = JSON.parse(btn.dataset.request);
    setFormValues(req);
  };
});
if (closeBtn && modal) {
  closeBtn.onclick = () => modal.classList.remove('active');
  modal.onclick = (e) => { if (e.target === modal) modal.classList.remove('active'); };
}
// Add cancel button logic
const cancelBtn = document.getElementById('cancel-request-modal');
if (cancelBtn) {
  cancelBtn.onclick = () => modal.classList.remove('active');
}
// Prevent submission of past date/time
form.addEventListener('submit', function(e) {
  var datetimeUsed = form.elements['datetime_used'].value;
  if (datetimeUsed) {
    var dt = new Date(datetimeUsed);
    var now = new Date();
    if (dt < now) {
      alert('You cannot request transportation for a past date/time.');
      e.preventDefault();
      return false;
    }
  }
});
// ...existing popup logic...
window.addEventListener('DOMContentLoaded', function() {
  var popup = document.getElementById('success-popup');
  if (popup) {
    setTimeout(function() {
      popup.classList.add('hide');
    }, 2500);
  }
  var conflict = document.getElementById('conflict-popup');
  if (conflict) {
    setTimeout(function() {
      conflict.classList.add('hide');
    }, 3500);
  }
});
</script>
<!-- Add some CSS for better dashboard look and status badge -->
<style>
.dashboard-table {
  table-layout: auto;
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 12px 0 rgba(0,0,0,0.04);
}
.dashboard-table th, .dashboard-table td {
  text-align: center;
  vertical-align: middle;
  word-break: break-word;
  white-space: pre-line;
  overflow-wrap: break-word;
  padding: 14px 14px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 1em;
}
.dashboard-table th {
  background: #f3f4f6;
  color: #374151;
  font-weight: 700;
  font-size: 1.08em;
  border-bottom: 2px solid #e5e7eb;
}
.dashboard-table tr:last-child td {
  border-bottom: none;
}
.dashboard-table tr:nth-child(even) { background: #f9fafb; }
.dashboard-table tr:nth-child(odd) { background: #fff; }
.dashboard-table th:nth-child(4), .dashboard-table td:nth-child(4) { min-width: 180px; }
.dashboard-table th:nth-child(5), .dashboard-table td:nth-child(5) { min-width: 140px; }
.dashboard-table th:nth-child(6), .dashboard-table td:nth-child(6) { min-width: 160px; }
.dashboard-table th:nth-child(10), .dashboard-table td:nth-child(10) { min-width: 120px; }
.dashboard-table th:nth-child(11), .dashboard-table td:nth-child(11) { min-width: 110px; }
.dashboard-table th:nth-child(12), .dashboard-table td:nth-child(12) { min-width: 100px; }
.dashboard-table-wrapper { width: 100%; }
.status-badge {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 12px;
  font-size: 0.95em;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  word-break: normal;
  overflow-wrap: normal;
}
.status-pending { background: #f59e42; }
.status-approved { background: #22c55e; }
.status-rejected { background: #ef4444; }
.status-accomplished { background:rgb(238, 182, 0); /* Indigo color for accomplished */ }
@media (max-width: 900px) {
  .dashboard-card { margin-top: 32px !important; padding: 0 8px 24px 8px; }
  .dashboard-table th, .dashboard-table td { padding: 10px 4px; font-size: 0.97em; }
}
@media (max-width: 600px) {
  .dashboard-card { margin-top: 18px !important; padding: 0 2px 12px 2px; }
  .dashboard-table th, .dashboard-table td { padding: 7px 2px; font-size: 0.93em; }
}
</style>
</center>
</body>
</html>