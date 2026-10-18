<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing & Invoicing - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=36">
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
      <div class="page-header-actions"><button class="btn-secondary" type="button" id="openExpenseModal"><i class="fa-solid fa-receipt"></i> Add Expense</button><button class="btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> Create Invoice</button></div>
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
    <div class="table-container billing-table-container">
      <table>
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Client</th>
            <th>Patient</th>
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

    <section class="content-panel billing-expenses-panel">
      <div class="panel-header"><div><h2>Clinic Expenses &amp; Payables</h2><p>Record clinic purchases, operating costs, and amounts still owed.</p></div></div>
      <div class="table-container"><table><thead><tr><th>Date</th><th>Reason</th><th>Details</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody id="expenseTableBody"><tr><td colspan="6">Loading expenses…</td></tr></tbody></table></div>
    </section>
  </main>

  <div class="modal-overlay" id="expenseModal">
    <div class="modal">
      <div class="modal-header"><h2>Add Clinic Expense</h2><button type="button" class="close-modal" id="closeExpenseModal" aria-label="Close expense form"><i class="fa-solid fa-xmark"></i></button></div>
      <form id="expenseForm">
        <div class="form-grid">
          <div class="form-group"><label for="expenseReason">Expense reason</label><select id="expenseReason" required><option>Office Supplies</option><option>Utilities</option><option>Rent</option><option>Clinic Supplies</option><option>Medication</option><option>Maintenance</option><option>Transportation</option><option>Other</option></select></div>
          <div class="form-group" id="expenseOtherWrap" hidden><label for="expenseOtherReason">Specify other reason</label><input id="expenseOtherReason" maxlength="255"></div>
          <div class="form-group" id="expenseDescriptionWrap"><label for="expenseDescription">Notes (optional)</label><input id="expenseDescription" maxlength="255" placeholder="Additional details"></div>
          <div class="form-group"><label for="expenseAmount">Amount (₱)</label><input id="expenseAmount" type="number" min="0.01" max="99999999.99" step="0.01" required></div>
          <div class="form-group"><label for="expenseDate">Date incurred</label><input id="expenseDate" type="date" required></div>
        </div>
        <p class="muted">New expenses are recorded as Unpaid until marked paid.</p>
        <div class="modal-actions"><button type="button" class="btn-secondary" id="cancelExpense">Cancel</button><button class="btn-primary" type="submit">Save Expense</button></div>
      </form>
    </div>
  </div>

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

  <div class="modal-overlay" id="paymentModal">
    <div class="modal">
      <div class="modal-header"><h2>Record Invoice Payment</h2><button type="button" class="close-modal" id="closePaymentModal" aria-label="Close payment form"><i class="fa-solid fa-xmark"></i></button></div>
      <p id="paymentInvoiceSummary" class="muted"></p>
      <form id="paymentForm">
        <div class="form-grid">
          <div class="form-group full"><label for="recordPaymentMethod">Payment method</label><select id="recordPaymentMethod" required></select></div>
        </div>
        <p class="muted">Recording payment marks the full outstanding invoice as paid.</p>
        <div class="modal-actions"><button type="button" class="btn-secondary" id="cancelPayment">Cancel</button><button type="submit" class="btn-primary">Confirm payment</button></div>
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
        <button type="button" class="btn-primary invoice-print-button" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
      </div>
    </div>
  </div>

  <script src="assets/app.js?v=14"></script>
  <script>
    let invoices = [];
    let invoiceClients = [];
    let invoicePets = [];
    let expenses = [];
    let billingSettings = null;
    let paymentInvoiceId = null;

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
          <td><strong>${clinicEscape(i.client)}</strong></td>
          <td>${clinicEscape(i.pet)}</td>
          <td>${clinicEscape(i.service)}</td>
          <td>${clinicEscape(i.date)}</td>
          <td><strong>₱${Number(i.amount).toFixed(2)}</strong></td>
          <td><span class="status-badge ${statusClass}">${i.status}</span></td>
          <td>
            <div class="action-btn-group">
              <button class="action-btn" onclick="viewReceipt(${i.record_id})" title="View Details"><i class="fa-solid fa-eye"></i></button>
              ${i.status === 'Paid' ? `<span class="invoice-payment-complete">${clinicEscape(i.payment_method || 'Paid')}</span>` : `<button type="button" class="btn-secondary invoice-pay-button" data-pay-invoice="${Number(i.record_id)}">Record payment</button>`}
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">No invoices yet.</td></tr>';
      }

      updateMetrics();
    }

    async function loadInvoices() {
      try {
        const [invoiceResult, clientResult, petResult, settingsResult, expenseResult] = await Promise.all([
          clinicRequest('invoices'), clinicRequest('clients-options'), clinicRequest('pets-options'), clinicRequest('settings'), clinicRequest('expenses')
        ]);
        invoices = invoiceResult.data;
        invoiceClients = clientResult.data;
        invoicePets = petResult.data;
        expenses = expenseResult.data;
        billingSettings = settingsResult.data;
        const paymentMethods = billingSettings.accepted_payment_methods || ['Cash', 'Card', 'GCash', 'Pay Later'];
        const paymentOptions = paymentMethods.map(method => `<option value="${clinicEscape(method)}">${clinicEscape(method)}</option>`).join('');
        document.getElementById('paymentMethod').innerHTML = paymentOptions;
        document.getElementById('recordPaymentMethod').innerHTML = paymentMethods.filter(method => method !== 'Pay Later').map(method => `<option value="${clinicEscape(method)}">${clinicEscape(method)}</option>`).join('');
        const expenseReasons = billingSettings.expense_reasons || ['Office Supplies', 'Utilities', 'Rent', 'Clinic Supplies', 'Medication', 'Maintenance', 'Transportation', 'Other'];
        document.getElementById('expenseReason').innerHTML = expenseReasons.map(reason => `<option value="${clinicEscape(reason)}">${clinicEscape(reason)}</option>`).join('');
        document.getElementById('clientName').innerHTML = '<option value="">Choose client</option>' + invoiceClients.map(client => `<option value="${client.id}">${clinicEscape(client.name)}</option>`).join('');
        updateInvoicePetOptions();
        renderTable();
        renderExpenses();
      } catch (error) { clinicShowError(error); }
    }

    function renderExpenses() {
      const body = document.getElementById('expenseTableBody');
      body.innerHTML = expenses.length ? expenses.map(expense => `<tr><td>${clinicEscape(expense.incurred_on)}</td><td>${clinicEscape(expense.reason)}</td><td>${clinicEscape(expense.description)}</td><td>₱${Number(expense.amount).toFixed(2)}</td><td><span class="status-badge ${expense.status === 'Paid' ? 'status-paid' : 'status-pending'}">${clinicEscape(expense.status)}</span></td><td><button type="button" class="btn-secondary" data-expense-id="${Number(expense.id)}">${expense.status === 'Paid' ? 'Mark unpaid' : 'Mark paid'}</button></td></tr>`).join('') : '<tr><td colspan="6">No clinic expenses recorded yet.</td></tr>';
    }

    function updateInvoicePetOptions() {
      const clientId = document.getElementById('clientName').value;
      const pets = invoicePets.filter(pet => String(pet.client_id) === String(clientId));
      document.getElementById('petName').innerHTML = '<option value="">No patient</option>' + pets.map(pet => `<option value="${pet.id}">${clinicEscape(pet.name)}</option>`).join('');
    }

    function openPaymentModal(id) {
      const invoice = invoices.find(row => Number(row.record_id) === Number(id));
      if (!invoice || invoice.status === 'Paid') return;
      paymentInvoiceId = invoice.record_id;
      document.getElementById('paymentInvoiceSummary').textContent = `${invoice.id} · ${invoice.client} · Outstanding amount ₱${Number(invoice.amount).toFixed(2)}`;
      document.getElementById('paymentModal').classList.add('active');
    }

    function viewReceipt(id) {
      const i = invoices.find(item => Number(item.record_id) === Number(id));
      if (!i) return;

      const container = document.getElementById('receiptContent');
      container.innerHTML = `
        <div class="invoice-detail-header">
          <span class="invoice-detail-label">Invoice</span>
          <strong class="invoice-detail-number">${clinicEscape(i.id)}</strong>
          <span class="status-badge ${i.status === 'Paid' ? 'status-paid' : i.status === 'Overdue' ? 'status-overdue' : 'status-pending'}">${clinicEscape(i.status)}</span>
        </div>
        <div class="invoice-detail-section">
          <div class="receipt-row"><span>Date issued</span><strong>${clinicEscape(i.date)}</strong></div>
          <div class="receipt-row"><span>Client</span><strong>${clinicEscape(i.client)}</strong></div>
          <div class="receipt-row"><span>Patient</span><strong>${clinicEscape(i.pet)}</strong></div>
        </div>
        <div class="invoice-detail-section">
          <div class="receipt-row"><span>Payment method</span><strong>${clinicEscape(i.payment_method || 'Not specified')}</strong></div>
          <div class="receipt-row"><span>Service / items</span><strong>${clinicEscape(i.service)}</strong></div>
        </div>
        <div class="receipt-row total"><span>Total billed</span><strong>₱${Number(i.amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></div>
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
    document.getElementById('closePaymentModal').addEventListener('click', () => document.getElementById('paymentModal').classList.remove('active'));
    document.getElementById('cancelPayment').addEventListener('click', () => document.getElementById('paymentModal').classList.remove('active'));
    document.getElementById('billingTableBody').addEventListener('click', event => {
    const button = event.target.closest('[data-pay-invoice]');
    if (button) openPaymentModal(Number(button.dataset.payInvoice));
    });
    document.getElementById('paymentForm').addEventListener('submit', async event => {
    event.preventDefault();
    if (!paymentInvoiceId) return;
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      await clinicRequest('invoices', 'update', {
        id: paymentInvoiceId,
        status: 'Paid',
        payment_method: document.getElementById('recordPaymentMethod').value
      });
      paymentInvoiceId = null;
      document.getElementById('paymentModal').classList.remove('active');
      await loadInvoices();
    } catch (error) { clinicShowError(error); }
    finally { button.disabled = false; }
    });
    const expenseModal = document.getElementById('expenseModal');
    document.getElementById('openExpenseModal').addEventListener('click', () => {
      document.getElementById('expenseForm').reset();
      document.getElementById('expenseDate').value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
      document.getElementById('expenseOtherWrap').hidden = true;
      document.getElementById('expenseOtherReason').required = false;
      expenseModal.classList.add('active');
    });
    document.getElementById('closeExpenseModal').addEventListener('click', () => expenseModal.classList.remove('active'));
    document.getElementById('cancelExpense').addEventListener('click', () => expenseModal.classList.remove('active'));
    document.getElementById('expenseReason').addEventListener('change', event => {
      const other = event.currentTarget.value === 'Other';
      document.getElementById('expenseOtherWrap').hidden = !other;
      document.getElementById('expenseOtherReason').required = other;
    });
    document.getElementById('expenseForm').addEventListener('submit', async event => {
      event.preventDefault();
      const button = event.currentTarget.querySelector('button[type="submit"]');
      button.disabled = true;
      const reason = document.getElementById('expenseReason').value;
      try {
        await clinicRequest('expenses', 'create', {
          reason,
          other_reason: document.getElementById('expenseOtherReason').value,
          description: document.getElementById('expenseDescription').value,
          amount: Number(document.getElementById('expenseAmount').value),
          incurred_on: document.getElementById('expenseDate').value
        });
        expenseModal.classList.remove('active');
        await loadInvoices();
      } catch (error) { clinicShowError(error); }
      finally { button.disabled = false; }
    });
    document.getElementById('expenseTableBody').addEventListener('click', async event => {
      const button = event.target.closest('[data-expense-id]');
      if (!button) return;
      const expense = expenses.find(row => Number(row.id) === Number(button.dataset.expenseId));
      if (!expense) return;
      button.disabled = true;
      try {
        await clinicRequest('expenses', 'update', { id: expense.id, status: expense.status === 'Paid' ? 'Unpaid' : 'Paid' });
        await loadInvoices();
      } catch (error) { clinicShowError(error); button.disabled = false; }
    });

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
