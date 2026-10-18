<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Management - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=15">
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
      <li class="nav-item active"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1>Staff Management</h1>
        <p>Manage veterinarians, veterinary technicians, and clinic personnel</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-user-plus"></i> Add Staff Member</button>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users-gear"></i></div>
        <div class="stat-info"><h3 id="total-staff-count">0</h3><p>Total Personnel</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--primary-color); background: #eff6ff;"><i class="fa-solid fa-user-doctor"></i></div>
        <div class="stat-info"><h3 id="vets-count">0</h3><p>Veterinarians</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--success); background: #dcfce7;"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info"><h3 id="active-staff-count">0</h3><p>Active Status</p></div>
      </div>
    </div>

    <div class="table-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search staff name, role, or email...">
      </div>
      <select class="filter-select" id="roleFilter">
        <option value="All">All Roles</option>
        <option value="Veterinarian">Veterinarian</option>
        <option value="Vet Tech">Vet Tech</option>
        <option value="Receptionist">Receptionist</option>
      </select>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Staff Name</th>
            <th>Role / Specialization</th>
            <th>Phone Number</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="staffTableBody"></tbody>
      </table>
    </div>
  </main>

  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Register Staff Member</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="staffForm">
        <div class="form-grid">
          <div class="form-group full">
            <label>Full Name</label>
            <input type="text" id="staffName" required placeholder="Enter full name">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select id="staffRole" required>
              <option value="Veterinarian">Veterinarian</option>
              <option value="Vet Tech">Vet Tech</option>
              <option value="Receptionist">Receptionist</option>
            </select>
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" id="staffPhone" required placeholder="Enter phone number">
          </div>
          <div class="form-group full">
            <label>Email Address</label>
            <input type="email" id="staffEmail" required placeholder="Enter email address">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Save Member</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    let staffMembers = [];
    let editingStaffId = null;

    function renderTable() {
      const tbody = document.getElementById('staffTableBody');
      const searchValue = document.getElementById('searchInput').value.trim().toLowerCase();
      const roleValue = document.getElementById('roleFilter').value;
      tbody.innerHTML = '';

      staffMembers
        .filter(s => (roleValue === 'All' || s.role === roleValue) &&
          [s.name, s.role, s.phone, s.email].some(value => String(value).toLowerCase().includes(searchValue)))
        .forEach(s => {
        tbody.innerHTML += `
          <tr>
            <td>${clinicEscape(s.name)}</td>
            <td><span class="category-badge">${clinicEscape(s.role)}</span></td>
            <td>${clinicEscape(s.phone)}</td>
            <td>${clinicEscape(s.email)}</td>
            <td><span class="status-badge ${s.status === 'Active' ? 'status-active' : 'status-inactive'}">${s.status}</span></td>
            <td>
              <button class="action-btn" onclick="editStaffMember(${s.id})" title="Edit staff member"><i class="fa-solid fa-pen"></i></button>
              <button class="action-btn" onclick="toggleStaffStatus(${s.id})" title="Toggle status"><i class="fa-solid fa-user-gear"></i></button>
              <button class="action-btn" onclick="deleteStaffMember(${s.id})" title="Delete staff member"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        `;
      });
      if (tbody.children.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No staff members yet.</td></tr>';
      }
      document.getElementById('total-staff-count').innerText = staffMembers.length;
      document.getElementById('vets-count').innerText = staffMembers.filter(s => s.role === 'Veterinarian').length;
      document.getElementById('active-staff-count').innerText = staffMembers.filter(s => s.status === 'Active').length;
    }

    async function loadStaff() {
      try {
        staffMembers = (await clinicRequest('staff')).data;
        renderTable();
      } catch (error) { clinicShowError(error); }
    }

    async function toggleStaffStatus(id) {
      const member = staffMembers.find(s => Number(s.id) === Number(id));
      if (member) {
        try {
          await clinicRequest('staff', 'status', { id: member.id, status: member.status === 'Active' ? 'Inactive' : 'Active' });
          await loadStaff();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function deleteStaffMember(id) {
      if (confirm('Delete this staff member?')) {
        try {
          await clinicRequest('staff', 'delete', { id });
          await loadStaff();
        } catch (error) { clinicShowError(error); }
      }
    }

    function editStaffMember(id) {
      const member = staffMembers.find(s => Number(s.id) === Number(id));
      if (!member) return;
      editingStaffId = member.id;
      document.getElementById('staffName').value = member.name;
      document.getElementById('staffRole').value = member.role;
      document.getElementById('staffPhone').value = member.phone;
      document.getElementById('staffEmail').value = member.email;
      document.querySelector('#staffForm button[type="submit"]').textContent = 'Save Changes';
      staffModal.classList.add('active');
    }

    const staffModal = document.getElementById('addModal');
    document.getElementById('openAddModal').addEventListener('click', () => {
      editingStaffId = null;
      document.getElementById('staffForm').reset();
      document.querySelector('#staffForm button[type="submit"]').textContent = 'Save Member';
      staffModal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => staffModal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => staffModal.classList.remove('active'));
    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('roleFilter').addEventListener('change', renderTable);

    document.getElementById('staffForm').addEventListener('submit', function(event) {
      event.preventDefault();
      const values = {
        name: document.getElementById('staffName').value.trim(),
        role: document.getElementById('staffRole').value,
        phone: document.getElementById('staffPhone').value.trim(),
        email: document.getElementById('staffEmail').value.trim()
      };
      const action = editingStaffId ? 'update' : 'create';
      if (editingStaffId) {
        const current = staffMembers.find(s => Number(s.id) === Number(editingStaffId));
        values.id = editingStaffId;
        values.status = current.status;
      }
      clinicRequest('staff', action, values).then(async () => {
        this.reset();
        editingStaffId = null;
        document.querySelector('#staffForm button[type="submit"]').textContent = 'Save Member';
        staffModal.classList.remove('active');
        await loadStaff();
      }).catch(clinicShowError);
    });

    loadStaff();
  </script>
</body>
</html>