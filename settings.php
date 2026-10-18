<?php
require_once __DIR__ . '/auth.php';
$isAdministrator = in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=34">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item active"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1>Settings</h1>
        <p>Manage the clinic details and preferences used by the system.</p>
      </div>
      <div class="settings-header-actions">
        <button class="btn-secondary settings-back-button settings-header-back-button" id="backToSettings" type="button" hidden>
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Settings
        </button>
      </div>
    </div>

    <p class="settings-message" id="settingsMessage" role="status" aria-live="polite"></p>

    <section class="settings-grid" id="settingsOptions" aria-label="Clinic settings">
      <a class="settings-option" href="#generalSettings" data-panel="generalSettings" aria-controls="generalSettings" aria-expanded="true">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-gear"></i></span>
        <span class="settings-option-copy"><strong>Clinic Details</strong><small>Name and contact information</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#appointmentSettings" data-panel="appointmentSettings" aria-controls="appointmentSettings" aria-expanded="false">
        <span class="settings-option-icon settings-purple"><i class="fa-regular fa-calendar-check"></i></span>
        <span class="settings-option-copy"><strong>Appointments</strong><small>Clinic hours, policies, and time slots</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#billingSettings" data-panel="billingSettings" aria-controls="billingSettings" aria-expanded="false">
        <span class="settings-option-icon settings-green"><i class="fa-solid fa-file-invoice-dollar"></i></span>
        <span class="settings-option-copy"><strong>Billing</strong><small>Tax rate and accepted payment methods</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#inventorySettings" data-panel="inventorySettings" aria-controls="inventorySettings" aria-expanded="false">
        <span class="settings-option-icon settings-amber"><i class="fa-solid fa-boxes-stacked"></i></span>
        <span class="settings-option-copy"><strong>Inventory</strong><small>Low-stock levels, suppliers, and expiration alerts</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#serviceSettings" data-panel="serviceSettings" aria-controls="serviceSettings" aria-expanded="false">
        <span class="settings-option-icon settings-green"><i class="fa-solid fa-hand-holding-medical"></i></span>
        <span class="settings-option-copy"><strong>Services & Pricing</strong><small>Update service categories and prices</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#catalogCategorySettings" data-panel="catalogCategorySettings" aria-controls="catalogCategorySettings" aria-expanded="false">
        <span class="settings-option-icon settings-amber"><i class="fa-solid fa-layer-group"></i></span>
        <span class="settings-option-copy"><strong>Catalog Categories</strong><small>Add, rename, or delete service and inventory categories</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#reportSettings" data-panel="reportSettings" aria-controls="reportSettings" aria-expanded="false">
        <span class="settings-option-icon settings-purple"><i class="fa-solid fa-chart-line"></i></span>
        <span class="settings-option-copy"><strong>Reports</strong><small>Default report period and export format</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#notificationSettings" data-panel="notificationSettings" aria-controls="notificationSettings" aria-expanded="false">
        <span class="settings-option-icon settings-amber"><i class="fa-regular fa-bell"></i></span>
        <span class="settings-option-copy"><strong>Notifications</strong><small>Email and SMS notification preferences</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#userPreferences" data-panel="userPreferences" aria-controls="userPreferences" aria-expanded="false">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-user-gear"></i></span>
        <span class="settings-option-copy"><strong>User & System Preferences</strong><small>Appearance, backup reminders, and audit history</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <?php if ($isAdministrator): ?>
      <a class="settings-option" href="users.php">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-user-lock"></i></span>
        <span class="settings-option-copy"><strong>User Accounts</strong><small>Create and manage employee logins</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <?php else: ?>
      <div class="settings-option settings-option-disabled" aria-disabled="true">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-user-lock"></i></span>
        <span class="settings-option-copy"><strong>User Accounts</strong><small>Administrator access only</small></span>
        <i class="fa-solid fa-lock settings-chevron"></i>
      </div>
      <?php endif; ?>
    </section>

    <section class="settings-editor" id="generalSettings" hidden>
      <h2>Clinic Details</h2>
      <p>These details identify the clinic on receipts and records.</p>
      <form id="generalForm" class="settings-form">
        <div class="form-grid">
          <div class="form-group full"><label for="clinicName">Clinic name</label><input id="clinicName" name="clinic_name" maxlength="150" required></div>
          <div class="form-group"><label for="clinicPhone">Phone</label><input id="clinicPhone" name="phone" type="tel" maxlength="40"></div>
          <div class="form-group"><label for="clinicEmail">Email</label><input id="clinicEmail" name="email" type="email" maxlength="190"></div>
          <div class="form-group full"><label for="clinicAddress">Address</label><input id="clinicAddress" name="address" maxlength="255"></div>
          <div class="form-group full"><label for="clinicLogoUrl">Logo URL or site path</label><input id="clinicLogoUrl" maxlength="500" placeholder="/test/assets/clinic-logo.png"><small>Use an HTTPS image URL or a path within this site.</small></div>
        </div>
        <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save clinic details</button>
      </form>
    </section>

    <section class="settings-editor" id="appointmentSettings" hidden>
      <h2>Appointment Hours</h2>
      <p>New appointments must fit clinic hours and start on the selected time-slot interval.</p>
      <form id="appointmentSettingsForm" class="settings-form">
        <div class="form-grid">
          <div class="form-group"><label for="openingTime">Opens at</label><input id="openingTime" name="opening_time" type="time" required></div>
          <div class="form-group"><label for="closingTime">Closes at</label><input id="closingTime" name="closing_time" type="time" required></div>
          <div class="form-group"><label for="appointmentInterval">Appointment time slots</label>
            <select id="appointmentInterval" name="appointment_interval_minutes" required>
              <option value="15">Every 15 minutes</option>
              <option value="30">Every 30 minutes</option>
              <option value="45">Every 45 minutes</option>
              <option value="60">Every 60 minutes</option>
            </select>
          </div>
          <div class="form-group full"><label for="cancellationPolicy">Cancellation policy</label><textarea id="cancellationPolicy" maxlength="1000" rows="3" placeholder="Enter the clinic cancellation policy"></textarea></div>
          <div class="form-group"><label><input type="checkbox" id="appointmentReminders"> Enable appointment reminders</label></div>
          <div class="form-group"><label for="reminderHours">Reminder lead time (hours)</label><input id="reminderHours" type="number" min="1" max="720" required></div>
        </div>
        <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save appointment hours</button>
      </form>
      <hr>
      <h3>Clinic closure dates</h3>
      <p>Add holidays or maintenance closures to prevent clients from requesting appointments on those dates.</p>
      <form id="appointmentClosureForm" class="settings-form">
        <div class="form-grid">
          <div class="form-group"><label for="closureStartDate">Start date</label><input id="closureStartDate" type="date" required></div>
          <div class="form-group"><label for="closureEndDate">End date</label><input id="closureEndDate" type="date" required></div>
          <div class="form-group full"><label for="closureReason">Reason (optional)</label><input id="closureReason" maxlength="255" placeholder="Holiday, maintenance, or other closure"></div>
        </div>
        <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Add closure</button>
      </form>
      <div class="table-container"><table><thead><tr><th>Start</th><th>End</th><th>Reason</th><th>Action</th></tr></thead><tbody id="appointmentClosureBody"><tr><td colspan="4">Open this panel to load upcoming closures.</td></tr></tbody></table></div>
    </section>

    <section class="settings-editor" id="billingSettings" hidden>
      <h2>Billing</h2>
      <p>POS tax is configurable here; set the rate to 0% to keep tax excluded. Payment methods selected here are available in checkout.</p>
      <form id="billingSettingsForm" class="settings-form">
        <div class="form-group"><label for="taxRate">Tax rate (%)</label><input id="taxRate" type="number" min="0" max="100" step="0.01" required></div>
        <div class="form-group full"><label for="paymentMethodsList">Accepted payment methods</label><textarea id="paymentMethodsList" rows="3" maxlength="1000" required></textarea><small>Enter one method per line. Use “Pay Later” for unpaid invoices.</small></div>
        <div class="form-group full"><label for="expenseReasonsList">Expense reason choices</label><textarea id="expenseReasonsList" rows="4" maxlength="1000" required></textarea><small>Enter one reason per line. Keep “Other” if you want a custom reason field.</small></div>
        <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save billing preferences</button>
      </form>
    </section>

    <section class="settings-editor" id="inventorySettings" hidden>
      <h2>Inventory Alerts</h2>
      <p>This level is used as the starting low-stock alert for newly added items. Existing items keep their own thresholds.</p>
      <form id="inventorySettingsForm" class="settings-form">
        <div class="form-group"><label for="defaultMinimum">Default low-stock quantity</label><input id="defaultMinimum" name="default_minimum_quantity" type="number" min="0" max="1000000" step="1" required></div>
        <div class="form-group"><label><input type="checkbox" id="reorderAlerts"> Enable reorder alerts</label></div>
        <div class="form-group"><label for="expirationAlertDays">Expiration alert lead time (days)</label><input id="expirationAlertDays" type="number" min="0" max="365" required></div>
        <div class="form-group full"><label for="supplierInfo">Supplier information</label><textarea id="supplierInfo" maxlength="1000" rows="2" placeholder="Clinic supplier contact details"></textarea></div>
        <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save inventory settings</button>
      </form>
    </section>

    <section class="settings-editor" id="serviceSettings" hidden>
      <h2>Services & Pricing</h2>
      <p>Update a service’s category or price. These changes apply to the service catalog and checkout.</p>
      <div class="table-container"><table><thead><tr><th>Service</th><th>Category</th><th>Price (₱)</th><th>Action</th></tr></thead><tbody id="serviceSettingsBody"><tr><td colspan="4">Open this panel to load services.</td></tr></tbody></table></div>
    </section>

    <section class="settings-editor" id="catalogCategorySettings" hidden>
      <h2>Catalog Categories</h2>
      <p>Manage the category choices shown when adding services or inventory items. Categories containing items cannot be deleted until those items are moved.</p>
      <div class="settings-category-manager">
        <form id="categoryForm" class="settings-category-form">
          <div class="form-group">
            <label for="categoryType">Catalog</label>
            <select id="categoryType"><option value="service">Services</option><option value="inventory">Inventory</option></select>
          </div>
          <div class="form-group">
            <label for="newCategoryName">Category name</label>
            <input id="newCategoryName" maxlength="100" required>
          </div>
          <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>><i class="fa-solid fa-plus"></i> Add category</button>
        </form>
        <div class="table-container"><table><thead><tr><th>Catalog</th><th>Category</th><th>Actions</th></tr></thead><tbody id="catalogCategoryBody"><tr><td colspan="3">Open this panel to load categories.</td></tr></tbody></table></div>
      </div>
    </section>

    <section class="settings-editor" id="reportSettings" hidden>
      <h2>Report Defaults</h2>
      <form id="reportSettingsForm" class="settings-form">
      <div class="form-grid">
        <div class="form-group"><label for="reportDefaultPeriod">Default period</label><select id="reportDefaultPeriod"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
        <div class="form-group"><label for="reportExportFormat">Export format</label><select id="reportExportFormat"><option value="csv">CSV</option><option value="excel">Excel</option><option value="pdf">PDF</option></select></div>
      </div>
      <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save report defaults</button>
      </form>
    </section>

    <section class="settings-editor" id="notificationSettings" hidden>
      <h2>Notification Preferences</h2>
      <p>Email and SMS choices below save your notification preferences. Automatic email and text messages are not sent by this app.</p>
      <form id="notificationSettingsForm" class="settings-form">
      <fieldset class="settings-fieldset"><legend>Appointments</legend><label><input type="checkbox" id="appointmentEmail"> Email</label><label><input type="checkbox" id="appointmentSms"> SMS</label></fieldset>
      <fieldset class="settings-fieldset"><legend>Low stock</legend><label><input type="checkbox" id="lowStockEmail"> Email</label><label><input type="checkbox" id="lowStockSms"> SMS</label></fieldset>
      <fieldset class="settings-fieldset"><legend>Unpaid bills</legend><label><input type="checkbox" id="unpaidEmail"> Email</label><label><input type="checkbox" id="unpaidSms"> SMS</label></fieldset>
      <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save notification preferences</button>
      </form>
    </section>

    <section class="settings-editor" id="userPreferences" hidden>
      <h2>User & System Preferences</h2>
      <p>Choose the interface appearance and customize the accent and sidebar colors. System uses the original clinic system colors.</p>
      <form id="userPreferencesForm" class="settings-form">
      <div class="form-grid">
        <div class="form-group full"><label for="themeMode">Appearance</label><select id="themeMode"><option value="system">System (default)</option><option value="light">Light</option><option value="dark">Dark</option></select></div>
      </div>
      <div class="user-preference-colors">
        <div class="form-group"><label for="themeColor">Accent color</label><input id="themeColor" type="color"><small>Used for buttons and selected navigation.</small></div>
        <div class="form-group"><label for="sidebarColor">Sidebar color</label><input id="sidebarColor" type="color"><small>Used for the navigation sidebar.</small></div>
      </div>
      <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save system preferences</button>
      </form>
      <h3>Role access</h3>
      <div class="role-access-list">
        <div><strong>Administrator</strong><span>Manage all clinic features, settings, and user accounts</span></div>
        <div><strong>Staff</strong><span>Legacy role with full application access</span></div>
        <div><strong>Vet</strong><span>Veterinary and appointment workflows</span></div>
        <div><strong>Receptionist</strong><span>Client, patient, and appointment workflows</span></div>
        <div><strong>Cashier</strong><span>Checkout and financial reports</span></div>
      </div>
      <div class="audit-history-header">
        <h3>Recent audit history</h3>
        <?php if ($isAdministrator): ?>
        <button class="btn-secondary audit-history-clear" id="clearAuditHistory" type="button">
          <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Clear history
        </button>
        <?php endif; ?>
      </div>
      <div class="table-container"><table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody id="auditLogBody"><tr><td colspan="4">Open this panel to load audit history.</td></tr></tbody></table></div>
    </section>

    <div class="settings-help" id="settingsHelp">
      <span class="settings-help-icon"><i class="fa-solid fa-circle-info"></i></span>
      <div><strong>Settings are saved to the clinic database.</strong><p>Only an administrator can change these preferences.</p></div>
    </div>

  </main>

  <script src="assets/app.js?v=14"></script>
  <script>
    const settingsMessage = document.getElementById('settingsMessage');
    const settingsPanels = [...document.querySelectorAll('.settings-editor')];
    let auditRefreshTimer = null;
    let auditLogsRequest = null;
    let currentSettings = null;
    const canManageSettings = <?= $isAdministrator ? 'true' : 'false' ?>;

    function showSettingsPanel(panelId) {
      let selectedPanel = null;
      settingsPanels.forEach(panel => {
        panel.hidden = panel.id !== panelId;
        if (panel.id === panelId) selectedPanel = panel;
      });
      document.getElementById('settingsOptions').hidden = selectedPanel !== null;
      document.getElementById('settingsHelp').hidden = selectedPanel !== null;
      document.getElementById('backToSettings').hidden = selectedPanel === null;
      if (auditRefreshTimer !== null) {
        window.clearInterval(auditRefreshTimer);
        auditRefreshTimer = null;
      }
      document.querySelectorAll('.settings-option').forEach(button => {
        button.setAttribute('aria-expanded', String(button.dataset.panel === panelId));
      });
      const pageTitle = document.querySelector('.page-title h1');
      const pageDescription = document.querySelector('.page-title p');
      if (selectedPanel) {
        const option = document.querySelector(`.settings-option[data-panel="${panelId}"]`);
        pageTitle.textContent = option?.querySelector('strong')?.textContent || 'Settings';
        pageDescription.textContent = option?.querySelector('small')?.textContent || '';
        pageDescription.hidden = !pageDescription.textContent;
        if (panelId === 'userPreferences' && canManageSettings) {
          auditRefreshTimer = window.setInterval(loadAuditLogs, 15000);
        }
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
      } else {
        pageTitle.textContent = 'Settings';
        pageDescription.textContent = 'Manage the clinic details and preferences used by the system.';
        pageDescription.hidden = false;
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
      }
    }

    function displaySettings(settings) {
      currentSettings = settings;
      document.getElementById('clinicName').value = settings.clinic_name;
      document.getElementById('clinicPhone').value = settings.phone;
      document.getElementById('clinicEmail').value = settings.email;
      document.getElementById('clinicAddress').value = settings.address;
      document.getElementById('clinicLogoUrl').value = settings.clinic_logo_url || '';
      document.getElementById('openingTime').value = settings.opening_time;
      document.getElementById('closingTime').value = settings.closing_time;
      document.getElementById('appointmentInterval').value = String(settings.appointment_interval_minutes);
      document.getElementById('cancellationPolicy').value = settings.cancellation_policy || '';
      document.getElementById('appointmentReminders').checked = Boolean(settings.appointment_reminders);
      document.getElementById('reminderHours').value = settings.reminder_hours_before || 24;
      document.getElementById('defaultMinimum').value = settings.default_minimum_quantity;
      document.getElementById('taxRate').value = settings.tax_rate;
      document.getElementById('paymentMethodsList').value = (settings.accepted_payment_methods || []).join('\n');
      document.getElementById('expenseReasonsList').value = (settings.expense_reasons || []).join('\n');
      document.getElementById('reorderAlerts').checked = Boolean(settings.reorder_alerts);
      document.getElementById('expirationAlertDays').value = settings.expiration_alert_days ?? 30;
      document.getElementById('supplierInfo').value = settings.supplier_info || '';
      document.getElementById('reportDefaultPeriod').value = settings.reports_default_period || 'monthly';
      document.getElementById('reportExportFormat').value = settings.reports_export_format || 'csv';
      document.getElementById('themeColor').value = settings.theme_color || '#2d83b5';
      document.getElementById('themeMode').value = settings.theme_mode || 'system';
      document.getElementById('sidebarColor').value = settings.sidebar_color || '#0284c7';
      applySystemPreferenceColors();
      const notifications = settings.notifications || {};
      document.getElementById('appointmentEmail').checked = Boolean(notifications.appointment_email);
      document.getElementById('appointmentSms').checked = Boolean(notifications.appointment_sms);
      document.getElementById('lowStockEmail').checked = Boolean(notifications.low_stock_email);
      document.getElementById('lowStockSms').checked = Boolean(notifications.low_stock_sms);
      document.getElementById('unpaidEmail').checked = Boolean(notifications.unpaid_email);
      document.getElementById('unpaidSms').checked = Boolean(notifications.unpaid_sms);
    }

    async function loadSettings() {
      try {
        const result = await clinicRequest('settings');
        displaySettings(result.data);
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      }
    }

    async function saveSettings(form, values, preferences = null) {
      const submit = form.querySelector('button[type="submit"]');
      submit.disabled = true;
      settingsMessage.classList.remove('settings-error');
      settingsMessage.textContent = 'Saving settings…';
      try {
        let savedSettings = currentSettings;
        if (Object.keys(values).length > 0) {
          const result = await clinicRequest('settings', 'update', values);
          savedSettings = result.data;
        }
        if (preferences) {
          const preferenceResult = await clinicRequest('settings', 'preferences', preferences);
          savedSettings = preferenceResult.data;
        }
        displaySettings(savedSettings);
        settingsMessage.textContent = 'Settings saved successfully.';
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      } finally {
        submit.disabled = false;
      }
    }

    document.getElementById('backToSettings').addEventListener('click', () => {
        history.replaceState(null, '', `${window.location.pathname}${window.location.search}`);
        showSettingsPanel(null);
    });

    document.querySelectorAll('a.settings-option[data-panel]').forEach(option => {
      option.setAttribute('aria-expanded', 'false');
      option.addEventListener('click', async event => {
        event.preventDefault();
        const panelId = option.dataset.panel;
        history.pushState(null, '', `#${panelId}`);
        showSettingsPanel(panelId);
        await loadSettingsPanelData(panelId);
      });
    });

    async function loadSettingsPanelData(panelId) {
      try {
        if (panelId === 'appointmentSettings') await loadAppointmentClosures();
        if (panelId === 'catalogCategorySettings') await loadCatalogCategories();
        if (panelId === 'serviceSettings') await loadServiceSettings();
        if (panelId === 'userPreferences' && canManageSettings) await loadAuditLogs();
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      }
    }

    async function loadAppointmentClosures() {
      const { data } = await clinicRequest('appointment-closures');
      const body = document.getElementById('appointmentClosureBody');
      body.innerHTML = data.length ? data.map(closure => `<tr><td>${clinicEscape(closure.start_date)}</td><td>${clinicEscape(closure.end_date)}</td><td>${clinicEscape(closure.reason || '—')}</td><td>${canManageSettings ? `<button type="button" class="btn-secondary" data-delete-closure="${Number(closure.id)}">Remove</button>` : '—'}</td></tr>`).join('') : '<tr><td colspan="4">No upcoming clinic closures.</td></tr>';
    }

    document.getElementById('appointmentClosureForm').addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const submit = form.querySelector('button[type="submit"]');
      submit.disabled = true;
      try {
        await clinicRequest('appointment-closures', 'create', {
          start_date: document.getElementById('closureStartDate').value,
          end_date: document.getElementById('closureEndDate').value,
          reason: document.getElementById('closureReason').value
        });
        form.reset();
        settingsMessage.classList.remove('settings-error');
        settingsMessage.textContent = 'Clinic closure saved.';
        await loadAppointmentClosures();
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      } finally {
        submit.disabled = false;
      }
    });
    document.getElementById('appointmentClosureBody').addEventListener('click', async event => {
      const button = event.target.closest('[data-delete-closure]');
      if (!button || !confirm('Remove this clinic closure?')) return;
      button.disabled = true;
      try {
        await clinicRequest('appointment-closures', 'delete', { id: Number(button.dataset.deleteClosure) });
        await loadAppointmentClosures();
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
        button.disabled = false;
      }
    });

    const availableSettingsPanels = new Set(settingsPanels.map(panel => panel.id));
    const initialSettingsPanel = window.location.hash.slice(1);
    const selectedSettingsPanel = availableSettingsPanels.has(initialSettingsPanel) ? initialSettingsPanel : null;
    if (selectedSettingsPanel === null && 'scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
    showSettingsPanel(selectedSettingsPanel);
    if (selectedSettingsPanel !== null) loadSettingsPanelData(selectedSettingsPanel);
    window.addEventListener('popstate', () => {
      const panelId = window.location.hash.slice(1);
      const nextPanel = availableSettingsPanels.has(panelId) ? panelId : null;
      showSettingsPanel(nextPanel);
      if (nextPanel !== null) loadSettingsPanelData(nextPanel);
    });

    document.getElementById('generalForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, {
        clinic_name: document.getElementById('clinicName').value,
        phone: document.getElementById('clinicPhone').value,
        email: document.getElementById('clinicEmail').value,
        address: document.getElementById('clinicAddress').value
      }, { clinic_logo_url: document.getElementById('clinicLogoUrl').value });
    });

    document.getElementById('appointmentSettingsForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, {
        opening_time: document.getElementById('openingTime').value,
        closing_time: document.getElementById('closingTime').value,
        appointment_interval_minutes: document.getElementById('appointmentInterval').value
      }, {
        cancellation_policy: document.getElementById('cancellationPolicy').value,
        appointment_reminders: document.getElementById('appointmentReminders').checked,
        reminder_hours_before: document.getElementById('reminderHours').value
      });
    });

    document.getElementById('inventorySettingsForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, { default_minimum_quantity: document.getElementById('defaultMinimum').value }, {
        reorder_alerts: document.getElementById('reorderAlerts').checked,
        expiration_alert_days: document.getElementById('expirationAlertDays').value,
        supplier_info: document.getElementById('supplierInfo').value
      });
    });

    document.getElementById('billingSettingsForm').addEventListener('submit', async event => {
      event.preventDefault();
      const paymentMethods = document.getElementById('paymentMethodsList').value.split(/\r?\n/).map(method => method.trim()).filter(Boolean);
      const expenseReasons = document.getElementById('expenseReasonsList').value.split(/\r?\n/).map(reason => reason.trim()).filter(Boolean);
      if (paymentMethods.length === 0) {
        settingsMessage.textContent = 'Select at least one accepted payment method.';
        settingsMessage.classList.add('settings-error');
        return;
      }
      if (expenseReasons.length === 0) {
        settingsMessage.textContent = 'Enter at least one expense reason.';
        settingsMessage.classList.add('settings-error');
        return;
      }
      await saveSettings(event.currentTarget, { tax_rate: document.getElementById('taxRate').value }, {
        accepted_payment_methods: paymentMethods,
        expense_reasons: expenseReasons
      });
    });

    document.getElementById('reportSettingsForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, {}, {
        reports_default_period: document.getElementById('reportDefaultPeriod').value,
        reports_export_format: document.getElementById('reportExportFormat').value
      });
    });

    document.getElementById('notificationSettingsForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, {}, {
        notifications: {
          appointment_email: document.getElementById('appointmentEmail').checked,
          appointment_sms: document.getElementById('appointmentSms').checked,
          low_stock_email: document.getElementById('lowStockEmail').checked,
          low_stock_sms: document.getElementById('lowStockSms').checked,
          unpaid_email: document.getElementById('unpaidEmail').checked,
          unpaid_sms: document.getElementById('unpaidSms').checked
        }
      });
    });

    document.getElementById('userPreferencesForm').addEventListener('submit', event => {
      event.preventDefault();
      saveSettings(event.currentTarget, {}, {
        theme_color: document.getElementById('themeColor').value,
        theme_mode: document.getElementById('themeMode').value,
        sidebar_color: document.getElementById('sidebarColor').value
      });
    });

    function applySystemPreferenceColors() {
      const useSystemColors = document.getElementById('themeMode').value === 'system';
      const themeColor = document.getElementById('themeColor');
      const sidebarColor = document.getElementById('sidebarColor');
      if (useSystemColors) {
        themeColor.value = '#2d83b5';
        sidebarColor.value = '#0284c7';
      }
      themeColor.disabled = useSystemColors;
      sidebarColor.disabled = useSystemColors;
      applySelectedTheme();
    }

    function applySelectedTheme() {
      const mode = document.getElementById('themeMode').value;
      const useSystemColors = mode === 'system';
      const themeColor = useSystemColors ? '#0ea5e9' : document.getElementById('themeColor').value;
      const sidebarColor = useSystemColors ? '#0284c7' : document.getElementById('sidebarColor').value;
      if (mode === 'dark' || mode === 'light') {
        document.documentElement.dataset.theme = mode;
      } else {
        delete document.documentElement.dataset.theme;
      }
      document.documentElement.style.setProperty('--primary-color', themeColor);
      document.documentElement.style.setProperty('--primary-hover', useSystemColors ? '#0284c7' : themeColor);
      document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
    }

    document.getElementById('themeMode').addEventListener('change', applySystemPreferenceColors);
    document.getElementById('themeColor').addEventListener('input', applySelectedTheme);
    document.getElementById('sidebarColor').addEventListener('input', applySelectedTheme);

    async function loadCatalogCategories() {
      try {
        const result = await clinicRequest('catalog-categories');
        const categories = result.data;
        document.getElementById('catalogCategoryBody').innerHTML = categories.length
          ? categories.map(category => `<tr>
              <td>${category.type === 'service' ? 'Services' : 'Inventory'}</td>
              <td><input class="filter-select" id="catalog-category-${Number(category.id)}" value="${clinicEscape(category.name)}" maxlength="100" aria-label="Category name"></td>
              <td>
                <button class="btn-secondary" type="button" data-save-category="${Number(category.id)}" ${canManageSettings ? '' : 'disabled'}>Rename</button>
                <button class="btn-secondary" type="button" data-delete-category="${Number(category.id)}" ${canManageSettings ? '' : 'disabled'}>Delete</button>
              </td>
            </tr>`).join('')
          : '<tr><td colspan="3">No categories yet.</td></tr>';
        document.querySelectorAll('[data-save-category]').forEach(button => button.addEventListener('click', async () => {
          button.disabled = true;
          try {
            await clinicRequest('catalog-categories', 'update', {
              id: Number(button.dataset.saveCategory),
              name: document.getElementById(`catalog-category-${button.dataset.saveCategory}`).value
            });
            settingsMessage.textContent = 'Category renamed.';
            await loadCatalogCategories();
            clinicNotifyCatalogCategoriesChanged();
          } catch (error) {
            settingsMessage.textContent = error.message;
            settingsMessage.classList.add('settings-error');
          } finally {
            button.disabled = !canManageSettings;
          }
        }));
        document.querySelectorAll('[data-delete-category]').forEach(button => button.addEventListener('click', async () => {
          if (!confirm('Delete this category? It must not contain any services or inventory items.')) return;
          button.disabled = true;
          try {
            await clinicRequest('catalog-categories', 'delete', { id: Number(button.dataset.deleteCategory) });
            settingsMessage.textContent = 'Category deleted.';
            await loadCatalogCategories();
            clinicNotifyCatalogCategoriesChanged();
          } catch (error) {
            settingsMessage.textContent = error.message;
            settingsMessage.classList.add('settings-error');
          } finally {
            button.disabled = !canManageSettings;
          }
        }));
        return categories;
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
        throw error;
      }
    }

    document.getElementById('categoryForm').addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const button = form.querySelector('button[type="submit"]');
      const nameInput = document.getElementById('newCategoryName');
      const categoryType = document.getElementById('categoryType').value;
      const categoryName = nameInput.value.trim();
      settingsMessage.classList.remove('settings-error');
      button.disabled = true;
      settingsMessage.textContent = 'Adding category…';
      try {
        const created = await clinicRequest('catalog-categories', 'create', {
          type: categoryType,
          name: categoryName
        });
        if (!Number.isInteger(Number(created.id)) || Number(created.id) < 1) {
          throw new Error('The category was saved, but the server did not confirm its new ID. Refresh the list to verify it.');
        }
        const categories = await loadCatalogCategories();
        const addedCategory = categories.find(category => Number(category.id) === Number(created.id));
        if (!addedCategory) {
          throw new Error('The category was saved, but it could not be found in the catalog list. Please refresh and try again.');
        }
        clinicNotifyCatalogCategoriesChanged();
        form.reset();
        settingsMessage.textContent = `${addedCategory.name} added to ${categoryType === 'service' ? 'Services' : 'Inventory'} categories. It is now available in category options.`;
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      } finally {
        button.disabled = !canManageSettings;
      }
    });

    async function loadServiceSettings() {
      try {
        const [result, categoryResult] = await Promise.all([clinicRequest('services'), clinicRequest('catalog-categories')]);
        const serviceCategories = [...new Set(categoryResult.data.map(category => category.name))].sort((a, b) => a.localeCompare(b));
        document.getElementById('serviceSettingsBody').innerHTML = result.data.length
          ? result.data.map(service => `<tr>
              <td>${clinicEscape(service.name)}</td>
              <td><select class="filter-select" id="service-category-${Number(service.id)}" aria-label="Category for ${clinicEscape(service.name)}">${serviceCategories.map(category => `<option value="${clinicEscape(category)}"${category === service.category ? ' selected' : ''}>${clinicEscape(category)}</option>`).join('')}</select></td>
              <td><input class="filter-select" id="service-price-${Number(service.id)}" type="number" min="0" max="99999999.99" step="0.01" value="${Number(service.price).toFixed(2)}" aria-label="Price for ${clinicEscape(service.name)}"></td>
              <td><button class="btn-secondary" type="button" data-save-service="${Number(service.id)}" ${canManageSettings ? '' : 'disabled'}>Save</button></td>
            </tr>`).join('')
          : '<tr><td colspan="4">No services have been added.</td></tr>';
        document.querySelectorAll('[data-save-service]').forEach(button => {
          button.addEventListener('click', async () => {
            button.disabled = true;
            try {
              await clinicRequest('services', 'settings-update', {
                id: Number(button.dataset.saveService),
                category: document.getElementById(`service-category-${button.dataset.saveService}`).value,
                price: document.getElementById(`service-price-${button.dataset.saveService}`).value
              });
              settingsMessage.textContent = 'Service category and price saved.';
              await loadServiceSettings();
            } catch (error) {
              settingsMessage.textContent = error.message;
              settingsMessage.classList.add('settings-error');
            } finally {
              button.disabled = false;
            }
          });
        });
      } catch (error) {
        settingsMessage.textContent = error.message;
        settingsMessage.classList.add('settings-error');
      }
    }

    clinicOnCatalogCategoriesChanged(() => {
      if (!document.getElementById('catalogCategorySettings').hidden) loadCatalogCategories();
      if (!document.getElementById('serviceSettings').hidden) loadServiceSettings();
    });

    async function loadAuditLogs() {
      if (auditLogsRequest !== null) return auditLogsRequest;
      auditLogsRequest = (async () => {
        try {
          const result = await clinicRequest('audit-logs');
          document.getElementById('auditLogBody').innerHTML = result.data.length
            ? result.data.map(log => `<tr>
                <td>${clinicEscape(formatAuditDate(log.created_at))}</td>
                <td>${clinicEscape(log.user_name)}</td>
                <td>${clinicEscape(formatAuditAction(log.action))}</td>
                <td>${clinicEscape(formatAuditDetails(log))}</td>
              </tr>`).join('')
            : '<tr><td colspan="4">No audit activity recorded yet.</td></tr>';
        } catch (error) {
          document.getElementById('auditLogBody').innerHTML = `<tr><td colspan="4">${clinicEscape(error.message)}</td></tr>`;
        } finally {
          auditLogsRequest = null;
        }
      })();
      return auditLogsRequest;
    }

    window.addEventListener('clinic:audit-history-updated', () => {
      if (canManageSettings && !document.getElementById('userPreferences').hidden) loadAuditLogs();
    });

    const clearAuditHistoryButton = document.getElementById('clearAuditHistory');
    if (clearAuditHistoryButton) {
      clearAuditHistoryButton.addEventListener('click', async () => {
        if (!confirm('Clear all recent audit history? This cannot be undone.')) return;
        clearAuditHistoryButton.disabled = true;
        settingsMessage.classList.remove('settings-error');
        settingsMessage.textContent = 'Clearing audit history…';
        try {
          await clinicRequest('audit-logs', 'clear', {});
          await loadAuditLogs();
          settingsMessage.textContent = '';
        } catch (error) {
          settingsMessage.textContent = error.message;
          settingsMessage.classList.add('settings-error');
        } finally {
          clearAuditHistoryButton.disabled = false;
        }
      });
    }

    function formatAuditDate(value) {
      const date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString();
    }

    function formatAuditAction(action) {
      const labels = {
        'clinic_settings.updated': 'Clinic details updated',
        'clinic_preferences.updated': 'System preferences updated',
        'service.pricing_updated': 'Service pricing updated',
        'catalog_category.created': 'Category added',
        'catalog_category.renamed': 'Category renamed',
        'catalog_category.deleted': 'Category deleted',
        'user.created': 'User account created',
        'user.updated': 'User account updated'
      };
      return labels[action] || String(action).replace(/[._-]+/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
    }

    function parseAuditDetails(rawDetails) {
      if (typeof rawDetails !== 'string') return rawDetails;
      try {
        return JSON.parse(rawDetails);
      } catch (error) {
        return null;
      }
    }

    function formatAuditDetails(log) {
      const details = parseAuditDetails(log.details);
      if (!details || typeof details !== 'object' || Array.isArray(details)) {
        return 'Details unavailable.';
      }
      const roleName = role => role === 'Administrator' || role === 'Admin' ? 'Administrator' : String(role);
      const money = value => `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

      if (log.action === 'catalog_category.created' || log.action === 'catalog_category.deleted') {
        return `${details.type === 'service' ? 'Service' : 'Inventory'} category: ${details.name || 'Unnamed'}.`;
      }
      if (log.action === 'catalog_category.renamed') {
        return `${details.type === 'service' ? 'Service' : 'Inventory'} category renamed from “${details.before || ''}” to “${details.after || ''}”.`;
      }
      if (log.action === 'service.pricing_updated') {
        const before = details.before || {};
        const after = details.after || {};
        const changes = [];
        if (before.category !== after.category) changes.push(`category: ${before.category || 'None'} → ${after.category || 'None'}`);
        if (Number(before.price) !== Number(after.price)) changes.push(`price: ${money(before.price)} → ${money(after.price)}`);
        return `${details.name || 'Service'}${changes.length ? ` — ${changes.join('; ')}` : ' updated'}.`;
      }
      if (log.action === 'user.created') {
        return `${details.username || 'New user'} added with ${roleName(details.role)} access.`;
      }
      if (log.action === 'user.updated') {
        const status = Number(details.active) === 1 ? 'active' : 'inactive';
        const password = details.password_reset ? ' Password was reset.' : '';
        return `${details.username || 'User account'} updated: ${roleName(details.role)}, ${status}.${password}`;
      }
      if (log.action === 'clinic_settings.updated') {
        const labels = {
          clinic_name: 'Clinic name',
          phone: 'Phone',
          email: 'Email',
          address: 'Address',
          opening_time: 'Opening time',
          closing_time: 'Closing time',
          appointment_interval_minutes: 'Appointment interval',
          tax_rate: 'Tax rate',
          default_minimum_quantity: 'Default low-stock quantity'
        };
        return Object.entries(details)
          .map(([key, value]) => `${labels[key] || key.replace(/_/g, ' ')}: ${value}`)
          .join('; ') + '.';
      }
      if (log.action === 'clinic_preferences.updated') {
        const before = details.before || {};
        const after = details.after || {};
        const labels = {
          theme_mode: 'Appearance',
          theme_color: 'Accent color',
          sidebar_color: 'Sidebar color',
          backup_frequency: 'Backup reminder',
          reports_default_period: 'Default report period',
          reports_export_format: 'Report export format',
          reorder_alerts: 'Reorder alerts',
          appointment_reminders: 'Appointment reminders',
          reminder_hours_before: 'Reminder lead time',
          expiration_alert_days: 'Expiration alert lead time'
        };
        const changes = [];
        for (const key of new Set([...Object.keys(before), ...Object.keys(after)])) {
          if (key === 'last_backup_at' || key === 'updated_at' || key === 'updated_by') continue;
          if (JSON.stringify(before[key]) === JSON.stringify(after[key])) continue;
          if (key === 'notifications') {
            const oldNotifications = before.notifications || {};
            const newNotifications = after.notifications || {};
            const names = {
              appointment_email: 'appointment email',
              appointment_sms: 'appointment SMS',
              low_stock_email: 'low-stock email',
              low_stock_sms: 'low-stock SMS',
              unpaid_email: 'unpaid-bill email',
              unpaid_sms: 'unpaid-bill SMS'
            };
            for (const [notificationKey, label] of Object.entries(names)) {
              if (oldNotifications[notificationKey] !== newNotifications[notificationKey]) {
                changes.push(`${label} ${newNotifications[notificationKey] ? 'enabled' : 'disabled'}`);
              }
            }
          } else if (key === 'theme_color' || key === 'sidebar_color') {
            changes.push(`${labels[key]} changed`);
          } else if (key === 'accepted_payment_methods') {
            changes.push(`Payment methods: ${(after[key] || []).join(', ') || 'None'}`);
          } else if (key === 'theme_mode') {
            const modes = { system: 'System default', light: 'Light mode', dark: 'Dark mode' };
            changes.push(`${labels[key]}: ${modes[after[key]] || after[key]}`);
          } else if (typeof after[key] === 'boolean') {
            changes.push(`${labels[key] || key.replace(/_/g, ' ')} ${after[key] ? 'enabled' : 'disabled'}`);
          } else if (after[key] !== undefined && after[key] !== '') {
            changes.push(`${labels[key] || key.replace(/_/g, ' ')}: ${after[key]}`);
          }
        }
        return changes.length ? changes.join('; ') + '.' : 'Preferences updated.';
      }

      return 'Settings updated.';
    }

    loadSettings();
  </script>
</body>
</html>
