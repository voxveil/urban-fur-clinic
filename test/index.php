<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

$error = (($_GET['account'] ?? '') === 'inactive') ? 'Your account is inactive. Please contact an administrator.' : '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoggedIn) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user = null;
    $statement = $mysqli->prepare(
        'SELECT id, password_hash, full_name, role
         FROM users
         WHERE username = ? AND active = 1
         LIMIT 1'
    );
    if ($statement === false) {
        $error = 'Unable to query the user account. Please try again.';
    } else {
        $statement->bind_param('s', $username);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();
    }

    if (is_array($user) && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: index.php');
        exit;
    }

    if ($statement !== false) {
        $error = 'Invalid username or password. Please try again.';
    }
}

$userCountResult = $mysqli->query('SELECT COUNT(*) AS total FROM users');
$userCount = $userCountResult instanceof mysqli_result ? (int) $userCountResult->fetch_assoc()['total'] : 0;
if (!$isLoggedIn && $userCount === 0) {
    header('Location: setup.php');
    exit;
}
$canViewFinancials = in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin', 'Staff', 'Cashier'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Urban Fur Veterinary Clinic - Pet HealthCare System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=17">
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

        <form id="loginForm" method="post" action="index.php">
          <div class="input-group">
            <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
            <input type="text" id="username" name="username" placeholder="Username" autocomplete="username" aria-label="Username" required value="<?= htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="input-group">
            <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" aria-label="Password" required>
            <button class="toggle-pwd" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
          </div>

          <?php if ($error !== ''): ?>
            <p class="login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <button type="submit" class="btn-login">
            Log In <i class="fa-solid fa-arrow-right"></i>
          </button>
        </form>

      </div>
    </div>
  </div>

  <!-- ==================== 2. DASHBOARD SCREEN ==================== -->
  <div id="dashboard-view" class="view<?= $isLoggedIn ? ' active' : '' ?>">
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar">
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
          <div class="dashboard-user-profile">
            <span class="dashboard-user-avatar"><?= htmlspecialchars(strtoupper(substr(trim((string) ($_SESSION['user_name'] ?? 'U')), 0, 1) ?: 'U'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="dashboard-user-details">
              <strong><?= htmlspecialchars((string) ($_SESSION['user_name'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></strong>
              <small><?= htmlspecialchars((string) ($_SESSION['user_role'] ?? 'Staff'), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
          </div>
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

  <script src="assets/app.js?v=7"></script>
  <script>
  const logoutButton = document.getElementById('logoutBtn');
  if (logoutButton) {
    logoutButton.addEventListener('click', () => window.location.assign('logout.php'));
  }

  const togglePassword = document.querySelector('.toggle-pwd');
  const passwordField = document.getElementById('password');

  if (togglePassword && passwordField) {
    togglePassword.addEventListener('click', function () {
      const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordField.setAttribute('type', type);
      const icon = this.querySelector('i');
      const showingPassword = type === 'text';
      this.setAttribute('aria-pressed', String(showingPassword));
      this.setAttribute('aria-label', showingPassword ? 'Hide password' : 'Show password');
      icon.classList.toggle('fa-eye', !showingPassword);
      icon.classList.toggle('fa-eye-slash', showingPassword);
    });
  }

  const dashboardView = document.getElementById('dashboard-view');
  if (dashboardView && dashboardView.classList.contains('active')) {
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
        if (scheduleFilter === 'upcoming') return appointmentTime > today && !['Completed', 'Cancelled'].includes(item.status);
        if (scheduleFilter === 'done') return item.status === 'Completed';
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
    function renderNotifications(notifications) {
      document.getElementById('notificationCount').textContent = String(notifications.length);
      document.getElementById('notificationCount').hidden = notifications.length === 0;
      notificationButton.setAttribute('aria-label', notifications.length
        ? `Notifications, ${notifications.length} items`
        : 'Notifications');
      notificationList.innerHTML = notifications.length ? notifications.map(notification => `
        <li class="dashboard-notification-item">
          <span class="notification-type-icon ${clinicEscape(notification.type)}"><i class="fa-solid ${clinicEscape(notification.icon)}" aria-hidden="true"></i></span>
          <span><strong>${clinicEscape(notification.title)}</strong><small>${clinicEscape(notification.message)}</small><time>${clinicEscape(notification.date || '')}</time></span>
        </li>`).join('') : '<li class="notification-empty">You’re all caught up.</li>';
    }
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
      renderSchedule();
      renderNotifications(data.notifications || []);
    }).catch(clinicShowError);
  }
</script>