<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (isset($_SESSION['client_user_id'])) {
    header('Location: client-portal.php');
    exit;
}
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
if ($mode !== 'register' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Location: index.php?role=client');
    exit;
}
if (!isset($_SESSION['client_form_token'])) {
    $_SESSION['client_form_token'] = bin2hex(random_bytes(32));
}
$error = (($_GET['account'] ?? '') === 'inactive') ? 'Your client account is unavailable. Please contact the clinic.' : '';
$values = ['full_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'username' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $mode = ($_POST['mode'] ?? '') === 'register' ? 'register' : 'login';
    if (!hash_equals((string) $_SESSION['client_form_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form expired. Please reload and try again.';
    } elseif ($mode === 'register') {
        foreach ($values as $field => $_) {
            $values[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $password = (string) ($_POST['password'] ?? '');
        if (!preg_match('/^09[0-9]{9}$/', $values['phone'])) {
            $error = 'Contact number must be exactly 11 digits in 09XXXXXXXXX format.';
        } elseif (!preg_match("/^[\\p{L}]+(?:[ '\\x{2019}-][\\p{L}]+)*$/u", $values['full_name']) || mb_strlen($values['full_name'], 'UTF-8') > 150
            || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email'], 'UTF-8') > 190
            || $values['address'] === '' || mb_strlen($values['address'], 'UTF-8') > 255
            || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $values['username'])
            || mb_strlen($password, 'UTF-8') < 8 || mb_strlen($password, 'UTF-8') > 255) {
            $error = 'Complete all required fields. Use a valid email, a 3–80 character username, and a password of at least 8 characters.';
        } else {
            try {
                $emailCheck = $mysqli->prepare('SELECT id, phone, status, account_user_id FROM clients WHERE email = ? LIMIT 1');
                $emailCheck->bind_param('s', $values['email']);
                $emailCheck->execute();
                $existingClient = $emailCheck->get_result()->fetch_assoc();
                $emailCheck->close();
                if (is_array($existingClient) && $existingClient['account_user_id'] !== null) {
                    throw new DomainException('An account with that email already exists. Please sign in.');
                }
                if (is_array($existingClient)) {
                    if ($existingClient['status'] !== 'Active') {
                        throw new DomainException('That clinic record is inactive. Contact the clinic to link your account.');
                    }
                    $storedPhone = preg_replace('/\D+/', '', (string) $existingClient['phone']);
                    $submittedPhone = preg_replace('/\D+/', '', $values['phone']);
                    if ($storedPhone === '' || !hash_equals($storedPhone, $submittedPhone)) {
                        throw new DomainException('That email is already on a clinic record. Contact the clinic to verify and link your account.');
                    }
                }
                $mysqli->begin_transaction();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Client';
                $userInsert = $mysqli->prepare('INSERT INTO users (username, password_hash, full_name, email, phone, role, active) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $userInsert->bind_param('ssssss', $values['username'], $passwordHash, $values['full_name'], $values['email'], $values['phone'], $role);
                $userInsert->execute();
                $userId = (int) $mysqli->insert_id;
                $userInsert->close();

                if (is_array($existingClient)) {
                    $existingClientId = (int) $existingClient['id'];
                    $clientUpdate = $mysqli->prepare('UPDATE clients SET account_user_id = ?, full_name = ?, phone = ?, address = ? WHERE id = ? AND account_user_id IS NULL AND status = "Active"');
                    $clientUpdate->bind_param('isssi', $userId, $values['full_name'], $values['phone'], $values['address'], $existingClientId);
                    $clientUpdate->execute();
                    if ($clientUpdate->affected_rows !== 1) throw new mysqli_sql_exception('The existing client record could not be linked.', 1062);
                    $clientUpdate->close();
                } else {
                    $clientInsert = $mysqli->prepare('INSERT INTO clients (account_user_id, full_name, phone, email, address, status) VALUES (?, ?, ?, ?, ?, "Active")');
                    $clientInsert->bind_param('issss', $userId, $values['full_name'], $values['phone'], $values['email'], $values['address']);
                    $clientInsert->execute();
                    $clientInsert->close();
                }
                $mysqli->commit();

                session_regenerate_id(true);
                unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['api_token']);
                $_SESSION['client_user_id'] = $userId;
                $_SESSION['client_form_token'] = bin2hex(random_bytes(32));
                header('Location: client-portal.php');
                exit;
            } catch (DomainException $exception) {
                $error = $exception->getMessage();
            } catch (mysqli_sql_exception $exception) {
                $mysqli->rollback();
                $error = $exception->getCode() === 1062 ? 'That username or email is already registered. Please sign in or choose another.' : 'We could not create your account. Please try again.';
                if ($exception->getCode() !== 1062) error_log('Client registration failed: ' . $exception->getMessage());
            }
        }
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = $mysqli->prepare(
            "SELECT u.id, u.password_hash FROM users u
             JOIN clients c ON c.account_user_id = u.id
             WHERE u.active = 1 AND u.role = 'Client' AND c.status = 'Active' AND (u.username = ? OR c.email = ?)
             LIMIT 1"
        );
        $statement->bind_param('ss', $identity, $identity);
        $statement->execute();
        $account = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (is_array($account) && password_verify($password, (string) $account['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['api_token']);
            $_SESSION['client_user_id'] = (int) $account['id'];
            $_SESSION['client_form_token'] = bin2hex(random_bytes(32));
            header('Location: client-portal.php');
            exit;
        }
        $error = 'Email, username, or password is incorrect.';
    }
}
?>
<!doctype html>
<html lang="en" class="<?= $mode === 'register' ? 'client-auth-register' : '' ?>">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $mode === 'register' ? 'Create Client Account' : 'Client Sign In' ?> · Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="client-portal.css?v=9">
  <link rel="stylesheet" href="client-theme.css?v=4">
</head>
<body class="client-auth-page">
  <main class="auth-card">
    <a class="client-brand" href="client-auth.php"><img class="client-brand-logo<?= $mode === 'register' ? ' client-brand-logo-register' : '' ?>" src="assets/clinic-logo.png" alt="Urban Fur Veterinary Clinic logo"><span>Urban Fur<small>Veterinary Clinic</small></span></a>
    <p class="eyebrow">PET PARENT PORTAL</p>
    <h1><?= $mode === 'register' ? 'Create your account' : 'Welcome back' ?></h1>
    <p class="muted"><?= $mode === 'register' ? 'Manage your pets and stay connected with their care.' : 'Sign in to see your pets, appointments, and care history.' ?></p>
    <?php if ($error !== ''): ?><div class="notice error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="client-auth.php?mode=<?= $mode ?>" class="client-form">
      <input type="hidden" name="mode" value="<?= $mode ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $_SESSION['client_form_token'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($mode === 'register'): ?>
        <label>Full name<input name="full_name" autocomplete="name" maxlength="150" pattern="[\p{L}]+([ '\u2019\-][\p{L}]+)*" title="Use letters only, with spaces, apostrophes, or hyphens between words." required value="<?= htmlspecialchars($values['full_name'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Email address<input type="email" name="email" autocomplete="email" maxlength="190" required value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Contact number<input name="phone" type="tel" inputmode="numeric" autocomplete="tel" minlength="11" maxlength="11" pattern="09[0-9]{9}" title="Enter 11 digits starting with 09." required value="<?= htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Address<input name="address" autocomplete="street-address" maxlength="255" required value="<?= htmlspecialchars($values['address'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Username<input name="username" autocomplete="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.\-]{3,80}" required value="<?= htmlspecialchars($values['username'], ENT_QUOTES, 'UTF-8') ?>"><small>3–80 letters, numbers, dots, dashes, or underscores</small></label>
        <label>Password<input name="password" type="password" autocomplete="new-password" minlength="8" maxlength="255" required><small>Use at least 8 characters.</small></label>
        <button class="button primary full" type="submit">Create account</button>
      <?php else: ?>
        <label>Email or username<input name="identity" autocomplete="username" required autofocus></label>
        <label>Password<span class="recovery-password-wrap"><input id="clientLoginPassword" name="password" type="password" autocomplete="current-password" required><button class="recovery-eye" type="button" data-password-toggle="clientLoginPassword" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye-slash" aria-hidden="true"></i></button></span></label>
        <p class="password-recovery-link"><a href="password-recovery.php?return=client">Forgot Password?</a></p>
        <button class="button primary full" type="submit">Sign in</button>
      <?php endif; ?>
    </form>
    <p class="auth-switch"><?php if ($mode === 'register'): ?>Already registered? <a href="client-auth.php">Sign in</a><?php else: ?>New to Urban Fur? <a href="client-auth.php?mode=register">Create a client account</a><?php endif; ?></p>
    <a class="staff-link" href="index.php">Clinic staff sign in</a>
  </main>
  <script>
    const clientRegisterName = document.querySelector('input[name="full_name"]');
    const clientRegisterPhone = document.querySelector('input[name="phone"]');
    clientRegisterName?.addEventListener('input', () => {
      clientRegisterName.value = clientRegisterName.value.replace(/[^\p{L} '\u2019-]/gu, '');
    });
    clientRegisterPhone?.addEventListener('input', () => {
      clientRegisterPhone.value = clientRegisterPhone.value.replace(/\D/g, '').slice(0, 11);
    });
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.querySelector('i').classList.toggle('fa-eye', visible);
        button.querySelector('i').classList.toggle('fa-eye-slash', !visible);
      });
    });
  </script>
</body>
</html>
