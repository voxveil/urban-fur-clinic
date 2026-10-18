<?php
require_once __DIR__ . '/auth.php';
$isAdministrator = in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=17">
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
    </div>

    <p class="settings-message" id="settingsMessage" role="status" aria-live="polite">Loading clinic settings…</p>

    <section class="settings-grid" aria-label="Clinic settings">
      <a class="settings-option" href="#generalSettings" data-panel="generalSettings" aria-controls="generalSettings" aria-expanded="true">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-gear"></i></span>
        <span class="settings-option-copy"><strong>Clinic Details</strong><small>Name and contact information</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#appointmentSettings" data-panel="appointmentSettings" aria-controls="appointmentSettings" aria-expanded="false">
        <span class="settings-option-icon settings-purple"><i class="fa-regular fa-calendar-check"></i></span>
        <span class="settings-option-copy"><strong>Appointments</strong><small>Clinic hours and time slots</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#billingSettings" data-panel="billingSettings" aria-controls="billingSettings" aria-expanded="false">
        <span class="settings-option-icon settings-green"><i class="fa-solid fa-file-invoice-dollar"></i></span>
        <span class="settings-option-copy"><strong>Billing</strong><small>Tax rate and accepted payment methods</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#inventorySettings" data-panel="inventorySettings" aria-controls="inventorySettings" aria-expanded="false">
        <span class="settings-option-icon settings-amber"><i class="fa-solid fa-boxes-stacked"></i></span>
        <span class="settings-option-copy"><strong>Inventory</strong><small>Reorder threshold, suppliers, and expiration alerts</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#serviceSettings" data-panel="serviceSettings" aria-controls="serviceSettings" aria-expanded="false">
        <span class="settings-option-icon settings-green"><i class="fa-solid fa-hand-holding-medical"></i></span>
        <span class="settings-option-copy"><strong>Services & Pricing</strong><small>Update service categories and prices</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#reportSettings" data-panel="reportSettings" aria-controls="reportSettings" aria-expanded="false">
        <span class="settings-option-icon settings-purple"><i class="fa-solid fa-chart-line"></i></span>
        <span class="settings-option-copy"><strong>Reports</strong><small>Default report period and export format</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#notificationSettings" data-panel="notificationSettings" aria-controls="notificationSettings" aria-expanded="false">
        <span class="settings-option-icon settings-amber"><i class="fa-regular fa-bell"></i></span>
        <span class="settings-option-copy"><strong>Notifications</strong><small>Email and SMS alert preferences</small></span>
        <i class="fa-solid fa-chevron-right settings-chevron"></i>
      </a>
      <a class="settings-option" href="#userPreferences" data-panel="userPreferences" aria-controls="userPreferences" aria-expanded="false">
        <span class="settings-option-icon settings-blue"><i class="fa-solid fa-user-gear"></i></span>
        <span class="settings-option-copy"><strong>User & System Preferences</strong><small>Theme, backup schedule, and audit history</small></span>
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

    <section class="settings-editor" id="generalSettings">
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
    </section>

    <section class="settings-editor" id="billingSettings" hidden>
      <h2>Billing</h2>
      <p>POS tax is configurable here; set the rate to 0% to keep tax excluded. Payment methods selected here are available in checkout.</p>
      <form id="billingSettingsForm" class="settings-form">
        <div class="form-group"><label for="taxRate">Tax rate (%)</label><input id="taxRate" type="number" min="0" max="100" step="0.01" required></div>
        <fieldset class="settings-fieldset"><legend>Accepted payment methods</legend>
          <label><input type="checkbox" name="paymentMethod" value="Cash"> Cash</label>
          <label><input type="checkbox" name="paymentMethod" value="Card"> Card</label>
          <label><input type="checkbox" name="paymentMethod" value="GCash"> GCash</label>
          <label><input type="checkbox" name="paymentMethod" value="Pay Later"> Pay Later</label>
        </fieldset>
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
      <h2>Service Categories & Pricing</h2>
      <p>Service category and price changes apply to the service catalog and point of sale.</p>
      <div class="table-container"><table><thead><tr><th>Service</th><th>Category</th><th>Price (₱)</th><th>Action</th></tr></thead><tbody id="serviceSettingsBody"><tr><td colspan="4">Open this panel to load services.</td></tr></tbody></table></div>
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
      <p>Save which notification channels staff want enabled. Email/SMS delivery requires a configured messaging provider.</p>
      <form id="notificationSettingsForm" class="settings-form">
      <fieldset class="settings-fieldset"><legend>Appointments</legend><label><input type="checkbox" id="appointmentEmail"> Email</label><label><input type="checkbox" id="appointmentSms"> SMS</label></fieldset>
      <fieldset class="settings-fieldset"><legend>Low stock</legend><label><input type="checkbox" id="lowStockEmail"> Email</label><label><input type="checkbox" id="lowStockSms"> SMS</label></fieldset>
      <fieldset class="settings-fieldset"><legend>Unpaid bills</legend><label><input type="checkbox" id="unpaidEmail"> Email</label><label><input type="checkbox" id="unpaidSms"> SMS</label></fieldset>
      <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save notification preferences</button>
      </form>
    </section>

    <section class="settings-editor" id="userPreferences" hidden>
      <h2>User & System Preferences</h2>
      <form id="userPreferencesForm" class="settings-form">
      <div class="form-grid">
        <div class="form-group"><label for="themeColor">Theme color</label><input id="themeColor" type="color"></div>
        <div class="form-group"><label for="backupFrequency">Backup frequency</label><select id="backupFrequency"><option value="disabled">Disabled</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
      </div>
      <button class="btn-primary" type="submit" <?= $isAdministrator ? '' : 'disabled' ?>>Save system preferences</button>
      </form>
      <h3>Role access</h3>
      <p>Administrator: full access. Vet: clinical records and appointments. Receptionist: client, patient, and appointment workflows. Cashier: billing, inventory, and reports. User accounts can be managed below.</p>
      <h3>Recent audit history</h3>
      <div class="table-container"><table><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody id="auditLogBody"><tr><td colspan="4">Open this panel to load audit history.</td></tr></tbody></table></div>
    </section>

    <div class="settings-help">
      <span class="settings-help-icon"><i class="fa-solid fa-circle-info"></i></span>
      <div><strong>Settings are saved to the clinic database.</strong><p>Only an administrator can change these preferences.</p></div>
    </div>
  </main>

  <script src="assets/app.js?v=6"></script>
  <script>
    const settingsMessage = document.getElementById('settingsMessage');
    const settingsPanels = [...document.querySelectorAll('.settings-editor')];
    let currentSettings = null;
    const canManageSettings = <?= $isAdministrator ? 'true' : 'false' ?>;

    function showSettingsPanel(panelId) {
      let selectedPanel = null;
      settingsPanels.forEach(panel => {
        panel.hidden = panel.id !== panelId;
        if (panel.id === panelId) selectedPanel = panel;
      });
      document.querySelectorAll('.settings-option').forEach(button => {
        button.setAttribute('aria-expanded', String(button.dataset.panel === panelId));
      });
      if (selectedPanel) selectedPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
      document.querySelectorAll('input[name="paymentMethod"]').forEach(input => {
        input.checked = settings.accepted_payment_methods.includes(input.value);
      });
      document.getElementById('reorderAlerts').checked = Boolean(settings.reorder_alerts);
      document.getElementById('expirationAlertDays').value = settings.expiration_alert_days ?? 30;
      document.getElementById('supplierInfo').value = settings.supplier_info || '';
      document.getElementById('reportDefaultPeriod').value = settings.reports_default_period || 'monthly';
      document.getElementById('reportExportFormat').value = settings.reports_export_format || 'csv';
      document.getElementById('themeColor').value = settings.theme_color || '#2d83b5';
      document.getElementById('backupFrequency').value = settings.backup_frequency || 'weekly';
      const notifications = settings.notifications || {};
      document.getElementById('appointmentEmail').checked = Boolean(notifications.appointment_email);
      document.getElementById('appointmentSms').checked = Boolean(notifications.appointment_sms);
      document.getElementById('lowStockEmail').checked = Boolean(notifications.low_stock_email);
      document.getElementById('lowStockSms').checked = Boolean(notifications.low_stock_sms);
      document.getElementById('unpaidEmail').checked = Boolean(notifications.unpaid_email);
      document.getElementById('unpaidSms').checked = Boolean(notifications.unpaid_sms);
      settingsMessage.textContent = 'Settings loaded from the clinic database.';
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

    document.querySelectorAll('a.settings-option[data-panel]').forEach(option => {
      option.setAttribute('aria-expanded', String(option.dataset.panel === 'generalSettings'));
      option.addEventListener('click', async () => {
        const panelId = option.dataset.panel;
        showSettingsPanel(panelId);
        if (panelId === 'serviceSettings') await loadServiceSettings();
        if (panelId === 'userPreferences' && canManageSettings) await loadAuditLogs();
      });
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
      const paymentMethods = [...document.querySelectorAll('input[name="paymentMethod"]:checked')].map(input => input.value);
      if (paymentMethods.length === 0) {
        settingsMessage.textContent = 'Select at least one accepted payment method.';
        settingsMessage.classList.add('settings-error');
        return;
      }
      await saveSettings(event.currentTarget, { tax_rate: document.getElementById('taxRate').value }, {
        accepted_payment_methods: paymentMethods
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
        backup_frequency: document.getElementById('backupFrequency').value
      });
    });

    async function loadServiceSettings() {
      try {
        const result = await clinicRequest('services');
        document.getElementById('serviceSettingsBody').innerHTML = result.data.length
          ? result.data.map(service => `<tr>
              <td>${clinicEscape(service.name)}</td>
              <td><input class="filter-select" id="service-category-${Number(service.id)}" value="${clinicEscape(service.category)}" maxlength="100" aria-label="Category for ${clinicEscape(service.name)}"></td>
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

    async function loadAuditLogs() {
      try {
        const result = await clinicRequest('audit-logs');
        document.getElementById('auditLogBody').innerHTML = result.data.length
          ? result.data.map(log => {
              let details = log.details;
              try { details = JSON.stringify(JSON.parse(log.details)); } catch (error) { /* Keep unreadable records visible as stored. */ }
              return `<tr><td>${clinicEscape(log.created_at)}</td><td>${clinicEscape(log.user_name)}</td><td>${clinicEscape(log.action)}</td><td>${clinicEscape(details)}</td></tr>`;
            }).join('')
          : '<tr><td colspan="4">No audit activity recorded yet.</td></tr>';
      } catch (error) {
        document.getElementById('auditLogBody').innerHTML = `<tr><td colspan="4">${clinicEscape(error.message)}</td></tr>`;
      }
    }

    loadSettings();
  </script>
</body>
</html>
