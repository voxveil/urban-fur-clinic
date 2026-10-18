<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory - Urban Fur Veterinary Clinic</title>
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
        <option value="Medications">Medications</option>
        <option value="Vaccines">Vaccines</option>
        <option value="Surgical Supplies">Surgical Supplies</option>
        <option value="Pet Food & Care">Pet Food & Care</option>
      </select>
    </div>

    <!-- Inventory Directory Table -->
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Item Details</th>
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
            <label>SKU Code</label>
            <input type="text" id="itemSku" required placeholder="Enter SKU">
          </div>
          <div class="form-group">
            <label>Category</label>
            <input type="text" id="itemCategory" maxlength="100" required placeholder="Enter category">
          </div>
          <div class="form-group">
            <label>Stock Quantity</label>
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
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn-primary">Save Item</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
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
          <td>
            <span class="item-name">${clinicEscape(i.name)}</span>
            <span class="sku-code">SKU: ${clinicEscape(i.sku)}</span>
          </td>
          <td><span class="category-badge">${clinicEscape(i.category)}</span></td>
          <td><strong>${i.quantity}</strong> units</td>
          <td>₱${i.price.toFixed(2)}</td>
          <td><span class="stock-badge ${stockClass}">${stockLabel}</span></td>
          <td>
            <button class="action-btn" onclick="editItem(${i.id})" title="Edit item"><i class="fa-solid fa-pen"></i></button>
            <button class="action-btn" onclick="adjustStock(${i.id}, 10)" title="Add +10 Stock"><i class="fa-solid fa-square-plus"></i></button>
            <button class="action-btn" onclick="deleteItem(${i.id})" title="Remove Item"><i class="fa-solid fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(row);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">No inventory items yet.</td></tr>';
      }

      updateMetrics();
    }

    async function loadInventory() {
      try {
        const [inventoryResult, settingsResult] = await Promise.all([clinicRequest('inventory'), clinicRequest('settings')]);
        inventory = inventoryResult.data.map(item => ({
          ...item, quantity: Number(item.quantity), minThreshold: Number(item.min_threshold), price: Number(item.price)
        }));
        defaultMinimumQuantity = Number(settingsResult.data.default_minimum_quantity);
        document.getElementById('itemMinimum').value = defaultMinimumQuantity;
        const categories = [...new Set([
          'Veterinary Services',
          'Grooming Services',
          'Tablets/Capsule',
          'Syrup',
          'Other Treatments',
          'Dog Food and Treats',
          'Grooming Products',
          'Accessories',
          ...inventory.map(item => item.category)
        ])].sort((a, b) => a.localeCompare(b));
        const categoryFilter = document.getElementById('categoryFilter');
        categoryFilter.innerHTML = '<option value="All">All Categories</option>' + categories.map(category => `<option value="${clinicEscape(category)}">${clinicEscape(category)}</option>`).join('');
        renderTable();
      } catch (error) { clinicShowError(error); }
    }

    async function adjustStock(id, amount) {
      const item = inventory.find(i => Number(i.id) === Number(id));
      if (item) {
        try {
          await clinicRequest('inventory', 'adjust', { id: item.id, amount });
          await loadInventory();
        } catch (error) { clinicShowError(error); }
      }
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
      document.getElementById('itemSku').value = item.sku;
      document.getElementById('itemCategory').value = item.category;
      document.getElementById('itemQuantity').value = item.quantity;
      document.getElementById('itemMinimum').value = item.minThreshold;
      document.getElementById('itemPrice').value = item.price;
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
      document.querySelector('#inventoryForm button[type="submit"]').textContent = 'Save Item';
      modal.classList.add('active');
    });
    document.getElementById('closeAddModal').addEventListener('click', () => modal.classList.remove('active'));
    document.getElementById('cancelBtn').addEventListener('click', () => modal.classList.remove('active'));

    document.getElementById('inventoryForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const values = {
        name: document.getElementById('itemName').value,
        sku: document.getElementById('itemSku').value,
        category: document.getElementById('itemCategory').value,
        quantity: parseInt(document.getElementById('itemQuantity').value, 10),
        min_threshold: parseInt(document.getElementById('itemMinimum').value, 10),
        price: parseFloat(document.getElementById('itemPrice').value)
      };
      const action = editingItemId ? 'update' : 'create';
      if (editingItemId) values.id = editingItemId;
      clinicRequest('inventory', action, values).then(async () => {
        this.reset();
        editingItemId = null;
        document.querySelector('#inventoryForm button[type="submit"]').textContent = 'Save Item';
        modal.classList.remove('active');
        await loadInventory();
      }).catch(clinicShowError);
    });

    loadInventory();
  </script>
</body>
</html>