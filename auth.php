<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$statement = $mysqli->prepare('SELECT full_name, role FROM users WHERE id = ? AND active = 1 LIMIT 1');
if ($statement === false) {
    http_response_code(500);
    exit('Unable to verify your account. Please try again.');
}
$userId = (int) $_SESSION['user_id'];
$statement->bind_param('i', $userId);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

if (!is_array($user)) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: index.php?account=inactive');
    exit;
}

if ($user['role'] === 'Client') {
    header('Location: client-portal.php');
    exit;
}

$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
