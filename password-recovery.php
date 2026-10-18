<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
if (!isset($_SESSION['recovery_form_token']) || !is_string($_SESSION['recovery_form_token'])) {
    $_SESSION['recovery_form_token'] = bin2hex(random_bytes(32));
}
$returnPage = ($_GET['return'] ?? '') === 'client' ? 'client-auth.php' : 'index.php';
?>
<!doctype html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password Recovery · Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="client-portal.css">
  <link rel="stylesheet" href="client-theme.css?v=4">
</head>
<body class="client-auth-page">
  <main class="auth-card recovery-card">
    <a class="client-brand" href="<?= htmlspecialchars($returnPage, ENT_QUOTES, 'UTF-8') ?>"><img class="client-brand-logo client-brand-logo-register" src="assets/clinic-logo.png" alt="Urban Fur Veterinary Clinic logo"><span>Urban Fur<small>Veterinary Clinic</small></span></a>
    <p class="eyebrow">ACCOUNT SECURITY</p>
    <h1>Password recovery</h1>
    <p class="muted">Verify your registered email or mobile number to create a new password.</p>
    <div id="recoveryMessage" class="notice" role="status" aria-live="polite" hidden></div>
    <section id="recoverySendStage" class="recovery-stage">
      <form id="recoverySendForm" class="client-form">
        <label for="recoveryIdentifier">Registered email or mobile number</label>
        <input id="recoveryIdentifier" name="identifier" type="text" autocomplete="username" maxlength="190" required>
        <button class="button primary full" type="submit">Send verification code</button>
      </form>
    </section>
    <section id="recoveryVerifyStage" class="recovery-stage" hidden>
      <p class="muted">Enter the six-digit code sent to your registered contact.</p>
      <form id="recoveryVerifyForm" class="client-form">
        <label for="recoveryCode">Verification code</label>
        <input id="recoveryCode" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
        <button class="button primary full" type="submit">Verify code</button>
      </form>
      <button class="recovery-text-button" id="resendRecoveryCode" type="button">Send another code</button>
    </section>
    <section id="recoveryResetStage" class="recovery-stage" hidden>
      <form id="recoveryResetForm" class="client-form">
        <label for="recoveryNewPassword">New password</label>
        <span class="recovery-password-wrap"><input id="recoveryNewPassword" name="new_password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required><button class="recovery-eye" type="button" data-password-toggle="recoveryNewPassword" aria-label="Show new password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button></span>
        <small>Use 12–255 characters with uppercase, lowercase, a number, and a symbol.</small>
        <label for="recoveryConfirmPassword">Confirm new password</label>
        <span class="recovery-password-wrap"><input id="recoveryConfirmPassword" name="confirm_password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required><button class="recovery-eye" type="button" data-password-toggle="recoveryConfirmPassword" aria-label="Show confirmation password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button></span>
        <button class="button primary full" type="submit">Save new password</button>
      </form>
    </section>
    <a class="staff-link recovery-login-link" href="<?= htmlspecialchars($returnPage, ENT_QUOTES, 'UTF-8') ?>">Back to sign in</a>
  </main>
  <script>
    const recoveryToken = <?= json_encode($_SESSION['recovery_form_token'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const recoveryMessage = document.getElementById('recoveryMessage');
    const stages = {
      send: document.getElementById('recoverySendStage'),
      verify: document.getElementById('recoveryVerifyStage'),
      reset: document.getElementById('recoveryResetStage')
    };
    function showRecoveryMessage(message, error = false) {
      recoveryMessage.textContent = message;
      recoveryMessage.className = `notice ${error ? 'error' : 'success'}`;
      recoveryMessage.hidden = false;
    }
    function showRecoveryStage(name) {
      Object.entries(stages).forEach(([key, stage]) => { stage.hidden = key !== name; });
      recoveryMessage.hidden = true;
    }
    async function recoveryRequest(action, payload = {}) {
      const response = await fetch('password-recovery-api.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': recoveryToken },
        body: JSON.stringify({ action, ...payload })
      });
      let result;
      try {
        result = await response.json();
      } catch {
        throw new Error(`The server returned an invalid response (HTTP ${response.status}).`);
      }
      if (!response.ok) throw new Error(result.error || `Request failed (HTTP ${response.status}).`);
      return result;
    }
    function bindRecoveryForm(form, action, getPayload, onSuccess) {
      form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
          const result = await recoveryRequest(action, getPayload());
          onSuccess(result);
        } catch (error) {
          showRecoveryMessage(error.message, true);
        } finally {
          button.disabled = false;
        }
      });
    }
    bindRecoveryForm(document.getElementById('recoverySendForm'), 'send',
      () => ({ identifier: document.getElementById('recoveryIdentifier').value.trim() }),
      result => { showRecoveryStage('verify'); showRecoveryMessage(result.message); });
    bindRecoveryForm(document.getElementById('recoveryVerifyForm'), 'verify',
      () => ({ code: document.getElementById('recoveryCode').value.trim() }),
      () => { showRecoveryStage('reset'); showRecoveryMessage('Identity verified. Choose your new password.'); });
    bindRecoveryForm(document.getElementById('recoveryResetForm'), 'reset',
      () => ({
        new_password: document.getElementById('recoveryNewPassword').value,
        confirm_password: document.getElementById('recoveryConfirmPassword').value
      }),
      () => {
        showRecoveryMessage('Your password was reset successfully. You can now sign in.');
        document.getElementById('recoveryResetStage').hidden = true;
        document.querySelector('.recovery-login-link').textContent = 'Sign in with your new password';
      });
    document.getElementById('resendRecoveryCode').addEventListener('click', async () => {
      try {
        const result = await recoveryRequest('send', { identifier: document.getElementById('recoveryIdentifier').value.trim() });
        showRecoveryMessage(result.message);
      } catch (error) {
        showRecoveryMessage(error.message, true);
      }
    });
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.querySelector('i').classList.toggle('fa-eye', !visible);
        button.querySelector('i').classList.toggle('fa-eye-slash', visible);
      });
    });
  </script>
</body>
</html>
