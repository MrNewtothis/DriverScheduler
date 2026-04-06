<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}
require_once '../connection/db.php';

// Filtering
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$where = 'WHERE role != "admin"';
$params = [];
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR email LIKE ? OR unit LIKE ? OR phone LIKE ?)';
    $params = array_merge($params, array_fill(0, 4, "%$search%"));
}
if ($role !== '') {
    $where .= ' AND role = ?';
    $params[] = $role;
}

// Fetch users
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY name ASC");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add/edit/delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $unit = trim($_POST['unit']);
        $birthdate = trim($_POST['birthdate']);
        $age = trim($_POST['age']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, unit, birthdate, age, phone, email, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $unit, $birthdate, $age, $phone, $email, $role, $password]);
        header('Location: users.php');
        exit;
    }
    if (isset($_POST['edit_user_id'])) {
        $id = $_POST['edit_user_id'];
        $name = trim($_POST['edit_name']);
        $unit = trim($_POST['edit_unit']);
        $birthdate = trim($_POST['edit_birthdate']);
        $age = trim($_POST['edit_age']);
        $phone = trim($_POST['edit_phone']);
        $email = trim($_POST['edit_email']);
        $role = trim($_POST['edit_role']);
        $sql = "UPDATE users SET name=?, unit=?, birthdate=?, age=?, phone=?, email=?, role=? WHERE id=?";
        $params = [$name, $unit, $birthdate, $age, $phone, $email, $role, $id];
        if (!empty($_POST['edit_password'])) {
            $sql = "UPDATE users SET name=?, unit=?, birthdate=?, age=?, phone=?, email=?, role=?, password=? WHERE id=?";
            $params = [$name, $unit, $birthdate, $age, $phone, $email, $role, password_hash($_POST['edit_password'], PASSWORD_DEFAULT), $id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Location: users.php');
        exit;
    }
    if (isset($_POST['delete_user_id'])) {
        $id = $_POST['delete_user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        header('Location: users.php');
        exit;
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    ?>
    <tbody id="user-table-body">
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['unit']) ?></td>
            <td><?= htmlspecialchars($user['birthdate']) ?></td>
            <td><?= htmlspecialchars($user['age']) ?></td>
            <td><?= htmlspecialchars($user['phone']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td class="action-cell">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="edit_user_id" value="<?= $user['id'] ?>">
                    <button type="button" class="main-btn" style="background:#2563eb; color:#fff; border-radius:8px;" onclick="openEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)">Edit</button>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                    <button type="submit" class="main-btn" style="background:#dc2626; color:#fff; border-radius:8px;">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <?php
    echo ob_get_clean();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - NIA Equipment Unit System</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/header-modern.css">
    <link rel="icon" type="image/png" href="../imgs/nialogo.png">
</head>
<body>
<?php include 'admin_header.php'; ?>
<div class="main-content" style="padding-left:max(16px,env(safe-area-inset-left));padding-right:max(16px,env(safe-area-inset-right));">
    <div class="dashboard-card">
        <div class="dashboard-header-row">
            <h2 class="dashboard-title" style="margin:0;">User Management</h2>
            <form id="user-search-form" method="get">
                <input type="text" id="user-search-input" name="search" placeholder="Search name, email, unit, phone" value="<?= htmlspecialchars($search) ?>" class="main-input">
                <select name="role" id="user-role-select" class="main-input">
                    <option value="">All Roles</option>
                    <option value="user"<?= $role==='user'?' selected':'' ?>>User</option>
                    <option value="manager"<?= $role==='manager'?' selected':'' ?>>Manager</option>
                </select>
                <button type="submit" class="main-btn main-btn--primary">Filter</button>
                <a href="users.php" class="main-btn main-btn--secondary">Reset</a>
                <button type="button" class="main-btn main-btn--accent" onclick="document.getElementById('add-user-modal').style.display='block';">+ Add User</button>
            </form>
        </div>
        <div class="dashboard-table-wrapper" id="user-table-wrapper">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Unit</th>
                        <th>Birthdate</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['unit']) ?></td>
                        <td><?= htmlspecialchars($user['birthdate']) ?></td>
                        <td><?= htmlspecialchars($user['age']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td class="action-cell">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit_user_id" value="<?= $user['id'] ?>">
                                <button type="button" class="main-btn" style="background:#2563eb; color:#fff; border-radius:8px;" onclick="openEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)">Edit</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="main-btn" style="background:#dc2626; color:#fff; border-radius:8px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Add User Modal -->
<div id="add-user-modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
    <div style="background:#fff; border-radius:18px; box-shadow:0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10); max-width:420px; width:96vw; margin:60px auto; padding:36px 32px 26px 32px; position:relative; top:40px; border:1.5px solid #e5e7eb;">
        <button type="button" onclick="document.getElementById('add-user-modal').style.display='none';" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;">&times;</button>
        <h3 style="margin-top:0; margin-bottom:22px; font-size:1.35em; color:#0f172a; letter-spacing:0.5px; font-weight:700;">Add New User</h3>
        <form method="post" action="" autocomplete="off">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="unit">Unit</label>
            <input type="text" id="unit" name="unit" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" min="18" max="100" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
            <label for="role">Role</label>
            <select id="role" name="role" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:14px;">
                <option value="user">User</option>
                <option value="manager">Manager</option>
            </select>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1em; background:#f8fafc; margin-top:4px; margin-bottom:18px;">
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="submit" name="add_user" class="main-btn" style="background:#22c55e; color:#fff; border-radius:8px;">Save</button>
            </div>
        </form>
    </div>
</div>
<!-- Edit User Modal (dynamically filled) -->
<div id="edit-user-modal" style="display:none; position:fixed; z-index:100; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
    <div id="edit-user-modal-content"></div>
</div>
<script>
function openEditUserModal(user) {
    var html = `<div class="user-mgmt-modal-content">
        <button type="button" onclick="document.getElementById('edit-user-modal').style.display='none';" class="user-mgmt-modal-close">&times;</button>
        <h3 style="margin-top:0; margin-bottom:22px; font-size:1.35em; color:#0f172a; letter-spacing:0.5px; font-weight:700;">Edit User</h3>
        <form method='post' action='' autocomplete='off'>
            <input type='hidden' name='edit_user_id' value='${user.id}'>
            <label for='edit_name'>Full Name</label>
            <input type='text' id='edit_name' name='edit_name' value='${user.name || ''}' required class="user-mgmt-modal-input">
            <label for='edit_unit'>Unit</label>
            <input type='text' id='edit_unit' name='edit_unit' value='${user.unit || ''}' required class="user-mgmt-modal-input">
            <label for='edit_birthdate'>Birthdate</label>
            <input type='date' id='edit_birthdate' name='edit_birthdate' value='${user.birthdate || ''}' required class="user-mgmt-modal-input">
            <label for='edit_age'>Age</label>
            <input type='number' id='edit_age' name='edit_age' value='${user.age || ''}' min='18' max='100' required class="user-mgmt-modal-input">
            <label for='edit_phone'>Phone Number</label>
            <input type='text' id='edit_phone' name='edit_phone' value='${user.phone || ''}' required class="user-mgmt-modal-input">
            <label for='edit_email'>Email</label>
            <input type='email' id='edit_email' name='edit_email' value='${user.email || ''}' required class="user-mgmt-modal-input">
            <label for='edit_role'>Role</label>
            <select id='edit_role' name='edit_role' required class="user-mgmt-modal-input">
                <option value='user' ${user.role==='user'?'selected':''}>User</option>
                <option value='manager' ${user.role==='manager'?'selected':''}>Manager</option>
            </select>
            <label for='edit_password'>Password (leave blank to keep current)</label>
            <input type='password' id='edit_password' name='edit_password' class="user-mgmt-modal-input">
            <div class='user-mgmt-modal-actions'>
                <button type='submit' class='user-mgmt-modal-btn user-mgmt-modal-save'>Save</button>
            </div>
        </form>
    </div>`;
    document.getElementById('edit-user-modal-content').innerHTML = html;
    document.getElementById('edit-user-modal').style.display = 'block';
}
document.getElementById('edit-user-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// Live search for users
const searchInput = document.getElementById('user-search-input');
const roleSelect = document.getElementById('user-role-select');
const tableBody = document.getElementById('user-table-body');
const tableWrapper = document.getElementById('user-table-wrapper');
let searchTimeout = null;

function fetchUsers() {
    const search = encodeURIComponent(searchInput.value);
    const role = encodeURIComponent(roleSelect.value);
    fetch(`users.php?search=${search}&role=${role}&ajax=1`)
        .then(res => res.text())
        .then(html => {
            // Replace the entire tbody
            tableBody.outerHTML = html;
            // Re-attach event listeners for edit buttons in the new tbody
            const newTableBody = document.getElementById('user-table-body');
            if (newTableBody) {
                Array.from(newTableBody.querySelectorAll('button.main-btn')).forEach(btn => {
                    if (btn.textContent.trim() === 'Edit') {
                        btn.onclick = function() {
                            const tr = btn.closest('tr');
                            const tds = tr.querySelectorAll('td');
                            const user = {
                                id: tr.querySelector('input[name="edit_user_id"]').value,
                                name: tds[0].textContent,
                                unit: tds[1].textContent,
                                birthdate: tds[2].textContent,
                                age: tds[3].textContent,
                                phone: tds[4].textContent,
                                email: tds[5].textContent,
                                role: tds[6].textContent
                            };
                            openEditUserModal(user);
                        };
                    }
                });
            }
        });
}

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchUsers, 200);
});
roleSelect.addEventListener('change', fetchUsers);
</script>
<style>
.main-content {
    padding-left: max(16px, env(safe-area-inset-left));
    padding-right: max(16px, env(safe-area-inset-right));
    box-sizing: border-box;
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
#user-search-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    margin: 0;
}
.dashboard-table-wrapper {
    width: 100%;
    overflow-x: auto;
    padding-left: max(12px, env(safe-area-inset-left));
    padding-right: max(12px, env(safe-area-inset-right));
    box-sizing: border-box;
    margin-bottom: 0;
}
@media (max-width: 900px) {
    .dashboard-table-wrapper {
        padding-left: max(8px, env(safe-area-inset-left));
        padding-right: max(8px, env(safe-area-inset-right));
    }
    .dashboard-header-row {
        padding-left: 8px;
        padding-right: 8px;
    }
}
@media (max-width: 700px) {
    .dashboard-table-wrapper {
        padding-left: max(4vw, env(safe-area-inset-left));
        padding-right: max(4vw, env(safe-area-inset-right));
    }
    .dashboard-header-row {
        flex-direction: column;
        align-items: stretch !important;
        gap: 12px !important;
        padding-left: 4vw;
        padding-right: 4vw;
    }
    #user-search-form {
        flex-direction: column;
        align-items: stretch !important;
        gap: 10px !important;
    }
}
@media (max-width: 500px) {
    .dashboard-table-wrapper {
        padding-left: max(2vw, env(safe-area-inset-left));
        padding-right: max(2vw, env(safe-area-inset-right));
    }
    .dashboard-header-row {
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
@media (max-width: 700px) {
    .dashboard-card > div[style*='display:flex'] {
        flex-direction: column;
        align-items: stretch !important;
        gap: 12px !important;
    }
    #user-search-form {
        flex-direction: column;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .main-btn, .main-btn--primary, .main-btn--secondary, .main-btn--accent {
        width: 100%;
        min-width: 0;
        margin-bottom: 6px;
    }
    .dashboard-table th, .dashboard-table td {
        padding: 8px 6px;
    }
}
@media (max-width: 500px) {
    .dashboard-table th, .dashboard-table td {
        padding: 6px 3px;
        font-size: 0.91em;
    }
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
.user-mgmt-modal-content {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10);
    max-width: 420px;
    width: 96vw;
    margin: 60px auto;
    padding: 36px 32px 26px 32px;
    position: relative;
    top: 40px;
    border: 1.5px solid #e5e7eb;
}
.user-mgmt-modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    background: none;
    border: none;
    font-size: 1.5em;
    color: #64748b;
    cursor: pointer;
}
.user-mgmt-modal-input {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 1em;
    background: #f8fafc;
    margin-top: 4px;
    margin-bottom: 14px;
    box-sizing: border-box;
}
.user-mgmt-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 10px;
}
.user-mgmt-modal-btn {
    background: #2563eb;
    color: #fff;
    border-radius: 8px;
    border: none;
    padding: 8px 18px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.user-mgmt-modal-save {
    background: #2563eb;
}
</style>
</body>
</html>
