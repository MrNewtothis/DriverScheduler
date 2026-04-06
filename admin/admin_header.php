<header class="header-modern">
    <div class="header-modern-left">
        <a href="main.php" class="header-modern-logo">
            <img src="../imgs/nialogo.png" alt="NIA Logo" class="header-modern-logo-img">
            <span class="header-modern-title">NIA Equipment Unit System</span>
        </a>
    </div>
    <nav class="header-modern-nav" id="headerModernNav">
        <a href="main.php" class="header-modern-link">Home</a>
        <a href="driverlog.php" class="header-modern-link">Driver List/Log</a>
        <a href="request.php" class="header-modern-link">Transpo Requests</a>
        <a href="report.php" class="header-modern-link">Report</a>
        <a href="driver_performance.php" class="header-modern-link">Driver Performance</a>
        <a href="users.php" class="header-modern-link">User Management</a>
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
<!-- Profile Modal -->
<div id="profileModal" class="profile-modal-overlay" style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100vw; height:100vh; background:rgba(30,41,59,0.18);">
    <div class="profile-modal-content" style="background:#fff; border-radius:16px; max-width:420px; width:96vw; margin:60px auto; padding:36px 32px 26px 32px; position:relative; top:40px; border:1.5px solid #e5e7eb; box-shadow:0 8px 32px rgba(30,41,59,0.18), 0 1.5px 8px rgba(30,41,59,0.10);">
        <button id="closeProfileModal" class="modal-close" style="position:absolute; top:18px; right:18px; background:none; border:none; font-size:1.5em; color:#64748b; cursor:pointer;">&times;</button>
        <h3 class="modal-title" style="margin-top:0; margin-bottom:22px; font-size:1.35em; color:#0f172a; letter-spacing:0.5px; font-weight:700;">Profile Information</h3>
        <div id="profileModalView">
            <div id="profileModalTable"></div>
            <div class="profile-modal-actions-row" style="display:flex; justify-content:space-between; gap:12px; margin-top:18px;">
                <button type="button" id="editProfileBtn" class="profile-modal-btn profile-modal-edit-btn" style="flex:1; margin-right:0; background:#2563eb; color:#fff; border-radius:8px;">Edit</button>
                <form method="post" action="logout.php" style="margin:0; padding:0; flex:1; display:flex; justify-content:flex-end;">
                    <button type="submit" class="profile-modal-btn profile-modal-logout-btn logout-btn-custom" style="width:100%; background:#dc2626; color:#fff; border-radius:8px;">Logout</button>
                </form>
            </div>
        </div>
        <div id="profileModalEdit" style="display:none; margin-top:10px;">
            <label for="editName">Full Name</label>
            <input type="text" id="editName" name="name" required style="width:100%;margin-bottom:10px;">
            <label for="editUnit">Unit</label>
            <input type="text" id="editUnit" name="unit" required style="width:100%;margin-bottom:10px;">
            <label for="editBirthdate">Birthdate</label>
            <input type="date" id="editBirthdate" name="birthdate" required style="width:100%;margin-bottom:10px;">
            <label for="editAge">Age</label>
            <input type="number" id="editAge" name="age" min="18" max="100" required style="width:100%;margin-bottom:10px;">
            <label for="editPhone">Phone Number</label>
            <input type="text" id="editPhone" name="phone" required style="width:100%;margin-bottom:10px;">
            <label for="editEmail">Email</label>
            <input type="email" id="editEmail" name="email" required style="width:100%;margin-bottom:10px;">
            <div id="profileModalError" class="profile-modal-error" style="color:#dc2626; margin-bottom:10px;"></div>
            <div style="display:flex; justify-content:space-between; gap:12px;">
                <button type="button" id="saveProfileBtn" class="profile-modal-btn profile-modal-save-btn" style="flex:1; background:#22c55e; color:#fff; border-radius:8px;">Save</button>
                <button type="button" id="cancelEditProfileBtn" class="profile-modal-btn profile-modal-cancel-btn" style="flex:1; background:#64748b; color:#fff; border-radius:8px;">Cancel</button>
            </div>
        </div>
    </div>
</div>
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
// Profile modal logic
const profileBtn = document.getElementById('profileIconBtn');
const profileModal = document.getElementById('profileModal');
const closeProfileModal = document.getElementById('closeProfileModal');
const profileModalView = document.getElementById('profileModalView');
const profileModalEdit = document.getElementById('profileModalEdit');
const editProfileBtn = document.getElementById('editProfileBtn');
const cancelEditProfileBtn = document.getElementById('cancelEditProfileBtn');
const saveProfileBtn = document.getElementById('saveProfileBtn');

profileBtn.addEventListener('click', function(e) {
    e.preventDefault();
    // Fetch user info via AJAX
    fetch('profile.php')
        .then(response => response.json())
        .then(data => {
            // Use table for better layout
            document.getElementById('profileModalTable').innerHTML = data.tableHtml || '';
            document.getElementById('editName').value = data.name || '';
            document.getElementById('editUnit').value = data.unit || '';
            document.getElementById('editBirthdate').value = data.birthdate || '';
            document.getElementById('editAge').value = data.age || '';
            document.getElementById('editPhone').value = data.phone || '';
            document.getElementById('editEmail').value = data.email || '';
            profileModal.style.display = 'block';
            profileModalView.style.display = 'block';
            profileModalEdit.style.display = 'none';
        });
});
closeProfileModal.addEventListener('click', function() {
    profileModal.style.display = 'none';
});
window.addEventListener('click', function(e) {
    if (e.target === profileModal) profileModal.style.display = 'none';
});
editProfileBtn.addEventListener('click', function() {
    profileModalView.style.display = 'none';
    profileModalEdit.style.display = 'block';
    document.getElementById('editName').value = document.getElementById('profileName').textContent;
    document.getElementById('editEmail').value = document.getElementById('profileEmail').textContent;
});
cancelEditProfileBtn.addEventListener('click', function() {
    profileModalView.style.display = 'block';
    profileModalEdit.style.display = 'none';
});
saveProfileBtn.addEventListener('click', function() {
    // Here you would send an AJAX request to update the profile
    // For now, just switch back to view mode
    profileModalView.style.display = 'block';
    profileModalEdit.style.display = 'none';
    // Optionally update the displayed info
    document.getElementById('profileName').textContent = document.getElementById('editName').value;
    document.getElementById('profileEmail').textContent = document.getElementById('editEmail').value;
});
</script>
