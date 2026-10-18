<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

$userCountResult = $mysqli->query('SELECT COUNT(*) AS total FROM users');
$userCount = $userCountResult instanceof mysqli_result ? (int) $userCountResult->fetch_assoc()['total'] : 0;
if ($userCount > 0) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['setup_token']) || !is_string($_SESSION['setup_token']) || $_SESSION['setup_token'] === '') {
    $_SESSION['setup_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $token = (string) ($_POST['setup_token'] ?? '');
    $sessionSetupToken = $_SESSION['setup_token'] ?? '';

    if (!is_string($sessionSetupToken) || !hash_equals($sessionSetupToken, $token)) {
        $error = 'Your setup session expired. Reload this page and try again.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
        $error = 'Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.';
    } elseif ($fullName === '' || mb_strlen($fullName) > 150) {
        $error = 'Enter your name (up to 150 characters).';
    } elseif (strlen($password) < 12) {
        $error = 'Choose a password with at least 12 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $statement = $mysqli->prepare(
                "INSERT INTO users (username, password_hash, full_name, role)
                 SELECT ?, ?, ?, 'Administrator'
                 WHERE NOT EXISTS (SELECT 1 FROM users)"
            );
            if ($statement === false) {
                throw new RuntimeException('Unable to prepare administrator insert statement.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement->bind_param('sss', $username, $passwordHash, $fullName);
            $statement->execute();

            if ($statement->affected_rows === 1) {
                $statement->close();
                session_regenerate_id(true);
                unset($_SESSION['setup_token']);
                header('Location: index.php?setup=complete');
                exit;
            }

            $statement->close();
            header('Location: index.php');
            exit;
        } catch (Throwable $exception) {
            if ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1062) {
                $error = 'That username is already in use.';
            } else {
                error_log('Administrator setup failed: ' . $exception->getMessage());
                http_response_code(500);
                $error = 'Unable to create the administrator account. Check the PHP error log.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set up Urban Fur Clinic</title>
  <link rel="stylesheet" href="style.css?v=6">
</head>
<body id="setup-page">
  <main class="setup-card">
    <h1>Set up your clinic account</h1>
    <p>Create the first administrator account to start using Urban Fur.</p>
    <?php if ($error !== ''): ?>
      <p class="setup-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post" action="setup.php">
      <input type="hidden" name="setup_token" value="<?= htmlspecialchars($_SESSION['setup_token'], ENT_QUOTES, 'UTF-8') ?>">
      <label for="full_name">Full name</label>
      <input id="full_name" name="full_name" type="text" maxlength="150" required value="<?= htmlspecialchars((string) ($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" minlength="3" maxlength="80" required autocomplete="username" value="<?= htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <label for="password">Password (at least 12 characters)</label>
      <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
      <label for="confirm_password">Confirm password</label>
      <input id="confirm_password" name="confirm_password" type="password" minlength="12" required autocomplete="new-password">
      <button class="btn-primary" type="submit">Create administrator account</button>
    </form>
  </main>
</body>
</html>
