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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    
    <title>NIA - Equipment Unit System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
    <link rel="stylesheet" href="../css/dashboard-calendar-layout.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <style>
        #calendar {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(30,41,59,0.10), 0 1.5px 8px rgba(30,41,59,0.08);
            padding: 24px 10px 10px 10px;
            margin-bottom: 36px;
            max-width: 950px;
            margin-left: auto;
            margin-right: auto;
        }
        #calendar .fc {
            background: #fff;
            border-radius: 14px;
        }
        #calendar .fc-daygrid-day {
            background: #f8fafc;
            border: none;
            border-radius: 12px;
            margin: 4px;
            transition: box-shadow 0.18s, background 0.18s;
            box-shadow: 0 1.5px 6px rgba(30,41,59,0.06);
            overflow: hidden;
            min-height: 80px;
            position: relative;
        }
        #calendar .fc-daygrid-day.fc-day-today {
            background: #e0f2fe !important;
            border: 2px solid #0ea5e9;
            box-shadow: 0 2px 12px #0ea5e933;
        }
        #calendar .fc-daygrid-day:hover {
            box-shadow: 0 4px 16px rgba(30,41,59,0.13);
            background: #f1f5f9;
            cursor: pointer;
            z-index: 2;
        }
        #calendar .fc-daygrid-day-number {
            font-weight: 700;
            color: #2563eb;
            padding: 6px 0 2px 10px;
            font-size: 1.08em;
            letter-spacing: 0.2px;
        }
        #calendar .fc-event {
            background: linear-gradient(90deg,#2563eb 60%,#0ea5e9 100%) !important;
            color: #fff !important;
            border-radius: 8px;
            border: none;
            font-size: 1em;
            padding: 4px 8px;
            margin: 4px 0 0 0;
            box-shadow: 0 2px 8px rgba(30,41,59,0.10);
            font-weight: 500;
            transition: background 0.18s;
        }
        #calendar .fc-event:hover {
            background: linear-gradient(90deg,#0ea5e9 60%,#2563eb 100%) !important;
            color: #fff !important;
        }
        #calendar .fc-scrollgrid {
            border-radius: 14px;
            overflow: hidden;
        }
        #calendar .fc-daygrid-day-frame {
            min-height: 80px;
            padding: 2px 2px 2px 2px;
            border-radius: 12px;
        }
        #calendar .fc-col-header-cell {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 1.08em;
            border-radius: 10px 10px 0 0;
            padding: 10px 0;
            letter-spacing: 0.5px;
        }
        #calendar .fc-daygrid-day-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-right: 8px;
        }
        @media (max-width: 700px) {
            #calendar .fc-daygrid-day-frame {
                min-height: 48px;
            }
            #calendar {
                padding: 6px 2px 2px 2px;
            }
        }
    </style>
</head>
<body>
<?php include 'admin_header.php'; ?>
    <div class="main-content">
        <div class="dashboard-calendar-flex">
            <div class="calendar-box">
                <div id="calendar" style="height:100%;width:100%;"></div>
                <!-- Calendar Modal -->
                <div id="calendar-modal" class="modal-overlay" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
                    <div class="modal-content" style="background:#fff; border-radius:14px; max-width:600px; width:96vw; margin:60px auto; padding:32px 24px 18px 24px; position:relative; top:40px; border:1.5px solid #e5e7eb;">
                        <button id="close-calendar-modal" class="modal-close" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;">&times;</button>
                        <h3 class="modal-title" id="calendar-modal-title">Transportation Requests</h3>
                        <table id="calendar-modal-table" class="modal-table" style="width:100%; margin-top:18px; border-collapse:collapse;"></table>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h1>Admin</h1>
                <hr>
                <h2 class="dashboard-title">Driver Schedule Today (<?= date('F j, Y') ?>)</h2>
                <div class="dashboard-table-wrapper">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th>Occupied (From - To)</th>
                                <th>Requester</th>
                                <th>Unit</th>
                                <th>Availability</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($drivers as $driver => $reqs): ?>
                                <?php foreach ($reqs as $i => $req): ?>
                                <tr>
                                    <?php if ($i === 0): ?>
                                        <td rowspan="<?= count($reqs) ?>" class="driver-cell"> <?= htmlspecialchars($driver) ?> </td>
                                    <?php endif; ?>
                                    <td> <?= date('g:i A', strtotime($req['time_from'])) ?> - <?= date('g:i A', strtotime($req['time_to'])) ?> </td>
                                    <td> <?= htmlspecialchars($req['requester_name']) ?> </td>
                                    <td> <?= htmlspecialchars($req['requesting_unit']) ?> </td>
                                    <?php if ($i === 0): ?>
                                        <td rowspan="<?= count($reqs) ?>" class="occupied-cell">
                                            <span>Occupied</span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <button class="details-btn" data-info='<?= json_encode($req) ?>'>View</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="no-requests">No driver requests for today. </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
    let calendarRequests = [];
    <?php
    try {
        $stmt = $pdo->prepare("SELECT id, datetime_used, time_from, time_to, requester_name, requesting_unit, purpose, driver, vehicle, status FROM transportation_requests WHERE status = 'approved'");
        $stmt->execute();
        $calendarReqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($calendarReqs as $req) {
            $date = date('Y-m-d', strtotime($req['datetime_used']));
            $title = $req['driver'] ? $req['driver'] : 'No Driver';
            $purpose = htmlspecialchars($req['purpose']);
            $requester = htmlspecialchars($req['requester_name']);
            $unit = htmlspecialchars($req['requesting_unit']);
            $vehicle = htmlspecialchars($req['vehicle']);
            $timeFrom = $req['time_from'] ? date('g:i A', strtotime($req['time_from'])) : '';
            $timeTo = $req['time_to'] ? date('g:i A', strtotime($req['time_to'])) : '';
            echo "calendarRequests.push({ id: '{$req['id']}', date: '$date', title: '" . addslashes($title) . "', time_from: '$timeFrom', time_to: '$timeTo', requester: '" . addslashes($requester) . "', unit: '" . addslashes($unit) . "', purpose: '" . addslashes($purpose) . "', vehicle: '" . addslashes($vehicle) . "' });\n";
        }
    } catch (Exception $e) {}
    ?>

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 600,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            events: calendarRequests.map(function(req) {
                return {
                    title: req.title + (req.time_from && req.time_to ? ' (' + req.time_from + '-' + req.time_to + ')' : ''),
                    start: req.date,
                    extendedProps: req
                };
            }),
            dateClick: function(info) {
                var date = info.dateStr;
                var reqs = calendarRequests.filter(r => r.date === date);
                var modal = document.getElementById('calendar-modal');
                var table = document.getElementById('calendar-modal-table');
                var title = document.getElementById('calendar-modal-title');
                title.textContent = 'Transportation Requests for ' + new Date(date).toLocaleDateString();
                let html = '<thead><tr><th>Driver</th><th>Time</th><th>Requester</th><th>Unit</th><th>Purpose</th><th>Vehicle</th></tr></thead><tbody>';
                if (reqs.length > 0) {
                    reqs.forEach(function(r) {
                        html += '<tr>' +
                            '<td>' + r.title + '</td>' +
                            '<td>' + (r.time_from && r.time_to ? r.time_from + ' - ' + r.time_to : '-') + '</td>' +
                            '<td>' + r.requester + '</td>' +
                            '<td>' + r.unit + '</td>' +
                            '<td>' + r.purpose + '</td>' +
                            '<td>' + r.vehicle + '</td>' +
                            '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="6">No approved requests for this date.</td></tr>';
                }
                html += '</tbody>';
                table.innerHTML = html;
                modal.style.display = 'block';
            },
            eventClick: function(info) {
                var date = info.event.startStr;
                var reqs = calendarRequests.filter(r => r.date === date);
                var modal = document.getElementById('calendar-modal');
                var table = document.getElementById('calendar-modal-table');
                var title = document.getElementById('calendar-modal-title');
                title.textContent = 'Transportation Requests for ' + new Date(date).toLocaleDateString();
                let html = '<thead><tr><th>Driver</th><th>Time</th><th>Requester</th><th>Unit</th><th>Purpose</th><th>Vehicle</th></tr></thead><tbody>';
                if (reqs.length > 0) {
                    reqs.forEach(function(r) {
                        html += '<tr>' +
                            '<td>' + r.title + '</td>' +
                            '<td>' + (r.time_from && r.time_to ? r.time_from + ' - ' + r.time_to : '-') + '</td>' +
                            '<td>' + r.requester + '</td>' +
                            '<td>' + r.unit + '</td>' +
                            '<td>' + r.purpose + '</td>' +
                            '<td>' + r.vehicle + '</td>' +
                            '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="6">No approved requests for this date.</td></tr>';
                }
                html += '</tbody>';
                table.innerHTML = html;
                modal.style.display = 'block';
            }
        });
        calendar.render();
        document.getElementById('close-calendar-modal').onclick = function() {
            document.getElementById('calendar-modal').style.display = 'none';
        };
        document.getElementById('calendar-modal').onclick = function(e) {
            if (e.target === this) this.style.display = 'none';
        };
    });
    </script>
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
            }
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
    </script>
</body>
</html>