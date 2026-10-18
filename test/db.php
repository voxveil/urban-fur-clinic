<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: ' urbanfurclinic';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli($dbHost, $dbUser, $dbPassword, '');
    $connection->set_charset('utf8mb4');

    $escapedDatabaseName = str_replace('`', '``', $dbName);
    $connection->query(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $escapedDatabaseName
        )
    );

    if (!$connection->select_db($dbName)) {
        throw new mysqli_sql_exception($connection->error, $connection->errno);
    }

    $schemaPath = __DIR__ . '/database.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('Database schema file is missing.');
    }

    $schemaSql = file_get_contents($schemaPath);
    if ($schemaSql === false) {
        throw new RuntimeException('Unable to read database schema file.');
    }

    $schemaSql = preg_replace('/^\s*CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+.*?;\s*/ims', '', $schemaSql);
    $schemaSql = preg_replace('/^\s*USE\s+.*?;\s*/ims', '', $schemaSql);
    if ($schemaSql === null) {
        throw new RuntimeException('Unable to parse database schema file.');
    }
    $schemaSql = trim($schemaSql);

    if ($schemaSql !== '') {
        $connection->multi_query($schemaSql);
        do {
            if ($connection->errno !== 0) {
                throw new mysqli_sql_exception($connection->error, $connection->errno);
            }
            $result = $connection->store_result();
            if ($result instanceof mysqli_result) {
                $result->free();
            }
        } while ($connection->more_results() && $connection->next_result());
        if ($connection->errno !== 0) {
            throw new mysqli_sql_exception($connection->error, $connection->errno);
        }
    }

    $requiredTables = ['users', 'clinic_settings', 'clinic_preferences', 'audit_logs', 'staff', 'clients', 'pets', 'services', 'inventory', 'appointments', 'invoices', 'invoice_items'];
    $tableCheck = $connection->prepare(
        'SELECT COUNT(*) AS total
         FROM information_schema.tables
         WHERE table_schema = ? AND table_name = ?'
    );
    if ($tableCheck === false) {
        throw new RuntimeException('Unable to verify database schema tables.');
    }

    foreach ($requiredTables as $requiredTable) {
        $tableCheck->bind_param('ss', $dbName, $requiredTable);
        $tableCheck->execute();
        $tableResult = $tableCheck->get_result();
        $tableRow = $tableResult->fetch_assoc();
        if ((int) ($tableRow['total'] ?? 0) !== 1) {
            throw new mysqli_sql_exception('Missing required table: ' . $requiredTable, 0);
        }
    }
    $tableCheck->close();

    $mysqli = $connection;
} catch (Throwable $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check your MySQL service and DB_* settings.');
}
