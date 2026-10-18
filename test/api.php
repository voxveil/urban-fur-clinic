<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function inputData(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return $_POST;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        respond(['error' => 'Request body must be valid JSON.'], 400);
    }

    return $decoded;
}

function requiredString(array $data, string $key, int $maxLength): string
{
    if (!isset($data[$key]) || !is_string($data[$key])) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' must be text.');
    }
    $value = trim($data[$key]);
    $length = preg_match_all('/./us', $value);
    if ($value === '' || $length === false || $length > $maxLength) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' is required and must be no longer than ' . $maxLength . ' characters.');
    }

    return $value;
}

function optionalString(array $data, string $key, int $maxLength): ?string
{
    $raw = $data[$key] ?? null;
    if ($raw === null) {
        return null;
    }
    if (!is_string($raw)) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' must be text.');
    }
    $value = trim($raw);
    if ($value === '') {
        return null;
    }
    $length = preg_match_all('/./us', $value);
    if ($length === false || $length > $maxLength) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' must be no longer than ' . $maxLength . ' characters.');
    }

    return $value;
}

function positiveId(array $data, string $key, bool $optional = false): ?int
{
    $value = $data[$key] ?? null;
    if ($optional && ($value === null || $value === '')) {
        return null;
    }
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
        throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $key)) . ' is invalid.');
    }

    return (int) $value;
}

function validEnum(array $data, string $key, array $allowed): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value) || !in_array($value, $allowed, true)) {
        throw new InvalidArgumentException('Invalid ' . str_replace('_', ' ', $key) . '.');
    }

    return $value;
}

function requiredDateTime(array $data, string $key): string
{
    $raw = $data[$key] ?? null;
    if (!is_string($raw)) {
        throw new InvalidArgumentException('A valid appointment date and time are required.');
    }
    $value = trim($raw);
    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if (!$dateTime || $dateTime->format('Y-m-d\TH:i') !== $value) {
        throw new InvalidArgumentException('A valid appointment date and time are required.');
    }

    return $dateTime->format('Y-m-d H:i:s');
}

function validDate(array $data, string $key): ?string
{
    $raw = $data[$key] ?? null;
    if ($raw === null) {
        return null;
    }
    if (!is_string($raw)) {
        throw new InvalidArgumentException('A valid ' . str_replace('_', ' ', $key) . ' is required.');
    }
    $value = trim($raw);
    if ($value === '') {
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('A valid ' . str_replace('_', ' ', $key) . ' is required.');
    }

    return $value;
}

function runWrite(string $sql, string $types, array $values): mysqli_stmt
{
    global $mysqli;
    $statement = $mysqli->prepare($sql);
    if ($statement === false) {
        throw new RuntimeException('Unable to prepare database operation.');
    }
    if ($types !== '') {
        $statement->bind_param($types, ...$values);
    }
    $statement->execute();

    return $statement;
}

function fetchAll(string $sql, string $types = '', array $values = []): array
{
    global $mysqli;
    $statement = $mysqli->prepare($sql);
    if ($statement === false) {
        throw new RuntimeException('Unable to prepare database query.');
    }
    if ($types !== '') {
        $statement->bind_param($types, ...$values);
    }
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    return $rows;
}

function fetchOne(string $sql, string $types = '', array $values = []): ?array
{
    $rows = fetchAll($sql, $types, $values);
    return $rows[0] ?? null;
}

function deleteById(string $table, int $id): void
{
    $allowed = ['clients', 'pets', 'staff', 'appointments', 'services', 'inventory', 'invoices'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Unsupported database operation.');
    }
    $statement = runWrite("DELETE FROM {$table} WHERE id = ?", 'i', [$id]);
    if ($statement->affected_rows !== 1) {
        $statement->close();
        respond(['error' => 'Record was not found.'], 404);
    }
    $statement->close();
}

function ensureRecordExists(string $table, int $id): void
{
    $allowed = ['clients', 'pets', 'staff', 'appointments', 'services', 'inventory', 'invoices'];
    if (!in_array($table, $allowed, true)) {
        throw new RuntimeException('Unsupported database operation.');
    }
    if (fetchOne("SELECT id FROM {$table} WHERE id = ?", 'i', [$id]) === null) {
        respond(['error' => 'Record was not found.'], 404);
    }
}

function rollbackIfActive(): void
{
    global $mysqli;
    if (($mysqli->server_status & MYSQLI_SERVER_STATUS_IN_TRANS) !== 0) {
        $mysqli->rollback();
    }
}

function getClinicSettings(): array
{
    $settings = fetchOne('SELECT clinic_name, phone, email, address, TIME_FORMAT(opening_time, "%H:%i") AS opening_time, TIME_FORMAT(closing_time, "%H:%i") AS closing_time, appointment_interval_minutes, tax_rate, default_minimum_quantity FROM clinic_settings WHERE id = 1');
    if ($settings === null) {
        throw new RuntimeException('Clinic settings have not been initialized.');
    }
    $settings['appointment_interval_minutes'] = (int) $settings['appointment_interval_minutes'];
    $settings['tax_rate'] = (float) $settings['tax_rate'];
    $settings['default_minimum_quantity'] = (int) $settings['default_minimum_quantity'];

    return array_merge($settings, getClinicPreferences());
}

function getClinicPreferences(): array
{
    $row = fetchOne('SELECT preferences_json FROM clinic_preferences WHERE id = 1');
    if ($row === null) {
        throw new RuntimeException('Clinic preferences have not been initialized.');
    }
    $preferences = json_decode((string) $row['preferences_json'], true);
    if (!is_array($preferences)) {
        throw new RuntimeException('Clinic preferences could not be read.');
    }

    return $preferences;
}

function recordAuditLog(string $action, array $details): void
{
    $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Unable to encode audit log details.');
    }
    runWrite(
        'INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)',
        'iss',
        [(int) $_SESSION['user_id'], $action, $encoded]
    );
}

function requireAdministrator(): void
{
    if (!in_array(($_SESSION['user_role'] ?? ''), ['Administrator', 'Admin'], true)) {
        respond(['error' => 'Only an administrator can perform this operation.'], 403);
    }
}

function lockUserManagement(): void
{
    global $mysqli;
    $mysqli->begin_transaction();
    $result = $mysqli->query('SELECT id FROM clinic_settings WHERE id = 1 FOR UPDATE');
    if (!$result instanceof mysqli_result || $result->num_rows !== 1) {
        throw new RuntimeException('Unable to lock user account management.');
    }
    $result->free();
}

$data = inputData();
$resource = (string) ($_GET['resource'] ?? $data['resource'] ?? '');
$action = (string) ($_GET['action'] ?? $data['action'] ?? '');

if (!isset($_SESSION['user_id'])) {
    respond(['error' => 'Please sign in to continue.'], 401);
}

$currentUser = fetchOne('SELECT id, full_name, role, active FROM users WHERE id = ?', 'i', [(int) $_SESSION['user_id']]);
if ($currentUser === null || (int) $currentUser['active'] !== 1) {
    $_SESSION = [];
    session_regenerate_id(true);
    respond(['error' => 'Your account is inactive. Please contact an administrator.'], 401);
}
$_SESSION['user_name'] = $currentUser['full_name'];
$_SESSION['user_role'] = $currentUser['role'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && $resource === 'token') {
    if (!isset($_SESSION['api_token'])) {
        $_SESSION['api_token'] = bin2hex(random_bytes(32));
    }
    respond(['csrfToken' => $_SESSION['api_token']]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    $sessionToken = $_SESSION['api_token'] ?? '';
    $requestToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        respond(['error' => 'Your session expired. Reload the page and try again.'], 419);
    }
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        switch ($resource) {
            case 'users':
                requireAdministrator();
                $users = fetchAll(
                    'SELECT id, username, full_name, role, active, created_at
                     FROM users ORDER BY full_name, username'
                );
                foreach ($users as &$user) {
                    $user['id'] = (int) $user['id'];
                    $user['active'] = (int) $user['active'];
                }
                unset($user);
                respond(['data' => $users]);

            case 'clients':
                $rows = fetchAll(
                    'SELECT c.id, c.full_name AS name, c.phone, c.email, c.address, c.status, c.created_at,
                            COUNT(p.id) AS pet_count,
                            SUM(CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END) AS pet_count_total
                     FROM clients c
                     LEFT JOIN pets p ON p.client_id = c.id
                     GROUP BY c.id
                     ORDER BY c.full_name'
                );
                respond(['data' => $rows]);

            case 'settings':
                respond(['data' => getClinicSettings()]);

            case 'audit-logs':
                requireAdministrator();
                respond(['data' => fetchAll(
                    'SELECT l.id, l.action, l.details, l.created_at, COALESCE(u.full_name, "Deleted user") AS user_name
                     FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id
                     ORDER BY l.created_at DESC, l.id DESC LIMIT 200'
                )]);

            case 'pets':
                $rows = fetchAll(
                    'SELECT p.id, p.client_id, p.name, p.species, p.breed, p.birth_date, p.created_at,
                            p.notes, p.status, c.full_name AS owner,
                            CASE WHEN p.birth_date IS NULL THEN NULL
                                 ELSE TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) END AS age
                     FROM pets p
                     JOIN clients c ON c.id = p.client_id
                     ORDER BY p.name'
                );
                respond(['data' => $rows]);

            case 'clients-options':
                respond(['data' => fetchAll("SELECT id, full_name AS name FROM clients WHERE status = 'Active' ORDER BY full_name")]);

            case 'clients-all-options':
                respond(['data' => fetchAll('SELECT id, full_name AS name, status FROM clients ORDER BY full_name')]);

            case 'pets-options':
                respond(['data' => fetchAll("SELECT p.id, p.name, p.client_id, c.full_name AS owner FROM pets p JOIN clients c ON c.id = p.client_id WHERE p.status = 'Active' ORDER BY p.name")]);

            case 'staff':
                respond(['data' => fetchAll('SELECT id, full_name AS name, role, phone, email, status FROM staff ORDER BY full_name')]);

            case 'services':
                respond(['data' => fetchAll('SELECT id, name, category, description, price, active FROM services ORDER BY name')]);

            case 'inventory':
                respond(['data' => fetchAll('SELECT id, sku, name, category, quantity, minimum_quantity AS min_threshold, unit_price AS price, created_at FROM inventory ORDER BY name')]);

            case 'catalog':
                $services = fetchAll('SELECT id, name, category, description, price, active FROM services ORDER BY name');
                foreach ($services as &$service) {
                    $service['item_type'] = 'service';
                    $service['stock'] = 'Service';
                    $service['price'] = (float) $service['price'];
                }
                unset($service);
                $inventory = fetchAll('SELECT id, name, category, unit_price AS price, quantity AS stock FROM inventory WHERE quantity > 0 ORDER BY name');
                foreach ($inventory as &$item) {
                    $item['item_type'] = 'inventory';
                    $item['price'] = (float) $item['price'];
                    $item['stock'] = (int) $item['stock'];
                }
                unset($item);
                respond(['data' => array_merge($services, $inventory)]);

            case 'appointments':
                $rows = fetchAll(
                    'SELECT a.id, a.pet_id, a.service_id, a.staff_id, a.appointment_at,
                            DATE_FORMAT(a.appointment_at, "%Y-%m-%d") AS raw_date,
                            DATE_FORMAT(a.appointment_at, "%b %e, %Y - %h:%i %p") AS date_time,
                            a.status, p.name AS pet_name, c.full_name AS owner_name,
                            COALESCE(s.name, "Unassigned service") AS service,
                            COALESCE(st.full_name, "Unassigned staff") AS vet
                     FROM appointments a
                     JOIN pets p ON p.id = a.pet_id
                     JOIN clients c ON c.id = p.client_id
                     LEFT JOIN services s ON s.id = a.service_id
                     LEFT JOIN staff st ON st.id = a.staff_id
                     ORDER BY a.appointment_at DESC'
                );
                respond(['data' => $rows]);

            case 'invoices':
                $rows = fetchAll(
                    'SELECT i.id, i.client_id, i.pet_id, i.appointment_id, i.amount,
                            i.payment_method, i.status, i.issued_on AS date,
                            c.full_name AS client,
                            COALESCE(p.name, "No patient") AS pet,
                            COALESCE(GROUP_CONCAT(ii.description ORDER BY ii.id SEPARATOR ", "), "Invoice") AS service
                     FROM invoices i
                     JOIN clients c ON c.id = i.client_id
                     LEFT JOIN pets p ON p.id = i.pet_id
                     LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
                     GROUP BY i.id
                     ORDER BY i.issued_on DESC, i.id DESC'
                );
                foreach ($rows as &$invoice) {
                    $invoice['record_id'] = (int) $invoice['id'];
                    $invoice['id'] = 'INV-' . date('Y', strtotime((string) $invoice['date'])) . '-' . str_pad((string) $invoice['id'], 5, '0', STR_PAD_LEFT);
                    $invoice['amount'] = (float) $invoice['amount'];
                }
                unset($invoice);
                respond(['data' => $rows]);

            case 'reports':
            case 'dashboard':
                $canViewFinancials = $resource === 'reports'
                    || in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin', 'Staff', 'Cashier'], true);
                $monthly = $weekly = $todaySales = $credit = null;
                if ($canViewFinancials) {
                    $monthly = fetchOne("SELECT COALESCE(SUM(amount), 0) AS revenue FROM invoices WHERE status = 'Paid' AND issued_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND issued_on <= CURDATE()");
                    $weekly = fetchOne("SELECT COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) AS revenue, COUNT(*) AS transactions FROM invoices WHERE issued_on >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND issued_on <= CURDATE()");
                    $todaySales = fetchOne("SELECT COALESCE(SUM(amount), 0) AS revenue FROM invoices WHERE status = 'Paid' AND issued_on = CURDATE()");
                    $credit = fetchOne("SELECT COALESCE(SUM(amount), 0) AS balance, COUNT(*) AS invoices FROM invoices WHERE payment_method = 'Pay Later' AND status IN ('Pending', 'Overdue')");
                }
                $completed = fetchOne("SELECT COUNT(*) AS total FROM appointments WHERE status = 'Completed' AND appointment_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND appointment_at <= NOW()");
                $counts = fetchOne('SELECT (SELECT COUNT(*) FROM pets) AS pets, (SELECT COUNT(*) FROM clients) AS clients, (SELECT COUNT(*) FROM staff WHERE status = "Active") AS staff, (SELECT COUNT(*) FROM appointments WHERE appointment_at >= NOW() AND status = "Scheduled") AS upcoming, (SELECT COUNT(*) FROM inventory WHERE quantity <= minimum_quantity) AS low_stock');
                $species = fetchAll("SELECT species, COUNT(*) AS total FROM pets WHERE status = 'Active' GROUP BY species ORDER BY total DESC");
                $services = fetchAll('SELECT s.name, COUNT(a.id) AS total FROM appointments a JOIN services s ON s.id = a.service_id GROUP BY s.id ORDER BY total DESC LIMIT 5');
                $schedule = fetchAll(
                    'SELECT c.full_name AS client, c.phone, p.name AS pet, p.breed,
                            a.appointment_at, a.status, COALESCE(s.name, "Unassigned service") AS service
                     FROM appointments a
                     JOIN pets p ON p.id = a.pet_id
                     JOIN clients c ON c.id = p.client_id
                     LEFT JOIN services s ON s.id = a.service_id
                     ORDER BY CASE WHEN a.appointment_at >= NOW() THEN 0 ELSE 1 END,
                              CASE WHEN a.appointment_at >= NOW() THEN a.appointment_at END ASC,
                              a.appointment_at DESC'
                );
                $notifications = [];
                $clinicPreferences = getClinicPreferences();
                if (!empty($clinicPreferences['appointment_reminders'])) {
                    $reminderHours = max(1, min(720, (int) ($clinicPreferences['reminder_hours_before'] ?? 24)));
                    $upcomingAppointments = fetchAll(
                        "SELECT a.appointment_at, p.name AS pet, c.full_name AS client
                         FROM appointments a
                         JOIN pets p ON p.id = a.pet_id
                         JOIN clients c ON c.id = p.client_id
                         WHERE a.appointment_at >= NOW()
                           AND a.appointment_at <= DATE_ADD(NOW(), INTERVAL {$reminderHours} HOUR)
                           AND a.status IN ('Scheduled', 'In Progress')
                         ORDER BY a.appointment_at LIMIT 5"
                    );
                    foreach ($upcomingAppointments as $appointment) {
                        $notifications[] = [
                            'type' => 'appointment',
                            'icon' => 'fa-calendar-check',
                            'title' => 'Upcoming appointment',
                            'message' => $appointment['pet'] . ' — ' . $appointment['client'],
                            'date' => date('M j, g:i A', strtotime((string) $appointment['appointment_at'])),
                        ];
                    }
                }
                if (!empty($clinicPreferences['reorder_alerts'])) {
                    $lowStockItems = fetchAll(
                        'SELECT name, quantity FROM inventory WHERE quantity <= minimum_quantity ORDER BY quantity, name LIMIT 5'
                    );
                    foreach ($lowStockItems as $item) {
                        $notifications[] = [
                            'type' => 'stock',
                            'icon' => 'fa-boxes-stacked',
                            'title' => 'Low stock',
                            'message' => $item['name'] . ' — ' . $item['quantity'] . ' left',
                            'date' => '',
                        ];
                    }
                }
                $unpaidInvoices = fetchAll(
                    'SELECT i.amount, i.issued_on, c.full_name AS client
                     FROM invoices i JOIN clients c ON c.id = i.client_id
                     WHERE i.status IN ("Pending", "Overdue")
                     ORDER BY i.issued_on DESC, i.id DESC LIMIT 5'
                );
                foreach ($unpaidInvoices as $invoice) {
                    $notifications[] = [
                        'type' => 'payment',
                        'icon' => 'fa-file-invoice-dollar',
                        'title' => 'Unpaid bill',
                        'message' => $invoice['client'] . ' — ₱' . number_format((float) $invoice['amount'], 2),
                        'date' => date('M j, Y', strtotime((string) $invoice['issued_on'])),
                    ];
                }
                $dashboardData = [
                    'completed_visits' => (int) ($completed['total'] ?? 0),
                    'counts' => $counts,
                    'species' => $species,
                    'services' => $services,
                    'schedule' => $schedule,
                    'notifications' => $notifications,
                ];
                if ($canViewFinancials) {
                    $dashboardData = array_merge([
                    'monthly_revenue' => (float) ($monthly['revenue'] ?? 0),
                    'weekly_revenue' => (float) ($weekly['revenue'] ?? 0),
                    'weekly_transactions' => (int) ($weekly['transactions'] ?? 0),
                    'today_sales' => (float) ($todaySales['revenue'] ?? 0),
                    'credit_balance' => (float) ($credit['balance'] ?? 0),
                    'credit_invoices' => (int) ($credit['invoices'] ?? 0),
                    ], $dashboardData);
                }
                respond($dashboardData);

            default:
                respond(['error' => 'Unknown data resource.'], 404);
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respond(['error' => 'Unsupported request method.'], 405);
    }

    switch ($resource . ':' . $action) {
        case 'users:create':
            requireAdministrator();
            $username = trim(requiredString($data, 'username', 80));
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
                throw new InvalidArgumentException('Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.');
            }
            $fullName = requiredString($data, 'full_name', 150);
            $role = validEnum($data, 'role', ['Administrator', 'Staff', 'Vet', 'Receptionist', 'Cashier']);
            $password = requiredString($data, 'password', 255);
            if (mb_strlen($password, 'UTF-8') < 12) {
                throw new InvalidArgumentException('Password must be at least 12 characters long.');
            }

            lockUserManagement();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = runWrite(
                'INSERT INTO users (username, password_hash, full_name, role, active) VALUES (?, ?, ?, ?, 1)',
                'ssss',
                [$username, $passwordHash, $fullName, $role]
            );
            $newUserId = $mysqli->insert_id;
            $statement->close();
            recordAuditLog('user.created', ['user_id' => $newUserId, 'username' => $username, 'role' => $role]);
            $mysqli->commit();
            respond(['id' => $newUserId], 201);

        case 'users:update':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $username = trim(requiredString($data, 'username', 80));
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
                throw new InvalidArgumentException('Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.');
            }
            $fullName = requiredString($data, 'full_name', 150);
            $role = validEnum($data, 'role', ['Administrator', 'Staff', 'Vet', 'Receptionist', 'Cashier']);
            $active = filter_var($data['active'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($active, [0, 1], true)) {
                throw new InvalidArgumentException('Account status is invalid.');
            }
            $password = $data['password'] ?? '';
            if (!is_string($password) || ($password !== '' && mb_strlen($password, 'UTF-8') < 12)) {
                throw new InvalidArgumentException('A new password must be at least 12 characters long.');
            }

            lockUserManagement();
            $target = fetchOne('SELECT id, role, active FROM users WHERE id = ? FOR UPDATE', 'i', [$id]);
            if ($target === null) {
                throw new InvalidArgumentException('User account was not found.');
            }
            if ($id === (int) $_SESSION['user_id'] && ($active !== 1 || $role !== 'Administrator')) {
                throw new InvalidArgumentException('You cannot deactivate or demote your own administrator account.');
            }
            if ($target['role'] === 'Administrator' && (int) $target['active'] === 1 && ($role !== 'Administrator' || $active !== 1)) {
                $admins = fetchOne("SELECT COUNT(*) AS total FROM users WHERE role = 'Administrator' AND active = 1");
                if ((int) ($admins['total'] ?? 0) <= 1) {
                    throw new InvalidArgumentException('At least one active administrator account must remain.');
                }
            }

            if ($password === '') {
                $statement = runWrite(
                    'UPDATE users SET username = ?, full_name = ?, role = ?, active = ? WHERE id = ?',
                    'sssii',
                    [$username, $fullName, $role, $active, $id]
                );
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = runWrite(
                    'UPDATE users SET username = ?, full_name = ?, role = ?, active = ?, password_hash = ? WHERE id = ?',
                    'sssisi',
                    [$username, $fullName, $role, $active, $passwordHash, $id]
                );
            }
            $statement->close();
            recordAuditLog('user.updated', ['user_id' => $id, 'username' => $username, 'role' => $role, 'active' => $active, 'password_reset' => $password !== '']);
            $mysqli->commit();
            respond(['updated' => true]);

        case 'settings:update':
            if (!in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin'], true)) {
                respond(['error' => 'Only an administrator can change clinic settings.'], 403);
            }

            $fields = [];
            $generalKeys = ['clinic_name', 'phone', 'email', 'address'];
            if (array_intersect($generalKeys, array_keys($data))) {
                $current = getClinicSettings();
                foreach ($generalKeys as $key) {
                    if (array_key_exists($key, $data)) {
                        $fields[$key] = $key === 'clinic_name'
                            ? requiredString($data, $key, 150)
                            : (optionalString($data, $key, $key === 'email' ? 190 : ($key === 'phone' ? 40 : 255)) ?? '');
                    } else {
                        $fields[$key] = $current[$key];
                    }
                }
                if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Enter a valid clinic email address.');
                }
            }

            if (array_intersect(['opening_time', 'closing_time', 'appointment_interval_minutes'], array_keys($data))) {
                $openingTime = $data['opening_time'] ?? '';
                $closingTime = $data['closing_time'] ?? '';
                if (!is_string($openingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $openingTime)
                    || !is_string($closingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $closingTime)
                    || $openingTime >= $closingTime) {
                    throw new InvalidArgumentException('Clinic closing time must be later than its opening time.');
                }
                $interval = filter_var($data['appointment_interval_minutes'] ?? null, FILTER_VALIDATE_INT);
                if ($interval === false || !in_array($interval, [15, 30, 45, 60], true)) {
                    throw new InvalidArgumentException('Appointment interval must be 15, 30, 45, or 60 minutes.');
                }
                $fields['opening_time'] = $openingTime . ':00';
                $fields['closing_time'] = $closingTime . ':00';
                $fields['appointment_interval_minutes'] = $interval;
            }

            if (array_key_exists('tax_rate', $data)) {
                $taxRate = filter_var($data['tax_rate'], FILTER_VALIDATE_FLOAT);
                if ($taxRate === false || $taxRate < 0 || $taxRate > 100) {
                    throw new InvalidArgumentException('Tax rate must be between 0 and 100 percent.');
                }
                $fields['tax_rate'] = $taxRate;
            }

            if (array_key_exists('default_minimum_quantity', $data)) {
                $minimumQuantity = filter_var($data['default_minimum_quantity'], FILTER_VALIDATE_INT);
                if ($minimumQuantity === false || $minimumQuantity < 0 || $minimumQuantity > 1000000) {
                    throw new InvalidArgumentException('Default low-stock quantity must be between 0 and 1,000,000.');
                }
                $fields['default_minimum_quantity'] = $minimumQuantity;
            }

            if ($fields === []) {
                throw new InvalidArgumentException('No clinic settings were provided.');
            }

            $columns = array_keys($fields);
            $assignments = implode(', ', array_map(static fn(string $column): string => "{$column} = ?", $columns));
            $types = '';
            foreach ($columns as $column) {
                $types .= in_array($column, ['appointment_interval_minutes', 'default_minimum_quantity'], true) ? 'i' : (in_array($column, ['tax_rate'], true) ? 'd' : 's');
            }
            runWrite("UPDATE clinic_settings SET {$assignments} WHERE id = 1", $types, array_values($fields));
            recordAuditLog('clinic_settings.updated', $fields);
            respond(['data' => getClinicSettings()]);

        case 'settings:preferences':
            requireAdministrator();
            $currentPreferences = getClinicPreferences();
            $updatedPreferences = $currentPreferences;
            $stringLimits = [
                'clinic_logo_url' => 500,
                'cancellation_policy' => 1000,
                'supplier_info' => 1000,
            ];
            foreach ($stringLimits as $key => $limit) {
                if (array_key_exists($key, $data)) {
                    $updatedPreferences[$key] = optionalString($data, $key, $limit) ?? '';
                }
            }
            if (array_key_exists('clinic_logo_url', $data) && $updatedPreferences['clinic_logo_url'] !== ''
                && !str_starts_with($updatedPreferences['clinic_logo_url'], '/')
                && (parse_url($updatedPreferences['clinic_logo_url'], PHP_URL_SCHEME) !== 'https'
                    || !filter_var($updatedPreferences['clinic_logo_url'], FILTER_VALIDATE_URL))) {
                throw new InvalidArgumentException('Clinic logo must be an absolute URL or a site-relative path.');
            }
            if (array_key_exists('clinic_logo_url', $data) && str_starts_with($updatedPreferences['clinic_logo_url'], '//')) {
                throw new InvalidArgumentException('Clinic logo must be a site-relative path or secure HTTPS URL.');
            }
            foreach (['appointment_reminders', 'reorder_alerts'] as $key) {
                if (array_key_exists($key, $data)) {
                    $value = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($value === null) {
                        throw new InvalidArgumentException('Invalid setting for ' . str_replace('_', ' ', $key) . '.');
                    }
                    $updatedPreferences[$key] = $value;
                }
            }
            if (array_key_exists('reminder_hours_before', $data)) {
                $hours = filter_var($data['reminder_hours_before'], FILTER_VALIDATE_INT);
                if ($hours === false || $hours < 1 || $hours > 720) {
                    throw new InvalidArgumentException('Reminder lead time must be between 1 and 720 hours.');
                }
                $updatedPreferences['reminder_hours_before'] = $hours;
            }
            if (array_key_exists('expiration_alert_days', $data)) {
                $days = filter_var($data['expiration_alert_days'], FILTER_VALIDATE_INT);
                if ($days === false || $days < 0 || $days > 365) {
                    throw new InvalidArgumentException('Expiration alert days must be between 0 and 365.');
                }
                $updatedPreferences['expiration_alert_days'] = $days;
            }
            if (array_key_exists('accepted_payment_methods', $data)) {
                $methods = $data['accepted_payment_methods'];
                $allowedMethods = ['Cash', 'Card', 'GCash', 'Pay Later'];
                if (!is_array($methods) || $methods === []) {
                    throw new InvalidArgumentException('Select at least one valid payment method.');
                }
                foreach ($methods as $method) {
                    if (!is_string($method) || !in_array($method, $allowedMethods, true)) {
                        throw new InvalidArgumentException('Select at least one valid payment method.');
                    }
                }
                $updatedPreferences['accepted_payment_methods'] = array_values(array_unique($methods));
            }
            if (array_key_exists('reports_default_period', $data)) {
                $updatedPreferences['reports_default_period'] = validEnum($data, 'reports_default_period', ['daily', 'weekly', 'monthly']);
            }
            if (array_key_exists('reports_export_format', $data)) {
                $updatedPreferences['reports_export_format'] = validEnum($data, 'reports_export_format', ['csv', 'excel', 'pdf']);
            }
            if (array_key_exists('backup_frequency', $data)) {
                $updatedPreferences['backup_frequency'] = validEnum($data, 'backup_frequency', ['disabled', 'daily', 'weekly', 'monthly']);
            }
            if (array_key_exists('theme_color', $data)) {
                $themeColor = $data['theme_color'];
                if (!is_string($themeColor) || !preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
                    throw new InvalidArgumentException('Choose a valid six-digit theme color.');
                }
                $updatedPreferences['theme_color'] = $themeColor;
            }
            if (array_key_exists('notifications', $data)) {
                $notificationSettings = $data['notifications'];
                $notificationKeys = ['appointment_email', 'appointment_sms', 'low_stock_email', 'low_stock_sms', 'unpaid_email', 'unpaid_sms'];
                if (!is_array($notificationSettings) || array_diff(array_keys($notificationSettings), $notificationKeys)) {
                    throw new InvalidArgumentException('Invalid notification settings.');
                }
                foreach ($notificationSettings as $key => $enabled) {
                    $value = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($value === null) {
                        throw new InvalidArgumentException('Invalid notification setting.');
                    }
                    $updatedPreferences['notifications'][$key] = $value;
                }
            }
            if ($updatedPreferences === $currentPreferences) {
                respond(['data' => getClinicSettings()]);
            }
            $preferencesJson = json_encode($updatedPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($preferencesJson === false) {
                throw new RuntimeException('Unable to save clinic preferences.');
            }
            runWrite('UPDATE clinic_preferences SET preferences_json = ?, updated_by = ? WHERE id = 1', 'si', [$preferencesJson, (int) $_SESSION['user_id']]);
            recordAuditLog('clinic_preferences.updated', ['before' => $currentPreferences, 'after' => $updatedPreferences]);
            respond(['data' => getClinicSettings()]);

        case 'services:settings-update':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $category = requiredString($data, 'category', 100);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter a valid non-negative service price.');
            }
            $service = fetchOne('SELECT name, category, price FROM services WHERE id = ?', 'i', [$id]);
            if ($service === null) {
                throw new InvalidArgumentException('Service was not found.');
            }
            $statement = runWrite('UPDATE services SET category = ?, price = ? WHERE id = ?', 'sdi', [$category, $price, $id]);
            recordAuditLog('service.pricing_updated', [
                'service_id' => $id,
                'name' => $service['name'],
                'before' => ['category' => $service['category'], 'price' => (float) $service['price']],
                'after' => ['category' => $category, 'price' => $price],
            ]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'clients:create':
            $name = requiredString($data, 'name', 150);
            $phone = requiredString($data, 'phone', 40);
            $email = requiredString($data, 'email', 190);
            $address = optionalString($data, 'address', 255);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            $statement = runWrite('INSERT INTO clients (full_name, phone, email, address) VALUES (?, ?, ?, ?)', 'ssss', [$name, $phone, $email, $address]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'clients:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            $phone = requiredString($data, 'phone', 40);
            $email = requiredString($data, 'email', 190);
            $address = optionalString($data, 'address', 255);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            $statement = runWrite('UPDATE clients SET full_name = ?, phone = ?, email = ?, address = ?, status = ? WHERE id = ?', 'sssssi', [$name, $phone, $email, $address, $status, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'clients:status':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            $statement = runWrite('UPDATE clients SET status = ? WHERE id = ?', 'si', [$status, $id]);
            respond(['updated' => $statement->affected_rows === 1]);

        case 'clients:delete':
            deleteById('clients', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'pets:create':
            $clientId = positiveId($data, 'client_id');
            $name = requiredString($data, 'name', 100);
            $species = requiredString($data, 'species', 60);
            $breed = requiredString($data, 'breed', 100);
            $birthDate = validDate($data, 'birth_date');
            $notes = optionalString($data, 'notes', 60000);
            $statement = runWrite('INSERT INTO pets (client_id, name, species, breed, birth_date, notes) VALUES (?, ?, ?, ?, ?, ?)', 'isssss', [$clientId, $name, $species, $breed, $birthDate, $notes]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'pets:update':
            $id = positiveId($data, 'id');
            ensureRecordExists('pets', $id);
            $clientId = positiveId($data, 'client_id');
            $name = requiredString($data, 'name', 100);
            $species = requiredString($data, 'species', 60);
            $breed = requiredString($data, 'breed', 100);
            $birthDate = validDate($data, 'birth_date');
            $notes = optionalString($data, 'notes', 60000);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            $statement = runWrite('UPDATE pets SET client_id = ?, name = ?, species = ?, breed = ?, birth_date = ?, notes = ?, status = ? WHERE id = ?', 'issssssi', [$clientId, $name, $species, $breed, $birthDate, $notes, $status, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'pets:status':
            $id = positiveId($data, 'id');
            ensureRecordExists('pets', $id);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            $statement = runWrite('UPDATE pets SET status = ? WHERE id = ?', 'si', [$status, $id]);
            respond(['updated' => true]);

        case 'pets:delete':
            deleteById('pets', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'staff:create':
            $name = requiredString($data, 'name', 150);
            $role = validEnum($data, 'role', ['Veterinarian', 'Vet Tech', 'Receptionist']);
            $phone = requiredString($data, 'phone', 40);
            $email = requiredString($data, 'email', 190);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            $statement = runWrite('INSERT INTO staff (full_name, role, phone, email) VALUES (?, ?, ?, ?)', 'ssss', [$name, $role, $phone, $email]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'staff:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            $role = validEnum($data, 'role', ['Veterinarian', 'Vet Tech', 'Receptionist']);
            $phone = requiredString($data, 'phone', 40);
            $email = requiredString($data, 'email', 190);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            $statement = runWrite('UPDATE staff SET full_name = ?, role = ?, phone = ?, email = ?, status = ? WHERE id = ?', 'sssssi', [$name, $role, $phone, $email, $status, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'staff:status':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            $statement = runWrite('UPDATE staff SET status = ? WHERE id = ?', 'si', [$status, $id]);
            respond(['updated' => $statement->affected_rows === 1]);

        case 'staff:delete':
            deleteById('staff', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'services:create':
            $name = requiredString($data, 'name', 150);
            $category = requiredString($data, 'category', 100);
            $description = optionalString($data, 'description', 60000);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter a valid non-negative service price.');
            }
            $statement = runWrite('INSERT INTO services (name, category, description, price) VALUES (?, ?, ?, ?)', 'sssd', [$name, $category, $description, $price]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'services:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            $category = requiredString($data, 'category', 100);
            $description = optionalString($data, 'description', 60000);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $active = filter_var($data['active'] ?? null, FILTER_VALIDATE_INT);
            if ($price === false || $price < 0 || $price > 99999999.99 || !in_array($active, [0, 1], true)) {
                throw new InvalidArgumentException('Invalid service price or active status.');
            }
            $statement = runWrite('UPDATE services SET name = ?, category = ?, description = ?, price = ?, active = ? WHERE id = ?', 'sssdii', [$name, $category, $description, $price, $active, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'services:delete':
            deleteById('services', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'inventory:create':
            $name = requiredString($data, 'name', 150);
            $sku = requiredString($data, 'sku', 80);
            $category = requiredString($data, 'category', 100);
            $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
            $minimum = filter_var($data['min_threshold'] ?? getClinicSettings()['default_minimum_quantity'], FILTER_VALIDATE_INT);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($quantity === false || $quantity < 0 || $minimum === false || $minimum < 0 || $price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter valid stock quantities and a non-negative price.');
            }
            $statement = runWrite('INSERT INTO inventory (sku, name, category, quantity, minimum_quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)', 'sssiid', [$sku, $name, $category, $quantity, $minimum, $price]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'inventory:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            $sku = requiredString($data, 'sku', 80);
            $category = requiredString($data, 'category', 100);
            $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
            $minimum = filter_var($data['min_threshold'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($quantity === false || $quantity < 0 || $minimum === false || $minimum < 0 || $price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter valid stock quantities and a non-negative price.');
            }
            $statement = runWrite('UPDATE inventory SET name = ?, sku = ?, category = ?, quantity = ?, minimum_quantity = ?, unit_price = ? WHERE id = ?', 'sssiidi', [$name, $sku, $category, $quantity, $minimum, $price, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'inventory:adjust':
            $id = positiveId($data, 'id');
            $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT);
            if ($amount === false || $amount === 0 || abs($amount) > 100000) {
                throw new InvalidArgumentException('Enter a valid stock adjustment amount.');
            }
            $statement = runWrite('UPDATE inventory SET quantity = quantity + ? WHERE id = ? AND quantity + ? >= 0', 'iii', [$amount, $id, $amount]);
            if ($statement->affected_rows !== 1) {
                throw new InvalidArgumentException('Stock adjustment would result in a negative quantity or the item was not found.');
            }
            respond(['updated' => true]);

        case 'inventory:delete':
            deleteById('inventory', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'appointments:create':
            $petId = positiveId($data, 'pet_id');
            $serviceId = positiveId($data, 'service_id', true);
            $staffId = positiveId($data, 'staff_id', true);
            $appointmentAt = requiredDateTime($data, 'appointment_at');
            $appointmentSettings = getClinicSettings();
            $scheduledTime = substr($appointmentAt, 11, 5);
            $openingTime = $appointmentSettings['opening_time'];
            $closingTime = $appointmentSettings['closing_time'];
            $appointmentMinutes = ((int) substr($scheduledTime, 0, 2) * 60) + (int) substr($scheduledTime, 3, 2);
            $openingMinutes = ((int) substr($openingTime, 0, 2) * 60) + (int) substr($openingTime, 3, 2);
            $closingMinutes = ((int) substr($closingTime, 0, 2) * 60) + (int) substr($closingTime, 3, 2);
            if ($appointmentMinutes < $openingMinutes || $appointmentMinutes + $appointmentSettings['appointment_interval_minutes'] > $closingMinutes
                || ($appointmentMinutes - $openingMinutes) % $appointmentSettings['appointment_interval_minutes'] !== 0) {
                throw new InvalidArgumentException('Choose a time during clinic hours that matches the appointment interval.');
            }
            $notes = optionalString($data, 'notes', 60000);
            $statement = runWrite("INSERT INTO appointments (pet_id, service_id, staff_id, appointment_at, notes) VALUES (?, ?, ?, ?, ?)", 'iiiss', [$petId, $serviceId, $staffId, $appointmentAt, $notes]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'appointments:update':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Scheduled', 'In Progress', 'Completed', 'Cancelled']);
            $statement = runWrite('UPDATE appointments SET status = ? WHERE id = ?', 'si', [$status, $id]);
            respond(['updated' => $statement->affected_rows === 1]);

        case 'appointments:delete':
            deleteById('appointments', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'invoices:create':
            $clientId = positiveId($data, 'client_id');
            $petId = positiveId($data, 'pet_id', true);
            $description = requiredString($data, 'description', 255);
            $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $method = requiredString($data, 'payment_method', 60);
            if (!in_array($method, getClinicSettings()['accepted_payment_methods'], true)) {
                throw new InvalidArgumentException('This payment method is not enabled in clinic settings.');
            }
            $status = validEnum($data, 'status', ['Paid', 'Pending', 'Overdue']);
            if ($method === 'Pay Later') {
                $status = 'Pending';
            }
            $issuedOn = validDate($data, 'issued_on');
            if ($issuedOn === null || $amount === false || $amount <= 0 || $amount > 99999999.99) {
                throw new InvalidArgumentException('Enter a valid invoice date and positive amount.');
            }
            if ($petId !== null) {
                $petOwner = fetchOne('SELECT client_id FROM pets WHERE id = ?', 'i', [$petId]);
                if (!$petOwner || (int) $petOwner['client_id'] !== $clientId) {
                    throw new InvalidArgumentException('Choose a patient registered to the selected client.');
                }
            }
            $mysqli->begin_transaction();
            $statement = runWrite('INSERT INTO invoices (client_id, pet_id, amount, payment_method, status, issued_on) VALUES (?, ?, ?, ?, ?, ?)', 'iidsss', [$clientId, $petId, $amount, $method, $status, $issuedOn]);
            $invoiceId = $mysqli->insert_id;
            runWrite('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, 1, ?)', 'isd', [$invoiceId, $description, $amount]);
            $mysqli->commit();
            respond(['id' => $invoiceId], 201);

        case 'invoices:update':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Paid', 'Pending', 'Overdue']);
            $statement = runWrite('UPDATE invoices SET status = ? WHERE id = ?', 'si', [$status, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'invoices:delete':
            deleteById('invoices', positiveId($data, 'id'));
            respond(['deleted' => true]);

        case 'checkout:create':
            $clientId = positiveId($data, 'client_id');
            $petId = positiveId($data, 'pet_id');
            $paymentMethod = validEnum($data, 'payment_method', ['Cash', 'GCash', 'Card', 'Pay Later']);
            $clinicSettings = getClinicSettings();
            if (!in_array($paymentMethod, $clinicSettings['accepted_payment_methods'], true)) {
                throw new InvalidArgumentException('This payment method is not enabled in clinic settings.');
            }
            $discountRate = filter_var($data['discount_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            $cashPaid = filter_var($data['cash_paid'] ?? null, FILTER_VALIDATE_FLOAT);
            $cart = $data['items'] ?? null;
            if ($discountRate === false || !in_array((float) $discountRate, [0.0, 0.05, 0.1, 0.2], true) || ($paymentMethod === 'Cash' && $cashPaid === false) || !is_array($cart) || count($cart) === 0 || count($cart) > 100) {
                throw new InvalidArgumentException('Invalid checkout details.');
            }
            $petOwner = fetchOne('SELECT client_id FROM pets WHERE id = ?', 'i', [$petId]);
            if (!$petOwner || (int) $petOwner['client_id'] !== $clientId) {
                throw new InvalidArgumentException('Choose a patient registered to the selected client.');
            }

            $mysqli->begin_transaction();
            $subtotal = 0.0;
            $receiptItems = [];
            foreach ($cart as $line) {
                if (!is_array($line)) {
                    throw new InvalidArgumentException('Invalid checkout item.');
                }
                $itemId = positiveId($line, 'id');
                $quantity = filter_var($line['qty'] ?? null, FILTER_VALIDATE_INT);
                $itemType = validEnum($line, 'item_type', ['service', 'inventory']);
                if ($quantity === false || $quantity < 1 || $quantity > 1000) {
                    throw new InvalidArgumentException('Invalid item quantity.');
                }
                if ($itemType === 'service') {
                    $item = fetchOne('SELECT id, name, price FROM services WHERE id = ? AND active = 1', 'i', [$itemId]);
                    if (!$item) {
                        throw new InvalidArgumentException('A selected service is no longer available.');
                    }
                    $price = (float) $item['price'];
                    $serviceId = $itemId;
                } else {
                    $item = fetchOne('SELECT id, name, unit_price AS price, quantity FROM inventory WHERE id = ? FOR UPDATE', 'i', [$itemId]);
                    if (!$item || (int) $item['quantity'] < $quantity) {
                        throw new InvalidArgumentException('Insufficient inventory stock for a selected item.');
                    }
                    $price = (float) $item['price'];
                    $serviceId = null;
                    runWrite('UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?', 'iii', [$quantity, $itemId, $quantity]);
                }
                $subtotal += $price * $quantity;
                $receiptItems[] = [
                    'description' => (string) $item['name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'service_id' => $serviceId,
                ];
            }

            $discount = round($subtotal * (float) $discountRate, 2);
            $taxableAmount = round($subtotal - $discount, 2);
            $vat = round($taxableAmount * $clinicSettings['tax_rate'] / 100, 2);
            $total = round($taxableAmount + $vat, 2);
            $paidAmount = $paymentMethod === 'Pay Later'
                ? 0.0
                : ($cashPaid === false ? $total : (float) $cashPaid);
            if ($paymentMethod !== 'Pay Later' && $paidAmount < $total) {
                throw new InvalidArgumentException('Payment amount is less than the total.');
            }

            $invoiceStatus = $paymentMethod === 'Pay Later' ? 'Pending' : 'Paid';
            $issuedOn = date('Y-m-d');
            $statement = runWrite('INSERT INTO invoices (client_id, pet_id, amount, payment_method, status, issued_on) VALUES (?, ?, ?, ?, ?, ?)', 'iidsss', [$clientId, $petId, $total, $paymentMethod, $invoiceStatus, $issuedOn]);
            $invoiceId = $mysqli->insert_id;
            foreach ($receiptItems as $item) {
                runWrite('INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price) VALUES (?, ?, ?, ?, ?)', 'iisid', [$invoiceId, $item['service_id'], $item['description'], $item['quantity'], $item['price']]);
            }
            if ($discount > 0) {
                runWrite('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, 1, ?)', 'isd', [$invoiceId, 'Discount', -$discount]);
            }
            if ($vat > 0) {
                runWrite('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, 1, ?)', 'isd', [$invoiceId, 'Tax (' . $clinicSettings['tax_rate'] . '%)', $vat]);
            }
            $mysqli->commit();
            respond([
                'id' => $invoiceId,
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'vat' => $vat,
                'tax_rate' => $clinicSettings['tax_rate'],
                'total' => $total,
                'paid' => round($paidAmount, 2),
                'change' => round($paidAmount - $total, 2),
                'status' => $invoiceStatus,
                'items' => $receiptItems,
            ], 201);

        default:
            respond(['error' => 'Unknown operation.'], 404);
    }
} catch (InvalidArgumentException $exception) {
    rollbackIfActive();
    respond(['error' => $exception->getMessage()], 422);
} catch (mysqli_sql_exception $exception) {
    rollbackIfActive();
    if ($exception->getCode() === 1062) {
        respond(['error' => 'A record with the same unique value already exists. Check the username, email address, or SKU.'], 409);
    }
    if ($exception->getCode() === 1451) {
        respond(['error' => 'This record is used by another record and cannot be deleted yet.'], 409);
    }
    if ($exception->getCode() === 1452) {
        respond(['error' => 'A related client, patient, service, or staff record does not exist.'], 422);
    }
    error_log('Clinic API database operation failed: ' . $exception->getMessage());
    respond(['error' => 'The database operation failed. Check the PHP error log.'], 500);
} catch (Throwable $exception) {
    rollbackIfActive();
    error_log('Clinic API failed: ' . $exception->getMessage());
    respond(['error' => 'The request could not be completed. Check the PHP error log.'], 500);
}
