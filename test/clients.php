<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clients - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=18">
</head>
<body>

  <!-- Navigation Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
   
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item active"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <!-- Main Workspace -->
  <main class="main-content">
    
    <div class="page-header">
      <div class="page-title">
        <h1>Client Management</h1>
        <p>View, search, and manage registered pet owners</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-user-plus"></i> Add New Client</button>
    </div>

    <!-- Overview Metrics Row -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
          <h3 id="total-clients-count">0</h3>
          <p>Total Clients</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--success); background: #dcfce7;"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
          <h3 id="active-clients-count">0</h3>
          <p>Active Clients</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: #8b5cf6; background: #f3e8ff;"><i class="fa-solid fa-shield-dog"></i></div>
        <div class="stat-info">
          <h3 id="total-registered-pets">0</h3>
          <p>Associated Pets</p>
        </div>
      </div>
    </div>

    <!-- Table Filter Header -->
    <div class="table-controls sort-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search client name, phone, or email...">
      </div>
      <div class="client-dropdown-filters">
        <label class="visually-hidden" for="sortOrder">Sort clients</label>
        <select class="filter-select" id="sortOrder">
          <option value="default">Default</option>
          <option value="ascending">Ascending (A-Z)</option>
          <option value="descending">Descending (Z-A)</option>
        </select>
        <select class="filter-select" id="statusFilter" aria-label="Filter clients by status">
          <option value="All">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Main Client Directory Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Phone Number</th>
            <th>Email Address</th>
            <th>Registered Pets</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="clientTableBody">
          <!-- Populated by JavaScript -->
        </tbody>
      </table>
    </div>
  </main>

  <!-- Add New Client Modal Form -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Register New Client</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="clientForm">
        <div class="form-grid">
          <div class="form-group full">
            <label>Full Name</label>
            <input type="text" id="clientName" required placeholder="Enter client name">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" id="clientPhone" required placeholder="Enter phone number">
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="clientEmail" required placeholder="Enter email address">
          </div>
          <div class="form-group full">
            <label>Address (optional)</label>
            <input type="text" id="clientAddress" maxlength="255" placeholder="Enter address">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Save Client</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=6"></script>
  <script>
    let clients = [];
    let editingClientId = null;
    let selectedSortOrder = 'default';

    function updateMetrics() {
      document.getElementById('total-clients-count').innerText = clients.length;
      document.getElementById('active-clients-count').innerText = clients.filter(c => c.status === 'Active').length;
      const totalPets = clients.reduce((sum, client) => sum + Number(client.pet_count || 0), 0);
      document.getElementById('total-registered-pets').innerText = totalPets;
    }

    function renderTable() {
      const tbody = document.getElementById('clientTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const statusVal = document.getElementById('statusFilter').value;
      tbody.innerHTML = '';

      const filtered = clients.filter(c => 
        (c.name.toLowerCase().includes(searchVal) ||
        c.phone.toLowerCase().includes(searchVal) ||
        c.email.toLowerCase().includes(searchVal)) &&
        (statusVal === 'All' || c.status === statusVal)
      );
      if (selectedSortOrder !== 'default') {
        const direction = selectedSortOrder === 'ascending' ? 1 : -1;
        filtered.sort((first, second) => direction * String(first.name).localeCompare(String(second.name), undefined, { sensitivity: 'base' }));
      }

      filtered.forEach(c => {
        const petTags = Number(c.pet_count) > 0 ? `${c.pet_count} registered` : 'None';
        const row = document.createElement('tr');
        
        row.innerHTML = `
          <td>
            <div class="client-cell">
              ${clinicEscape(c.name)}
            </div>
          </td>
          <td>${clinicEscape(c.phone)}</td>
          <td>${clinicEscape(c.email)}</td>
          <td>${petTags}</td>
          <td>
            <span class="status-badge ${c.status === 'Active' ? 'status-active' : 'status-inactive'}">
              ${clinicEscape(c.status)}
            </span>
          </td>
          <td>
            <button class="action-btn" onclick="editClient(${c.id})" title="Edit Client"><i class="fa-solid fa-pen"></i></button>
            <button class="action-btn" onclick="toggleStatus(${c.id})" title="Toggle Active/Inactive"><i class="fa-solid fa-user-gear"></i></button>
            <button class="action-btn" onclick="deleteClient(${c.id})" title="Delete Client"><i class="fa-solid fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No clients yet.</td></tr>';
      }

      updateMetrics();
    }

    async function loadClients() {
      try {
        clients = (await clinicRequest('clients')).data;
        renderTable();
      } catch (error) {
        clinicShowError(error);
      }
    }

    async function toggleStatus(id) {
      const client = clients.find(c => Number(c.id) === Number(id));
      if (client) {
        try {
          await clinicRequest('clients', 'status', { id: client.id, status: client.status === 'Active' ? 'Inactive' : 'Active' });
          await loadClients();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function deleteClient(id) {
      if (confirm("Are you sure you want to delete this client profile?")) {
        try {
          await clinicRequest('clients', 'delete', { id });
          await loadClients();
        } catch (error) { clinicShowError(error); }
      }
    }

    function editClient(id) {
      const client = clients.find(c => Number(c.id) === Number(id));
      if (!client) return;
      editingClientId = client.id;
      document.getElementById('clientName').value = client.name;
      document.getElementById('clientPhone').value = client.phone;
      document.getElementById('clientEmail').value = client.email;
      document.getElementById('clientAddress').value = client.address || '';
      document.querySelector('#clientForm button[type="submit"]').textContent = 'Save Changes';
      modal.classList.add('active');
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('statusFilter').addEventListener('change', renderTable);
    document.getElementById('sortOrder').addEventListener('change', event => {
      selectedSortOrder = event.target.value;
      renderTable();
    });

    const modal = document.getElementById('addModal');
    document.getElementById('openAddModal').addEventListener('click', () => {
      editingClientId = null;
      document.getElementById('clientForm').reset();
      document.querySelector('#clientForm button[type="submit"]').textContent = 'Save Client';
      modal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));

    document.getElementById('clientForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const values = {
        name: document.getElementById('clientName').value,
        phone: document.getElementById('clientPhone').value,
        email: document.getElementById('clientEmail').value,
        address: document.getElementById('clientAddress').value
      };
      const action = editingClientId ? 'update' : 'create';
      if (editingClientId) {
        const current = clients.find(c => Number(c.id) === Number(editingClientId));
        values.id = editingClientId;
        values.status = current.status;
      }
      clinicRequest('clients', action, values).then(async () => {
        this.reset();
        editingClientId = null;
        document.querySelector('#clientForm button[type="submit"]').textContent = 'Save Client';
        modal.classList.remove('active');
        await loadClients();
      }).catch(clinicShowError);
    });

    loadClients();
  </script>
</body>
</html>