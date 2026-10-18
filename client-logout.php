<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
unset($_SESSION['client_user_id'], $_SESSION['client_form_token']);
session_regenerate_id(true);
header('Location: client-auth.php');
exit;
