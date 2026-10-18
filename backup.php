<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

header('Cache-Control: no-store');

function backupError(string $message, int $status): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    backupError('Use POST to create a backup.', 405);
}

if (!isset($_SESSION['user_id'])) {
    backupError('Please sign in to continue.', 401);
}

$userStatement = $mysqli->prepare('SELECT role, active FROM users WHERE id = ?');
$userId = (int) $_SESSION['user_id'];
$userStatement->bind_param('i', $userId);
$userStatement->execute();
$user = $userStatement->get_result()->fetch_assoc();
$userStatement->close();
if ($user === null || (int) $user['active'] !== 1) {
    backupError('Your account is inactive. Please sign in again.', 401);
}
if (!in_array($user['role'], ['Administrator', 'Admin'], true)) {
    backupError('Only an administrator can download a database backup.', 403);
}

$sessionToken = $_SESSION['api_token'] ?? '';
$requestToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
    backupError('Your session expired. Reload the page and try again.', 419);
}

$preferenceRow = $mysqli->query('SELECT preferences_json FROM clinic_preferences WHERE id = 1')->fetch_assoc();
if ($preferenceRow === null) {
    backupError('Clinic preferences are not initialized.', 500);
}
$preferences = json_decode((string) $preferenceRow['preferences_json'], true);
if (!is_array($preferences)) {
    backupError('Clinic preferences could not be read.', 500);
}
$tableNames = ['users', 'clinic_settings', 'clinic_preferences', 'audit_logs', 'staff', 'clients', 'pets', 'services', 'inventory', 'catalog_categories', 'appointments', 'invoices', 'invoice_items'];
$sql = "-- Urban Fur Clinic database backup\n";
$sql .= '-- Created: ' . date(DATE_ATOM) . "\n";
$sql .= 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;' . "\n";
$sql .= 'USE `' . str_replace('`', '``', $dbName) . '`;' . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
$backupAt = date('Y-m-d H:i:s');

foreach ($tableNames as $tableName) {
    $escapedTableName = '`' . str_replace('`', '``', $tableName) . '`';
    $createResult = $mysqli->query('SHOW CREATE TABLE ' . $escapedTableName);
    $createRow = $createResult->fetch_assoc();
    if ($createRow === null) {
        backupError('Unable to read the database structure for ' . $tableName . '.', 500);
    }
    $createSql = $createRow['Create Table'];
    $sql .= 'DROP TABLE IF EXISTS ' . $escapedTableName . ";\n" . $createSql . ";\n";
    $rows = $mysqli->query('SELECT * FROM ' . $escapedTableName);
    while ($row = $rows->fetch_assoc()) {
        if ($tableName === 'clinic_preferences') {
            $snapshotPreferences = json_decode((string) $row['preferences_json'], true);
            if (!is_array($snapshotPreferences)) {
                backupError('Clinic preferences could not be read for the backup.', 500);
            }
            $snapshotPreferences['last_backup_at'] = $backupAt;
            $row['preferences_json'] = json_encode($snapshotPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($row['preferences_json'] === false) {
                backupError('Unable to serialize clinic preferences for the backup.', 500);
            }
        }
        $values = [];
        foreach ($row as $value) {
            $values[] = $value === null ? 'NULL' : "'" . $mysqli->real_escape_string((string) $value) . "'";
        }
        $sql .= 'INSERT INTO ' . $escapedTableName . ' VALUES (' . implode(', ', $values) . ");\n";
    }
    $sql .= "\n";
}
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

$preferences['last_backup_at'] = $backupAt;
$encodedPreferences = json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encodedPreferences === false) {
    backupError('Unable to record the backup time.', 500);
}
$updatePreferences = $mysqli->prepare('UPDATE clinic_preferences SET preferences_json = ?, updated_by = ? WHERE id = 1');
$updatePreferences->bind_param('si', $encodedPreferences, $userId);
$updatePreferences->execute();
$updatePreferences->close();

$filename = 'urbanfurclinic-' . date('Ymd-His') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
echo $sql;
