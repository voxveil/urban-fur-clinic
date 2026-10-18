<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

function applyDatabaseSchema(mysqli $connection, string $dbName, string $schemaSql): void
{
    $schemaSql = preg_replace('/^\s*CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+.*?;\s*/ims', '', $schemaSql);
    $schemaSql = preg_replace('/^\s*USE\s+.*?;\s*/ims', '', $schemaSql);
    if ($schemaSql === null) {
        throw new RuntimeException('Unable to parse database schema file.');
    }
    $schemaSql = trim($schemaSql);

    if ($schemaSql === '') {
        return;
    }

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

function rebuildDatabase(mysqli $connection, string $dbName, string $schemaSql): void
{
    $escapedDatabaseName = str_replace('`', '``', $dbName);
    $connection->query(sprintf('DROP DATABASE IF EXISTS `%s`', $escapedDatabaseName));
    $connection->query(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $escapedDatabaseName
        )
    );

    if (!$connection->select_db($dbName)) {
        throw new mysqli_sql_exception($connection->error, $connection->errno);
    }

    applyDatabaseSchema($connection, $dbName, $schemaSql);
}

$dbHost = trim((string) (getenv('DB_HOST') ?: '127.0.0.1'));
$dbName = trim((string) (getenv('DB_NAME') ?: 'urbanfurclinic_app'));
if ($dbName === '') {
    $dbName = 'urbanfurclinic_app';
}
$dbUser = trim((string) (getenv('DB_USER') ?: 'root'));
$dbPassword = (string) (getenv('DB_PASSWORD') ?: '');

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $schemaPath = __DIR__ . '/database.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('Database schema file is missing.');
    }

    $schemaSql = file_get_contents($schemaPath);
    if ($schemaSql === false) {
        throw new RuntimeException('Unable to read database schema file.');
    }

    for ($attempt = 0; $attempt < 2; $attempt++) {
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

        try {
            applyDatabaseSchema($connection, $dbName, $schemaSql);
            break;
        } catch (Throwable $schemaException) {
            $message = $schemaException->getMessage();
            if ($attempt === 0 && (
                str_contains($message, 'Tablespace') ||
                str_contains($message, "doesn't exist in engine") ||
                str_contains($message, 'Missing required table:') ||
                str_contains($message, 'Table') && str_contains($message, 'doesn\'t exist')
            )) {
                rebuildDatabase($connection, $dbName, $schemaSql);
                break;
            }
            throw $schemaException;
        }
    }

    $inventoryUniqueIndex = $connection->prepare(
        'SELECT COUNT(*) AS total
         FROM information_schema.statistics
         WHERE table_schema = ? AND table_name = ? AND index_name = ?'
    );
    $inventoryTable = 'inventory';
    $inventoryIndex = 'uq_inventory_name';
    $inventoryUniqueIndex->bind_param('sss', $dbName, $inventoryTable, $inventoryIndex);
    $inventoryUniqueIndex->execute();
    $inventoryIndexExists = (int) $inventoryUniqueIndex->get_result()->fetch_assoc()['total'] > 0;
    $inventoryIndex = 'uq_inventory_category_name';
    $inventoryUniqueIndex->bind_param('sss', $dbName, $inventoryTable, $inventoryIndex);
    $inventoryUniqueIndex->execute();
    $legacyInventoryIndexExists = (int) $inventoryUniqueIndex->get_result()->fetch_assoc()['total'] > 0;
    $inventoryUniqueIndex->close();

    if (!$inventoryIndexExists) {
        $duplicateVariants = $connection->query(
            'SELECT LOWER(TRIM(name)) AS normalized_name, quantity, minimum_quantity, unit_price,
                    MIN(id) AS keeper_id, COUNT(*) AS copies
             FROM inventory
             GROUP BY LOWER(TRIM(name)), quantity, minimum_quantity, unit_price
             ORDER BY copies DESC, keeper_id ASC'
        );
        $keeperIds = [];
        $seenInventoryNames = [];
        while ($variant = $duplicateVariants->fetch_assoc()) {
            $productKey = strtolower((string) $variant['normalized_name']);
            if (!isset($seenInventoryNames[$productKey])) {
                $seenInventoryNames[$productKey] = true;
                $keeperIds[] = (int) $variant['keeper_id'];
            }
        }
        $duplicateVariants->free();

        if ($keeperIds !== []) {
            $keeperIdList = implode(',', $keeperIds);
            $connection->query('DELETE FROM inventory WHERE id NOT IN (' . $keeperIdList . ')');
        }
        if ($legacyInventoryIndexExists) {
            $connection->query('ALTER TABLE inventory DROP INDEX uq_inventory_category_name');
        }
        $connection->query('ALTER TABLE inventory ADD UNIQUE KEY uq_inventory_name (name)');
    }

    $legacySkuRows = $connection->query("SELECT id, category FROM inventory WHERE sku LIKE 'DOCX-%'");
    $skuUpdate = $connection->prepare('UPDATE inventory SET sku = ? WHERE id = ?');
    while ($legacySku = $legacySkuRows->fetch_assoc()) {
        $prefix = strtoupper(substr((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) $legacySku['category']), 0, 5));
        if ($prefix === '') {
            $prefix = 'ITEM';
        }
        $sku = $prefix . '-' . str_pad((string) $legacySku['id'], 6, '0', STR_PAD_LEFT);
        $legacyId = (int) $legacySku['id'];
        $skuUpdate->bind_param('si', $sku, $legacyId);
        $skuUpdate->execute();
    }
    $skuUpdate->close();
    $legacySkuRows->free();

    $columnCheck = $connection->prepare(
        'SELECT IS_NULLABLE
         FROM information_schema.columns
         WHERE table_schema = ? AND table_name = ? AND column_name = ?'
    );
    if ($columnCheck === false) {
        throw new RuntimeException('Unable to verify database column migrations.');
    }

    $columnMigrations = [
        ['users', 'email', 'ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL'],
        ['users', 'phone', 'ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL'],
        ['users', 'profile_image', 'ALTER TABLE users ADD COLUMN profile_image MEDIUMTEXT NULL'],
        ['clients', 'email', 'ALTER TABLE clients MODIFY email VARCHAR(190) NULL'],
        ['clients', 'account_user_id', 'ALTER TABLE clients ADD COLUMN account_user_id INT UNSIGNED NULL UNIQUE'],
        ['clients', 'emergency_phone', 'ALTER TABLE clients ADD COLUMN emergency_phone VARCHAR(40) NULL'],
        ['pets', 'gender', 'ALTER TABLE pets ADD COLUMN gender VARCHAR(20) NULL'],
        ['pets', 'life_status', "ALTER TABLE pets ADD COLUMN life_status ENUM('Alive', 'Deceased') NOT NULL DEFAULT 'Alive'"],
        ['medical_records', 'prescription', 'ALTER TABLE medical_records ADD COLUMN prescription TEXT NULL'],
        ['inventory', 'expiration_date', 'ALTER TABLE inventory ADD COLUMN expiration_date DATE NULL'],
        ['inventory', 'condition_status', "ALTER TABLE inventory ADD COLUMN condition_status ENUM('Good', 'Damaged') NOT NULL DEFAULT 'Good'"],
    ];
    foreach ($columnMigrations as [$tableName, $columnName, $migrationSql]) {
        $columnCheck->bind_param('sss', $dbName, $tableName, $columnName);
        $columnCheck->execute();
        $columnResult = $columnCheck->get_result();
        $columnInfo = $columnResult->fetch_assoc();
        if ($columnInfo === null) {
            $connection->query($migrationSql);
        } elseif ($tableName === 'clients' && $columnName === 'email' && $columnInfo['IS_NULLABLE'] !== 'YES') {
            $connection->query($migrationSql);
        }
    }
    $columnCheck->close();

    $petNameTriggers = [
        'trg_pets_validate_name_insert' => 'CREATE TRIGGER trg_pets_validate_name_insert BEFORE INSERT ON pets FOR EACH ROW BEGIN IF NEW.name NOT REGEXP CONCAT(\'^[[:alpha:]]+(([[:space:]]+|[-\', CHAR(39), \'])[[:alpha:]]+)*$\') THEN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Pet names may contain letters, spaces, apostrophes, and hyphens only.\'; END IF; END',
        'trg_pets_validate_name_update' => 'CREATE TRIGGER trg_pets_validate_name_update BEFORE UPDATE ON pets FOR EACH ROW BEGIN IF NEW.name NOT REGEXP CONCAT(\'^[[:alpha:]]+(([[:space:]]+|[-\', CHAR(39), \'])[[:alpha:]]+)*$\') THEN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Pet names may contain letters, spaces, apostrophes, and hyphens only.\'; END IF; END',
    ];
    $triggerCheck = $connection->prepare('SELECT COUNT(*) AS total FROM information_schema.triggers WHERE trigger_schema = ? AND trigger_name = ?');
    foreach ($petNameTriggers as $triggerName => $triggerSql) {
        $triggerCheck->bind_param('ss', $dbName, $triggerName);
        $triggerCheck->execute();
        if ((int) $triggerCheck->get_result()->fetch_assoc()['total'] === 0) $connection->query($triggerSql);
    }
    $triggerCheck->close();

    $appointmentSlotIndex = $connection->query(
        "SELECT COUNT(*) AS total FROM information_schema.statistics
         WHERE table_schema = '" . $connection->real_escape_string($dbName) . "'
           AND table_name = 'appointments' AND index_name = 'idx_appointments_slot'"
    )->fetch_assoc();
    if ((int) ($appointmentSlotIndex['total'] ?? 0) === 0) {
        $connection->query('ALTER TABLE appointments ADD INDEX idx_appointments_slot (appointment_at, staff_id, status)');
    }

    $appointmentStatus = $connection->query(
        "SELECT COLUMN_TYPE FROM information_schema.columns
         WHERE table_schema = '" . $connection->real_escape_string($dbName) . "'
           AND table_name = 'appointments' AND column_name = 'status'"
    )->fetch_assoc();
    if (is_array($appointmentStatus) && !str_contains((string) $appointmentStatus['COLUMN_TYPE'], "'No Show'")) {
        $connection->query("ALTER TABLE appointments MODIFY status ENUM('Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'No Show') NOT NULL DEFAULT 'Scheduled'");
    }

    $clientAccountForeignKey = $connection->query(
        "SELECT COUNT(*) AS total FROM information_schema.key_column_usage
         WHERE table_schema = '" . $connection->real_escape_string($dbName) . "'
           AND table_name = 'clients' AND constraint_name = 'fk_clients_account_user'"
    )->fetch_assoc();
    if ((int) ($clientAccountForeignKey['total'] ?? 0) === 0) {
        $connection->query('ALTER TABLE clients ADD CONSTRAINT fk_clients_account_user FOREIGN KEY (account_user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    $preferenceRow = $connection->query('SELECT preferences_json FROM clinic_preferences WHERE id = 1')->fetch_assoc();
    if ($preferenceRow !== null) {
        $preferences = json_decode((string) $preferenceRow['preferences_json'], true);
        if (!is_array($preferences)) {
            throw new RuntimeException('Clinic preferences could not be read during migration.');
        }
        $defaults = [
            'theme_mode' => 'system',
            'sidebar_color' => '#0284c7',
            'last_backup_at' => null,
            'accepted_payment_methods' => ['Cash', 'Card', 'GCash', 'Pay Later'],
            'expense_reasons' => ['Office Supplies', 'Utilities', 'Rent', 'Clinic Supplies', 'Medication', 'Maintenance', 'Transportation', 'Other'],
        ];
        $migratedPreferences = array_merge($defaults, $preferences);
        if (($migratedPreferences['theme_color'] ?? null) === '#58a98c') {
            $migratedPreferences['theme_color'] = '#2d83b5';
        }
        if (($migratedPreferences['sidebar_color'] ?? null) === '#4d9c80') {
            $migratedPreferences['sidebar_color'] = '#0284c7';
        }
        if ($migratedPreferences !== $preferences) {
            $encodedPreferences = json_encode($migratedPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedPreferences === false) {
                throw new RuntimeException('Unable to migrate clinic preferences.');
            }
            $preferenceUpdate = $connection->prepare('UPDATE clinic_preferences SET preferences_json = ? WHERE id = 1');
            $preferenceUpdate->bind_param('s', $encodedPreferences);
            $preferenceUpdate->execute();
            $preferenceUpdate->close();
        }
    }

    $requiredTables = ['users', 'password_recovery_codes', 'clinic_settings', 'clinic_preferences', 'audit_logs', 'staff', 'clients', 'pets', 'services', 'inventory', 'catalog_categories', 'appointments', 'appointment_services', 'invoices', 'invoice_items'];
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
