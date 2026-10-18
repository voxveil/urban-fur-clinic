<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

$error = (($_GET['account'] ?? '') === 'inactive') ? 'Your account is inactive. Please contact an administrator.' : '';
$loginRole = (($_GET['role'] ?? $_POST['login_role'] ?? 'admin') === 'client') ? 'client' : 'admin';
$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn && ($_SESSION['user_role'] ?? '') === 'Client') {
    header('Location: client-portal.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoggedIn) {
    $username = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user = null;
    if ($loginRole === 'client') {
        $statement = $mysqli->prepare(
            "SELECT u.id, u.password_hash, u.full_name, u.role
             FROM users u JOIN clients c ON c.account_user_id = u.id
             WHERE (u.username = ? OR c.email = ?) AND u.active = 1
               AND u.role = 'Client' AND c.status = 'Active' LIMIT 1"
        );
    } else {
        $statement = $mysqli->prepare(
            "SELECT id, password_hash, full_name, role
             FROM users WHERE (username = ? OR email = ?) AND active = 1 AND role <> 'Client' LIMIT 1"
        );
    }
    if ($statement === false) {
        $error = 'Unable to query the user account. Please try again.';
    } else {
        if ($loginRole === 'client') {
            $statement->bind_param('ss', $username, $username);
        } else {
            $statement->bind_param('ss', $username, $username);
        }
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();
    }

    if (is_array($user) && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['client_user_id'], $_SESSION['client_form_token']);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        if ($user['role'] === 'Client') {
            $_SESSION['client_user_id'] = (int) $user['id'];
            $_SESSION['client_form_token'] = bin2hex(random_bytes(32));
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
            header('Location: client-portal.php');
            exit;
        }
        header('Location: index.php');
        exit;
    }

    if ($statement !== false) {
        $error = 'Invalid username or password. Please try again.';
    }
}

$userCountResult = $mysqli->query("SELECT COUNT(*) AS total FROM users WHERE role <> 'Client'");
$userCount = $userCountResult instanceof mysqli_result ? (int) $userCountResult->fetch_assoc()['total'] : 0;
if (!$isLoggedIn && $userCount === 0) {
    header('Location: setup.php');
    exit;
}
$canViewFinancials = in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin', 'Staff', 'Cashier'], true);
$canAcceptAppointments = in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Urban Fur Veterinary Clinic - Pet HealthCare System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=39">
</head>
<body>

  <!-- ==================== 1. LOGIN SCREEN ==================== -->
  <div id="login-view" class="view<?= $isLoggedIn ? '' : ' active' ?>">
    <div class="login-container">
      <div class="login-branding">
        <img class="clinic-logo" src="assets/clinic-logo.png" alt="Urban Fur Veterinary Clinic logo">
        <h1 class="brand-title">Urban Fur Veterinary Clinic</h1>
        <p class="brand-subtitle">Pet HealthCare Management System</p>
        <span class="brand-accent" aria-hidden="true"></span>
      </div>

      <div class="login-form-container">
        <div class="form-header">
          <h2>Welcome Back!</h2>
          <p>Log in to your account</p>
        </div>
        <div class="login-role-switch" role="tablist" aria-label="Choose sign-in type">
          <button type="button" class="login-role-option<?= $loginRole === 'admin' ? ' active' : '' ?>" id="adminLoginTab" role="tab" aria-selected="<?= $loginRole === 'admin' ? 'true' : 'false' ?>" data-login-role="admin">Admin / Staff</button>
          <button type="button" class="login-role-option<?= $loginRole === 'client' ? ' active' : '' ?>" id="clientLoginTab" role="tab" aria-selected="<?= $loginRole === 'client' ? 'true' : 'false' ?>" data-login-role="client">Pet Owner</button>
        </div>

        <form id="loginForm" method="post" action="index.php">
          <input type="hidden" name="login_role" id="loginRole" value="<?= htmlspecialchars($loginRole, ENT_QUOTES, 'UTF-8') ?>">
          <div class="input-group">
            <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
            <input type="text" id="username" name="identity" placeholder="Email or username" autocomplete="username" aria-label="Email or username" required value="<?= htmlspecialchars((string) ($_POST['identity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="input-group">
            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" aria-label="Password" required>
            <button class="toggle-pwd" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye-slash" aria-hidden="true"></i></button>
          </div>

          <?php if ($error !== ''): ?>
            <p class="login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <p class="password-recovery-link"><a id="forgotPasswordLink" href="password-recovery.php<?= $loginRole === 'client' ? '?return=client' : '' ?>">Forgot Password?</a></p>
          <button type="submit" class="btn-login">
            Log In <i class="fa-solid fa-arrow-right"></i>
          </button>
        </form>
        <p class="client-login-link" id="clientRegisterLink" <?= $loginRole === 'client' ? '' : 'hidden' ?>><a href="client-auth.php?mode=register">New client? Register here</a></p>

      </div>
    </div>
  </div>

  <!-- ==================== 2. DASHBOARD SCREEN ==================== -->
  <div id="dashboard-view" class="view<?= $isLoggedIn ? ' active' : '' ?>">
    <button class="dashboard-mobile-nav-toggle" id="dashboardMobileNavToggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="adminDashboardSidebar"><i class="fa-solid fa-bars"></i></button>
    <div class="dashboard-mobile-nav-backdrop" id="dashboardMobileNavBackdrop" aria-hidden="true"></div>
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar" id="adminDashboardSidebar">
      <div class="sidebar-brand">
        <i class="fa-solid fa-paw"></i> Urban Fur
      </div>

      <ul class="nav-list">
        <li class="nav-item active"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
        <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
        <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
        <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
        <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
        <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
        <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
        <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
        <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
      </ul>

    </aside>

    <!-- Main Content View -->
    <main class="main-content">
      <div class="page-header">
        <div class="page-title">
          <h1>Dashboard</h1>
          <p>Appointments, clients, and sales at a glance.</p>
        </div>
        <div class="dashboard-account-actions">
          <time class="dashboard-current-date" id="dashboardCurrentDate"></time>
          <div class="dashboard-notification-wrap">
            <button class="dashboard-notification-button" id="notificationButton" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationPopover">
              <i class="fa-regular fa-bell" aria-hidden="true"></i>
              <span class="notification-count" id="notificationCount" hidden>0</span>
            </button>
            <section class="dashboard-notification-popover" id="notificationPopover" aria-label="Notifications" hidden>
              <div class="notification-popover-heading"><strong>Notifications</strong><button type="button" id="closeNotifications" aria-label="Close notifications">&times;</button></div>
              <ul id="notificationList"><li class="notification-empty">Loading notifications…</li></ul>
            </section>
          </div>
          <button class="dashboard-user-profile" id="openAdminProfile" type="button" aria-label="Edit your profile">
            <span class="dashboard-user-avatar"><?= htmlspecialchars(strtoupper(substr(trim((string) ($_SESSION['user_name'] ?? 'U')), 0, 1) ?: 'U'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="dashboard-user-details">
              <strong><?= htmlspecialchars((string) ($_SESSION['user_name'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></strong>
              <small><?= htmlspecialchars((string) ($_SESSION['user_role'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
          </button>
          <button class="btn-secondary" id="logoutBtn" type="button"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
        </div>
      </div>

      <section>
        <div class="stats-row">
          <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-users"></i></span><div class="stat-info"><h3 id="dashboard-clients">0</h3><p>Clients</p></div></div>
          <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-paw"></i></span><div class="stat-info"><h3 id="dashboard-pets">0</h3><p>Patient Records</p></div></div>
          <?php if ($canViewFinancials): ?>
          <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-peso-sign"></i></span><div class="stat-info"><h3 id="dashboard-sales">₱0.00</h3><p>Today's Paid Sales</p></div></div>
          <?php endif; ?>
          <div class="stat-card"><span class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></span><div class="stat-info"><h3 id="dashboard-low-stock">0</h3><p>Low-stock Items</p></div></div>
        </div>
        <div class="page-header schedule-header">
          <div class="page-title">
            <h2>Upcoming Schedule</h2>
          </div>
          <div class="settings-tabs schedule-tabs" role="tablist" aria-label="Filter appointments">
            <button class="tab-btn active" type="button" role="tab" aria-selected="true" data-schedule-filter="all">All</button>
            <button class="tab-btn" type="button" role="tab" aria-selected="false" data-schedule-filter="today">Today</button>
            <button class="tab-btn" type="button" role="tab" aria-selected="false" data-schedule-filter="upcoming">Upcoming</button>
            <button class="tab-btn" type="button" role="tab" aria-selected="false" data-schedule-filter="done">Done</button>
          </div>
        </div>
        <div class="table-container">
          <table>
            <thead><tr><th>Client</th><th>Mobile Number</th><th>Patient</th><th>Breed</th><th>Scheduled</th><th>Service</th><th>Status</th></tr></thead>
            <tbody id="dashboardScheduleBody"><tr><td colspan="7">Loading schedule…</td></tr></tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div class="modal-overlay" id="adminProfileModal" role="dialog" aria-modal="true" aria-labelledby="adminProfileHeading">
    <div class="modal">
      <div class="modal-header"><h2 id="adminProfileHeading">My Profile</h2><button type="button" class="close-modal" id="closeAdminProfile" aria-label="Close profile"><i class="fa-solid fa-xmark"></i></button></div>
      <form id="adminProfileForm">
        <div class="profile-photo-editor"><img id="adminProfilePreview" alt="Profile photo preview" hidden><span id="adminProfileInitial" class="dashboard-user-avatar"><?= htmlspecialchars(strtoupper(substr(trim((string) ($_SESSION['user_name'] ?? 'U')), 0, 1) ?: 'U'), ENT_QUOTES, 'UTF-8') ?></span><label class="btn-secondary" for="adminProfilePhoto">Choose photo</label><input id="adminProfilePhoto" type="file" accept="image/png,image/jpeg,image/webp" hidden></div>
        <div class="form-grid"><div class="form-group"><label for="adminProfileName">Full name</label><input id="adminProfileName" name="full_name" maxlength="150" required></div><div class="form-group"><label for="adminProfileUsername">Username</label><input id="adminProfileUsername" name="username" maxlength="80" required></div><div class="form-group"><label for="adminProfileEmail">Email</label><input id="adminProfileEmail" name="email" type="email" maxlength="190"></div><div class="form-group"><label for="adminProfilePhone">Phone</label><input id="adminProfilePhone" name="phone" maxlength="40"></div></div>
        <div class="modal-actions"><button type="button" class="btn-secondary" id="cancelAdminProfile">Cancel</button><button type="submit" class="btn-primary">Save profile</button></div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=14"></script>
  <script>
  const logoutButton = document.getElementById('logoutBtn');
  if (logoutButton) {
    logoutButton.addEventListener('click', () => window.location.assign('logout.php'));
  }

  const togglePassword = document.querySelector('.toggle-pwd');
  const passwordField = document.getElementById('password');
  const loginRoleInput = document.getElementById('loginRole');
  document.querySelectorAll('[data-login-role]').forEach(tab => {
    tab.addEventListener('click', () => {
      const role = tab.dataset.loginRole;
      loginRoleInput.value = role;
      document.querySelectorAll('[data-login-role]').forEach(option => {
        const selected = option === tab;
        option.classList.toggle('active', selected);
        option.setAttribute('aria-selected', String(selected));
      });
      const clientLogin = role === 'client';
      const identityInput = document.getElementById('username');
      identityInput.placeholder = 'Email or username';
      identityInput.setAttribute('aria-label', 'Email or username');
      document.getElementById('forgotPasswordLink').href = `password-recovery.php${clientLogin ? '?return=client' : ''}`;
      document.getElementById('clientRegisterLink').hidden = !clientLogin;
    });
  });

  if (togglePassword && passwordField) {
    togglePassword.addEventListener('click', function () {
      const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordField.setAttribute('type', type);
      const icon = this.querySelector('i');
      const showingPassword = type === 'text';
      this.setAttribute('aria-pressed', String(showingPassword));
      this.setAttribute('aria-label', showingPassword ? 'Hide password' : 'Show password');
      icon.className = `fa-solid ${showingPassword ? 'fa-eye' : 'fa-eye-slash'}`;
    });
  }

  const dashboardView = document.getElementById('dashboard-view');
  if (dashboardView && dashboardView.classList.contains('active')) {
    const dashboardNavButton = document.getElementById('dashboardMobileNavToggle');
    const closeDashboardNav = () => { dashboardView.classList.remove('mobile-nav-open'); dashboardNavButton.setAttribute('aria-expanded', 'false'); dashboardNavButton.setAttribute('aria-label', 'Open navigation'); };
    dashboardNavButton.addEventListener('click', () => { const open = dashboardView.classList.toggle('mobile-nav-open'); dashboardNavButton.setAttribute('aria-expanded', String(open)); dashboardNavButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation'); });
    document.getElementById('dashboardMobileNavBackdrop').addEventListener('click', closeDashboardNav);
    document.querySelectorAll('#adminDashboardSidebar .nav-item a').forEach(link => link.addEventListener('click', closeDashboardNav));

    const profileModal = document.getElementById('adminProfileModal');
    const profileForm = document.getElementById('adminProfileForm');
    let adminProfileImage = '';
    const closeProfile = () => profileModal.classList.remove('active');
    const paintProfile = image => {
      adminProfileImage = image || '';
      const preview = document.getElementById('adminProfilePreview');
      const initial = document.getElementById('adminProfileInitial');
      preview.hidden = !adminProfileImage; preview.src = adminProfileImage || '';
      initial.hidden = Boolean(adminProfileImage);
      document.querySelectorAll('.dashboard-user-avatar').forEach(avatar => {
        if (avatar.id === 'adminProfileInitial') return;
        avatar.replaceChildren();
        if (adminProfileImage) {
          const image = document.createElement('img'); image.src = adminProfileImage; image.alt = ''; avatar.append(image);
        } else {
          avatar.textContent = document.getElementById('adminProfileName').value.trim().charAt(0) || 'U';
        }
      });
    };
    document.getElementById('openAdminProfile').addEventListener('click', async () => {
      try {
        const { data } = await clinicRequest('profile');
        profileForm.elements.full_name.value = data.full_name || '';
        profileForm.elements.username.value = data.username || '';
        profileForm.elements.email.value = data.email || '';
        profileForm.elements.phone.value = data.phone || '';
        paintProfile(data.profile_image || '');
        profileModal.classList.add('active');
      } catch (error) { clinicShowError(error); }
    });
    document.getElementById('closeAdminProfile').addEventListener('click', closeProfile);
    document.getElementById('cancelAdminProfile').addEventListener('click', closeProfile);
    document.getElementById('adminProfilePhoto').addEventListener('change', event => {
      const file = event.target.files?.[0]; if (!file) return;
      if (file.size > 1048576 || !['image/png', 'image/jpeg', 'image/webp'].includes(file.type)) { clinicShowError(new Error('Choose a PNG, JPG, or WebP image up to 1 MB.')); event.target.value = ''; return; }
      const reader = new FileReader(); reader.onload = () => paintProfile(String(reader.result)); reader.readAsDataURL(file);
    });
    profileForm.addEventListener('submit', async event => {
      event.preventDefault(); const button = event.submitter; button.disabled = true;
      try {
        await clinicRequest('profile', 'update', { full_name: profileForm.elements.full_name.value, username: profileForm.elements.username.value, email: profileForm.elements.email.value, phone: profileForm.elements.phone.value, profile_image: adminProfileImage });
        document.querySelector('.dashboard-user-details strong').textContent = profileForm.elements.full_name.value;
        document.querySelector('.dashboard-user-details small').textContent = <?= json_encode((string) ($_SESSION['user_role'] ?? 'Staff')) ?>;
        closeProfile();
      } catch (error) { clinicShowError(error); } finally { button.disabled = false; }
    });
    const canAcceptAppointments = <?= $canAcceptAppointments ? 'true' : 'false' ?>;
    const formatPeso = value => `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const dashboardDate = document.getElementById('dashboardCurrentDate');
    const updateDashboardDate = () => {
      dashboardDate.textContent = new Date().toLocaleString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    };
    updateDashboardDate();
    window.setInterval(updateDashboardDate, 60000);
    let scheduleAppointments = [];
    let scheduleFilter = 'all';
    function renderSchedule() {
      const body = document.getElementById('dashboardScheduleBody');
      const today = new Date();
      const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
      const filtered = scheduleAppointments.filter(item => {
        const appointmentTime = new Date(String(item.appointment_at).replace(' ', 'T'));
        if (scheduleFilter === 'today') return String(item.appointment_at).slice(0, 10) === todayKey;
        if (scheduleFilter === 'upcoming') return appointmentTime > today && !['Completed', 'Cancelled', 'No Show'].includes(item.status);
        if (scheduleFilter === 'done') return ['Completed', 'No Show'].includes(item.status);
        return true;
      });
      body.innerHTML = filtered.length ? filtered.map(item => {
        const statusClass = item.status === 'Completed' ? 'status-completed' : item.status === 'In Progress' ? 'status-in-progress' : item.status === 'Cancelled' ? 'status-cancelled' : 'status-scheduled';
        const appointmentDate = new Date(String(item.appointment_at).replace(' ', 'T'));
        const appointmentDay = new Date(appointmentDate.getFullYear(), appointmentDate.getMonth(), appointmentDate.getDate());
        const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        const dayOffset = Math.round((appointmentDay - todayDate) / 86400000);
        const relativeDay = dayOffset === 0 ? 'Today' : dayOffset === 1 ? 'Tomorrow' : dayOffset === -1 ? 'Yesterday' : appointmentDate.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        return `<tr>
          <td><strong>${clinicEscape(item.client)}</strong></td>
          <td>${clinicEscape(item.phone)}</td>
          <td>${clinicEscape(item.pet)}</td>
          <td>${clinicEscape(item.breed)}</td>
          <td>${clinicEscape(relativeDay)}, ${clinicEscape(appointmentDate.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }))}</td>
          <td>${clinicEscape(item.service)}</td>
          <td><span class="status-badge ${statusClass}">${clinicEscape(item.status)}</span></td>
        </tr>`;
      }).join('') : `<tr><td colspan="7">No ${scheduleFilter === 'all' ? '' : scheduleFilter + ' '}appointments found.</td></tr>`;
    }

    document.querySelectorAll('[data-schedule-filter]').forEach(tab => {
      tab.addEventListener('click', () => {
        scheduleFilter = tab.dataset.scheduleFilter;
        document.querySelectorAll('[data-schedule-filter]').forEach(item => {
          const selected = item === tab;
          item.classList.toggle('active', selected);
          item.setAttribute('aria-selected', String(selected));
        });
        renderSchedule();
      });
    });

    const notificationButton = document.getElementById('notificationButton');
    const notificationPopover = document.getElementById('notificationPopover');
    const notificationList = document.getElementById('notificationList');
    function setNotificationPopover(open) {
      notificationPopover.hidden = !open;
      notificationButton.setAttribute('aria-expanded', String(open));
    }
    let pendingAppointmentRequests = [];
    const knownAppointmentRequestIds = new Set();
    function renderNotifications(notifications) {
      const notificationTotal = notifications.length + pendingAppointmentRequests.length;
      document.getElementById('notificationCount').textContent = String(notificationTotal);
      document.getElementById('notificationCount').hidden = notificationTotal === 0;
      notificationButton.setAttribute('aria-label', notificationTotal
        ? `Notifications, ${notificationTotal} items`
        : 'Notifications');
      const requestItems = pendingAppointmentRequests.map(request => `
        <li class="dashboard-notification-item appointment-request-notification">
          <span class="notification-type-icon appointment"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i></span>
          <span class="appointment-request-copy">
            <strong>New appointment request · ${clinicEscape(request.pet)}</strong>
            <small>${clinicEscape(request.client)} · ${clinicEscape(request.service)} · ${clinicEscape(new Date(String(request.appointment_at).replace(' ', 'T')).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }))}</small>
            <small>Purpose: ${clinicEscape(request.purpose || 'Not provided')}</small>
            <span class="appointment-request-actions">
              <button type="button" class="appointment-request-accept" data-appointment-decision="accept" data-appointment-id="${Number(request.id)}">Accept</button>
              <button type="button" class="appointment-request-decline" data-appointment-decision="decline" data-appointment-id="${Number(request.id)}">Decline</button>
            </span>
          </span>
        </li>`).join('');
      const regularItems = notifications.map(notification => `
        <li class="dashboard-notification-item">
          <span class="notification-type-icon ${clinicEscape(notification.type)}"><i class="fa-solid ${clinicEscape(notification.icon)}" aria-hidden="true"></i></span>
          <span><strong>${clinicEscape(notification.title)}</strong><small>${clinicEscape(notification.message)}</small><time>${clinicEscape(notification.date || '')}</time></span>
        </li>`).join('');
      notificationList.innerHTML = notificationTotal ? requestItems + regularItems : '<li class="notification-empty">You’re all caught up.</li>';
    }
    async function refreshAppointmentRequests(openForNew = true) {
      if (!canAcceptAppointments) return;
      const result = await clinicRequest('appointment-notifications');
      const requests = result.data || [];
      const hasNewRequest = requests.some(request => !knownAppointmentRequestIds.has(Number(request.id)));
      requests.forEach(request => knownAppointmentRequestIds.add(Number(request.id)));
      pendingAppointmentRequests = requests;
      renderNotifications(window.dashboardNotifications || []);
      if (openForNew && hasNewRequest) setNotificationPopover(true);
    }
    notificationList.addEventListener('click', async event => {
      const button = event.target.closest('[data-appointment-decision]');
      if (!button) return;
      const decision = button.dataset.appointmentDecision;
      const appointmentId = Number(button.dataset.appointmentId);
      if (!['accept', 'decline'].includes(decision) || !Number.isInteger(appointmentId)) return;
      button.disabled = true;
      try {
        const result = await clinicRequest('appointments', 'decision', { id: appointmentId, decision });
        window.alert(result.status === 'Scheduled' ? 'Appointment accepted.' : 'Appointment declined.');
        pendingAppointmentRequests = pendingAppointmentRequests.filter(request => Number(request.id) !== appointmentId);
        renderNotifications(window.dashboardNotifications || []);
        const dashboardData = await clinicRequest('dashboard');
        scheduleAppointments = dashboardData.schedule;
        window.dashboardNotifications = dashboardData.notifications || [];
        renderSchedule();
        renderNotifications(window.dashboardNotifications);
        await refreshAppointmentRequests(false);
      } catch (error) {
        clinicShowError(error);
        button.disabled = false;
        await refreshAppointmentRequests(false).catch(clinicShowError);
      }
    });
    notificationButton.addEventListener('click', () => {
      setNotificationPopover(notificationPopover.hidden);
    });
    document.getElementById('closeNotifications').addEventListener('click', () => setNotificationPopover(false));
    document.addEventListener('click', event => {
      if (!event.target.closest('.dashboard-notification-wrap')) setNotificationPopover(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') setNotificationPopover(false);
    });

    clinicRequest('dashboard').then(data => {
      document.getElementById('dashboard-clients').textContent = Number(data.counts.clients).toLocaleString();
      document.getElementById('dashboard-pets').textContent = Number(data.counts.pets).toLocaleString();
      const salesSummary = document.getElementById('dashboard-sales');
      if (salesSummary) salesSummary.textContent = formatPeso(data.today_sales);
      document.getElementById('dashboard-low-stock').textContent = Number(data.counts.low_stock).toLocaleString();
      scheduleAppointments = data.schedule;
      window.dashboardNotifications = data.notifications || [];
      renderSchedule();
      renderNotifications(window.dashboardNotifications);
      refreshAppointmentRequests(true).catch(clinicShowError);
      if (canAcceptAppointments) window.setInterval(() => refreshAppointmentRequests(true).catch(clinicShowError), 15000);
    }).catch(clinicShowError);
  }
</script>
