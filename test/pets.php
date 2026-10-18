<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patients (Pets) - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=18">
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
   
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item active"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
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

  <!-- Main View -->
  <main class="main-content">
    
    <div class="page-header">
      <div class="page-title">
        <h1>Patients (Pets)</h1>
        <p>Manage patient records and medical history</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> Add Patient</button>
    </div>

    <!-- Category Filters -->
    <div class="stats-row">
      <div class="stat-card active" onclick="filterSpecies('All', this)">
        <div class="stat-icon"><i class="fa-solid fa-paw"></i></div>
        <div class="stat-info">
          <h3 id="total-count">0</h3>
          <p>Total Patients</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterSpecies('Dog', this)">
        <div class="stat-icon"><i class="fa-solid fa-dog"></i></div>
        <div class="stat-info">
          <h3 id="dog-count">0</h3>
          <p>Dogs</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterSpecies('Cat', this)">
        <div class="stat-icon"><i class="fa-solid fa-cat"></i></div>
        <div class="stat-info">
          <h3 id="cat-count">0</h3>
          <p>Cats</p>
        </div>
      </div>
      <div class="stat-card" onclick="filterSpecies('Other', this)">
        <div class="stat-icon"><i class="fa-solid fa-dove"></i></div>
        <div class="stat-info">
          <h3 id="other-count">0</h3>
          <p>Others</p>
        </div>
      </div>
    </div>

    <!-- Search & Filters Header -->
    <div class="table-controls sort-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search by pet name, breed, or owner...">
      </div>
      <div class="client-dropdown-filters">
        <label class="visually-hidden" for="sortOrder">Sort patients</label>
        <select class="filter-select" id="sortOrder">
          <option value="default">Default</option>
          <option value="ascending">Ascending (A-Z)</option>
          <option value="descending">Descending (Z-A)</option>
        </select>
        <select class="filter-select" id="statusFilter" aria-label="Filter patients by status">
          <option value="All">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Patient Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Pet Name</th>
            <th>Species</th>
            <th>Breed</th>
            <th>Age</th>
            <th>Owner</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="patientTableBody">
          <!-- Rows rendered by JS -->
        </tbody>
      </table>
    </div>
  </main>

  <!-- Add New Patient Modal -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Add New Patient</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="patientForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Pet Name</label>
            <input type="text" id="petName" required placeholder="Enter pet name">
          </div>
          <div class="form-group">
            <label>Species</label>
            <select id="species" required>
              <option value="Dog">Dog</option>
              <option value="Cat">Cat</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Breed</label>
            <input type="text" id="breed" required placeholder="Enter breed">
          </div>
          <div class="form-group">
            <label>Date of Birth (optional)</label>
            <input type="date" id="birthDate">
          </div>
          <div class="form-group full">
            <label>Owner Name</label>
            <select id="owner" required></select>
            <small id="ownerHelp" role="status"></small>
          </div>
          <div class="form-group full">
            <label>Medical Notes (optional)</label>
            <textarea id="petNotes" maxlength="60000" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Save Patient</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    let patients = [];
    let clientOptions = [];
    let editingPetId = null;
    let selectedSpecies = 'All';
    let selectedSortOrder = 'default';

    function renderTable() {
      const tbody = document.getElementById('patientTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const statusVal = document.getElementById('statusFilter').value;

      tbody.innerHTML = '';

      const filtered = patients.filter(p => {
        const matchesSpecies = selectedSpecies === 'All' || p.species === selectedSpecies;
        const matchesSearch = [p.name, p.breed, p.owner].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesStatus = statusVal === 'All' || p.status === statusVal;

        return matchesSpecies && matchesSearch && matchesStatus;
      });
      if (selectedSortOrder !== 'default') {
        const direction = selectedSortOrder === 'ascending' ? 1 : -1;
        filtered.sort((first, second) => direction * String(first.name).localeCompare(String(second.name), undefined, { sensitivity: 'base' }));
      }

      filtered.forEach(p => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${clinicEscape(p.name)}</td>
          <td>${clinicEscape(p.species)}</td>
          <td>${clinicEscape(p.breed)}</td>
          <td>${p.age === null ? '—' : `${p.age} yr`}</td>
          <td>${clinicEscape(p.owner)}</td>
          <td>
            <span class="status-badge ${p.status === 'Active' ? 'status-active' : 'status-inactive'}">
              ${clinicEscape(p.status)}
            </span>
          </td>
          <td>
            <button type="button" class="action-btn" onclick="editPatient(${p.id})" title="Edit Patient" aria-label="Edit ${clinicEscape(p.name)}"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="action-btn" onclick="toggleStatus(${p.id})" title="Toggle Active/Inactive" aria-label="Toggle status for ${clinicEscape(p.name)}"><i class="fa-solid fa-rotate"></i></button>
            <button type="button" class="action-btn" onclick="deletePatient(${p.id})" title="Delete Patient" aria-label="Delete ${clinicEscape(p.name)}"><i class="fa-solid fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No patients yet.</td></tr>';
      }

      document.getElementById('total-count').innerText = patients.length;
      document.getElementById('dog-count').innerText = patients.filter(p => p.species === 'Dog').length;
      document.getElementById('cat-count').innerText = patients.filter(p => p.species === 'Cat').length;
      document.getElementById('other-count').innerText = patients.filter(p => p.species === 'Other').length;
    }

    function filterSpecies(species, cardElement) {
      selectedSpecies = species;
      document.querySelectorAll('.stat-card').forEach(el => el.classList.remove('active'));
      cardElement.classList.add('active');
      renderTable();
    }

    async function loadPatients() {
      try {
        const [petResult, clientResult] = await Promise.all([clinicRequest('pets'), clinicRequest('clients-all-options')]);
        patients = petResult.data;
        clientOptions = clientResult.data;
        const ownerSelect = document.getElementById('owner');
        ownerSelect.innerHTML = '<option value="">Choose client</option>' + clientOptions.map(client => `<option value="${client.id}">${clinicEscape(client.name)}${client.status === 'Inactive' ? ' (Inactive)' : ''}</option>`).join('');
        const noClients = clientOptions.length === 0;
        ownerSelect.disabled = noClients;
        document.querySelector('#patientForm button[type="submit"]').disabled = noClients;
        document.getElementById('ownerHelp').innerHTML = noClients
          ? 'Add a client before registering a pet. <a href="clients.php">Go to Clients</a>.'
          : '';
        renderTable();
      } catch (error) {
        document.getElementById('ownerHelp').textContent = error.message;
        document.querySelector('#patientForm button[type="submit"]').disabled = true;
        clinicShowError(error);
      }
    }

    async function toggleStatus(id) {
      const patient = patients.find(p => Number(p.id) === Number(id));
      if (patient) {
        try {
          await clinicRequest('pets', 'status', { id: patient.id, status: patient.status === 'Active' ? 'Inactive' : 'Active' });
          await loadPatients();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function deletePatient(id) {
      if (confirm("Are you sure you want to delete this patient record?")) {
        try {
          await clinicRequest('pets', 'delete', { id });
          await loadPatients();
        } catch (error) { clinicShowError(error); }
      }
    }

    function editPatient(id) {
      const patient = patients.find(p => Number(p.id) === Number(id));
      if (!patient) return;
      editingPetId = patient.id;
      document.getElementById('petName').value = patient.name;
      document.getElementById('species').value = patient.species;
      document.getElementById('breed').value = patient.breed;
      document.getElementById('birthDate').value = patient.birth_date || '';
      document.getElementById('owner').value = patient.client_id;
      document.getElementById('petNotes').value = patient.notes || '';
      document.querySelector('#patientForm button[type="submit"]').textContent = 'Save Changes';
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
      editingPetId = null;
      document.getElementById('patientForm').reset();
      document.querySelector('#patientForm button[type="submit"]').textContent = 'Save Patient';
      modal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));

    document.getElementById('patientForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const values = {
        name: document.getElementById('petName').value,
        species: document.getElementById('species').value,
        breed: document.getElementById('breed').value,
        birth_date: document.getElementById('birthDate').value,
        notes: document.getElementById('petNotes').value,
        client_id: document.getElementById('owner').value
      };
      const action = editingPetId ? 'update' : 'create';
      if (editingPetId) {
        const current = patients.find(p => Number(p.id) === Number(editingPetId));
        values.id = editingPetId;
        values.status = current.status;
      }
      clinicRequest('pets', action, values).then(async () => {
        this.reset();
        editingPetId = null;
        document.querySelector('#patientForm button[type="submit"]').textContent = 'Save Patient';
        modal.classList.remove('active');
        await loadPatients();
      }).catch(clinicShowError);
    });

    loadPatients();
  </script>
</body>
</html>