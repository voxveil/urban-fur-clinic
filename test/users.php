<?php
require_once __DIR__ . '/auth.php';

if (($_SESSION['user_role'] ?? '') !== 'Administrator') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Accounts - Urban Fur Veterinary Clinic</title>
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
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
      <li class="nav-item"><a href="logout.php" style="color:#ef4444"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a></li>
    </ul>
  </aside>

  <main class="main-content">
    <div class="page-header">
      <div class="page-title">
        <h1>User Accounts</h1>
        <p>Create and manage individual sign-in accounts for clinic employees.</p>
      </div>
      <button class="btn-primary" id="addUserButton"><i class="fa-solid fa-user-plus"></i> Add User Account</button>
    </div>

    <div class="settings-help user-account-help">
      <span class="settings-help-icon"><i class="fa-solid fa-circle-info"></i></span>
      <div>
        <strong>Staff record and login account are different.</strong>
        <p>Assign access by role: Admin, Vet, Receptionist, or Cashier. Only Administrators can manage accounts and clinic settings. Deactivated users are signed out when they next access the system.</p>
      </div>
    </div>
    <p class="settings-message" id="usersMessage" role="status" aria-live="polite">Loading user accounts…</p>

    <div class="table-container">
      <table>
        <thead>
          <tr><th>Name</th><th>Username</th><th>Access role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
        </thead>
        <tbody id="usersTableBody"></tbody>
      </table>
    </div>
  </main>

  <div class="modal-overlay" id="userModal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="userModalTitle">Create User Account</h2>
        <button type="button" class="close-modal user-modal-close" id="closeUserModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="userForm">
        <div class="form-grid">
          <div class="form-group full">
            <label for="userFullName">Full name</label>
            <input id="userFullName" name="full_name" type="text" maxlength="150" autocomplete="name" required>
          </div>
          <div class="form-group full">
            <label for="userUsername">Username</label>
            <input id="userUsername" name="username" type="text" minlength="3" maxlength="80" pattern="[a-zA-Z0-9_.-]{3,80}" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label for="userRole">Access role</label>
            <select id="userRole" name="role" required>
              <option value="Administrator">Admin</option>
              <option value="Vet">Vet</option>
              <option value="Receptionist">Receptionist</option>
              <option value="Cashier">Cashier</option>
              <option value="Staff">Staff (legacy full access)</option>
            </select>
          </div>
          <div class="form-group">
            <label for="userActive">Account status</label>
            <select id="userActive" name="active" required>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <div class="form-group full">
            <label for="userPassword">Password <span id="passwordRequirement">(at least 12 characters)</span></label>
            <input id="userPassword" name="password" type="password" minlength="12" maxlength="255" autocomplete="new-password">
            <small id="passwordHelp">Required for a new account. Leave blank when editing to keep the current password.</small>
          </div>
          <div class="form-group full" id="confirmPasswordGroup">
            <label for="userPasswordConfirm">Confirm password</label>
            <input id="userPasswordConfirm" name="password_confirm" type="password" minlength="12" maxlength="255" autocomplete="new-password">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" id="cancelUserModal">Cancel</button>
          <button class="btn-primary" id="saveUserButton" type="submit">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js?v=5"></script>
  <script>
    const currentUserId = <?= (int) $_SESSION['user_id'] ?>;
    const userModal = document.getElementById('userModal');
    const userForm = document.getElementById('userForm');
    const usersMessage = document.getElementById('usersMessage');
    let users = [];
    let editingUser = null;

    function setUsersMessage(message, isError = false) {
      usersMessage.textContent = message;
      usersMessage.classList.toggle('settings-error', isError);
    }

    function renderUsers() {
      const body = document.getElementById('usersTableBody');
      if (users.length === 0) {
        body.innerHTML = '<tr><td colspan="6">No user accounts found.</td></tr>';
        return;
      }
      body.innerHTML = users.map(user => `
        <tr>
          <td>${clinicEscape(user.full_name)}${Number(user.id) === currentUserId ? ' <span class="category-badge">You</span>' : ''}</td>
          <td>${clinicEscape(user.username)}</td>
          <td>${clinicEscape(user.role === 'Administrator' ? 'Admin' : user.role)}</td>
          <td><span class="status-badge ${Number(user.active) === 1 ? 'status-active' : 'status-inactive'}">${Number(user.active) === 1 ? 'Active' : 'Inactive'}</span></td>
          <td>${clinicEscape(user.created_at)}</td>
          <td class="user-actions">
            <button class="action-btn" type="button" onclick="editUser(${Number(user.id)})" title="Edit account"><i class="fa-solid fa-pen"></i></button>
            <button class="action-btn" type="button" onclick="toggleUserStatus(${Number(user.id)})" title="${Number(user.active) === 1 ? 'Deactivate' : 'Activate'} account"><i class="fa-solid ${Number(user.active) === 1 ? 'fa-user-slash' : 'fa-user-check'}"></i></button>
          </td>
        </tr>
      `).join('');
    }

    async function loadUsers() {
      try {
        const result = await clinicRequest('users');
        users = result.data;
        renderUsers();
        setUsersMessage(`${users.length} user account${users.length === 1 ? '' : 's'} found.`);
      } catch (error) {
        setUsersMessage(error.message, true);
      }
    }

    function openUserModal(user = null) {
      editingUser = user;
      userForm.reset();
      document.getElementById('userModalTitle').textContent = user ? 'Edit User Account' : 'Create User Account';
      document.getElementById('saveUserButton').textContent = user ? 'Save Changes' : 'Create Account';
      document.getElementById('userFullName').value = user ? user.full_name : '';
      document.getElementById('userUsername').value = user ? user.username : '';
      document.getElementById('userRole').value = user ? user.role : 'Receptionist';
      document.getElementById('userActive').value = user ? String(user.active) : '1';
      document.getElementById('userPassword').required = !user;
      document.getElementById('userPasswordConfirm').required = !user;
      document.getElementById('passwordRequirement').textContent = user ? '(optional; at least 12 characters if changed)' : '(at least 12 characters)';
      document.getElementById('userRole').disabled = Boolean(user && Number(user.id) === currentUserId);
      document.getElementById('userActive').disabled = Boolean(user && Number(user.id) === currentUserId);
      userModal.classList.add('active');
    }

    function editUser(id) {
      const user = users.find(item => Number(item.id) === Number(id));
      if (user) openUserModal(user);
    }

    async function toggleUserStatus(id) {
      const user = users.find(item => Number(item.id) === Number(id));
      if (!user || Number(user.id) === currentUserId) return;
      const nextStatus = Number(user.active) === 1 ? 0 : 1;
      if (nextStatus === 0 && !confirm(`Deactivate ${user.full_name}'s account? They will no longer be able to sign in.`)) return;
      try {
        await clinicRequest('users', 'update', {
          id: user.id,
          username: user.username,
          full_name: user.full_name,
          role: user.role,
          active: nextStatus,
          password: ''
        });
        await loadUsers();
      } catch (error) {
        setUsersMessage(error.message, true);
      }
    }

    document.getElementById('addUserButton').addEventListener('click', () => openUserModal());
    document.getElementById('closeUserModal').addEventListener('click', () => userModal.classList.remove('active'));
    document.getElementById('cancelUserModal').addEventListener('click', () => userModal.classList.remove('active'));
    userForm.addEventListener('submit', async event => {
      event.preventDefault();
      const payload = {
        username: document.getElementById('userUsername').value.trim(),
        full_name: document.getElementById('userFullName').value.trim(),
        role: document.getElementById('userRole').value,
        active: Number(document.getElementById('userActive').value),
        password: document.getElementById('userPassword').value
      };
      const passwordConfirm = document.getElementById('userPasswordConfirm').value;
      if (payload.password !== passwordConfirm) {
        setUsersMessage('The passwords do not match.', true);
        return;
      }
      const action = editingUser ? 'update' : 'create';
      if (editingUser) payload.id = editingUser.id;
      const saveButton = document.getElementById('saveUserButton');
      saveButton.disabled = true;
      try {
        await clinicRequest('users', action, payload);
        userModal.classList.remove('active');
        setUsersMessage(editingUser ? 'User account updated.' : 'User account created.');
        await loadUsers();
      } catch (error) {
        setUsersMessage(error.message, true);
      } finally {
        saveButton.disabled = false;
      }
    });

    loadUsers();
  </script>
</body>
</html>
