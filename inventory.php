<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=34">
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
      <li class="nav-item active"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <!-- Workspace View -->
  <main class="main-content">
    
    <div class="page-header">
      <div class="page-title">
        <h1>Inventory Control</h1>
        <p>Monitor medical supplies, medications, equipment, and stock levels</p>
      </div>
      <button class="btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> Add New Item</button>
    </div>

    <!-- Summary Metrics -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="stat-info">
          <h3 id="total-items-count">0</h3>
          <p>Total Item Types</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--success); background: #dcfce7;"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
          <h3 id="instock-count">0</h3>
          <p>Sufficient Stock</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--warning); background: #fef3c7;"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-info">
          <h3 id="lowstock-count">0</h3>
          <p>Low Stock Alerts</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="color: var(--danger); background: #fee2e2;"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="stat-info">
          <h3 id="outstock-count">0</h3>
          <p>Out of Stock</p>
        </div>
      </div>
    </div>

    <!-- Control Filters Bar -->
    <div class="table-controls">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search item name, SKU, or category...">
      </div>
      <select class="category-filter" id="categoryFilter">
        <option value="All">All Categories</option>
      </select>
    </div>

    <!-- Inventory Directory Table -->
    <div class="table-container inventory-table-container">
      <table class="inventory-table">
        <colgroup>
          <col class="inventory-name-column">
          <col class="inventory-sku-column">
          <col class="inventory-category-column">
          <col class="inventory-quantity-column">
          <col class="inventory-price-column">
          <col class="inventory-status-column">
          <col class="inventory-actions-column">
        </colgroup>
        <thead>
          <tr>
            <th>Item Details</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock Quantity</th>
            <th>Unit Price</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="inventoryTableBody">
          <!-- Rendered via JavaScript -->
        </tbody>
      </table>
    </div>

    <section class="content-panel inventory-history-panel">
      <div class="panel-header"><div><h2>Stock Movement History</h2><p>Recorded stock-in and stock-out transactions.</p></div></div>
      <div class="table-container"><table><thead><tr><th>Date &amp; time</th><th>Item</th><th>Movement</th><th>Quantity</th><th>Reason</th><th>Recorded by</th></tr></thead><tbody id="stockMovementRows"><tr><td colspan="6">Loading stock activity…</td></tr></tbody></table></div>
    </section>
  </main>

  <!-- Add Item Modal -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Add Inventory Item</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeAddModal"></i>
      </div>
      <form id="inventoryForm">
        <div class="form-grid">
          <div class="form-group full">
            <label>Item Name</label>
            <input type="text" id="itemName" required placeholder="Enter item name">
          </div>
          <div class="form-group">
            <label>Category</label>
            <select id="itemCategory" required></select>
          </div>
          <div class="form-group">
            <label for="itemQuantity">Initial Stock Quantity</label>
            <input type="number" id="itemQuantity" min="0" required placeholder="Enter quantity">
          </div>
          <div class="form-group">
            <label>Minimum Quantity</label>
            <input type="number" id="itemMinimum" min="0" value="15" required>
          </div>
          <div class="form-group">
            <label>Unit Price (₱)</label>
            <input type="number" step="0.01" id="itemPrice" required placeholder="Enter unit price">
          </div>
          <div class="form-group">
            <label>Expiration Date (optional)</label>
            <input type="date" id="itemExpiration">
          </div>
          <div class="form-group">
            <label>Condition</label>
            <select id="itemCondition"><option value="Good">Good</option><option value="Damaged">Damaged</option></select>
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Save Item</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="stockMovementModal">
    <div class="modal">
      <div class="modal-header"><h2 id="stockMovementTitle">Record stock movement</h2><button type="button" class="close-modal" id="closeStockMovement" aria-label="Close stock movement"><i class="fa-solid fa-xmark"></i></button></div>
      <p id="stockMovementItem" class="muted"></p>
      <form id="stockMovementForm">
        <input type="hidden" id="stockMovementItemId">
        <input type="hidden" id="stockMovementType">
        <div class="form-grid">
          <div class="form-group"><label for="stockMovementQuantity">Quantity</label><input id="stockMovementQuantity" type="number" min="1" max="1000000" required></div>
          <div class="form-group" id="stockInReasonWrap"><label for="stockInReason">Reason</label><input id="stockInReason" maxlength="255" value="Supplier delivery" required></div>
          <div class="form-group" id="stockOutReasonWrap" hidden><label for="stockOutReason">Reason</label><select id="stockOutReason" required><option>Used</option><option>Damaged</option><option>Expired</option><option>Adjustment</option><option>Other</option></select></div>
          <div class="form-group full" id="stockOtherReasonWrap" hidden><label for="stockOtherReason">Specify reason</label><input id="stockOtherReason" maxlength="255"></div>
          <div class="form-group" id="stockExpirationWrap"><label for="stockMovementExpiration">Batch expiration date (optional)</label><input id="stockMovementExpiration" type="date"></div>
        </div>
        <div class="modal-actions"><button type="button" class="btn-secondary" id="cancelStockMovement">Cancel</button><button class="btn-primary" type="submit">Save movement</button></div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=14"></script>
  <script>
    let inventory = [];
    let editingItemId = null;
    let defaultMinimumQuantity = 15;

    function updateMetrics() {
      document.getElementById('total-items-count').innerText = inventory.length;
      document.getElementById('instock-count').innerText = inventory.filter(i => i.quantity > i.minThreshold).length;
      document.getElementById('lowstock-count').innerText = inventory.filter(i => i.quantity <= i.minThreshold && i.quantity > 0).length;
      document.getElementById('outstock-count').innerText = inventory.filter(i => i.quantity === 0).length;
    }

    function renderTable() {
      const tbody = document.getElementById('inventoryTableBody');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const catVal = document.getElementById('categoryFilter').value;

      tbody.innerHTML = '';

      const filtered = inventory.filter(i => {
        const matchesSearch = [i.name, i.sku, i.category].some(value => String(value).toLowerCase().includes(searchVal));
        const matchesCat = catVal === 'All' || i.category === catVal;

        return matchesSearch && matchesCat;
      });

      filtered.forEach(i => {
        let stockClass = 'stock-in';
        let stockLabel = 'In Stock';

        if (i.quantity === 0) {
          stockClass = 'stock-out';
          stockLabel = 'Out of Stock';
        } else if (i.quantity <= i.minThreshold) {
          stockClass = 'stock-low';
          stockLabel = 'Low Stock';
        }

        const row = document.createElement('tr');
        row.innerHTML = `
          <td><span class="item-name">${clinicEscape(i.name)}</span></td>
          <td><span class="sku-code">${clinicEscape(i.sku)}</span></td>
          <td><span class="category-badge">${clinicEscape(i.category)}</span></td>
          <td><strong>${i.quantity}</strong> units</td>
          <td>₱${i.price.toFixed(2)}</td>
          <td><span class="stock-badge ${stockClass}">${stockLabel}</span>${i.condition_status === 'Damaged' ? '<br><span class="stock-badge stock-out">Damaged</span>' : ''}${i.expiration_date ? `<br><small>Expires ${clinicEscape(i.expiration_date)}</small>` : ''}</td>
          <td>
            <div class="action-btn-group">
              <button class="action-btn" onclick="editItem(${i.id})" title="Edit item"><i class="fa-solid fa-pen"></i></button>
              <button class="action-btn" onclick="openStockMovement(${i.id}, 'in')" title="Stock In"><i class="fa-solid fa-circle-plus"></i></button>
              <button class="action-btn" onclick="openStockMovement(${i.id}, 'out')" title="Stock Out"><i class="fa-solid fa-circle-minus"></i></button>
              <button class="action-btn" onclick="deleteItem(${i.id})" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No inventory items yet.</td></tr>';
      }

      updateMetrics();
    }

    async function loadInventory() {
      try {
        const [inventoryResult, settingsResult, categoryResult, movementResult] = await Promise.all([
          clinicRequest('inventory'), clinicRequest('settings'), clinicRequest('catalog-categories'), clinicRequest('inventory-movements')
        ]);
        inventory = inventoryResult.data.map(item => ({
          ...item, quantity: Number(item.quantity), minThreshold: Number(item.min_threshold), price: Number(item.price)
        }));
        const movementBody = document.getElementById('stockMovementRows');
        movementBody.innerHTML = movementResult.data.length ? movementResult.data.map(movement => `<tr><td>${clinicEscape(new Date(String(movement.created_at).replace(' ', 'T')).toLocaleString())}</td><td>${clinicEscape(movement.item)}</td><td>${clinicEscape(movement.movement_type)}</td><td>${Number(movement.quantity)}</td><td>${clinicEscape(movement.reason)}${movement.expiration_date ? `<br><small>Expires ${clinicEscape(movement.expiration_date)}</small>` : ''}</td><td>${clinicEscape(movement.recorded_by)}</td></tr>`).join('') : '<tr><td colspan="6">No stock movements recorded yet.</td></tr>';
        defaultMinimumQuantity = Number(settingsResult.data.default_minimum_quantity);
        document.getElementById('itemMinimum').value = defaultMinimumQuantity;
        const categorySelect = document.getElementById('itemCategory');
        const selectedCategory = categorySelect.value;
        const inventoryCategories = categoryResult.data.filter(category => category.type === 'inventory');
        categorySelect.innerHTML = inventoryCategories.map(category => `<option value="${clinicEscape(category.name)}">${clinicEscape(category.name)}</option>`).join('');
        if (inventoryCategories.some(category => category.name === selectedCategory)) categorySelect.value = selectedCategory;
        const categoryFilter = document.getElementById('categoryFilter');
        const selectedFilter = categoryFilter.value;
        categoryFilter.innerHTML = '<option value="All">All Categories</option>' + inventoryCategories.map(category => `<option value="${clinicEscape(category.name)}">${clinicEscape(category.name)}</option>`).join('');
        if (inventoryCategories.some(category => category.name === selectedFilter)) categoryFilter.value = selectedFilter;
        renderTable();
      } catch (error) { clinicShowError(error); }
    }

    clinicOnCatalogCategoriesChanged(loadInventory);

    function openStockMovement(id, direction) {
      const item = inventory.find(i => Number(i.id) === Number(id));
      if (!item) return;
      document.getElementById('stockMovementItemId').value = item.id;
      document.getElementById('stockMovementType').value = direction;
      document.getElementById('stockMovementTitle').textContent = direction === 'in' ? 'Record Stock In' : 'Record Stock Out';
      document.getElementById('stockMovementItem').textContent = `${item.name} · ${item.quantity} available`;
      document.getElementById('stockInReasonWrap').hidden = direction !== 'in';
      document.getElementById('stockOutReasonWrap').hidden = direction !== 'out';
      document.getElementById('stockExpirationWrap').hidden = direction !== 'in';
      document.getElementById('stockInReason').required = direction === 'in';
      document.getElementById('stockOutReason').required = direction === 'out';
      document.getElementById('stockOtherReasonWrap').hidden = true;
      document.getElementById('stockOtherReason').required = false;
      document.getElementById('stockMovementForm').reset();
      document.getElementById('stockMovementType').value = direction;
      document.getElementById('stockMovementItemId').value = item.id;
      document.getElementById('stockInReason').value = 'Supplier delivery';
      document.getElementById('stockMovementModal').classList.add('active');
    }

    async function deleteItem(id) {
      if (confirm("Are you sure you want to delete this inventory item?")) {
        try {
          await clinicRequest('inventory', 'delete', { id });
          await loadInventory();
        } catch (error) { clinicShowError(error); }
      }
    }

    function editItem(id) {
      const item = inventory.find(i => Number(i.id) === Number(id));
      if (!item) return;
      editingItemId = item.id;
      document.getElementById('itemName').value = item.name;
      document.getElementById('itemCategory').value = item.category;
      document.getElementById('itemQuantity').value = item.quantity;
      document.getElementById('itemMinimum').value = item.minThreshold;
      document.getElementById('itemPrice').value = item.price;
      document.getElementById('itemExpiration').value = item.expiration_date || '';
      document.getElementById('itemCondition').value = item.condition_status || 'Good';
      document.getElementById('itemQuantity').disabled = true;
      document.querySelector('#inventoryForm label[for="itemQuantity"]').textContent = 'Current Stock Quantity';
      document.querySelector('#inventoryForm button[type="submit"]').textContent = 'Save Changes';
      modal.classList.add('active');
    }

    document.getElementById('searchInput').addEventListener('input', renderTable);
    document.getElementById('categoryFilter').addEventListener('change', renderTable);

    const modal = document.getElementById('addModal');
    document.getElementById('openAddModal').addEventListener('click', () => {
      editingItemId = null;
      document.getElementById('inventoryForm').reset();
      document.getElementById('itemMinimum').value = defaultMinimumQuantity;
      document.getElementById('itemQuantity').disabled = false;
      document.querySelector('#inventoryForm label[for="itemQuantity"]').textContent = 'Initial Stock Quantity';
      document.getElementById('itemCondition').value = 'Good';
      document.querySelector('#inventoryForm button[type="submit"]').textContent = 'Save Item';
      modal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('closeStockMovement').addEventListener('click', () => document.getElementById('stockMovementModal').classList.remove('active'));
    document.getElementById('cancelStockMovement').addEventListener('click', () => document.getElementById('stockMovementModal').classList.remove('active'));
    document.getElementById('stockOutReason').addEventListener('change', event => {
      const other = event.currentTarget.value === 'Other';
      document.getElementById('stockOtherReasonWrap').hidden = !other;
      document.getElementById('stockOtherReason').required = other;
    });
    document.getElementById('stockMovementForm').addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const direction = document.getElementById('stockMovementType').value;
      const payload = {
        id: Number(document.getElementById('stockMovementItemId').value),
        quantity: Number(document.getElementById('stockMovementQuantity').value)
      };
      if (direction === 'in') {
        payload.reason = document.getElementById('stockInReason').value;
        payload.expiration_date = document.getElementById('stockMovementExpiration').value;
      } else {
        payload.reason = document.getElementById('stockOutReason').value;
        if (payload.reason === 'Other') payload.other_reason = document.getElementById('stockOtherReason').value;
      }
      const submit = form.querySelector('button[type="submit"]');
      submit.disabled = true;
      try {
        await clinicRequest('inventory', direction === 'in' ? 'stock-in' : 'stock-out', payload);
        document.getElementById('stockMovementModal').classList.remove('active');
        await loadInventory();
      } catch (error) {
        clinicShowError(error);
      } finally {
        submit.disabled = false;
      }
    });

    document.getElementById('inventoryForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const values = {
        name: document.getElementById('itemName').value,
        category: document.getElementById('itemCategory').value,
        quantity: parseInt(document.getElementById('itemQuantity').value, 10),
        min_threshold: parseInt(document.getElementById('itemMinimum').value, 10),
        price: parseFloat(document.getElementById('itemPrice').value),
        expiration_date: document.getElementById('itemExpiration').value,
        condition_status: document.getElementById('itemCondition').value
      };
      const action = editingItemId ? 'update' : 'create';
      if (editingItemId) values.id = editingItemId;
      clinicRequest('inventory', action, values).then(async () => {
        this.reset();
        editingItemId = null;
        document.getElementById('itemQuantity').disabled = false;
        document.querySelector('#inventoryForm label[for="itemQuantity"]').textContent = 'Initial Stock Quantity';
        document.querySelector('#inventoryForm button[type="submit"]').textContent = 'Save Item';
        modal.classList.remove('active');
        await loadInventory();
      }).catch(clinicShowError);
    });

    loadInventory();
  </script>
</body>
</html>
