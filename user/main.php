<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Session and role check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';
$dateToday = date('Y-m-d');
$requests = [];
$drivers = [];
try {
    $stmt = $pdo->prepare("SELECT driver, time_from, time_to, requester_name, requesting_unit, purpose FROM transportation_requests WHERE DATE(datetime_used) = ? AND status = 'approved' ORDER BY driver, time_from");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
    <link rel="stylesheet" href="../css/usermain.css">
    <link rel="stylesheet" href="../css/dashboard-calendar-layout.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="stylesheet" href="../css/logout-btn.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
</head>
<body>
    <header class="header-modern">
        <div class="header-modern-left">
            <a href="user/main.php" class="header-modern-logo">
                <img src="../imgs/nialogo.png" alt="NIA Logo" class="header-modern-logo-img">
                <span class="header-modern-title">NIA Equipment Unit System</span>
            </a>
        </div>
        <nav class="header-modern-nav" id="headerModernNav">
            <a href="driversperf.php" class="header-modern-link">Driver Performance Review</a>
            <a href="transporeq.php" class="header-modern-link">Transportation Request</a>
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
    <div class="main-content">
        <div class="dashboard-calendar-flex">
            <div class="calendar-box">
                <div id="calendar"></div>
            </div>
            <div class="dashboard-card">
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
    <div id="calendar-modal" class="modal-overlay" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
        <div class="modal-content" style="background:#fff; border-radius:14px; max-width:600px; width:96vw; margin:60px auto; padding:32px 24px 18px 24px; position:relative; top:40px; border:1.5px solid #e5e7eb;">
            <button id="close-calendar-modal" class="modal-close" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;">&times;</button>
            <h3 class="modal-title" id="calendar-modal-title">Transportation Requests</h3>
            <table id="calendar-modal-table" class="modal-table" style="width:100%; margin-top:18px; border-collapse:collapse;"></table>
        </div>
    </div>
    <!-- Profile Modal -->
    <div id="profileModal" class="modal-overlay" style="display:none; z-index:2000;">
        <div class="modal-content profile-modal-content">
            <button id="closeProfileModal" class="modal-close">&times;</button>
            <h3 class="modal-title">My Profile</h3>
            <form id="profileModalForm">
                <div id="profileModalView">
                    <div class="profile-modal-row"><b>Name:</b> <span id="profileName"></span></div>
                    <div class="profile-modal-row"><b>Unit:</b> <span id="profileUnit"></span></div>
                    <div class="profile-modal-row"><b>Birthdate:</b> <span id="profileBirthdate"></span></div>
                    <div class="profile-modal-row"><b>Age:</b> <span id="profileAge"></span></div>
                    <div class="profile-modal-row"><b>Phone:</b> <span id="profilePhone"></span></div>
                    <div class="profile-modal-row"><b>Email:</b> <span id="profileEmail"></span></div>
                    <div class="profile-modal-actions-row">
                        <button type="button" id="editProfileBtn" class="profile-modal-btn profile-modal-edit-btn" style="width:auto;flex:1;">Edit</button>
                        <button type="button" id="logoutProfileBtn" class="profile-modal-btn profile-modal-logout-btn logout-btn-custom" style="width:auto;flex:1;display:flex;align-items:center;justify-content:center;gap:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" style="vertical-align:middle;"><path stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m4 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1"/></svg>
                            <span style="vertical-align:middle;">Logout</span>
                        </button>
                    </div>
                </div>
                <div id="profileModalEdit" style="display:none;">
                    <label for="editName">Full Name</label>
                    <input type="text" id="editName" name="name" required>
                    <label for="editUnit">Unit</label>
                    <input type="text" id="editUnit" name="unit" required>
                    <label for="editBirthdate">Birthdate</label>
                    <input type="date" id="editBirthdate" name="birthdate" required>
                    <label for="editAge">Age</label>
                    <input type="number" id="editAge" name="age" min="18" max="100" required>
                    <label for="editPhone">Phone Number</label>
                    <input type="text" id="editPhone" name="phone" required>
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" required>
                    <div id="profileModalError" class="profile-modal-error"></div>
                    <button type="submit" class="profile-modal-btn profile-modal-save-btn">Save</button>
                    <button type="button" id="cancelEditProfileBtn" class="profile-modal-btn profile-modal-cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <style>
    .profile-modal-content {
        max-width: 420px;
        width: 96vw;
        margin: 60px auto;
        padding: 32px 24px 18px 24px;
        border-radius: 16px;
        background: #fff;
        position: relative;
        top: 40px;
        border: 1.5px solid #e5e7eb;
        box-shadow: 0 4px 24px 0 rgba(31,38,135,0.10);
    }
    .profile-modal-row {
        margin-bottom: 1rem;
        font-size: 1.05em;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.4rem;
    }
    .profile-modal-btn {
        width: 100%;
        border: none;
        border-radius: 8px;
        padding: 0.7rem 1rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.7rem;
        transition: background 0.2s;
    }
    .profile-modal-edit-btn {
        background: #1a3c6b;
        color: #fff;
    }
    .profile-modal-edit-btn:hover {
        background: #0d2446;
    }
    .profile-modal-save-btn {
        background: #1a3c6b;
        color: #fff;
        margin-bottom: 0.5rem;
    }
    .profile-modal-save-btn:hover {
        background: #0d2446;
    }
    .profile-modal-cancel-btn {
        background: #e5e7eb;
        color: #1a3c6b;
        margin-bottom: 0.5rem;
    }
    .profile-modal-cancel-btn:hover {
        background: #cbd5e1;
    }
    .profile-modal-logout-btn {
        background: #dc2626;
        color: #fff;
    }
    .profile-modal-logout-btn:hover {
        background: #b91c1c;
        color: #fff;
    }
    .profile-modal-logout-btn:hover svg path {
        stroke: #fff;
    }
    .profile-modal-error {
        color: #ef4444;
        font-weight: 600;
        margin-bottom: 1rem;
        text-align: center;
    }
    #profileModal label {
        font-weight: 600;
        color: #1a3c6b;
        margin-bottom: 4px;
        display: block;
    }
    #profileModal input[type="text"],
    #profileModal input[type="email"],
    #profileModal input[type="date"],
    #profileModal input[type="number"] {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 1px solid #d1d9e6;
        border-radius: 8px;
        font-size: 1rem;
        background: #f7fafd;
        margin-bottom: 1.2rem;
        box-sizing: border-box;
    }
    #profileModal .modal-title {
        text-align: center;
        margin-bottom: 18px;
        font-size: 1.5em;
        font-weight: 700;
        color: #1a3c6b;
    }
    .profile-modal-actions-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 12px;
        margin-top: 0.7rem;
        margin-bottom: 0;
    }
    .profile-modal-actions-row .profile-modal-edit-btn {
        margin-top: 0;
        margin-bottom: 0;
    }
    .profile-modal-actions-row .profile-modal-logout-btn {
        margin-top: 0;
        margin-bottom: 0;
    }
    </style>
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

    // Profile modal logic
    const profileIconBtn = document.getElementById('profileIconBtn');
    const profileModal = document.getElementById('profileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');
    const profileModalView = document.getElementById('profileModalView');
    const profileModalEdit = document.getElementById('profileModalEdit');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditProfileBtn = document.getElementById('cancelEditProfileBtn');
    const profileModalForm = document.getElementById('profileModalForm');
    const profileModalError = document.getElementById('profileModalError');

    let userProfile = null;

    profileIconBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fetch('profile.php?modal=1')
            .then(res => res.json())
            .then(data => {
                userProfile = data;
                document.getElementById('profileName').textContent = data.name;
                document.getElementById('profileUnit').textContent = data.unit;
                document.getElementById('profileBirthdate').textContent = data.birthdate;
                document.getElementById('profileAge').textContent = data.age;
                document.getElementById('profilePhone').textContent = data.phone;
                document.getElementById('profileEmail').textContent = data.email;
                profileModalView.style.display = '';
                profileModalEdit.style.display = 'none';
                profileModal.style.display = 'block';
            });
    });
    closeProfileModal.onclick = function() { profileModal.style.display = 'none'; };
    profileModal.onclick = function(e) { if (e.target === this) profileModal.style.display = 'none'; };
    editProfileBtn.onclick = function() {
        document.getElementById('editName').value = userProfile.name;
        document.getElementById('editUnit').value = userProfile.unit;
        document.getElementById('editBirthdate').value = userProfile.birthdate;
        document.getElementById('editAge').value = userProfile.age;
        document.getElementById('editPhone').value = userProfile.phone;
        document.getElementById('editEmail').value = userProfile.email;
        profileModalView.style.display = 'none';
        profileModalEdit.style.display = '';
        // Hide logout button while editing
        document.querySelector('.profile-modal-logout-btn').style.display = 'none';
    };
    cancelEditProfileBtn.onclick = function() {
        profileModalView.style.display = '';
        profileModalEdit.style.display = 'none';
        profileModalError.textContent = '';
        // Show logout button again
        document.querySelector('.profile-modal-logout-btn').style.display = '';
    };
    profileModalForm.onsubmit = function(e) {
        if (profileModalEdit.style.display === 'none') return false;
        e.preventDefault();
        profileModalError.textContent = '';
        const formData = new FormData(profileModalForm);
        fetch('profile.php?modal=1', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                userProfile = data.user;
                document.getElementById('profileName').textContent = data.user.name;
                document.getElementById('profileUnit').textContent = data.user.unit;
                document.getElementById('profileBirthdate').textContent = data.user.birthdate;
                document.getElementById('profileAge').textContent = data.user.age;
                document.getElementById('profilePhone').textContent = data.user.phone;
                document.getElementById('profileEmail').textContent = data.user.email;
                profileModalView.style.display = '';
                profileModalEdit.style.display = 'none';
                profileModalError.textContent = '';
                // Show logout button again
                document.querySelector('.profile-modal-logout-btn').style.display = '';
            } else {
                profileModalError.textContent = data.error || 'Update failed.';
            }
        })
        .catch(() => { profileModalError.textContent = 'Update failed.'; });
    };

    // Replace logout form with JS redirect
    const logoutProfileBtn = document.getElementById('logoutProfileBtn');
    if (logoutProfileBtn) {
        logoutProfileBtn.onclick = function() {
            window.location.href = 'logout.php';
        };
    }
    </script>
</body>
</html>