<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function recoveryRespond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function recoverySendCode(string $channel, string $destination, string $code): void
{
    $message = "Your Urban Fur Clinic password reset code is {$code}. It expires in 10 minutes. Do not share this code.";
    if ($channel === 'email') {
        $from = trim((string) getenv('MAIL_FROM'));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email delivery is not configured. Set MAIL_FROM and configure XAMPP SMTP.');
        }
        $headers = "From: Urban Fur Clinic <{$from}>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        set_error_handler(static function (int $severity, string $warning): never {
            throw new ErrorException($warning, 0, $severity);
        });
        try {
            $sent = mail($destination, 'Urban Fur Clinic password reset code', $message, $headers);
        } finally {
            restore_error_handler();
        }
        if (!$sent) {
            throw new RuntimeException('The SMTP relay did not accept the recovery email.');
        }
        return;
    }

    $gatewayUrl = trim((string) getenv('SMS_GATEWAY_URL'));
    $gatewayToken = trim((string) getenv('SMS_GATEWAY_TOKEN'));
    $sender = trim((string) getenv('SMS_GATEWAY_FROM'));
    if ($gatewayUrl === '' || $gatewayToken === '' || $sender === ''
        || !filter_var($gatewayUrl, FILTER_VALIDATE_URL)
        || parse_url($gatewayUrl, PHP_URL_SCHEME) !== 'https') {
        throw new RuntimeException('SMS delivery is not configured. Set SMS_GATEWAY_URL, SMS_GATEWAY_TOKEN, and SMS_GATEWAY_FROM.');
    }

    $requestBody = json_encode([
        'to' => $destination,
        'from' => $sender,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($requestBody === false) {
        throw new RuntimeException('Unable to encode the SMS request.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP cURL extension is required for SMS delivery.');
    }
    $curl = curl_init($gatewayUrl);
    if ($curl === false) {
        throw new RuntimeException('Unable to initialize the SMS gateway request.');
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $gatewayToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $requestBody,
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($response === false || $status < 200 || $status >= 300) {
        error_log('Password recovery SMS gateway failed: HTTP ' . $status . ' ' . $curlError);
        throw new RuntimeException('The SMS gateway could not deliver the recovery code.');
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    recoveryRespond(['error' => 'Method not allowed.'], 405);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    recoveryRespond(['error' => 'Invalid request body.'], 400);
}
$sessionToken = $_SESSION['recovery_form_token'] ?? '';
$requestToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
    recoveryRespond(['error' => 'Your recovery session expired. Reload the page and try again.'], 419);
}

$action = (string) ($input['action'] ?? '');
try {
    if ($action === 'send') {
        $identifier = trim((string) ($input['identifier'] ?? ''));
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $channel = 'email';
        } elseif (preg_match('/^09[0-9]{9}$/', $identifier) === 1) {
            $channel = 'phone';
        } else {
            recoveryRespond(['error' => 'Enter a valid registered email address or 11-digit mobile number.'], 422);
        }

        if ($channel === 'email') {
            $accountRows = $mysqli->prepare(
                "SELECT DISTINCT u.id, CASE WHEN u.role = 'Client' THEN c.email ELSE u.email END AS email,
                        CASE WHEN u.role = 'Client' THEN c.phone ELSE u.phone END AS phone
                 FROM users u
                 LEFT JOIN clients c ON c.account_user_id = u.id
                 WHERE u.active = 1 AND (u.role <> 'Client' OR c.status = 'Active')
                   AND CASE WHEN u.role = 'Client' THEN c.email ELSE u.email END = ?"
            );
        } else {
            $accountRows = $mysqli->prepare(
                "SELECT DISTINCT u.id, CASE WHEN u.role = 'Client' THEN c.email ELSE u.email END AS email,
                        CASE WHEN u.role = 'Client' THEN c.phone ELSE u.phone END AS phone
                 FROM users u
                 LEFT JOIN clients c ON c.account_user_id = u.id
                 WHERE u.active = 1 AND (u.role <> 'Client' OR c.status = 'Active')
                   AND CASE WHEN u.role = 'Client' THEN c.phone ELSE u.phone END = ?"
            );
        }
        $accountRows->bind_param('s', $identifier);
        $accountRows->execute();
        $matches = $accountRows->get_result()->fetch_all(MYSQLI_ASSOC);
        $accountRows->close();

        unset($_SESSION['recovery_user_id']);
        if (count($matches) === 1) {
            $account = $matches[0];
            $destination = (string) $account[$channel];
            if ($destination !== '') {
                $existing = $mysqli->prepare('SELECT created_at FROM password_recovery_codes WHERE user_id = ? LIMIT 1');
                $userId = (int) $account['id'];
                $existing->bind_param('i', $userId);
                $existing->execute();
                $existingCode = $existing->get_result()->fetch_assoc();
                $existing->close();
                if (is_array($existingCode) && strtotime((string) $existingCode['created_at']) > time() - 60) {
                    recoveryRespond(['error' => 'Please wait one minute before requesting another code.'], 429);
                }

                $code = (string) random_int(100000, 999999);
                $codeHash = password_hash($code, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);
                $insert = $mysqli->prepare(
                    'INSERT INTO password_recovery_codes (user_id, channel, destination, code_hash, expires_at, attempts, verified_at)
                     VALUES (?, ?, ?, ?, ?, 0, NULL)
                     ON DUPLICATE KEY UPDATE channel = VALUES(channel), destination = VALUES(destination),
                         code_hash = VALUES(code_hash), expires_at = VALUES(expires_at), attempts = 0,
                         verified_at = NULL, created_at = CURRENT_TIMESTAMP'
                );
                $insert->bind_param('issss', $userId, $channel, $destination, $codeHash, $expiresAt);
                $insert->execute();
                $insert->close();
                try {
                    recoverySendCode($channel, $destination, $code);
                } catch (Throwable $deliveryException) {
                    $delete = $mysqli->prepare('DELETE FROM password_recovery_codes WHERE user_id = ?');
                    $delete->bind_param('i', $userId);
                    $delete->execute();
                    $delete->close();
                    error_log('Password recovery delivery failed: ' . $deliveryException->getMessage());
                    recoveryRespond(['error' => $deliveryException->getMessage()], 503);
                }
                $_SESSION['recovery_user_id'] = $userId;
            }
        }
        recoveryRespond(['message' => 'If an active account matches those details, a verification code has been sent.']);
    }

    $userId = filter_var($_SESSION['recovery_user_id'] ?? null, FILTER_VALIDATE_INT);
    if ($userId === false || $userId < 1) {
        recoveryRespond(['error' => 'The code is invalid or expired. Request a new code and try again.'], 422);
    }

    if ($action === 'verify') {
        $code = trim((string) ($input['code'] ?? ''));
        if (preg_match('/^[0-9]{6}$/', $code) !== 1) {
            recoveryRespond(['error' => 'Enter the six-digit verification code.'], 422);
        }
        $mysqli->begin_transaction();
        $statement = $mysqli->prepare('SELECT code_hash, expires_at, attempts FROM password_recovery_codes WHERE user_id = ? FOR UPDATE');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $recovery = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($recovery) || strtotime((string) $recovery['expires_at']) <= time() || (int) $recovery['attempts'] >= 5) {
            $mysqli->rollback();
            recoveryRespond(['error' => 'The code is invalid or expired. Request a new code and try again.'], 422);
        }
        if (!password_verify($code, (string) $recovery['code_hash'])) {
            $increment = $mysqli->prepare('UPDATE password_recovery_codes SET attempts = attempts + 1 WHERE user_id = ?');
            $increment->bind_param('i', $userId);
            $increment->execute();
            $increment->close();
            $mysqli->commit();
            recoveryRespond(['error' => 'The verification code is incorrect.'], 422);
        }
        $verified = $mysqli->prepare('UPDATE password_recovery_codes SET verified_at = NOW() WHERE user_id = ?');
        $verified->bind_param('i', $userId);
        $verified->execute();
        $verified->close();
        $mysqli->commit();
        $_SESSION['recovery_verified_at'] = time();
        recoveryRespond(['verified' => true]);
    }

    if ($action === 'reset') {
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');
        if (!hash_equals($newPassword, $confirmPassword)) {
            recoveryRespond(['error' => 'The passwords do not match.'], 422);
        }
        if (strlen($newPassword) < 8 || strlen($newPassword) > 255
            || preg_match('/[a-z]/', $newPassword) !== 1
            || preg_match('/[A-Z]/', $newPassword) !== 1
            || preg_match('/[0-9]/', $newPassword) !== 1
            || preg_match('/[^a-zA-Z0-9]/', $newPassword) !== 1) {
            recoveryRespond(['error' => 'Use at least 8 characters with uppercase, lowercase, a number, and a symbol.'], 422);
        }
        $verifiedAt = filter_var($_SESSION['recovery_verified_at'] ?? null, FILTER_VALIDATE_INT);
        if ($verifiedAt === false || $verifiedAt < time() - 600) {
            recoveryRespond(['error' => 'Your verification expired. Request a new code and try again.'], 422);
        }
        $mysqli->begin_transaction();
        $statement = $mysqli->prepare(
            'SELECT code_hash FROM password_recovery_codes
             WHERE user_id = ? AND verified_at IS NOT NULL AND verified_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
               AND expires_at > NOW() FOR UPDATE'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $verified = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($verified)) {
            $mysqli->rollback();
            recoveryRespond(['error' => 'Your verification expired. Request a new code and try again.'], 422);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND active = 1');
        $update->bind_param('si', $passwordHash, $userId);
        $update->execute();
        if ($update->affected_rows !== 1) {
            $update->close();
            $mysqli->rollback();
            recoveryRespond(['error' => 'The account could not be updated. Please request a new code.'], 409);
        }
        $update->close();
        $delete = $mysqli->prepare('DELETE FROM password_recovery_codes WHERE user_id = ?');
        $delete->bind_param('i', $userId);
        $delete->execute();
        $delete->close();
        $mysqli->commit();
        unset($_SESSION['recovery_user_id'], $_SESSION['recovery_verified_at']);
        recoveryRespond(['reset' => true]);
    }

    recoveryRespond(['error' => 'Unknown recovery action.'], 404);
} catch (Throwable $exception) {
    if ($mysqli->errno !== 0) {
        $mysqli->rollback();
    }
    error_log('Password recovery failed: ' . $exception->getMessage());
    recoveryRespond(['error' => 'Password recovery could not be completed. Please try again.'], 500);
}
