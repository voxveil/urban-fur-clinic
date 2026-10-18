<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patients (Pets) - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=36">
</head>
<body class="patients-page">

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
        <label class="visually-hidden" for="statusFilter">Filter by record status</label>
        <select class="filter-select" id="statusFilter" aria-label="Filter patients by record status">
          <option value="All">All record statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
        <label class="visually-hidden" for="lifeStatusFilter">Filter by life status</label>
        <select class="filter-select" id="lifeStatusFilter" aria-label="Filter patients by life status">
          <option value="All">All life statuses</option>
          <option value="Alive">Alive</option>
          <option value="Deceased">Deceased</option>
        </select>
      </div>
    </div>

    <!-- Patient Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Pet ID / Name</th>
            <th>Species</th>
            <th>Breed</th>
            <th>Gender</th>
            <th>Age</th>
            <th>Owner</th>
            <th>Record Status</th>
            <th>Life Status</th>
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
        <h2 id="patientModalTitle">Add New Patient</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="patientForm">
        <div class="form-grid">
          <div class="form-group">
            <label for="petName">Pet Name</label>
            <input type="text" id="petName" required maxlength="100" pattern="[\p{L}]+([ '\u2019\-][\p{L}]+)*" title="Use letters only, with spaces, apostrophes, or hyphens between words." placeholder="Enter pet name">
          </div>
          <div class="form-group">
            <label>Species</label>
            <select id="species" required>
              <option value="Dog">Dog</option>
              <option value="Cat">Cat</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group" id="speciesOtherGroup" hidden>
            <label for="speciesOther">Specify species</label>
            <input id="speciesOther" type="text" maxlength="60" placeholder="Enter the kind of pet">
          </div>
          <div class="form-group">
            <label for="breed">Breed</label>
            <select id="breed" required></select>
          </div>
          <div class="form-group" id="breedOtherGroup" hidden>
            <label for="breedOther">Specify breed</label>
            <input id="breedOther" type="text" maxlength="100" placeholder="Enter the breed">
          </div>
          <div class="form-group">
            <label>Gender (optional)</label>
            <select id="gender">
              <option value="">Not specified</option>
              <option value="Female">Female</option>
              <option value="Male">Male</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" id="birthDate" max="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label for="lifeStatus">Life status</label>
            <select id="lifeStatus" required><option value="Alive">Alive</option><option value="Deceased">Deceased</option></select>
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

  <div class="modal-overlay" id="medicalRecordsModal">
    <section class="modal medical-records-modal" role="dialog" aria-modal="true" aria-labelledby="medicalRecordsTitle">
      <div class="modal-header">
        <div>
          <h2 id="medicalRecordsTitle">Visit History</h2>
          <p id="medicalRecordsPatient" class="medical-records-patient"></p>
        </div>
        <button type="button" class="close-modal" id="closeMedicalRecords" aria-label="Close Visit History"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <section class="medical-record-history" aria-labelledby="medicalHistoryHeading">
        <h3 id="medicalHistoryHeading">Visit History</h3>
        <div id="medicalRecordsList" aria-live="polite">
          <p class="medical-record-empty">Select a patient to view their history.</p>
        </div>
      </section>
      <form id="medicalRecordForm">
        <h3>Add Medical Record</h3>
        <div class="form-grid">
          <div class="form-group full">
            <label for="recordedOn">Visit Date</label>
            <input type="date" id="recordedOn" required>
          </div>
          <div class="form-group full">
            <label for="diagnosis">Diagnosis / Assessment</label>
            <input type="text" id="diagnosis" maxlength="255" required placeholder="Enter diagnosis or assessment">
          </div>
          <div class="form-group full">
            <label for="treatment">Treatment</label>
            <textarea id="treatment" maxlength="60000" rows="3" placeholder="Treatment or medication provided"></textarea>
          </div>
          <div class="form-group full">
            <label for="prescription">Prescription</label>
            <textarea id="prescription" maxlength="60000" rows="3" placeholder="Medication and dosage instructions"></textarea>
          </div>
          <div class="form-group full">
            <label for="recordNotes">Additional Notes</label>
            <textarea id="recordNotes" maxlength="60000" rows="3" placeholder="Follow-up instructions or other notes"></textarea>
          </div>
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn-primary">Save Medical Record</button>
        </div>
      </form>
    </section>
  </div>

  <script src="assets/app.js?v=14"></script>
  <script>
    let patients = [];
    let clientOptions = [];
    let editingPetId = null;
    let selectedMedicalPet = null;
    const breedOptions = {
      Dog: ['Aspin', 'Labrador Retriever', 'Golden Retriever', 'German Shepherd', 'Poodle', 'Beagle', 'Shih Tzu', 'Chihuahua', 'Pomeranian', 'Husky', 'Other'],
      Cat: ['Persian', 'Siamese', 'British Shorthair', 'Maine Coon', 'Ragdoll', 'Bengal', 'American Shorthair', 'Scottish Fold', 'Domestic Shorthair', 'Other'],
      Other: ['Other']
    };
    function updateBreedOptions(selected = '') {
      const select = document.getElementById('breed');
      const selectedSpeciesValue = document.getElementById('species').value;
      const breeds = breedOptions[selectedSpeciesValue] || breedOptions.Other;
      select.innerHTML = '<option value="">Choose breed</option>' + breeds.map(breed => `<option value="${clinicEscape(breed)}">${clinicEscape(breed)}</option>`).join('');
      select.value = selected && breeds.includes(selected) ? selected : (selected ? 'Other' : '');
      document.getElementById('speciesOtherGroup').hidden = selectedSpeciesValue !== 'Other';
      document.getElementById('speciesOther').required = selectedSpeciesValue === 'Other';
      updateCustomBreedField();
      if (selected && !breeds.includes(selected)) document.getElementById('breedOther').value = selected;
    }
    function updateCustomBreedField() {
      const isCustom = document.getElementById('breed').value === 'Other';
      document.getElementById('breedOtherGroup').hidden = !isCustom;
      document.getElementById('breedOther').required = isCustom;
    }
    let selectedSpecies = 'All';
    let selectedSortOrder = 'default';

    function currentLocalDate() {
      const now = new Date();
      return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    }

    function renderTable() {
      const tbody = document.getElementById('patientTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const statusVal = document.getElementById('statusFilter').value;
      const lifeStatusVal = document.getElementById('lifeStatusFilter').value;

      tbody.innerHTML = '';

      const filtered = patients.filter(p => {
        const matchesSpecies = selectedSpecies === 'All' || p.species === selectedSpecies;
        const matchesSearch = [`PET-${String(p.id).padStart(4, '0')}`, p.name, p.breed, p.owner].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesStatus = statusVal === 'All' || p.status === statusVal;
        const matchesLifeStatus = lifeStatusVal === 'All' || p.life_status === lifeStatusVal;

        return matchesSpecies && matchesSearch && matchesStatus && matchesLifeStatus;
      });
      if (selectedSortOrder !== 'default') {
        const direction = selectedSortOrder === 'ascending' ? 1 : -1;
        filtered.sort((first, second) => direction * String(first.name).localeCompare(String(second.name), undefined, { sensitivity: 'base' }));
      }

      filtered.forEach(p => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td><small class="pet-identifier">PET-${String(p.id).padStart(4, '0')}</small><br>${clinicEscape(p.name)}</td>
          <td>${clinicEscape(p.species)}</td>
          <td>${clinicEscape(p.breed)}</td>
          <td>${clinicEscape(p.gender || '—')}</td>
          <td>${p.age === null ? '—' : `${p.age} yr`}</td>
          <td>${clinicEscape(p.owner)}</td>
          <td><span class="status-badge ${p.status === 'Active' ? 'status-active' : 'status-inactive'}">${clinicEscape(p.status)}</span></td>
          <td><span class="status-badge ${p.life_status === 'Deceased' ? 'status-inactive' : 'status-active'}">${clinicEscape(p.life_status || 'Alive')}</span></td>
          <td>
            <div class="action-btn-group">
            <button type="button" class="action-btn" onclick="openMedicalRecords(${p.id})" title="Visit history" aria-label="Visit history for ${clinicEscape(p.name)}"><i class="fa-solid fa-eye"></i></button>
            <button type="button" class="action-btn" onclick="editPatient(${p.id})" title="Edit patient" aria-label="Edit ${clinicEscape(p.name)}"><i class="fa-solid fa-pen-to-square"></i></button>
            <button type="button" class="action-btn" onclick="toggleStatus(${p.id})" title="${p.status === 'Active' ? 'Deactivate patient' : 'Activate patient'}" aria-label="${p.status === 'Active' ? 'Deactivate' : 'Activate'} ${clinicEscape(p.name)}"><i class="fa-solid ${p.status === 'Active' ? 'fa-toggle-on' : 'fa-toggle-off'}"></i></button>
            <button type="button" class="action-btn" onclick="deletePatient(${p.id})" title="Delete patient" aria-label="Delete ${clinicEscape(p.name)}"><i class="fa-solid fa-trash-can"></i></button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9">No patients found.</td></tr>';
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

    async function openMedicalRecords(id) {
      selectedMedicalPet = patients.find(patient => Number(patient.id) === Number(id));
      if (!selectedMedicalPet) return;

      document.getElementById('medicalRecordsPatient').textContent =
        `${selectedMedicalPet.pet_code} · ${selectedMedicalPet.name} · ${selectedMedicalPet.species} · Owner: ${selectedMedicalPet.owner}`;
      document.getElementById('medicalRecordsList').innerHTML = selectedMedicalPet.notes
        ? `<div class="medical-record-patient-notes"><strong>Patient Notes</strong><p>${clinicEscape(selectedMedicalPet.notes)}</p></div><p class="medical-record-loading">Loading visit history…</p>`
        : '<p class="medical-record-loading">Loading visit history…</p>';
      document.getElementById('medicalRecordForm').reset();
      document.getElementById('recordedOn').value = currentLocalDate();
      document.getElementById('medicalRecordsModal').classList.add('active');

      try {
        const patient = selectedMedicalPet;
        const result = await clinicRequest('medical-records', '', null, { pet_id: patient.id });
        if (selectedMedicalPet !== patient) return;
        renderMedicalRecords(result.data);
      } catch (error) {
        if (!selectedMedicalPet || Number(selectedMedicalPet.id) !== Number(id)) return;
        document.getElementById('medicalRecordsList').innerHTML =
          '<p class="medical-record-empty">Unable to load this patient’s medical history.</p>';
        clinicShowError(error);
      }
    }

    function renderMedicalRecords(records) {
      const list = document.getElementById('medicalRecordsList');
      const patientNotes = selectedMedicalPet && selectedMedicalPet.notes
        ? `<div class="medical-record-patient-notes"><strong>Patient Notes</strong><p>${clinicEscape(selectedMedicalPet.notes)}</p></div>`
        : '';
      if (records.length === 0) {
        list.innerHTML = `${patientNotes}<p class="medical-record-empty">No visit history yet. Add the first visit record below.</p>`;
        return;
      }

      list.innerHTML = patientNotes + records.map(record => `
        <article class="medical-record-entry">
          <div class="medical-record-entry-header">
            <time datetime="${clinicEscape(record.recorded_on)}">${clinicEscape(new Date(`${record.recorded_on}T00:00:00`).toLocaleDateString())}</time>
            <span>${clinicEscape(record.recorded_by)}</span>
          </div>
          <h4>${clinicEscape(record.diagnosis)}</h4>
          ${record.treatment ? `<p><strong>Treatment</strong><br>${clinicEscape(record.treatment)}</p>` : ''}
          ${record.prescription ? `<p><strong>Prescription</strong><br>${clinicEscape(record.prescription)}</p>` : ''}
          ${record.notes ? `<p><strong>Notes</strong><br>${clinicEscape(record.notes)}</p>` : ''}
        </article>
      `).join('');
    }

    async function loadMedicalRecords() {
      if (!selectedMedicalPet) return;
      const patient = selectedMedicalPet;
      const result = await clinicRequest('medical-records', '', null, { pet_id: patient.id });
      if (selectedMedicalPet !== patient) return;
      renderMedicalRecords(result.data);
    }

    function editPatient(id) {
      const patient = patients.find(p => Number(p.id) === Number(id));
      if (!patient) return;
      editingPetId = patient.id;
      document.getElementById('patientModalTitle').textContent = 'Edit Patient';
      document.getElementById('petName').value = patient.name;
      const isPresetSpecies = ['Dog', 'Cat'].includes(patient.species);
      document.getElementById('species').value = isPresetSpecies ? patient.species : 'Other';
      document.getElementById('speciesOther').value = isPresetSpecies ? '' : patient.species;
      updateBreedOptions(patient.breed);
      document.getElementById('gender').value = patient.gender || '';
      document.getElementById('birthDate').value = patient.birth_date || '';
      document.getElementById('lifeStatus').value = patient.life_status || 'Alive';
      document.getElementById('owner').value = patient.client_id;
      document.getElementById('petNotes').value = patient.notes || '';
      document.querySelector('#patientForm button[type="submit"]').textContent = 'Save Changes';
      modal.classList.add('active');
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('statusFilter').addEventListener('change', renderTable);
    document.getElementById('lifeStatusFilter').addEventListener('change', renderTable);
    document.getElementById('sortOrder').addEventListener('change', event => {
      selectedSortOrder = event.target.value;
      renderTable();
    });

    const modal = document.getElementById('addModal');
    document.getElementById('species').addEventListener('change', () => {
      document.getElementById('breedOther').value = '';
      updateBreedOptions();
    });
    document.getElementById('breed').addEventListener('change', updateCustomBreedField);
    document.getElementById('openAddModal').addEventListener('click', () => {
      editingPetId = null;
      document.getElementById('patientForm').reset();
      document.getElementById('patientModalTitle').textContent = 'Add New Patient';
      updateBreedOptions();
      document.querySelector('#patientForm button[type="submit"]').textContent = 'Save Patient';
      modal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('closeMedicalRecords').addEventListener('click', () => {
      document.getElementById('medicalRecordsModal').classList.remove('active');
      selectedMedicalPet = null;
    });

    document.getElementById('medicalRecordForm').addEventListener('submit', async function(event) {
      event.preventDefault();
      if (!selectedMedicalPet) return;
      const submitButton = this.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      try {
        await clinicRequest('medical-records', 'create', {
          pet_id: selectedMedicalPet.id,
          recorded_on: document.getElementById('recordedOn').value,
          diagnosis: document.getElementById('diagnosis').value,
          treatment: document.getElementById('treatment').value,
          prescription: document.getElementById('prescription').value,
          notes: document.getElementById('recordNotes').value
        });
        this.reset();
        document.getElementById('recordedOn').value = currentLocalDate();
        await loadMedicalRecords();
      } catch (error) {
        clinicShowError(error);
      } finally {
        submitButton.disabled = false;
      }
    });

    document.getElementById('patientForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const petNameInput = document.getElementById('petName');
      petNameInput.setCustomValidity(/^[\p{L}]+(?:[ '\u2019-][\p{L}]+)*$/u.test(petNameInput.value.trim()) ? '' : 'Pet name may contain letters only, with spaces, apostrophes, or hyphens between words.');
      if (!this.reportValidity()) return;
      const values = {
        name: document.getElementById('petName').value,
        species: document.getElementById('species').value,
        species_other: document.getElementById('speciesOther').value.trim(),
        breed: document.getElementById('breed').value,
        breed_other: document.getElementById('breedOther').value.trim(),
        gender: document.getElementById('gender').value,
        birth_date: document.getElementById('birthDate').value,
        life_status: document.getElementById('lifeStatus').value,
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

    updateBreedOptions();
    loadPatients();
  </script>
</body>
</html>
