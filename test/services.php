<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> Services - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=15">
</head>
<body>
  <!-- Sidebar Navigation -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <i class="fa-solid fa-paw"></i> Urban Fur
    </div>

    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item active"><a href="services.php"><i class="fa-solid fa-cash-register"></i>Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1>Services</h1>
        <p>Process service billing, medications, and retail items</p>
      </div>
      <button class="btn-primary" id="openServiceModal"><i class="fa-solid fa-plus"></i> Add Service</button>
    </div>

    <div class="pos-container">
      <!-- Left: Service/Product Catalog -->
      <div class="pos-catalog">
        <div class="table-controls">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search service or item name...">
          </div>
          <select class="filter-select" id="categoryFilter">
            <option value="All">All Categories</option>
            <option value="Veterinary Services">Veterinary Services</option>
            <option value="Grooming Services">Grooming Services</option>
            <option value="Tablets/Capsule">Tablets/Capsule</option>
            <option value="Syrup">Syrup</option>
            <option value="Other Treatments">Other Treatments</option>
            <option value="Dog Food and Treats">Dog Food and Treats</option>
            <option value="Grooming Products">Grooming Products</option>
            <option value="Accessories">Accessories</option>
          </select>
        </div>

        <div class="pos-grid" id="posGrid"></div>
      </div>

      <!-- Right: Receipt & Cart -->
      <div class="pos-receipt">
        <div>
          <div class="receipt-header">
            <h3><i class="fa-solid fa-receipt"></i> Current Order</h3>
            <button class="btn-clear-cart" id="clearCartBtn"><i class="fa-solid fa-trash-can"></i> Clear</button>
          </div>
          <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Transaction #: <span id="trans-id"></span></p>
          
          <div class="cart-items" id="cartItems">
            <p style="text-align: center; color: #94a3b8; margin-top: 30px;">No items added yet</p>
          </div>
        </div>

        <div class="checkout-summary">
          <div class="form-group">
            <label for="checkoutClient">Client</label>
            <select id="checkoutClient" class="pos-input" required></select>
          </div>
          <div class="form-group">
            <label for="checkoutPet">Patient</label>
            <select id="checkoutPet" class="pos-input" required></select>
          </div>
          <!-- Discount & Payment Controls -->
          <div class="form-row">
            <div class="form-group">
              <label for="discountSelect">Discount</label>
              <select id="discountSelect" class="pos-input">
                <option value="0">None (0%)</option>
                <option value="0.20">Senior / PWD (20%)</option>
                <option value="0.10">Promo (10%)</option>
                <option value="0.05">Member (5%)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="paymentMethod">Payment Method</label>
              <select id="paymentMethod" class="pos-input">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash / Maya</option>
                <option value="Card">Credit/Debit Card</option>
                <option value="Pay Later">Pay Later</option>
              </select>
            </div>
          </div>

          <div class="summary-row">
            <span>Subtotal:</span>
            <span id="subtotal-val">₱0.00</span>
          </div>
          <div class="summary-row">
            <span>Discount:</span>
            <span id="discount-val" style="color: #ef4444;">-₱0.00</span>
          </div>
          <div class="summary-row" id="taxRow" hidden>
            <span id="taxLabel">Tax:</span>
            <span id="tax-val">₱0.00</span>
          </div>
          <div class="summary-row total">
            <span>Total:</span>
            <span id="total-val">₱0.00</span>
          </div>

          <div class="form-group" id="cashPaymentGroup" style="margin-top: 5px;">
            <label for="cashInput">Amount Rendered (₱):</label>
            <input type="number" id="cashInput" class="pos-input" placeholder="0.00" min="0" step="any">
          </div>

          <div class="summary-row" id="changeRow">
            <span>Change:</span>
            <strong id="change-val" style="color: #16a34a;">₱0.00</strong>
          </div>

          <button class="btn-checkout" id="checkoutBtn" disabled><i class="fa-solid fa-circle-check"></i> Complete Transaction</button>
        </div>
      </div>
    </div>
  </main>

  <!-- Thermal Receipt Modal -->
  <div class="modal-overlay" id="receiptModal">
    <div class="modal-content">
      <div class="receipt-print" id="printableReceipt">
        <img id="receiptClinicLogo" class="receipt-clinic-logo" alt="Clinic logo" hidden>
        <h2 id="receiptClinicName">URBAN FUR CLINIC</h2>
        <div id="receiptClinicPhone" style="text-align:center;"></div>
        <div id="receiptClinicAddress" style="text-align:center;"></div>
        <div class="receipt-divider"></div>
        <div>Trans: <span id="modal-trx"></span></div>
        <div>Date: <span id="modal-date"></span></div>
        <div>Method: <span id="modal-method"></span></div>
        <div>Status: <span id="modal-status"></span></div>
        <div class="receipt-divider"></div>
        <div id="modal-items"></div>
        <div class="receipt-divider"></div>
        <div>Subtotal: <span id="modal-subtotal" style="float:right;"></span></div>
        <div>Discount: <span id="modal-discount" style="float:right;"></span></div>
        <div id="modal-tax-row" hidden>Tax: <span id="modal-tax" style="float:right;"></span></div>
        <div style="font-weight: bold; margin-top: 4px;">TOTAL: <span id="modal-total" style="float:right;"></span></div>
        <div>Paid: <span id="modal-paid" style="float:right;"></span></div>
        <div>Balance: <span id="modal-balance" style="float:right;"></span></div>
        <div>Change: <span id="modal-change" style="float:right;"></span></div>
        <div class="receipt-divider"></div>
        <p style="text-align: center; margin-top: 10px;">Thank you.</p>
      </div>
      <div class="modal-actions">
        <button class="btn-modal btn-modal-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        <button class="btn-modal btn-modal-close" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="serviceModal">
    <div class="modal">
      <div class="modal-header">
        <h2>Add Clinic Service</h2>
        <i class="fa-solid fa-xmark close-modal" id="closeServiceModal"></i>
      </div>
      <form id="serviceForm">
        <div class="form-grid">
          <div class="form-group full">
            <label>Service Name</label>
            <input type="text" id="serviceName" maxlength="150" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <input type="text" id="serviceCategory" maxlength="100" required>
          </div>
          <div class="form-group">
            <label>Price (₱)</label>
            <input type="number" id="servicePrice" min="0" step="0.01" required>
          </div>
          <div class="form-group full">
            <label>Description (optional)</label>
            <input type="text" id="serviceDescription" maxlength="255">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelServiceModal">Cancel</button>
          <button type="submit" class="btn-primary">Save Service</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    let items = [];
    let cart = [];
    let checkoutPets = [];
    let checkoutTaxRate = 0;
    document.getElementById('trans-id').innerText = '#TRX-' + Date.now();

    function renderCatalog() {
      const grid = document.getElementById('posGrid');
      const searchVal = document.getElementById('searchInput').value.toLowerCase();
      const catVal = document.getElementById('categoryFilter').value;
      grid.innerHTML = '';

      const filtered = items.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(searchVal);
        const matchesCat = catVal === 'All' || item.category === catVal;
        return matchesSearch && matchesCat;
      });

      filtered.forEach(item => {
        const card = document.createElement('div');
        card.className = 'pos-card';
        card.onclick = () => addToCart(item.item_type, item.id);
        
        const stockBadge = item.item_type === 'service'
          ? (Number(item.active) === 1 ? 'Service' : 'Inactive')
          : `Stock: ${item.stock}`;

        card.innerHTML = `
          <div>
            <div class="card-title">${clinicEscape(item.name)}</div>
            <div class="card-cat">${clinicEscape(item.category)}</div>
          </div>
          <div class="card-footer-info">
            <div class="card-price">₱${item.price.toFixed(2)}</div>
            <span class="stock-tag">${stockBadge}</span>
          </div>
        `;
        grid.appendChild(card);
      });
      if (filtered.length === 0) {
        grid.innerHTML = '<p>No services or items have been added yet.</p>';
      }
    }

    function addToCart(type, id) {
      const selectedItem = items.find(i => i.item_type === type && Number(i.id) === Number(id));
      if (!selectedItem || (type === 'service' && Number(selectedItem.active) !== 1)) {
        alert('This service is inactive and cannot be added to an order.');
        return;
      }
      const existing = cart.find(i => i.item_type === type && Number(i.id) === Number(id));
      if (existing) {
        if (type === 'inventory' && existing.qty >= Number(existing.stock)) {
          alert('Not enough stock is available.');
          return;
        }
        existing.qty += 1;
      } else {
        const item = items.find(i => i.item_type === type && Number(i.id) === Number(id));
        cart.push({ ...item, qty: 1 });
      }
      renderCart();
    }

    function updateQty(type, id, delta) {
      const item = cart.find(i => i.item_type === type && Number(i.id) === Number(id));
      if (item) {
        if (type === 'inventory' && item.qty + delta > Number(item.stock)) {
          alert('Not enough stock is available.');
          return;
        }
        item.qty += delta;
        if (item.qty <= 0) {
          removeFromCart(type, id);
          return;
        }
      }
      renderCart();
    }

    function removeFromCart(type, id) {
      cart = cart.filter(i => !(i.item_type === type && Number(i.id) === Number(id)));
      renderCart();
    }

    function renderCart() {
      const container = document.getElementById('cartItems');
      if (cart.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #94a3b8; margin-top: 30px;">No items added yet</p>';
      } else {
        container.innerHTML = '';
        cart.forEach(item => {
          const div = document.createElement('div');
          div.className = 'cart-item';
          div.innerHTML = `
            <div class="cart-item-info">
              <div class="cart-item-title">${clinicEscape(item.name)}</div>
              <div style="color: #64748b;">₱${item.price.toFixed(2)}</div>
              <div class="cart-item-qty">
                <button class="qty-btn" onclick="updateQty('${item.item_type}', ${item.id}, -1)">-</button>
                <span>${item.qty}</span>
                <button class="qty-btn" onclick="updateQty('${item.item_type}', ${item.id}, 1)">+</button>
                <button class="btn-remove-item" onclick="removeFromCart('${item.item_type}', ${item.id})" title="Remove item">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
            <div style="font-weight:600; font-size: 14px;">₱${(item.price * item.qty).toFixed(2)}</div>
          `;
          container.appendChild(div);
        });
      }
      calculateTotals();
    }

    function calculateTotals() {
      const rawSubtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
      const discountRate = parseFloat(document.getElementById('discountSelect').value) || 0;
      
      const discountAmount = Math.round(rawSubtotal * discountRate * 100) / 100;
      const discountedSubtotal = rawSubtotal - discountAmount;
      const taxAmount = Math.round(discountedSubtotal * checkoutTaxRate) / 100;
      const total = discountedSubtotal + taxAmount;

      document.getElementById('subtotal-val').innerText = '₱' + rawSubtotal.toFixed(2);
      document.getElementById('discount-val').innerText = '-₱' + discountAmount.toFixed(2);
      document.getElementById('tax-val').innerText = '₱' + taxAmount.toFixed(2);
      document.getElementById('taxLabel').textContent = `Tax (${checkoutTaxRate}%):`;
      document.getElementById('taxRow').hidden = taxAmount === 0;
      document.getElementById('total-val').innerText = '₱' + total.toFixed(2);

      calculateChange(total);
    }

    function calculateChange(total) {
      const cash = parseFloat(document.getElementById('cashInput').value) || 0;
      const change = cash >= total ? cash - total : 0;
      document.getElementById('change-val').innerText = '₱' + change.toFixed(2);
    }

    async function loadCatalog() {
      try {
        const [catalogResult, clientResult, petResult, settingsResult] = await Promise.all([
          clinicRequest('catalog'), clinicRequest('clients-options'), clinicRequest('pets-options'), clinicRequest('settings')
        ]);
        items = catalogResult.data;
        document.getElementById('receiptClinicName').textContent = settingsResult.data.clinic_name;
        const receiptLogo = document.getElementById('receiptClinicLogo');
        receiptLogo.src = settingsResult.data.clinic_logo_url || '';
        receiptLogo.hidden = !settingsResult.data.clinic_logo_url;
        checkoutTaxRate = Number(settingsResult.data.tax_rate) || 0;
        const paymentNames = { Cash: 'Cash', Card: 'Credit/Debit Card', GCash: 'GCash / Maya', 'Pay Later': 'Pay Later' };
        const paymentSelect = document.getElementById('paymentMethod');
        paymentSelect.innerHTML = settingsResult.data.accepted_payment_methods
          .map(method => `<option value="${clinicEscape(method)}">${clinicEscape(paymentNames[method] || method)}</option>`).join('');
        paymentSelect.dispatchEvent(new Event('change'));
        const receiptPhone = document.getElementById('receiptClinicPhone');
        receiptPhone.textContent = settingsResult.data.phone;
        receiptPhone.hidden = !settingsResult.data.phone;
        const receiptAddress = document.getElementById('receiptClinicAddress');
        receiptAddress.textContent = settingsResult.data.address;
        receiptAddress.hidden = !settingsResult.data.address;
        document.getElementById('checkoutBtn').disabled = false;
        const clientSelect = document.getElementById('checkoutClient');
        clientSelect.innerHTML = '<option value="">Choose client</option>' + clientResult.data.map(client => `<option value="${client.id}">${clinicEscape(client.name)}</option>`).join('');
        checkoutPets = petResult.data;
        updateCheckoutPets();
        const categories = [...new Set(items.map(item => item.category))];
        document.getElementById('categoryFilter').innerHTML = '<option value="All">All Categories</option>' + categories.map(category => `<option value="${clinicEscape(category)}">${clinicEscape(category)}</option>`).join('');
        renderCatalog();
      } catch (error) { clinicShowError(error); }
    }

    function updateCheckoutPets() {
      const clientId = document.getElementById('checkoutClient').value;
      const pets = checkoutPets.filter(pet => String(pet.client_id) === String(clientId));
      document.getElementById('checkoutPet').innerHTML = '<option value="">Choose patient</option>' + pets.map(pet => `<option value="${pet.id}">${clinicEscape(pet.name)}</option>`).join('');
    }

    document.getElementById('cashInput').addEventListener('input', () => calculateTotals());
    document.getElementById('discountSelect').addEventListener('change', () => calculateTotals());
    document.getElementById('paymentMethod').addEventListener('change', () => {
      const isCash = document.getElementById('paymentMethod').value === 'Cash';
      document.getElementById('cashPaymentGroup').hidden = !isCash;
      document.getElementById('changeRow').hidden = !isCash;
      if (!isCash) document.getElementById('cashInput').value = '';
      calculateTotals();
    });
    document.getElementById('searchInput').addEventListener('input', renderCatalog);
    document.getElementById('categoryFilter').addEventListener('change', renderCatalog);

    document.getElementById('clearCartBtn').addEventListener('click', () => {
      if (cart.length > 0 && confirm("Are you sure you want to clear the cart?")) {
        cart = [];
        renderCart();
      }
    });

    document.getElementById('checkoutClient').addEventListener('change', updateCheckoutPets);
    document.getElementById('checkoutBtn').addEventListener('click', async () => {
      if (cart.length === 0) {
        alert("Please add items to the cart first!");
        return;
      }
      const clientId = document.getElementById('checkoutClient').value;
      const petId = document.getElementById('checkoutPet').value;
      if (!clientId || !petId) {
        alert('Choose a client and patient before completing the transaction.');
        return;
      }
      
      const rawSubtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
      const discountRate = parseFloat(document.getElementById('discountSelect').value) || 0;
      const discount = Math.round(rawSubtotal * discountRate * 100) / 100;
      const total = rawSubtotal - discount;
      const cash = parseFloat(document.getElementById('cashInput').value) || 0;
      const paymentMethod = document.getElementById('paymentMethod').value;

      if (paymentMethod === 'Cash' && cash < total) {
        alert("Insufficient payment amount!");
        return;
      }

      try {
        const result = await clinicRequest('checkout', 'create', {
          client_id: clientId,
          pet_id: petId,
          payment_method: paymentMethod,
          discount_rate: discountRate,
          cash_paid: paymentMethod === 'Cash' ? cash : null,
          items: cart.map(item => ({ id: item.id, item_type: item.item_type, qty: item.qty }))
        });
        showReceiptModal(result);
        await loadCatalog();
      } catch (error) { clinicShowError(error); }
    });

    function showReceiptModal(result) {
      document.getElementById('modal-trx').innerText = `INV-${new Date().getFullYear()}-${String(result.id).padStart(5, '0')}`;
      document.getElementById('modal-date').innerText = new Date().toLocaleDateString() + " " + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      document.getElementById('modal-method').innerText = document.getElementById('paymentMethod').value;
      document.getElementById('modal-status').innerText = result.status;
      
      const itemsContainer = document.getElementById('modal-items');
      itemsContainer.innerHTML = result.items.map(item => `
          <div style="display:flex; justify-content:space-between;">
            <span>${item.quantity}x ${clinicEscape(item.description)}</span>
            <span>₱${(item.price * item.quantity).toFixed(2)}</span>
          </div>
        `).join('');

      document.getElementById('modal-subtotal').innerText = '₱' + result.subtotal.toFixed(2);
      document.getElementById('modal-discount').innerText = '-₱' + result.discount.toFixed(2);
      document.getElementById('modal-tax').innerText = `₱${result.vat.toFixed(2)} (${result.tax_rate}%)`;
      document.getElementById('modal-tax-row').hidden = result.vat === 0;
      document.getElementById('modal-total').innerText = '₱' + result.total.toFixed(2);
      document.getElementById('modal-paid').innerText = '₱' + result.paid.toFixed(2);
      document.getElementById('modal-balance').innerText = '₱' + (result.total - result.paid).toFixed(2);
      document.getElementById('modal-change').innerText = '₱' + result.change.toFixed(2);

      document.getElementById('receiptModal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('receiptModal').classList.remove('active');
      cart = [];
      document.getElementById('cashInput').value = '';
      document.getElementById('paymentMethod').value = 'Cash';
      document.getElementById('cashPaymentGroup').hidden = false;
      document.getElementById('changeRow').hidden = false;
      document.getElementById('trans-id').innerText = '#TRX-' + Date.now();
      renderCart();
    }

    const serviceModal = document.getElementById('serviceModal');
    document.getElementById('openServiceModal').addEventListener('click', () => {
      document.getElementById('serviceForm').reset();
      document.querySelector('#serviceForm button[type="submit"]').textContent = 'Save Service';
      serviceModal.classList.add('active');
    });
    document.getElementById('closeServiceModal').addEventListener('click', () => serviceModal.classList.remove('active'));
    document.getElementById('cancelServiceModal').addEventListener('click', () => serviceModal.classList.remove('active'));
    document.getElementById('serviceForm').addEventListener('submit', async function(event) {
      event.preventDefault();
      try {
        const values = {
          name: document.getElementById('serviceName').value,
          category: document.getElementById('serviceCategory').value,
          price: document.getElementById('servicePrice').value,
          description: document.getElementById('serviceDescription').value
        };
        await clinicRequest('services', 'create', values);
        this.reset();
        document.querySelector('#serviceForm button[type="submit"]').textContent = 'Save Service';
        serviceModal.classList.remove('active');
        await loadCatalog();
      } catch (error) { clinicShowError(error); }
    });

    loadCatalog();
    renderCart();
  </script>
</body>
</html>