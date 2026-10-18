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

function validPetName(string $name): bool
{
    return preg_match("/^[\\p{L}]+(?:[ '\\x{2019}-][\\p{L}]+)*$/u", $name) === 1;
}

function validPersonName(string $name): bool
{
    return preg_match("/^[\\p{L}]+(?:[ '\\x{2019}-][\\p{L}]+)*$/u", $name) === 1;
}

function requirePhilippineMobile(string $phone): string
{
    if (preg_match('/^09[0-9]{9}$/', $phone) !== 1) {
        throw new InvalidArgumentException('Contact number must be exactly 11 digits in 09XXXXXXXXX format.');
    }
    return $phone;
}

function validatePetBreed(string $species, string $breed): void
{
    $breeds = [
        'Dog' => ['Aspin', 'Labrador Retriever', 'Golden Retriever', 'German Shepherd', 'Poodle', 'Beagle', 'Shih Tzu', 'Chihuahua', 'Pomeranian', 'Husky', 'Other'],
        'Cat' => ['Persian', 'Siamese', 'British Shorthair', 'Maine Coon', 'Ragdoll', 'Bengal', 'American Shorthair', 'Scottish Fold', 'Domestic Shorthair', 'Other'],
        'Other' => ['Other'],
    ];
    if (isset($breeds[$species]) && !in_array($breed, $breeds[$species], true)) {
        throw new InvalidArgumentException('Choose a breed that matches the selected species.');
    }
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

function validatedChoiceList(mixed $values, string $label): array
{
    if (!is_array($values) || count($values) < 1 || count($values) > 30) {
        throw new InvalidArgumentException('Enter between 1 and 30 ' . $label . ' choices.');
    }
    $choices = [];
    foreach ($values as $value) {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Each ' . $label . ' must be text.');
        }
        $choice = trim($value);
        if ($choice === '' || mb_strlen($choice, 'UTF-8') > 60) {
            throw new InvalidArgumentException('Each ' . $label . ' must be between 1 and 60 characters.');
        }
        if (!in_array($choice, $choices, true)) {
            $choices[] = $choice;
        }
    }
    if ($choices === []) {
        throw new InvalidArgumentException('Enter at least one ' . $label . ' choice.');
    }
    return $choices;
}

function requireCatalogCategory(string $type, string $name): void
{
    $category = $type === 'service'
        ? fetchOne(
            'SELECT id FROM catalog_categories WHERE category_type IN (?, ?) AND name = ? LIMIT 1',
            'sss',
            ['service', 'inventory', $name]
        )
        : fetchOne('SELECT id FROM catalog_categories WHERE category_type = ? AND name = ?', 'ss', [$type, $name]);
    if ($category === null) {
        throw new InvalidArgumentException('Choose a category from Settings before saving this item.');
    }
}

function inventorySku(string $category, int $id): string
{
    $prefix = strtoupper(substr((string) preg_replace('/[^a-zA-Z0-9]/', '', $category), 0, 5));
    if ($prefix === '') {
        $prefix = 'ITEM';
    }
    return $prefix . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
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

function lockAppointmentTime(string $appointmentAt): void
{
    if ((int) (fetchOne('SELECT GET_LOCK(CONCAT("urbanfur_appointment_", DATE_FORMAT(?, "%Y%m%d%H%i")), 5) AS acquired', 's', [$appointmentAt])['acquired'] ?? 0) !== 1) {
        throw new InvalidArgumentException('This time is being booked. Please try another available time.');
    }
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
    global $mysqli, $transactionActive;
    if ($transactionActive) {
        $mysqli->rollback();
        $transactionActive = false;
    }
}

function beginTransaction(): void
{
    global $mysqli, $transactionActive;
    $mysqli->begin_transaction();
    $transactionActive = true;
}

function commitTransaction(): void
{
    global $mysqli, $transactionActive;
    $mysqli->commit();
    $transactionActive = false;
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

function ensureAppointmentDateOpen(string $appointmentAt): void
{
    $date = substr($appointmentAt, 0, 10);
    $closure = fetchOne(
        'SELECT reason FROM appointment_closures WHERE start_date <= ? AND end_date >= ? ORDER BY start_date LIMIT 1',
        'ss',
        [$date, $date]
    );
    if ($closure !== null) {
        $reason = trim((string) $closure['reason']);
        throw new InvalidArgumentException('The clinic is closed on that date.' . ($reason !== '' ? ' Reason: ' . $reason : ''));
    }
}

function notifyAppointmentClient(int $appointmentId, string $status): void
{
    if (!in_array($status, ['Scheduled', 'Cancelled'], true)) {
        return;
    }
    $appointment = fetchOne(
        'SELECT c.id AS client_id, p.name AS pet_name, a.appointment_at
         FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN clients c ON c.id = p.client_id
         WHERE a.id = ?',
        'i',
        [$appointmentId]
    );
    if ($appointment === null) {
        throw new RuntimeException('Unable to notify the client because the appointment was not found.');
    }
    $accepted = $status === 'Scheduled';
    $title = $accepted ? 'Appointment accepted' : 'Appointment declined';
    $message = $accepted
        ? sprintf('%s’s appointment on %s was accepted by the clinic.', $appointment['pet_name'], date('M j, Y g:i A', strtotime((string) $appointment['appointment_at'])))
        : sprintf('%s’s appointment request on %s was declined by the clinic. Please contact us to choose another time.', $appointment['pet_name'], date('M j, Y g:i A', strtotime((string) $appointment['appointment_at'])));
    runWrite(
        'INSERT INTO client_notifications (client_id, appointment_id, title, message) VALUES (?, ?, ?, ?)',
        'iiss',
        [(int) $appointment['client_id'], $appointmentId, $title, $message]
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
    beginTransaction();
    global $mysqli;
    $result = $mysqli->query('SELECT id FROM clinic_settings WHERE id = 1 FOR UPDATE');
    if (!$result instanceof mysqli_result || $result->num_rows !== 1) {
        throw new RuntimeException('Unable to lock user account management.');
    }
    $result->free();
}

$data = inputData();
$transactionActive = false;
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
if ($currentUser['role'] === 'Client') {
    respond(['error' => 'Client accounts must use the client portal.'], 403);
}

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
            case 'profile':
                respond(['data' => fetchOne('SELECT id, username, full_name, email, phone, profile_image FROM users WHERE id = ?', 'i', [(int) $_SESSION['user_id']])]);

            case 'users':
                requireAdministrator();
                $users = fetchAll(
                    "SELECT id, username, full_name, email, phone, role, active, created_at
                     FROM users WHERE role <> 'Client' ORDER BY full_name, username"
                );
                foreach ($users as &$user) {
                    $user['id'] = (int) $user['id'];
                    $user['active'] = (int) $user['active'];
                }
                unset($user);
                respond(['data' => $users]);

            case 'clients':
                $rows = fetchAll(
                    'SELECT c.id, c.full_name AS name, c.phone, c.email, c.emergency_phone, c.address, c.status, c.created_at,
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
                    '                    SELECT p.id, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.client_id, p.name, p.species, p.breed, p.gender, p.birth_date, p.life_status, p.created_at,
                            p.notes, p.status, c.full_name AS owner,
                            CASE WHEN p.birth_date IS NULL THEN NULL
                                 ELSE TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) END AS age
                     FROM pets p
                     JOIN clients c ON c.id = p.client_id
                     ORDER BY p.name'
                );
                respond(['data' => $rows]);

            case 'medical-records':
                $petId = positiveId(['pet_id' => $_GET['pet_id'] ?? null], 'pet_id');
                ensureRecordExists('pets', $petId);
                $rows = fetchAll(
                    'SELECT r.id, r.pet_id, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, r.recorded_on, r.diagnosis, r.treatment, r.prescription, r.notes,
                            COALESCE(u.full_name, "Former user") AS recorded_by
                     FROM medical_records r JOIN pets p ON p.id = r.pet_id
                     LEFT JOIN users u ON u.id = r.recorded_by
                     WHERE r.pet_id = ?
                     ORDER BY r.recorded_on DESC, r.id DESC',
                    'i',
                    [$petId]
                );
                respond(['data' => $rows]);

            case 'clients-options':
                respond(['data' => fetchAll("SELECT id, full_name AS name FROM clients WHERE status = 'Active' ORDER BY full_name")]);

            case 'clients-all-options':
                respond(['data' => fetchAll('SELECT id, full_name AS name, status FROM clients ORDER BY full_name')]);

            case 'pets-options':
                respond(['data' => fetchAll("SELECT p.id, p.name, p.client_id, c.full_name AS owner FROM pets p JOIN clients c ON c.id = p.client_id WHERE p.status = 'Active' AND p.life_status = 'Alive' ORDER BY p.name")]);

            case 'staff':
                respond(['data' => fetchAll('SELECT id, full_name AS name, role, phone, email, status FROM staff ORDER BY full_name')]);

            case 'services':
                respond(['data' => fetchAll('SELECT id, name, category, description, price, active FROM services ORDER BY name')]);

            case 'catalog-categories':
                $type = (string) ($_GET['type'] ?? '');
                if ($type !== '' && !in_array($type, ['service', 'inventory'], true)) {
                    throw new InvalidArgumentException('Invalid catalog category type.');
                }
                $categories = $type === ''
                    ? fetchAll('SELECT id, name, category_type AS type FROM catalog_categories ORDER BY category_type, name')
                    : fetchAll('SELECT id, name, category_type AS type FROM catalog_categories WHERE category_type = ? ORDER BY name', 's', [$type]);
                respond(['data' => $categories]);

            case 'inventory':
                respond(['data' => fetchAll(
                    'SELECT id, sku, name, category, quantity, minimum_quantity AS min_threshold,
                            unit_price AS price, expiration_date, condition_status, created_at
                     FROM inventory ORDER BY name'
                )]);

            case 'inventory-movements':
                respond(['data' => fetchAll(
                    'SELECT m.id, m.inventory_id, i.name AS item, m.movement_type, m.quantity,
                            m.reason, m.expiration_date, m.created_at, COALESCE(u.full_name, "Former user") AS recorded_by
                     FROM inventory_movements m
                     JOIN inventory i ON i.id = m.inventory_id
                     LEFT JOIN users u ON u.id = m.recorded_by
                     ORDER BY m.created_at DESC, m.id DESC LIMIT 200'
                )]);

            case 'expenses':
                respond(['data' => fetchAll(
                    'SELECT e.id, e.reason, e.description, e.amount, e.incurred_on, e.status, e.created_at,
                            COALESCE(u.full_name, "Former user") AS created_by
                     FROM expenses e LEFT JOIN users u ON u.id = e.created_by
                     ORDER BY e.incurred_on DESC, e.id DESC'
                )]);

            case 'end-of-day-reports':
                if (!in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin', 'Staff', 'Cashier'], true)) {
                    respond(['error' => 'You do not have access to end-of-day reports.'], 403);
                }
                respond(['data' => fetchAll(
                    'SELECT r.id, r.closed_on, r.official_closing_time, r.report_json, r.created_at,
                            COALESCE(u.full_name, "Former user") AS generated_by
                     FROM end_of_day_reports r
                     LEFT JOIN users u ON u.id = r.generated_by
                     ORDER BY r.closed_on DESC LIMIT 90'
                )]);

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
                    'SELECT a.id, CONCAT("APT-", LPAD(a.id, 4, "0")) AS appointment_code, a.pet_id, a.service_id, a.staff_id, a.appointment_at,
                            DATE_FORMAT(a.appointment_at, "%Y-%m-%d") AS raw_date,
                            DATE_FORMAT(a.appointment_at, "%b %e, %Y - %h:%i %p") AS date_time,
                            a.status, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet_name, c.full_name AS owner_name,
                            COALESCE(GROUP_CONCAT(DISTINCT selected_service.name ORDER BY selected_service.name SEPARATOR ", "), s.name, NULLIF(a.notes, ""), "Unassigned service") AS service,
                            COALESCE(st.full_name, "Unassigned staff") AS vet
                     FROM appointments a
                     JOIN pets p ON p.id = a.pet_id
                     JOIN clients c ON c.id = p.client_id
                     LEFT JOIN services s ON s.id = a.service_id
                     LEFT JOIN appointment_services aps ON aps.appointment_id = a.id
                     LEFT JOIN services selected_service ON selected_service.id = aps.service_id
                     LEFT JOIN staff st ON st.id = a.staff_id
                     GROUP BY a.id, p.name, c.full_name, s.name, a.notes, st.full_name
                     ORDER BY a.appointment_at DESC'
                );
                respond(['data' => $rows]);

            case 'appointment-notifications':
                requireAdministrator();
                $requests = fetchAll(
                    'SELECT a.id, a.appointment_at, a.notes AS purpose, c.full_name AS client, c.phone,
                            p.name AS pet, p.species, p.breed,
                            COALESCE(GROUP_CONCAT(DISTINCT selected_service.name ORDER BY selected_service.name SEPARATOR ", "), s.name, "Visit") AS service
                     FROM appointments a
                     JOIN pets p ON p.id = a.pet_id
                     JOIN clients c ON c.id = p.client_id
                     LEFT JOIN services s ON s.id = a.service_id
                     LEFT JOIN appointment_services aps ON aps.appointment_id = a.id
                     LEFT JOIN services selected_service ON selected_service.id = aps.service_id
                     WHERE a.status = "Pending"
                     GROUP BY a.id, a.appointment_at, a.notes, p.name, p.species, p.breed, c.full_name, c.phone, s.name
                     ORDER BY a.created_at DESC, a.id DESC
                     LIMIT 20'
                );
                respond(['data' => $requests]);

            case 'appointment-closures':
                if (!in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Admin', 'Staff', 'Cashier'], true)) {
                    respond(['error' => 'You do not have access to appointment closures.'], 403);
                }
                respond(['data' => fetchAll(
                    'SELECT id, start_date, end_date, reason, created_at
                     FROM appointment_closures
                     WHERE end_date >= CURDATE()
                     ORDER BY start_date, end_date, id'
                )]);

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
                $counts = fetchOne('SELECT (SELECT COUNT(*) FROM pets) AS pets, (SELECT COUNT(*) FROM clients) AS clients, (SELECT COUNT(*) FROM staff WHERE status = "Active") AS staff, (SELECT COUNT(*) FROM appointments WHERE appointment_at >= NOW() AND status IN ("Pending", "Scheduled")) AS upcoming, (SELECT COUNT(*) FROM inventory WHERE quantity <= minimum_quantity) AS low_stock');
                $species = fetchAll("SELECT species, COUNT(*) AS total FROM pets WHERE status = 'Active' GROUP BY species ORDER BY total DESC");
                $services = fetchAll('SELECT s.name, COUNT(DISTINCT aps.appointment_id) AS total FROM appointment_services aps JOIN services s ON s.id = aps.service_id GROUP BY s.id ORDER BY total DESC LIMIT 5');
                $schedule = fetchAll(
                    'SELECT c.full_name AS client, c.phone, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet, p.breed,
                            a.appointment_at, a.status, COALESCE(GROUP_CONCAT(DISTINCT selected_service.name ORDER BY selected_service.name SEPARATOR ", "), s.name, "Unassigned service") AS service
                     FROM appointments a
                     JOIN pets p ON p.id = a.pet_id
                     JOIN clients c ON c.id = p.client_id
                     LEFT JOIN services s ON s.id = a.service_id
                     LEFT JOIN appointment_services aps ON aps.appointment_id = a.id
                     LEFT JOIN services selected_service ON selected_service.id = aps.service_id
                     GROUP BY a.id, p.name, c.full_name, s.name, a.notes
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
                $stockWarnings = fetchAll(
                    'SELECT name, quantity, condition_status, expiration_date
                     FROM inventory
                     WHERE condition_status = "Damaged"
                        OR (expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
                     ORDER BY condition_status DESC, expiration_date, name LIMIT 10'
                );
                foreach ($stockWarnings as $warning) {
                    $isDamaged = $warning['condition_status'] === 'Damaged';
                    $notifications[] = [
                        'type' => 'stock',
                        'icon' => $isDamaged ? 'fa-triangle-exclamation' : 'fa-hourglass-end',
                        'title' => $isDamaged ? 'Damaged stock' : ((string) $warning['expiration_date'] <= date('Y-m-d') ? 'Expired stock' : 'Expiring stock'),
                        'message' => $warning['name'] . ($isDamaged ? ' requires review.' : ' — expires ' . date('M j, Y', strtotime((string) $warning['expiration_date']))),
                        'date' => '',
                    ];
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
        case 'profile:update':
            $id = (int) $_SESSION['user_id'];
            $username = trim(requiredString($data, 'username', 80));
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) throw new InvalidArgumentException('Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.');
            $fullName = requiredString($data, 'full_name', 150);
            if (!validPersonName($fullName)) throw new InvalidArgumentException('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $email = optionalString($data, 'email', 190);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
            $phone = optionalString($data, 'phone', 40);
            $hasImage = array_key_exists('profile_image', $data);
            $image = $data['profile_image'] ?? null;
            if ($image !== null && $image !== '') {
                if (!is_string($image) || !preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/]+={0,2})$#', $image, $matches)) throw new InvalidArgumentException('Choose a PNG, JPG, or WebP profile image.');
                $decodedImage = base64_decode($matches[2], true);
                if ($decodedImage === false || strlen($decodedImage) > 1048576) throw new InvalidArgumentException('Profile images must be 1 MB or smaller.');
                $imageInfo = getimagesizefromstring($decodedImage);
                if (!is_array($imageInfo) || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg', 'image/webp'], true)) throw new InvalidArgumentException('The selected profile image could not be read.');
            } elseif ($image === '') {
                $image = null;
            }
            if (!$hasImage) {
                runWrite('UPDATE users SET username = ?, full_name = ?, email = ?, phone = ? WHERE id = ?', 'ssssi', [$username, $fullName, $email, $phone, $id]);
            } else {
                runWrite('UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?', 'sssssi', [$username, $fullName, $email, $phone, $image, $id]);
            }
            $_SESSION['user_name'] = $fullName;
            respond(['updated' => true]);

        case 'audit-logs:clear':
            requireAdministrator();
            $statement = runWrite('DELETE FROM audit_logs', '', []);
            $deletedCount = $statement->affected_rows;
            $statement->close();
            respond(['deleted' => $deletedCount]);

        case 'users:create':
            requireAdministrator();
            $username = trim(requiredString($data, 'username', 80));
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
                throw new InvalidArgumentException('Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.');
            }
            $fullName = requiredString($data, 'full_name', 150);
            if (!validPersonName($fullName)) throw new InvalidArgumentException('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $email = optionalString($data, 'email', 190);
            $phone = optionalString($data, 'phone', 40);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid recovery email address.');
            }
            if ($phone !== null) {
                $phone = requirePhilippineMobile($phone);
            }
            if ($email === null && $phone === null) {
                throw new InvalidArgumentException('Provide a recovery email address or mobile number.');
            }
            $role = validEnum($data, 'role', ['Administrator', 'Staff', 'Vet', 'Receptionist', 'Cashier']);
            $password = requiredString($data, 'password', 255);
            if (mb_strlen($password, 'UTF-8') < 8) {
                throw new InvalidArgumentException('Password must be at least 8 characters long.');
            }

            lockUserManagement();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = runWrite(
                'INSERT INTO users (username, password_hash, full_name, email, phone, role, active) VALUES (?, ?, ?, ?, ?, ?, 1)',
                'ssssss',
                [$username, $passwordHash, $fullName, $email, $phone, $role]
            );
            $newUserId = $mysqli->insert_id;
            $statement->close();
            recordAuditLog('user.created', ['user_id' => $newUserId, 'username' => $username, 'role' => $role]);
            commitTransaction();
            respond(['id' => $newUserId], 201);

        case 'users:update':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $username = trim(requiredString($data, 'username', 80));
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
                throw new InvalidArgumentException('Username must be 3-80 characters and use only letters, numbers, dots, dashes, or underscores.');
            }
            $fullName = requiredString($data, 'full_name', 150);
            if (!validPersonName($fullName)) throw new InvalidArgumentException('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $email = optionalString($data, 'email', 190);
            $phone = optionalString($data, 'phone', 40);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid recovery email address.');
            }
            if ($phone !== null) {
                $phone = requirePhilippineMobile($phone);
            }
            if ($email === null && $phone === null) {
                throw new InvalidArgumentException('Provide a recovery email address or mobile number.');
            }
            $role = validEnum($data, 'role', ['Administrator', 'Staff', 'Vet', 'Receptionist', 'Cashier']);
            $active = filter_var($data['active'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($active, [0, 1], true)) {
                throw new InvalidArgumentException('Account status is invalid.');
            }
            $password = $data['password'] ?? '';
            if (!is_string($password) || ($password !== '' && mb_strlen($password, 'UTF-8') < 8)) {
                throw new InvalidArgumentException('A new password must be at least 8 characters long.');
            }

            lockUserManagement();
            $target = fetchOne('SELECT id, role, active FROM users WHERE id = ? FOR UPDATE', 'i', [$id]);
            if ($target === null) {
                throw new InvalidArgumentException('User account was not found.');
            }
            if ($email !== null && fetchOne('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1', 'si', [$email, $id]) !== null) {
                throw new InvalidArgumentException('That recovery email is already assigned to another account.');
            }
            if ($phone !== null && fetchOne('SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1', 'si', [$phone, $id]) !== null) {
                throw new InvalidArgumentException('That recovery mobile number is already assigned to another account.');
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
                    'UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, active = ? WHERE id = ?',
                    'sssssii',
                    [$username, $fullName, $email, $phone, $role, $active, $id]
                );
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = runWrite(
                    'UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, active = ?, password_hash = ? WHERE id = ?',
                    'sssssisi',
                    [$username, $fullName, $email, $phone, $role, $active, $passwordHash, $id]
                );
            }
            $statement->close();
            recordAuditLog('user.updated', ['user_id' => $id, 'username' => $username, 'role' => $role, 'active' => $active, 'password_reset' => $password !== '']);
            commitTransaction();
            respond(['updated' => true]);

        case 'appointment-closures:create':
            requireAdministrator();
            $startDate = validDate($data, 'start_date');
            $endDate = validDate($data, 'end_date');
            $reason = optionalString($data, 'reason', 255) ?? '';
            if ($startDate === null || $endDate === null || $startDate > $endDate) throw new InvalidArgumentException('Choose a valid closure date range with the end date on or after the start date.');
            beginTransaction();
            $statement = runWrite(
                'INSERT INTO appointment_closures (start_date, end_date, reason, created_by) VALUES (?, ?, ?, ?)',
                'sssi',
                [$startDate, $endDate, $reason, (int) $_SESSION['user_id']]
            );
            $closureId = (int) $mysqli->insert_id;
            recordAuditLog('appointment.closure_created', ['start_date' => $startDate, 'end_date' => $endDate, 'reason' => $reason]);
            commitTransaction();
            respond(['id' => $closureId], 201);

        case 'appointment-closures:delete':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $closure = fetchOne('SELECT start_date, end_date, reason FROM appointment_closures WHERE id = ?', 'i', [$id]);
            if ($closure === null) throw new InvalidArgumentException('Clinic closure was not found.');
            beginTransaction();
            runWrite('DELETE FROM appointment_closures WHERE id = ?', 'i', [$id]);
            recordAuditLog('appointment.closure_deleted', $closure);
            commitTransaction();
            respond(['deleted' => true]);

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
                $methods = validatedChoiceList($data['accepted_payment_methods'], 'payment method');
                if (count(array_filter($methods, static fn(string $method): bool => $method !== 'Pay Later')) === 0) {
                    throw new InvalidArgumentException('At least one payment method must accept a payment now.');
                }
                $updatedPreferences['accepted_payment_methods'] = $methods;
            }
            if (array_key_exists('expense_reasons', $data)) {
                $updatedPreferences['expense_reasons'] = validatedChoiceList($data['expense_reasons'], 'expense reason');
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
            if (array_key_exists('theme_mode', $data)) {
                $updatedPreferences['theme_mode'] = validEnum($data, 'theme_mode', ['system', 'light', 'dark']);
            }
            if (array_key_exists('sidebar_color', $data)) {
                $sidebarColor = $data['sidebar_color'];
                if (!is_string($sidebarColor) || !preg_match('/^#[0-9a-fA-F]{6}$/', $sidebarColor)) {
                    throw new InvalidArgumentException('Choose a valid sidebar color.');
                }
                $updatedPreferences['sidebar_color'] = $sidebarColor;
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
            requireCatalogCategory('service', $category);
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

        case 'catalog-categories:create':
            requireAdministrator();
            $type = validEnum($data, 'type', ['service', 'inventory']);
            $name = requiredString($data, 'name', 100);
            $statement = runWrite('INSERT INTO catalog_categories (category_type, name) VALUES (?, ?)', 'ss', [$type, $name]);
            $categoryId = (int) $mysqli->insert_id;
            $statement->close();
            recordAuditLog('catalog_category.created', ['type' => $type, 'name' => $name]);
            respond(['id' => $categoryId], 201);

        case 'catalog-categories:update':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 100);
            $category = fetchOne('SELECT category_type, name FROM catalog_categories WHERE id = ?', 'i', [$id]);
            if ($category === null) {
                throw new InvalidArgumentException('Category was not found.');
            }
            beginTransaction();
            runWrite('UPDATE catalog_categories SET name = ? WHERE id = ?', 'si', [$name, $id]);
            $targetTable = $category['category_type'] === 'service' ? 'services' : 'inventory';
            runWrite("UPDATE {$targetTable} SET category = ? WHERE category = ?", 'ss', [$name, $category['name']]);
            if ($category['category_type'] === 'inventory') {
                $inventoryRows = fetchAll('SELECT id FROM inventory WHERE category = ?', 's', [$name]);
                foreach ($inventoryRows as $inventoryRow) {
                    $sku = inventorySku($name, (int) $inventoryRow['id']);
                    runWrite('UPDATE inventory SET sku = ? WHERE id = ?', 'si', [$sku, (int) $inventoryRow['id']]);
                }
            }
            recordAuditLog('catalog_category.renamed', ['type' => $category['category_type'], 'before' => $category['name'], 'after' => $name]);
            commitTransaction();
            respond(['updated' => true]);

        case 'catalog-categories:delete':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $category = fetchOne('SELECT category_type, name FROM catalog_categories WHERE id = ?', 'i', [$id]);
            if ($category === null) {
                throw new InvalidArgumentException('Category was not found.');
            }
            $targetTable = $category['category_type'] === 'service' ? 'services' : 'inventory';
            if (fetchOne("SELECT id FROM {$targetTable} WHERE category = ? LIMIT 1", 's', [$category['name']]) !== null) {
                throw new InvalidArgumentException('Move all services or items to another category before deleting this category.');
            }
            runWrite('DELETE FROM catalog_categories WHERE id = ?', 'i', [$id]);
            recordAuditLog('catalog_category.deleted', ['type' => $category['category_type'], 'name' => $category['name']]);
            respond(['deleted' => true]);

        case 'clients:create':
            $name = requiredString($data, 'name', 150);
            if (!validPersonName($name)) throw new InvalidArgumentException('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $phone = requirePhilippineMobile(requiredString($data, 'phone', 40));
            $email = requiredString($data, 'email', 80);
            $emergencyPhone = optionalString($data, 'emergency_phone', 40);
            if ($emergencyPhone !== null) $emergencyPhone = requirePhilippineMobile($emergencyPhone);
            $address = optionalString($data, 'address', 255);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            beginTransaction();
            runWrite('INSERT INTO clients (full_name, phone, email, emergency_phone, address) VALUES (?, ?, ?, ?, ?)', 'sssss', [$name, $phone, $email, $emergencyPhone, $address]);
            $clientId = (int) $mysqli->insert_id;
            $username = strtolower($email);
            $passwordCharacters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
            $temporaryPassword = '';
            for ($passwordIndex = 0; $passwordIndex < 8; $passwordIndex++) {
                $temporaryPassword .= $passwordCharacters[random_int(0, strlen($passwordCharacters) - 1)];
            }
            $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
            runWrite(
                'INSERT INTO users (username, password_hash, full_name, email, phone, role, active) VALUES (?, ?, ?, ?, ?, "Client", 1)',
                'sssss',
                [$username, $passwordHash, $name, $email, $phone]
            );
            $userId = (int) $mysqli->insert_id;
            runWrite('UPDATE clients SET account_user_id = ? WHERE id = ?', 'ii', [$userId, $clientId]);
            recordAuditLog('walk_in_client.account_created', ['client_id' => $clientId, 'user_id' => $userId]);
            commitTransaction();
            respond(['id' => $clientId, 'account' => ['username' => $username, 'temporary_password' => $temporaryPassword]], 201);

        case 'clients:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            if (!validPersonName($name)) throw new InvalidArgumentException('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $phone = requirePhilippineMobile(requiredString($data, 'phone', 40));
            $email = optionalString($data, 'email', 190);
            $emergencyPhone = optionalString($data, 'emergency_phone', 40);
            if ($emergencyPhone !== null) $emergencyPhone = requirePhilippineMobile($emergencyPhone);
            $address = optionalString($data, 'address', 255);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Enter a valid email address.');
            }
            beginTransaction();
            $statement = runWrite('UPDATE clients SET full_name = ?, phone = ?, email = ?, emergency_phone = ?, address = ?, status = ? WHERE id = ?', 'ssssssi', [$name, $phone, $email, $emergencyPhone, $address, $status, $id]);
            runWrite(
                'UPDATE users u JOIN clients c ON c.account_user_id = u.id
                 SET u.full_name = ?, u.phone = ?, u.email = ?
                 WHERE c.id = ? AND u.role = "Client"',
                'sssi',
                [$name, $phone, $email, $id]
            );
            commitTransaction();
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
            if (!validPetName($name)) throw new InvalidArgumentException('Pet name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $speciesChoice = validEnum($data, 'species', ['Dog', 'Cat', 'Other']);
            $species = $speciesChoice === 'Other' ? requiredString($data, 'species_other', 60) : $speciesChoice;
            $breedChoice = requiredString($data, 'breed', 100);
            $breed = $breedChoice === 'Other' ? requiredString($data, 'breed_other', 100) : $breedChoice;
            validatePetBreed($speciesChoice, $breedChoice);
            $gender = optionalString($data, 'gender', 20);
            $birthDate = validDate($data, 'birth_date');
            if ($birthDate === null || $birthDate > date('Y-m-d')) throw new InvalidArgumentException('Enter a valid date of birth that is not in the future.');
            $notes = optionalString($data, 'notes', 60000);
            $lifeStatus = validEnum($data, 'life_status', ['Alive', 'Deceased']);
            $statement = runWrite('INSERT INTO pets (client_id, name, species, breed, gender, birth_date, notes, life_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 'isssssss', [$clientId, $name, $species, $breed, $gender, $birthDate, $notes, $lifeStatus]);
            respond(['id' => $mysqli->insert_id], 201);

        case 'pets:update':
            $id = positiveId($data, 'id');
            ensureRecordExists('pets', $id);
            $clientId = positiveId($data, 'client_id');
            $name = requiredString($data, 'name', 100);
            if (!validPetName($name)) throw new InvalidArgumentException('Pet name may contain letters only, with spaces, apostrophes, or hyphens between words.');
            $speciesChoice = validEnum($data, 'species', ['Dog', 'Cat', 'Other']);
            $species = $speciesChoice === 'Other' ? requiredString($data, 'species_other', 60) : $speciesChoice;
            $breedChoice = requiredString($data, 'breed', 100);
            $breed = $breedChoice === 'Other' ? requiredString($data, 'breed_other', 100) : $breedChoice;
            validatePetBreed($speciesChoice, $breedChoice);
            $gender = optionalString($data, 'gender', 20);
            $birthDate = validDate($data, 'birth_date');
            $notes = optionalString($data, 'notes', 60000);
            $status = validEnum($data, 'status', ['Active', 'Inactive']);
            $lifeStatus = validEnum($data, 'life_status', ['Alive', 'Deceased']);
            $statement = runWrite('UPDATE pets SET client_id = ?, name = ?, species = ?, breed = ?, gender = ?, birth_date = ?, notes = ?, status = ?, life_status = ? WHERE id = ?', 'issssssssi', [$clientId, $name, $species, $breed, $gender, $birthDate, $notes, $status, $lifeStatus, $id]);
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

        case 'medical-records:create':
            $petId = positiveId($data, 'pet_id');
            ensureRecordExists('pets', $petId);
            $recordedOn = validDate($data, 'recorded_on');
            if ($recordedOn === null) {
                throw new InvalidArgumentException('A valid visit date is required.');
            }
            $diagnosis = requiredString($data, 'diagnosis', 255);
            $treatment = optionalString($data, 'treatment', 60000);
            $prescription = optionalString($data, 'prescription', 60000);
            $notes = optionalString($data, 'notes', 60000);
            $statement = runWrite(
                'INSERT INTO medical_records (pet_id, recorded_on, diagnosis, treatment, prescription, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                'isssssi',
                [$petId, $recordedOn, $diagnosis, $treatment, $prescription, $notes, (int) $_SESSION['user_id']]
            );
            respond(['id' => $mysqli->insert_id], 201);

        case 'staff:create':
            $name = requiredString($data, 'name', 150);
            $role = validEnum($data, 'role', ['Veterinarian', 'Vet Tech', 'Receptionist']);
            $phone = requirePhilippineMobile(requiredString($data, 'phone', 40));
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
            $phone = requirePhilippineMobile(requiredString($data, 'phone', 40));
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
            requireCatalogCategory('service', $category);
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
            requireCatalogCategory('service', $category);
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
            $category = requiredString($data, 'category', 100);
            requireCatalogCategory('inventory', $category);
            if (fetchOne('SELECT id FROM inventory WHERE name = ?', 's', [$name]) !== null) {
                throw new InvalidArgumentException('An item with this name already exists in inventory.');
            }
            $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
            $minimum = filter_var($data['min_threshold'] ?? getClinicSettings()['default_minimum_quantity'], FILTER_VALIDATE_INT);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $expirationDate = validDate($data, 'expiration_date');
            if ($quantity === false || $quantity < 0 || $minimum === false || $minimum < 0 || $price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter valid stock quantities and a non-negative price.');
            }
            beginTransaction();
            runWrite('INSERT INTO inventory (sku, name, category, quantity, minimum_quantity, unit_price, expiration_date) VALUES ("", ?, ?, ?, ?, ?, ?)', 'ssiids', [$name, $category, $quantity, $minimum, $price, $expirationDate]);
            $id = $mysqli->insert_id;
            $sku = inventorySku($category, $id);
            runWrite('UPDATE inventory SET sku = ? WHERE id = ?', 'si', [$sku, $id]);
            if ($quantity > 0) {
                runWrite(
                    'INSERT INTO inventory_movements (inventory_id, movement_type, quantity, reason, expiration_date, recorded_by)
                     VALUES (?, "Stock In", ?, "Initial stock", ?, ?)',
                    'iisi',
                    [$id, $quantity, $expirationDate, (int) $_SESSION['user_id']]
                );
            }
            recordAuditLog('inventory.item_created', ['inventory_id' => $id, 'initial_stock' => $quantity]);
            commitTransaction();
            respond(['id' => $id, 'sku' => $sku], 201);

        case 'inventory:update':
            $id = positiveId($data, 'id');
            $name = requiredString($data, 'name', 150);
            $category = requiredString($data, 'category', 100);
            requireCatalogCategory('inventory', $category);
            if (fetchOne('SELECT id FROM inventory WHERE name = ? AND id <> ?', 'si', [$name, $id]) !== null) {
                throw new InvalidArgumentException('An item with this name already exists in inventory.');
            }
            $minimum = filter_var($data['min_threshold'] ?? null, FILTER_VALIDATE_INT);
            $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $expirationDate = validDate($data, 'expiration_date');
            $condition = validEnum($data, 'condition_status', ['Good', 'Damaged']);
            if ($minimum === false || $minimum < 0 || $price === false || $price < 0 || $price > 99999999.99) {
                throw new InvalidArgumentException('Enter valid stock quantities and a non-negative price.');
            }
            $sku = inventorySku($category, $id);
            $statement = runWrite(
                'UPDATE inventory SET name = ?, sku = ?, category = ?, minimum_quantity = ?,
                 unit_price = ?, expiration_date = ?, condition_status = ? WHERE id = ?',
                'sssidssi',
                [$name, $sku, $category, $minimum, $price, $expirationDate, $condition, $id]
            );
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'inventory:stock-in':
            $id = positiveId($data, 'id');
            $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
            $reason = requiredString($data, 'reason', 255);
            $expirationDate = validDate($data, 'expiration_date');
            if ($quantity === false || $quantity < 1 || $quantity > 1000000) throw new InvalidArgumentException('Enter a valid stock-in quantity.');
            beginTransaction();
            if (fetchOne('SELECT id FROM inventory WHERE id = ? FOR UPDATE', 'i', [$id]) === null) throw new InvalidArgumentException('Inventory item was not found.');
            runWrite('UPDATE inventory SET quantity = quantity + ?, expiration_date = COALESCE(?, expiration_date) WHERE id = ?', 'isi', [$quantity, $expirationDate, $id]);
            runWrite(
                'INSERT INTO inventory_movements (inventory_id, movement_type, quantity, reason, expiration_date, recorded_by)
                 VALUES (?, "Stock In", ?, ?, ?, ?)',
                'iissi',
                [$id, $quantity, $reason, $expirationDate, (int) $_SESSION['user_id']]
            );
            recordAuditLog('inventory.stock_in', ['inventory_id' => $id, 'quantity' => $quantity, 'reason' => $reason]);
            commitTransaction();
            respond(['updated' => true]);

        case 'inventory:stock-out':
            $id = positiveId($data, 'id');
            $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
            $reason = validEnum($data, 'reason', ['Used', 'Damaged', 'Expired', 'Adjustment', 'Other']);
            $notes = $reason === 'Other' ? requiredString($data, 'other_reason', 255) : (optionalString($data, 'notes', 255) ?? $reason);
            if ($quantity === false || $quantity < 1 || $quantity > 1000000) throw new InvalidArgumentException('Enter a valid stock-out quantity.');
            beginTransaction();
            $statement = runWrite(
                'UPDATE inventory SET quantity = quantity - ?,
                 condition_status = IF(? = "Damaged", "Damaged", condition_status),
                 expiration_date = IF(? = "Expired", CURDATE(), expiration_date)
                 WHERE id = ? AND quantity >= ?',
                'issii',
                [$quantity, $reason, $reason, $id, $quantity]
            );
            if ($statement->affected_rows !== 1) throw new InvalidArgumentException('Stock-out quantity exceeds available stock or the item was not found.');
            runWrite(
                'INSERT INTO inventory_movements (inventory_id, movement_type, quantity, reason, recorded_by)
                 VALUES (?, "Stock Out", ?, ?, ?)',
                'iisi',
                [$id, $quantity, $notes, (int) $_SESSION['user_id']]
            );
            recordAuditLog('inventory.stock_out', ['inventory_id' => $id, 'quantity' => $quantity, 'reason' => $notes]);
            commitTransaction();
            respond(['updated' => true]);

        case 'expenses:create':
            requireAdministrator();
            $expenseReasons = getClinicPreferences()['expense_reasons'] ?? ['Office Supplies', 'Utilities', 'Rent', 'Clinic Supplies', 'Medication', 'Maintenance', 'Transportation', 'Other'];
            if (!is_array($expenseReasons)) throw new RuntimeException('Expense reason settings are invalid.');
            $reason = requiredString($data, 'reason', 60);
            if (!in_array($reason, $expenseReasons, true)) throw new InvalidArgumentException('Choose an expense reason enabled in billing settings.');
            $description = $reason === 'Other'
                ? requiredString($data, 'other_reason', 255)
                : (optionalString($data, 'description', 255) ?? $reason);
            $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $incurredOn = validDate($data, 'incurred_on');
            if ($amount === false || $amount <= 0 || $amount > 99999999.99 || $incurredOn === null) {
                throw new InvalidArgumentException('Enter a valid expense amount and date.');
            }
            beginTransaction();
            runWrite(
                'INSERT INTO expenses (reason, description, amount, incurred_on, created_by) VALUES (?, ?, ?, ?, ?)',
                'ssdsi',
                [$reason, $description, $amount, $incurredOn, (int) $_SESSION['user_id']]
            );
            $expenseId = (int) $mysqli->insert_id;
            recordAuditLog('expense.created', ['expense_id' => $expenseId, 'reason' => $reason, 'amount' => $amount]);
            commitTransaction();
            respond(['id' => $expenseId], 201);

        case 'expenses:update':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Unpaid', 'Paid']);
            beginTransaction();
            $expense = fetchOne('SELECT status, reason, amount FROM expenses WHERE id = ? FOR UPDATE', 'i', [$id]);
            if ($expense === null) throw new InvalidArgumentException('Expense was not found.');
            runWrite('UPDATE expenses SET status = ? WHERE id = ?', 'si', [$status, $id]);
            recordAuditLog('expense.status_updated', ['expense_id' => $id, 'from' => $expense['status'], 'to' => $status]);
            commitTransaction();
            respond(['updated' => true]);

        case 'end-of-day-reports:close':
            requireAdministrator();
            $closedOn = date('Y-m-d');
            $existing = fetchOne('SELECT id, closed_on, official_closing_time, report_json FROM end_of_day_reports WHERE closed_on = ?', 's', [$closedOn]);
            if ($existing !== null) {
                $existing['report'] = json_decode((string) $existing['report_json'], true);
                unset($existing['report_json']);
                respond(['data' => $existing, 'already_closed' => true]);
            }
            $closingTime = date('H:i:s');
            beginTransaction();
            $appointments = fetchAll(
                'SELECT a.id, a.appointment_at, a.status, c.full_name AS client, p.name AS pet,
                        COALESCE(GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", "), "Visit") AS service
                 FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN clients c ON c.id = p.client_id
                 LEFT JOIN appointment_services aps ON aps.appointment_id = a.id
                 LEFT JOIN services s ON s.id = aps.service_id
                 WHERE DATE(a.appointment_at) = ? GROUP BY a.id, c.full_name, p.name ORDER BY a.appointment_at, a.id',
                's', [$closedOn]
            );
            $invoices = fetchAll(
                'SELECT i.id, i.issued_on, i.amount, i.payment_method, i.status, c.full_name AS client,
                        COALESCE(GROUP_CONCAT(ii.description ORDER BY ii.id SEPARATOR ", "), "Invoice") AS details
                 FROM invoices i JOIN clients c ON c.id = i.client_id
                 LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
                 WHERE i.issued_on = ? GROUP BY i.id, c.full_name ORDER BY i.id',
                's', [$closedOn]
            );
            $expensesToday = fetchAll(
                'SELECT id, reason, description, amount, status FROM expenses WHERE incurred_on = ? ORDER BY id',
                's', [$closedOn]
            );
            $movements = fetchAll(
                'SELECT m.id, i.name AS item, m.movement_type, m.quantity, m.reason, m.expiration_date
                 FROM inventory_movements m JOIN inventory i ON i.id = m.inventory_id
                 WHERE DATE(m.created_at) = ? ORDER BY m.id',
                's', [$closedOn]
            );
            $newClients = fetchAll('SELECT id, full_name FROM clients WHERE DATE(created_at) = ? ORDER BY id', 's', [$closedOn]);
            $newPets = fetchAll('SELECT id, name, species, breed FROM pets WHERE DATE(created_at) = ? ORDER BY id', 's', [$closedOn]);
            $visits = fetchAll(
                'SELECT r.id, r.recorded_on, r.diagnosis, p.name AS pet, c.full_name AS client
                 FROM medical_records r JOIN pets p ON p.id = r.pet_id JOIN clients c ON c.id = p.client_id
                 WHERE r.recorded_on = ? ORDER BY r.id',
                's', [$closedOn]
            );
            $totals = [
                'appointments' => count($appointments),
                'completed_appointments' => count(array_filter($appointments, static fn(array $row): bool => $row['status'] === 'Completed')),
                'clients_added' => count($newClients),
                'pets_added' => count($newPets),
                'invoices' => count($invoices),
                'paid_sales' => array_sum(array_map(static fn(array $row): float => $row['status'] === 'Paid' ? (float) $row['amount'] : 0.0, $invoices)),
                'unpaid_invoice_balance' => array_sum(array_map(static fn(array $row): float => in_array($row['status'], ['Pending', 'Overdue'], true) ? (float) $row['amount'] : 0.0, $invoices)),
                'expenses' => count($expensesToday),
                'expense_total' => array_sum(array_map(static fn(array $row): float => (float) $row['amount'], $expensesToday)),
                'stock_in_units' => array_sum(array_map(static fn(array $row): int => $row['movement_type'] === 'Stock In' ? (int) $row['quantity'] : 0, $movements)),
                'stock_out_units' => array_sum(array_map(static fn(array $row): int => $row['movement_type'] === 'Stock Out' ? (int) $row['quantity'] : 0, $movements)),
                'medical_records' => count($visits),
            ];
            $report = [
                'closed_on' => $closedOn,
                'official_closing_time' => $closingTime,
                'totals' => $totals,
                'activities' => [
                    'appointments' => $appointments, 'invoices' => $invoices, 'expenses' => $expensesToday,
                    'stock_movements' => $movements, 'new_clients' => $newClients, 'new_pets' => $newPets,
                    'medical_records' => $visits,
                ],
            ];
            $json = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) throw new RuntimeException('Unable to serialize end-of-day operations.');
            runWrite(
                'INSERT INTO end_of_day_reports (closed_on, official_closing_time, report_json, generated_by) VALUES (?, ?, ?, ?)',
                'sssi',
                [$closedOn, $closingTime, $json, (int) $_SESSION['user_id']]
            );
            $reportId = (int) $mysqli->insert_id;
            recordAuditLog('end_of_day.closed', ['report_id' => $reportId, 'closed_on' => $closedOn, 'official_closing_time' => $closingTime]);
            commitTransaction();
            respond(['data' => ['id' => $reportId, 'closed_on' => $closedOn, 'official_closing_time' => $closingTime, 'report' => $report]], 201);

        case 'inventory:delete':
            $id = positiveId($data, 'id');
            if (fetchOne('SELECT id FROM inventory_movements WHERE inventory_id = ? LIMIT 1', 'i', [$id]) !== null) {
                throw new InvalidArgumentException('This item has stock history and cannot be deleted. Keep its movement records for audit.');
            }
            deleteById('inventory', $id);
            respond(['deleted' => true]);

        case 'appointments:create':
            $petId = positiveId($data, 'pet_id');
            $petForAppointment = fetchOne('SELECT id FROM pets WHERE id = ? AND status = "Active" AND life_status = "Alive"', 'i', [$petId]);
            if ($petForAppointment === null) throw new InvalidArgumentException('Only living, active pets can be scheduled for appointments.');
            $serviceId = positiveId($data, 'service_id', true);
            $staffId = positiveId($data, 'staff_id', true);
            $appointmentAt = requiredDateTime($data, 'appointment_at');
            ensureAppointmentDateOpen($appointmentAt);
            if (strtotime($appointmentAt) <= time()) {
                throw new InvalidArgumentException('Appointments must be scheduled for a future time.');
            }
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
            lockAppointmentTime($appointmentAt);
            $slotConflict = $staffId === null
                ? fetchOne('SELECT id FROM appointments WHERE appointment_at = ? AND status NOT IN ("Cancelled", "Completed", "No Show") LIMIT 1', 's', [$appointmentAt])
                : fetchOne('SELECT id FROM appointments WHERE appointment_at = ? AND (staff_id = ? OR staff_id IS NULL) AND status NOT IN ("Cancelled", "Completed", "No Show") LIMIT 1', 'si', [$appointmentAt, $staffId]);
            if ($slotConflict !== null) {
                throw new InvalidArgumentException('That veterinarian already has an appointment at this time.');
            }
            $serviceIds = $data['service_ids'] ?? (isset($data['service_id']) && $data['service_id'] !== '' ? [$data['service_id']] : []);
            if (!is_array($serviceIds) || count($serviceIds) > 30) throw new InvalidArgumentException('Select one or more valid services.');
            $serviceIds = array_values(array_unique(array_map(static function ($serviceId): int {
                if (filter_var($serviceId, FILTER_VALIDATE_INT) === false || (int) $serviceId < 1) throw new InvalidArgumentException('Choose valid services.');
                return (int) $serviceId;
            }, $serviceIds)));
            if ($serviceIds === []) throw new InvalidArgumentException('Select at least one service.');
            foreach ($serviceIds as $selectedServiceId) {
                if (fetchOne('SELECT id FROM services WHERE id = ? AND active = 1', 'i', [$selectedServiceId]) === null) throw new InvalidArgumentException('One of the selected services is unavailable.');
            }
            $notes = optionalString($data, 'notes', 60000);
            $primaryServiceId = $serviceIds[0] ?? $serviceId;
            beginTransaction();
            $statement = runWrite("INSERT INTO appointments (pet_id, service_id, staff_id, appointment_at, notes) VALUES (?, ?, ?, ?, ?)", 'iiiss', [$petId, $primaryServiceId, $staffId, $appointmentAt, $notes]);
            $appointmentId = (int) $mysqli->insert_id;
            foreach ($serviceIds as $selectedServiceId) runWrite('INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)', 'ii', [$appointmentId, $selectedServiceId]);
            commitTransaction();
            respond(['id' => $mysqli->insert_id], 201);

        case 'appointments:update':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled', 'No Show']);
            $current = fetchOne('SELECT status FROM appointments WHERE id = ?', 'i', [$id]);
            if ($current === null) throw new InvalidArgumentException('Appointment was not found.');
            beginTransaction();
            $statement = runWrite('UPDATE appointments SET status = ? WHERE id = ?', 'si', [$status, $id]);
            if ($current['status'] === 'Pending' && in_array($status, ['Scheduled', 'Cancelled'], true)) {
                notifyAppointmentClient($id, $status);
                recordAuditLog('appointment.request_' . ($status === 'Scheduled' ? 'accept' : 'decline'), ['appointment_id' => $id]);
            }
            commitTransaction();
            respond(['updated' => $statement->affected_rows === 1]);

        case 'appointments:decision':
            requireAdministrator();
            $id = positiveId($data, 'id');
            $decision = validEnum($data, 'decision', ['accept', 'decline']);
            $status = $decision === 'accept' ? 'Scheduled' : 'Cancelled';
            beginTransaction();
            $appointment = fetchOne('SELECT id, status FROM appointments WHERE id = ? FOR UPDATE', 'i', [$id]);
            if ($appointment === null || $appointment['status'] !== 'Pending') {
                rollbackIfActive();
                throw new InvalidArgumentException('This appointment request was already handled or no longer exists.');
            }
            runWrite('UPDATE appointments SET status = ? WHERE id = ? AND status = "Pending"', 'si', [$status, $id]);
            notifyAppointmentClient($id, $status);
            recordAuditLog('appointment.request_' . $decision, ['appointment_id' => $id]);
            commitTransaction();
            respond(['updated' => true, 'status' => $status]);

        case 'appointments:reschedule':
            $id = positiveId($data, 'id');
            $appointmentAt = requiredDateTime($data, 'appointment_at');
            ensureAppointmentDateOpen($appointmentAt);
            ensureAppointmentDateOpen($appointmentAt);
            if (strtotime($appointmentAt) <= time()) {
                throw new InvalidArgumentException('Appointments can only be moved to a future time.');
            }
            $current = fetchOne('SELECT id, staff_id, status FROM appointments WHERE id = ?', 'i', [$id]);
            if ($current === null) {
                throw new InvalidArgumentException('Appointment was not found.');
            }
            if (in_array($current['status'], ['Completed', 'Cancelled', 'No Show'], true)) {
                throw new InvalidArgumentException('Completed, cancelled, or missed appointments cannot be rescheduled.');
            }
            $appointmentSettings = getClinicSettings();
            $scheduledTime = substr($appointmentAt, 11, 5);
            $openingTime = $appointmentSettings['opening_time'];
            $closingTime = $appointmentSettings['closing_time'];
            $appointmentMinutes = ((int) substr($scheduledTime, 0, 2) * 60) + (int) substr($scheduledTime, 3, 2);
            $openingMinutes = ((int) substr($openingTime, 0, 2) * 60) + (int) substr($openingTime, 3, 2);
            $closingMinutes = ((int) substr($closingTime, 0, 2) * 60) + (int) substr($closingTime, 3, 2);
            $interval = $appointmentSettings['appointment_interval_minutes'];
            if ($appointmentMinutes < $openingMinutes || $appointmentMinutes + $interval > $closingMinutes || ($appointmentMinutes - $openingMinutes) % $interval !== 0) {
                throw new InvalidArgumentException('Choose a time during clinic hours that matches the appointment interval.');
            }
            lockAppointmentTime($appointmentAt);
            $slotConflict = $current['staff_id'] === null
                ? fetchOne('SELECT id FROM appointments WHERE appointment_at = ? AND id <> ? AND status NOT IN ("Cancelled", "Completed", "No Show") LIMIT 1', 'si', [$appointmentAt, $id])
                : fetchOne('SELECT id FROM appointments WHERE appointment_at = ? AND id <> ? AND (staff_id = ? OR staff_id IS NULL) AND status NOT IN ("Cancelled", "Completed", "No Show") LIMIT 1', 'sii', [$appointmentAt, $id, (int) $current['staff_id']]);
            if ($slotConflict !== null) {
                throw new InvalidArgumentException('That veterinarian already has an appointment at this time.');
            }
            runWrite('UPDATE appointments SET appointment_at = ? WHERE id = ?', 'si', [$appointmentAt, $id]);
            respond(['rescheduled' => true]);

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
            beginTransaction();
            $statement = runWrite('INSERT INTO invoices (client_id, pet_id, amount, payment_method, status, issued_on) VALUES (?, ?, ?, ?, ?, ?)', 'iidsss', [$clientId, $petId, $amount, $method, $status, $issuedOn]);
            $invoiceId = $mysqli->insert_id;
            runWrite('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, 1, ?)', 'isd', [$invoiceId, $description, $amount]);
            commitTransaction();
            respond(['id' => $invoiceId], 201);

        case 'invoices:update':
            $id = positiveId($data, 'id');
            $status = validEnum($data, 'status', ['Paid', 'Pending', 'Overdue']);
            $invoice = fetchOne('SELECT status, payment_method FROM invoices WHERE id = ?', 'i', [$id]);
            if ($invoice === null) throw new InvalidArgumentException('Invoice was not found.');
            $paymentMethod = $invoice['payment_method'];
            if ($status === 'Paid') {
                $paymentMethod = requiredString($data, 'payment_method', 60);
                $paymentMethods = getClinicPreferences()['accepted_payment_methods'] ?? ['Cash', 'Card', 'GCash', 'Pay Later'];
                if (!is_array($paymentMethods) || !in_array($paymentMethod, $paymentMethods, true) || $paymentMethod === 'Pay Later') {
                    throw new InvalidArgumentException('Choose an enabled payment method other than Pay Later to record payment.');
                }
            }
            $statement = runWrite('UPDATE invoices SET status = ?, payment_method = ? WHERE id = ?', 'ssi', [$status, $paymentMethod, $id]);
            respond(['updated' => $statement->affected_rows >= 0]);

        case 'checkout:create':
            $clientId = positiveId($data, 'client_id');
            $petId = positiveId($data, 'pet_id');
            $paymentMethod = requiredString($data, 'payment_method', 60);
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

            beginTransaction();
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
                    $item = fetchOne('SELECT id, name, unit_price AS price, quantity, expiration_date, condition_status FROM inventory WHERE id = ? FOR UPDATE', 'i', [$itemId]);
                    if (!$item || (int) $item['quantity'] < $quantity) {
                        throw new InvalidArgumentException('Insufficient inventory stock for a selected item.');
                    }
                    if ($item['condition_status'] === 'Damaged' || ($item['expiration_date'] !== null && $item['expiration_date'] <= date('Y-m-d'))) {
                        throw new InvalidArgumentException('Damaged or expired products cannot be sold.');
                    }
                    $price = (float) $item['price'];
                    $serviceId = null;
                    runWrite('UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?', 'iii', [$quantity, $itemId, $quantity]);
                    runWrite(
                        'INSERT INTO inventory_movements (inventory_id, movement_type, quantity, reason, expiration_date, recorded_by)
                         VALUES (?, "Stock Out", ?, "Checkout sale", ?, ?)',
                        'iisi',
                        [$itemId, $quantity, $item['expiration_date'], (int) $_SESSION['user_id']]
                    );
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
            commitTransaction();
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
        if ($resource === 'catalog-categories' && $action === 'create') {
            respond(['error' => 'That category already exists in the selected catalog.'], 409);
        }
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
