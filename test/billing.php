<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing & Invoicing - Urban Fur Veterinary Clinic</title>
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
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item active"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <!-- Workspace View -->
  <main class="main-content">
    
    <div class="page-header">
      <div class="page-title">
        <h1>Billing & Invoices</h1>
        <p>Track client transactions, outstanding balances, and issue invoices</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> Create Invoice</button>
    </div>

    <!-- Summary Metrics -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--success); background: #dcfce7;"><i class="fa-solid fa-dollar-sign"></i></div>
        <div class="stat-info">
          <h3 id="total-revenue">₱0.00</h3>
          <p>Total Revenue</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="stat-info">
          <h3 id="total-invoices">0</h3>
          <p>Total Invoices</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--warning); background: #fef3c7;"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
          <h3 id="pending-count">0</h3>
          <p>Pending Invoices</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--danger); background: #fee2e2;"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-info">
          <h3 id="overdue-count">0</h3>
          <p>Overdue Invoices</p>
        </div>
      </div>
    </div>

    <!-- Control Filters Bar -->
    <div class="table-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search invoice ID, client, or pet...">
      </div>
      <select class="status-filter" id="statusFilter">
        <option value="All">All Statuses</option>
        <option value="Paid">Paid</option>
        <option value="Pending">Pending</option>
        <option value="Overdue">Overdue</option>
      </select>
    </div>

    <!-- Billing Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Client & Patient</th>
            <th>Service Description</th>
            <th>Date Issued</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="billingTableBody">
          <!-- Rendered via JavaScript -->
        </tbody>
      </table>
    </div>
  </main>

  <!-- Create Invoice Modal -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Create New Invoice</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="billingForm">
        <div class="form-grid">
          <div class="form-group">
            <label>Client Name</label>
            <select id="clientName" required></select>
          </div>
          <div class="form-group">
            <label>Pet Name</label>
            <select id="petName"></select>
          </div>
          <div class="form-group full">
            <label>Service / Item Description</label>
            <input type="text" id="serviceDesc" required placeholder="Enter service or item">
          </div>
          <div class="form-group">
            <label>Total Amount (₱)</label>
            <input type="number" step="0.01" id="totalAmount" required placeholder="Enter amount">
          </div>
          <div class="form-group">
            <label>Payment Method</label>
            <select id="paymentMethod"></select>
          </div>
          <div class="form-group">
            <label>Payment Status</label>
            <select id="paymentStatus">
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
              <option value="Overdue">Overdue</option>
            </select>
          </div>
          <div class="form-group">
            <label>Issue Date</label>
            <input type="date" id="issueDate" required>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Generate Invoice</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Invoice Receipt Modal -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Invoice Details</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeViewModal"></i>
      </div>
      <div class="receipt-details" id="receiptContent">
        <!-- Rendered via JavaScript -->
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-secondary" id="closeViewBtn">Close</button>
        <button type="button" class="btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
      </div>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    let invoices = [];
    let invoiceClients = [];
    let invoicePets = [];

    function updateMetrics() {
      const revenue = invoices
        .filter(i => i.status === 'Paid')
        .reduce((sum, i) => sum + Number(i.amount), 0);

      document.getElementById('total-revenue').innerText = `₱${revenue.toFixed(2)}`;
      document.getElementById('total-invoices').innerText = invoices.length;
      document.getElementById('pending-count').innerText = invoices.filter(i => i.status === 'Pending').length;
      document.getElementById('overdue-count').innerText = invoices.filter(i => i.status === 'Overdue').length;
    }

    function renderTable() {
      const tbody = document.getElementById('billingTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const statusVal = document.getElementById('statusFilter').value;

      tbody.innerHTML = '';

      const filtered = invoices.filter(i => {
        const matchesSearch = [i.id, i.client, i.pet, i.service].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesStatus = statusVal === 'All' || i.status === statusVal;

        return matchesSearch && matchesStatus;
      });

      filtered.forEach(i => {
        let statusClass = 'status-paid';
        if (i.status === 'Pending') statusClass = 'status-pending';
        if (i.status === 'Overdue') statusClass = 'status-overdue';

        const row = document.createElement('tr');
        row.innerHTML = `
          <td><span class="invoice-id">${clinicEscape(i.id)}</span></td>
          <td>
            <strong>${clinicEscape(i.client)}</strong>
            <span class="client-sub">Patient: ${clinicEscape(i.pet)}</span>
          </td>
          <td>${clinicEscape(i.service)}</td>
          <td>${clinicEscape(i.date)}</td>
          <td><strong>₱${Number(i.amount).toFixed(2)}</strong></td>
          <td><span class="status-badge ${statusClass}">${i.status}</span></td>
          <td>
            <button class="action-btn" onclick="viewReceipt(${i.record_id})" title="View Details"><i class="fa-solid fa-eye"></i></button>
            <button class="action-btn" onclick="togglePaymentStatus(${i.record_id})" title="Toggle Paid/Pending"><i class="fa-solid fa-circle-dollar-to-slot"></i></button>
            <button class="action-btn" onclick="deleteInvoice(${i.record_id})" title="Delete Invoice"><i class="fa-solid fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No invoices yet.</td></tr>';
      }

      updateMetrics();
    }

    async function loadInvoices() {
      try {
        const [invoiceResult, clientResult, petResult, settingsResult] = await Promise.all([
          clinicRequest('invoices'), clinicRequest('clients-options'), clinicRequest('pets-options'), clinicRequest('settings')
        ]);
        invoices = invoiceResult.data;
        invoiceClients = clientResult.data;
        invoicePets = petResult.data;
        const paymentNames = { Cash: 'Cash', Card: 'Credit/Debit Card', GCash: 'GCash', 'Pay Later': 'Pay Later' };
        document.getElementById('paymentMethod').innerHTML = settingsResult.data.accepted_payment_methods
          .map(method => `<option value="${clinicEscape(method)}">${clinicEscape(paymentNames[method] || method)}</option>`).join('');
        document.getElementById('clientName').innerHTML = '<option value="">Choose client</option>' + invoiceClients.map(client => `<option value="${client.id}">${clinicEscape(client.name)}</option>`).join('');
        updateInvoicePetOptions();
        renderTable();
      } catch (error) { clinicShowError(error); }
    }

    function updateInvoicePetOptions() {
      const clientId = document.getElementById('clientName').value;
      const pets = invoicePets.filter(pet => String(pet.client_id) === String(clientId));
      document.getElementById('petName').innerHTML = '<option value="">No patient</option>' + pets.map(pet => `<option value="${pet.id}">${clinicEscape(pet.name)}</option>`).join('');
    }

    async function togglePaymentStatus(id) {
      const invoice = invoices.find(i => Number(i.record_id) === Number(id));
      if (invoice) {
        try {
          await clinicRequest('invoices', 'update', { id, status: invoice.status === 'Paid' ? 'Pending' : 'Paid' });
          await loadInvoices();
        } catch (error) { clinicShowError(error); }
      }
    }

    async function deleteInvoice(id) {
      if (confirm("Are you sure you want to delete this invoice record?")) {
        try {
          await clinicRequest('invoices', 'delete', { id });
          await loadInvoices();
        } catch (error) { clinicShowError(error); }
      }
    }

    function viewReceipt(id) {
      const i = invoices.find(item => Number(item.record_id) === Number(id));
      if (!i) return;

      const container = document.getElementById('receiptContent');
      container.innerHTML = `
        <div class="receipt-row"><span>Invoice ID:</span><strong>${clinicEscape(i.id)}</strong></div>
        <div class="receipt-row"><span>Date:</span><span>${clinicEscape(i.date)}</span></div>
        <div class="receipt-row"><span>Client:</span><span>${clinicEscape(i.client)}</span></div>
        <div class="receipt-row"><span>Patient:</span><span>${clinicEscape(i.pet)}</span></div>
        <div class="receipt-row"><span>Payment Method:</span><span>${clinicEscape(i.payment_method || 'Not specified')}</span></div>
        <div class="receipt-row"><span>Payment Status:</span><strong>${clinicEscape(i.status)}</strong></div>
        <div class="receipt-row"><span>Service Rendered:</span><span>${clinicEscape(i.service)}</span></div>
        <div class="receipt-row total"><span>Total Billed:</span><span>₱${Number(i.amount).toFixed(2)}</span></div>
      `;

      document.getElementById('viewModal').classList.add('active');
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('statusFilter').addEventListener('change', renderTable);
    document.getElementById('clientName').addEventListener('change', updateInvoicePetOptions);
    document.getElementById('paymentMethod').addEventListener('change', event => {
      const payLater = event.currentTarget.value === 'Pay Later';
      document.getElementById('paymentStatus').disabled = payLater;
      if (payLater) document.getElementById('paymentStatus').value = 'Pending';
    });

    const addModal = document.getElementById('addModal');
    document.getElementById('openAddModal').addEventListener('click', () => {
      document.getElementById('issueDate').value = new Date().toISOString().split('T')[0];
      addModal.classList.add('active');
    });

    document.getElementById('closeAddModal').addEventListener('click', () => addModal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => addModal.classList.remove('active'));
    document.getElementById('closeViewModal').addEventListener('click', () => document.getElementById('viewModal').classList.remove('active'));
    document.getElementById('closeViewBtn').addEventListener('click', () => document.getElementById('viewModal').classList.remove('active'));

    document.getElementById('billingForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const values = {
        client_id: document.getElementById('clientName').value,
        pet_id: document.getElementById('petName').value,
        description: document.getElementById('serviceDesc').value,
        amount: parseFloat(document.getElementById('totalAmount').value),
        payment_method: document.getElementById('paymentMethod').value,
        status: document.getElementById('paymentStatus').value,
        issued_on: document.getElementById('issueDate').value
      };

      clinicRequest('invoices', 'create', values).then(async () => {
        addModal.classList.remove('active');
        this.reset();
        document.getElementById('paymentStatus').disabled = false;
        await loadInvoices();
      }).catch(clinicShowError);
    });

    loadInvoices();
  </script>
</body>
</html>