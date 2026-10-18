<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointments - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=15">
</head>
<body>

  <!-- Navigation Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
    
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item active"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
  </aside>

  <!-- Main View -->
  <main class="main-content">
    
    <div class="page-header">
      <div class="page-title">
        <h1>Appointments</h1>
        <p>Schedule and manage patient appointments</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> New Appointment</button>
    </div>

    <!-- Quick Status Filter Cards -->
    <div class="stats-row">
      <div class="stat-card active" onclick="filterStatus('All', this)">
        <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
        <div class="stat-info">
          <h3 id="total-count">0</h3>
          <p>Total Appointments</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--info); background: #e0f2fe;"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
          <h3 id="scheduled-count">0</h3>
          <p>Scheduled</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterStatus('In Progress', this)">
        <div class="stat-icon" style="color: var(--warning); background: #fef3c7;"><i class="fa-solid fa-spinner"></i></div>
        <div class="stat-info">
          <h3 id="progress-count">0</h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterStatus('Completed', this)">
        <div class="stat-icon" style="color: var(--success); background: #dcfce7;"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
          <h3 id="completed-count">0</h3>
          <p>Completed</p>
        </div>
      </div>
    </div>

    <!-- Filter Bar Controls -->
    <div class="table-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search pet, owner, or service...">
      </div>
      <select class="filter-select" id="appointmentStatusFilter" aria-label="Filter appointments by status">
        <option value="All">All</option>
        <option value="In Progress">In Progress</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <input type="date" class="date-filter" id="dateFilter">
    </div>

    <!-- Appointments Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Pet Name</th>
            <th>Owner Name</th>
            <th>Service Type</th>
            <th>Date & Time</th>
            <th>Veterinarian</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="appointmentTableBody">
          <!-- Rows will be dynamically loaded by JavaScript -->
        </tbody>
      </table>
    </div>
  </main>

  <!-- New Appointment Modal Form -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Schedule New Appointment</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="appointmentForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Pet Name</label>
            <select id="petName" required></select>
          </div>
          <div class="form-group">
            <label>Service Type</label>
            <select id="serviceType"></select>
          </div>
          <div class="form-group">
            <label>Assign Veterinarian</label>
            <select id="vetName"></select>
          </div>
          <div class="form-group">
            <label>Date</label>
            <input type="date" id="appDate" required>
          </div>
          <div class="form-group">
            <label>Time</label>
            <input type="time" id="appTime" required>
            <small id="appointmentHours"></small>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Book Appointment</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    let appointments = [];
    let activePets = [];
    let activeServices = [];
    let activeStaff = [];
    let appointmentSettings = null;
    function updateCounts() {
      document.getElementById('total-count').innerText = appointments.length;
      document.getElementById('scheduled-count').innerText = appointments.filter(a => a.status === 'Scheduled').length;
      document.getElementById('progress-count').innerText = appointments.filter(a => a.status === 'In Progress').length;
      document.getElementById('completed-count').innerText = appointments.filter(a => a.status === 'Completed').length;
    }

    function renderTable() {
      const tbody = document.getElementById('appointmentTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const dateVal = document.getElementById('dateFilter').value;

      tbody.innerHTML = '';

      const filtered = appointments.filter(a => {
        const selectedStatus = document.getElementById('appointmentStatusFilter').value;
        const matchesStatus = selectedStatus === 'All' || a.status === selectedStatus;
        const matchesSearch = [a.pet_name, a.owner_name, a.service, a.vet].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesDate = !dateVal || a.rawDate === dateVal;

        return matchesStatus && matchesSearch && matchesDate;
      });

      filtered.forEach(a => {
        let badgeClass = 'status-scheduled';
        if (a.status === 'In Progress') badgeClass = 'status-in-progress';
        if (a.status === 'Completed') badgeClass = 'status-completed';
        if (a.status === 'Cancelled') badgeClass = 'status-cancelled';

        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${clinicEscape(a.pet_name)}</td>
          <td>${clinicEscape(a.owner_name)}</td>
          <td>${clinicEscape(a.service)}</td>
          <td>${clinicEscape(a.date_time)}</td>
          <td>${clinicEscape(a.vet)}</td>
          <td><span class="status-badge ${badgeClass}">${clinicEscape(a.status)}</span></td>
          <td>
            ${!['Completed', 'Cancelled'].includes(a.status) ? `<button class="action-btn" onclick="toggleStatus(${a.id})" title="Advance Status"><i class="fa-solid fa-rotate"></i></button>` : ''}
            ${!['Completed', 'Cancelled'].includes(a.status) ? `<button class="action-btn appointment-cancel-btn" onclick="cancelAppointment(${a.id})" title="Mark as Cancelled"><i class="fa-solid fa-calendar-xmark"></i></button>` : ''}
            <button class="action-btn" onclick="deleteAppointment(${a.id})" title="Delete Appointment"><i class="fa-solid fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No appointments yet.</td></tr>';
      }

      updateCounts();
    }

    function filterStatus(status, cardElement) {
      const statusFilter = document.getElementById('appointmentStatusFilter');
      statusFilter.value = ['All', 'In Progress', 'Completed', 'Cancelled'].includes(status) ? status : 'All';
      document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active'));
      if (cardElement) cardElement.classList.add('active');
      renderTable();
    }

    async function loadAppointments() {
      try {
        const [appointmentResult, petsResult, servicesResult, staffResult, settingsResult] = await Promise.all([
          clinicRequest('appointments'), clinicRequest('pets-options'), clinicRequest('services'), clinicRequest('staff'), clinicRequest('settings')
        ]);
        appointments = appointmentResult.data;
        activePets = petsResult.data;
        activeServices = servicesResult.data.filter(service => Number(service.active) === 1);
        activeStaff = staffResult.data.filter(member => member.status === 'Active');
        appointmentSettings = settingsResult.data;
        const appointmentTime = document.getElementById('appTime');
        appointmentTime.min = appointmentSettings.opening_time;
        appointmentTime.max = appointmentSettings.closing_time;
        appointmentTime.step = String(appointmentSettings.appointment_interval_minutes * 60);
        document.getElementById('appointmentHours').textContent = `Hours: ${appointmentSettings.opening_time}–${appointmentSettings.closing_time}; every ${appointmentSettings.appointment_interval_minutes} minutes`;
        document.getElementById('petName').innerHTML = '<option value="">Choose patient</option>' + activePets.map(pet => `<option value="${pet.id}">${clinicEscape(pet.name)} — ${clinicEscape(pet.owner)}</option>`).join('');
        document.getElementById('serviceType').innerHTML = '<option value="">No service selected</option>' + activeServices.map(service => `<option value="${service.id}">${clinicEscape(service.name)}</option>`).join('');
        document.getElementById('vetName').innerHTML = '<option value="">Unassigned</option>' + activeStaff.map(member => `<option value="${member.id}">${clinicEscape(member.name)}</option>`).join('');
        renderTable();
      } catch (error) { clinicShowError(error); }
    }

    async function toggleStatus(id) {
      const item = appointments.find(a => Number(a.id) === Number(id));
      if (item) {
        const sequence = ['Scheduled', 'In Progress', 'Completed'];
        const nextStatus = sequence[(sequence.indexOf(item.status) + 1) % sequence.length];
        try {
          await clinicRequest('appointments', 'update', { id: item.id, status: nextStatus });
          await loadAppointments();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function deleteAppointment(id) {
      if (confirm("Permanently delete this appointment record?")) {
        try {
          await clinicRequest('appointments', 'delete', { id });
          await loadAppointments();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function cancelAppointment(id) {
      const item = appointments.find(appointment => Number(appointment.id) === Number(id));
      if (!item || ['Completed', 'Cancelled'].includes(item.status)) return;
      if (!confirm(`Mark ${item.pet_name}'s appointment as cancelled?`)) return;
      try {
        await clinicRequest('appointments', 'update', { id: item.id, status: 'Cancelled' });
        await loadAppointments();
      } catch (error) { clinicShowError(error); }
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('dateFilter').addEventListener('change', renderTable);
    document.getElementById('appointmentStatusFilter').addEventListener('change', event => {
      document.querySelectorAll('.stats-row .stat-card').forEach(card => card.classList.remove('active'));
      const activeCard = {
        All: document.getElementById('total-count'),
        'In Progress': document.getElementById('progress-count'),
        Completed: document.getElementById('completed-count')
      }[event.target.value];
      if (activeCard) activeCard.closest('.stat-card').classList.add('active');
      renderTable();
    });

    const modal = document.getElementById('addModal');
    document.getElementById('openAddModal').addEventListener('click', () => modal.classList.add('active'));
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));

    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const rawDate = document.getElementById('appDate').value;
      const rawTime = document.getElementById('appTime').value;
      if (!appointmentSettings) {
        clinicShowError(new Error('Appointment settings are still loading. Please try again.'));
        return;
      }
      const selectedMinutes = Number(rawTime.slice(0, 2)) * 60 + Number(rawTime.slice(3, 5));
      const openingMinutes = Number(appointmentSettings.opening_time.slice(0, 2)) * 60 + Number(appointmentSettings.opening_time.slice(3, 5));
      const closingMinutes = Number(appointmentSettings.closing_time.slice(0, 2)) * 60 + Number(appointmentSettings.closing_time.slice(3, 5));
      if (selectedMinutes < openingMinutes || selectedMinutes + appointmentSettings.appointment_interval_minutes > closingMinutes
        || (selectedMinutes - openingMinutes) % appointmentSettings.appointment_interval_minutes !== 0) {
        clinicShowError(new Error('Choose a time during clinic hours that matches the appointment interval.'));
        return;
      }

      const values = {
        pet_id: document.getElementById('petName').value,
        service_id: document.getElementById('serviceType').value,
        staff_id: document.getElementById('vetName').value,
        appointment_at: `${rawDate}T${rawTime}`
      };
      clinicRequest('appointments', 'create', values).then(async () => {
        modal.classList.remove('active');
        this.reset();
        await loadAppointments();
      }).catch(clinicShowError);
    });

    loadAppointments();
  </script>
</body>
</html>