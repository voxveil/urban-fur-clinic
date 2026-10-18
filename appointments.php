<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointments - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=36">
</head>
<body class="appointments-page">

  <button class="mobile-nav-toggle" id="mobileNavToggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="appointmentsSidebar"><i class="fa-solid fa-bars"></i></button>
  <div class="mobile-nav-backdrop" id="mobileNavBackdrop" aria-hidden="true"></div>

  <!-- Navigation Sidebar -->
  <aside class="sidebar" id="appointmentsSidebar">
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
      <div class="stat-card" onclick="filterStatus('Pending', this)">
        <div class="stat-icon" style="color: var(--warning); background: #fef3c7;"><i class="fa-regular fa-hourglass-half"></i></div>
        <div class="stat-info">
          <h3 id="pending-count">0</h3>
          <p>Pending Requests</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterStatus('Scheduled', this)">
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
      <div class="stat-card" onclick="filterStatus('No Show', this)">
        <div class="stat-icon" style="color: var(--danger); background: #fee2e2;"><i class="fa-solid fa-user-clock"></i></div>
        <div class="stat-info"><h3 id="no-show-count">0</h3><p>No Show</p></div>
      </div>
    </div>

    <section class="appointment-calendar-panel" id="appointmentCalendar" aria-labelledby="calendarHeading">
      <div class="appointment-calendar-heading">
        <div><p class="calendar-eyebrow">SCHEDULE PLANNER</p><h2 id="calendarHeading">Appointment Calendar</h2><p>Drag a grip, or tap it and choose a new date on touch screens.</p></div>
        <div class="calendar-toolbar">
          <div class="calendar-view-switch" role="group" aria-label="Appointment view"><button type="button" class="active" data-appointment-view="calendar">Calendar</button><button type="button" data-appointment-view="list">List</button></div>
          <button type="button" class="calendar-today" id="calendarToday">Today</button>
          <button type="button" class="calendar-arrow" id="calendarPrevious" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button>
          <h3 id="calendarMonthLabel"></h3>
          <button type="button" class="calendar-arrow" id="calendarNext" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
      </div>
      <div class="appointment-calendar-layout" id="calendarView">
        <div class="calendar-month-wrap"><div class="calendar-weekdays"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div><div class="appointment-calendar-grid" id="appointmentCalendarGrid"></div></div>
        <aside class="calendar-agenda"><p class="calendar-eyebrow">DAY AGENDA</p><h3 id="calendarAgendaDate"></h3><div id="calendarAgendaItems" class="calendar-agenda-items"></div></aside>
      </div>
      <div id="appointmentListView" hidden>
        <div class="table-controls">
          <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search pet, owner, or service..."></div>
          <select class="filter-select" id="appointmentStatusFilter" aria-label="Filter appointments by status"><option value="All">All statuses</option><option value="Pending">Pending</option><option value="Scheduled">Confirmed / Scheduled</option><option value="In Progress">In Progress</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option><option value="No Show">No Show</option></select>
          <input type="date" class="date-filter" id="dateFilter" aria-label="Filter by date">
        </div>
        <div class="table-container"><table><thead><tr><th>Pet Name</th><th>Owner Name</th><th>Service / Action</th><th>Date &amp; Time</th><th>Veterinarian</th><th>Status</th><th>Actions</th></tr></thead><tbody id="appointmentTableBody"></tbody></table></div>
      </div>
    </section>
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
            <fieldset class="service-checkbox-group full"><legend>Services</legend><div id="appointmentServiceOptions" class="service-checkboxes"></div></fieldset>
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

  <script src="assets/app.js?v=14"></script>
  <script>
    let appointments = [];
    let activePets = [];
    let activeServices = [];
    let activeStaff = [];
    let appointmentSettings = null;
    let appointmentClosures = [];
    let calendarMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    let selectedCalendarDate = calendarDateKey(new Date());
    let draggedAppointmentId = null;
    let pendingTouchRescheduleId = null;

    function calendarDateKey(date) {
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function setAppointmentView(view) {
      const showCalendar = view === 'calendar';
      document.getElementById('calendarView').hidden = !showCalendar;
      document.getElementById('appointmentListView').hidden = showCalendar;
      document.querySelectorAll('[data-appointment-view]').forEach(button => button.classList.toggle('active', button.dataset.appointmentView === view));
    }

    function renderCalendar() {
      document.getElementById('calendarMonthLabel').textContent = calendarMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
      document.getElementById('calendarAgendaDate').textContent = new Date(`${selectedCalendarDate}T00:00:00`).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
      const grid = document.getElementById('appointmentCalendarGrid');
      const first = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);
      const offset = (first.getDay() + 6) % 7;
      const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset);
      grid.innerHTML = Array.from({ length: 42 }, (_, index) => {
        const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index);
        const key = calendarDateKey(day);
        const dayAppointments = appointments.filter(item => item.raw_date === key).sort((a, b) => String(a.appointment_at).localeCompare(String(b.appointment_at)));
        const closure = appointmentClosures.find(item => item.start_date <= key && item.end_date >= key);
        const classes = [day.getMonth() !== calendarMonth.getMonth() ? 'outside-month' : '', key === selectedCalendarDate ? 'selected' : '', key === calendarDateKey(new Date()) ? 'today' : ''].filter(Boolean);
        const events = dayAppointments.slice(0, 3).map(item => {
          const time = String(item.appointment_at).slice(11, 16);
          const canMove = !['Completed', 'Cancelled', 'No Show'].includes(item.status);
          const dragHandle = canMove ? `<button type="button" class="calendar-drag-handle${pendingTouchRescheduleId === Number(item.id) ? ' reschedule-selected' : ''}" draggable="true" data-drag-appointment="${Number(item.id)}" aria-label="Move ${clinicEscape(item.pet_name)} appointment" title="Drag or tap to reschedule">⠿</button>` : '';
          return `<div class="calendar-event status-${String(item.status).toLowerCase().replaceAll(' ', '-')}" title="${clinicEscape(time)} · ${clinicEscape(item.pet_code)} · ${clinicEscape(item.pet_name)} · ${clinicEscape(item.service)}">${dragHandle}<button type="button" class="calendar-event-open" data-open-appointment="${Number(item.id)}"><time>${clinicEscape(time)}</time> ${clinicEscape(item.pet_code)} ${clinicEscape(item.pet_name)}</button></div>`;
        }).join('');
        const more = dayAppointments.length > 3 ? `<span class="calendar-more">+${dayAppointments.length - 3} more</span>` : '';
        const closureEvent = closure ? `<div class="calendar-closure-event" title="${clinicEscape(closure.reason || 'Clinic closed')}"><i class="fa-solid fa-lock"></i> Closed${closure.reason ? ` · ${clinicEscape(closure.reason)}` : ''}</div>` : '';
        return `<div class="calendar-day ${classes.join(' ')}${closure ? ' clinic-closed-day' : ''}" data-calendar-date="${key}" role="group" aria-label="${clinicEscape(day.toLocaleDateString(undefined, { month: 'long', day: 'numeric' }))}${closure ? `: Clinic closed${closure.reason ? `: ${clinicEscape(closure.reason)}` : ''}` : ''}"><button type="button" class="calendar-day-number" data-select-calendar-date="${key}">${day.getDate()}</button><div class="calendar-day-events">${closureEvent}${events}${more}</div></div>`;
      }).join('');

      const agenda = appointments.filter(item => item.raw_date === selectedCalendarDate).sort((a, b) => String(a.appointment_at).localeCompare(String(b.appointment_at)));
      const selectedClosure = appointmentClosures.find(item => item.start_date <= selectedCalendarDate && item.end_date >= selectedCalendarDate);
      const closureNotice = selectedClosure ? `<article class="calendar-closure-notice"><strong><i class="fa-solid fa-lock"></i> Clinic closed</strong><span>${clinicEscape(selectedClosure.reason || 'No closure reason provided.')}</span></article>` : '';
      document.getElementById('calendarAgendaItems').innerHTML = closureNotice + (agenda.length ? agenda.map(item => `<article class="calendar-agenda-item"><div class="agenda-time">${clinicEscape(String(item.appointment_at).slice(11, 16))}</div><div class="agenda-details"><strong>${clinicEscape(item.pet_code)} · ${clinicEscape(item.pet_name)} · ${clinicEscape(item.service)}</strong><small>${clinicEscape(item.owner_name)} · ${clinicEscape(item.vet)}</small><span class="status-badge status-${String(item.status).toLowerCase().replaceAll(' ', '-')}">${clinicEscape(item.status === 'Scheduled' ? 'Confirmed' : item.status)}</span></div><button type="button" class="agenda-open" data-open-appointment="${Number(item.id)}" aria-label="Open appointment"><i class="fa-solid fa-arrow-right"></i></button></article>`).join('') : '<p class="calendar-empty">No appointments on this date.</p>');
    }

    async function rescheduleAppointment(id, date) {
      const item = appointments.find(appointment => Number(appointment.id) === Number(id));
      if (!item || ['Completed', 'Cancelled'].includes(item.status)) return;
      if (item.raw_date === date) return;
      if (appointmentClosures.some(closure => closure.start_date <= date && closure.end_date >= date)) {
        clinicShowError(new Error('The clinic is closed on that date.'));
        return;
      }
      const time = String(item.appointment_at).slice(11, 16);
      try {
        await clinicRequest('appointments', 'reschedule', { id: item.id, appointment_at: `${date}T${time}` });
        selectedCalendarDate = date;
        const selected = new Date(`${date}T00:00:00`);
        calendarMonth = new Date(selected.getFullYear(), selected.getMonth(), 1);
        await loadAppointments();
      } catch (error) { clinicShowError(error); }
    }

    function changeCalendarMonth(offset) {
      calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + offset, 1);
      if (selectedCalendarDate.slice(0, 7) !== calendarDateKey(calendarMonth).slice(0, 7)) selectedCalendarDate = calendarDateKey(calendarMonth);
      renderCalendar();
    }
    document.getElementById('calendarPrevious').addEventListener('click', () => changeCalendarMonth(-1));
    document.getElementById('calendarNext').addEventListener('click', () => changeCalendarMonth(1));
    document.getElementById('calendarToday').addEventListener('click', () => { const today = new Date(); calendarMonth = new Date(today.getFullYear(), today.getMonth(), 1); selectedCalendarDate = calendarDateKey(today); renderCalendar(); });
    document.querySelectorAll('[data-appointment-view]').forEach(button => button.addEventListener('click', () => setAppointmentView(button.dataset.appointmentView)));
    document.getElementById('appointmentCalendarGrid').addEventListener('click', event => {
      const moveHandle = event.target.closest('[data-drag-appointment]');
      if (moveHandle) {
        pendingTouchRescheduleId = Number(moveHandle.dataset.dragAppointment);
        renderCalendar();
        return;
      }
      const dayButton = event.target.closest('[data-select-calendar-date]');
      if (dayButton) {
        if (pendingTouchRescheduleId) {
          const appointmentId = pendingTouchRescheduleId;
          pendingTouchRescheduleId = null;
          rescheduleAppointment(appointmentId, dayButton.dataset.selectCalendarDate);
          return;
        }
        selectedCalendarDate = dayButton.dataset.selectCalendarDate; renderCalendar(); return;
      }
      const openButton = event.target.closest('[data-open-appointment]');
      if (openButton) {
        const item = appointments.find(appointment => Number(appointment.id) === Number(openButton.dataset.openAppointment));
        if (item) { setAppointmentView('list'); document.getElementById('dateFilter').value = item.raw_date; document.getElementById('appointmentStatusFilter').value = 'All'; document.getElementById('searchInput').value = ''; renderTable(); document.getElementById(`appointment-row-${item.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      }
    });
    document.getElementById('appointmentCalendarGrid').addEventListener('dragstart', event => {
      const handle = event.target.closest('[data-drag-appointment]');
      if (!handle) return;
      draggedAppointmentId = Number(handle.dataset.dragAppointment);
      event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(draggedAppointmentId));
    });
    document.getElementById('appointmentCalendarGrid').addEventListener('dragover', event => {
      const day = event.target.closest('[data-calendar-date]');
      if (day && draggedAppointmentId) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; }
    });
    document.getElementById('appointmentCalendarGrid').addEventListener('drop', event => {
      const day = event.target.closest('[data-calendar-date]');
      if (!day) return;
      event.preventDefault();
      const id = Number(event.dataTransfer.getData('text/plain')) || draggedAppointmentId;
      draggedAppointmentId = null;
      if (id) rescheduleAppointment(id, day.dataset.calendarDate);
    });
    document.getElementById('appointmentCalendarGrid').addEventListener('dragend', () => { draggedAppointmentId = null; });

    function updateCounts() {
      document.getElementById('total-count').innerText = appointments.length;
      document.getElementById('pending-count').innerText = appointments.filter(a => a.status === 'Pending').length;
      document.getElementById('scheduled-count').innerText = appointments.filter(a => a.status === 'Scheduled').length;
      document.getElementById('progress-count').innerText = appointments.filter(a => a.status === 'In Progress').length;
      document.getElementById('completed-count').innerText = appointments.filter(a => a.status === 'Completed').length;
      document.getElementById('no-show-count').innerText = appointments.filter(a => a.status === 'No Show').length;
    }

    function renderTable() {
      const tbody = document.getElementById('appointmentTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const dateVal = document.getElementById('dateFilter').value;

      tbody.innerHTML = '';

      const filtered = appointments.filter(a => {
        const selectedStatus = document.getElementById('appointmentStatusFilter').value;
        const matchesStatus = selectedStatus === 'All' || a.status === selectedStatus;
        const matchesSearch = [a.pet_code, a.pet_name, a.owner_name, a.service, a.vet].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesDate = !dateVal || a.raw_date === dateVal;

        return matchesStatus && matchesSearch && matchesDate;
      });

      filtered.forEach(a => {
        let badgeClass = 'status-scheduled';
        if (a.status === 'Pending') badgeClass = 'status-pending';
        if (a.status === 'In Progress') badgeClass = 'status-in-progress';
        if (a.status === 'Completed') badgeClass = 'status-completed';
        if (a.status === 'Cancelled') badgeClass = 'status-cancelled';
        if (a.status === 'No Show') badgeClass = 'status-no-show';

        const row = document.createElement('tr');
        row.id = 'appointment-row-' + a.id;
        row.innerHTML = `
          <td><small class="pet-identifier">${clinicEscape(a.pet_code)}</small><br>${clinicEscape(a.pet_name)}</td>
          <td>${clinicEscape(a.owner_name)}</td>
          <td>${clinicEscape(a.service)}</td>
          <td>${clinicEscape(a.date_time)}</td>
          <td>${clinicEscape(a.vet)}</td>
          <td><span class="status-badge ${badgeClass}">${clinicEscape(a.status)}</span></td>
          <td>
            <div class="action-btn-group">
              ${!['Completed', 'Cancelled', 'No Show'].includes(a.status) ? `<button class="action-btn" onclick="toggleStatus(${a.id})" title="Advance Status"><i class="fa-solid fa-arrow-right"></i></button>` : ''}
              ${['Scheduled', 'In Progress'].includes(a.status) && new Date(String(a.appointment_at).replace(' ', 'T')).getTime() < Date.now() ? `<button class="action-btn appointment-no-show-btn" onclick="markNoShow(${a.id})" title="Mark as No Show" aria-label="Mark ${clinicEscape(a.pet_name)} as no show"><i class="fa-solid fa-user-clock"></i></button>` : ''}
              ${!['Completed', 'Cancelled', 'No Show'].includes(a.status) ? `<button class="action-btn appointment-cancel-btn" onclick="cancelAppointment(${a.id})" title="Mark as Cancelled"><i class="fa-solid fa-calendar-xmark"></i></button>` : ''}
              <button class="action-btn" onclick="deleteAppointment(${a.id})" title="Delete Appointment"><i class="fa-solid fa-trash"></i></button>
            </div>
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
      setAppointmentView('list');
      const statusFilter = document.getElementById('appointmentStatusFilter');
      statusFilter.value = ['All', 'Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'No Show'].includes(status) ? status : 'All';
      document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active'));
      if (cardElement) cardElement.classList.add('active');
      renderTable();
    }

    async function loadAppointments() {
      try {
        const [appointmentResult, petsResult, servicesResult, staffResult, settingsResult, closureResult] = await Promise.all([
          clinicRequest('appointments'), clinicRequest('pets-options'), clinicRequest('services'), clinicRequest('staff'), clinicRequest('settings'), clinicRequest('appointment-closures')
        ]);
        appointments = appointmentResult.data;
        activePets = petsResult.data;
        activeServices = servicesResult.data.filter(service => Number(service.active) === 1);
        activeStaff = staffResult.data.filter(member => member.status === 'Active');
        appointmentSettings = settingsResult.data;
        appointmentClosures = closureResult.data;
        const appointmentDate = document.getElementById('appDate');
        appointmentDate.min = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        const appointmentTime = document.getElementById('appTime');
        appointmentTime.min = appointmentSettings.opening_time;
        appointmentTime.max = appointmentSettings.closing_time;
        appointmentTime.step = String(appointmentSettings.appointment_interval_minutes * 60);
        document.getElementById('appointmentHours').textContent = `Hours: ${appointmentSettings.opening_time}–${appointmentSettings.closing_time}; every ${appointmentSettings.appointment_interval_minutes} minutes`;
        document.getElementById('petName').innerHTML = '<option value="">Choose patient</option>' + activePets.map(pet => `<option value="${pet.id}">${clinicEscape(pet.name)} — ${clinicEscape(pet.owner)}</option>`).join('');
        document.getElementById('appointmentServiceOptions').innerHTML = activeServices.map(service => `<label class="service-checkbox"><input type="checkbox" name="service_ids" value="${Number(service.id)}"><span>${clinicEscape(service.name)}</span></label>`).join('') || '<p class="muted">No services available.</p>';
        document.getElementById('vetName').innerHTML = '<option value="">Unassigned</option>' + activeStaff.map(member => `<option value="${member.id}">${clinicEscape(member.name)}</option>`).join('');
        renderTable();
        renderCalendar();
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
      if (!item || ['Completed', 'Cancelled', 'No Show'].includes(item.status)) return;
      if (!confirm(`Mark ${item.pet_name}'s appointment as cancelled?`)) return;
      try {
        await clinicRequest('appointments', 'update', { id: item.id, status: 'Cancelled' });
        await loadAppointments();
      } catch (error) { clinicShowError(error); }
    }

    async function markNoShow(id) {
      const item = appointments.find(appointment => Number(appointment.id) === Number(id));
      if (!item || new Date(String(item.appointment_at).replace(' ', 'T')).getTime() >= Date.now()) return;
      if (!confirm(`Mark ${item.pet_name}'s appointment as No Show?`)) return;
      try { await clinicRequest('appointments', 'update', { id: item.id, status: 'No Show' }); await loadAppointments(); }
      catch (error) { clinicShowError(error); }
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('dateFilter').addEventListener('change', renderTable);
    document.getElementById('appointmentStatusFilter').addEventListener('change', event => {
      document.querySelectorAll('.stats-row .stat-card').forEach(card => card.classList.remove('active'));
      const activeCard = {
        All: document.getElementById('total-count'),
        Pending: document.getElementById('pending-count'),
        Scheduled: document.getElementById('scheduled-count'),
        'In Progress': document.getElementById('progress-count'),
        Completed: document.getElementById('completed-count'),
        'No Show': document.getElementById('no-show-count')
      }[event.target.value];
      if (activeCard) activeCard.closest('.stat-card').classList.add('active');
      renderTable();
    });

    const modal = document.getElementById('addModal');
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const closeMobileNav = () => {
      document.body.classList.remove('mobile-nav-open');
      mobileNavToggle.setAttribute('aria-expanded', 'false');
      mobileNavToggle.setAttribute('aria-label', 'Open navigation');
    };
    mobileNavToggle.addEventListener('click', () => {
      const isOpen = document.body.classList.toggle('mobile-nav-open');
      mobileNavToggle.setAttribute('aria-expanded', String(isOpen));
      mobileNavToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });
    document.getElementById('mobileNavBackdrop').addEventListener('click', closeMobileNav);
    document.querySelectorAll('#appointmentsSidebar .nav-item a').forEach(link => link.addEventListener('click', closeMobileNav));
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

      const serviceIds = Array.from(this.querySelectorAll('input[name="service_ids"]:checked'), input => Number(input.value));
      if (serviceIds.length === 0) {
        clinicShowError(new Error('Select at least one service.'));
        return;
      }

      const values = {
        pet_id: document.getElementById('petName').value,
        service_ids: serviceIds,
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
