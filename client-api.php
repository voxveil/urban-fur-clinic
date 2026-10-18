<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function clientRespond(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function clientFail(string $message, int $status = 422): never { clientRespond(['error' => $message], $status); }
function clientInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return $_POST;
    $data = json_decode($raw, true);
    if (!is_array($data)) clientFail('Invalid request body.', 400);
    return $data;
}
function clientText(array $data, string $key, int $max, bool $required = true): string {
    $raw = $data[$key] ?? '';
    if (!is_string($raw)) clientFail('Please check the ' . str_replace('_', ' ', $key) . ' field.');
    $value = trim($raw);
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) clientFail('Please check the ' . str_replace('_', ' ', $key) . ' field.');
    return $value;
}
function clientValidPetName(string $name): bool { return preg_match("/^[\\p{L}]+(?:[ '\\x{2019}-][\\p{L}]+)*$/u", $name) === 1; }
function clientValidPersonName(string $name): bool { return preg_match("/^[\\p{L}]+(?:[ '\\x{2019}-][\\p{L}]+)*$/u", $name) === 1; }
function clientValidPhone(string $phone): bool { return preg_match('/^09[0-9]{9}$/', $phone) === 1; }
function clientValidateBreed(string $species, string $breed): bool {
    $options = [
        'Dog' => ['Aspin', 'Labrador Retriever', 'Golden Retriever', 'German Shepherd', 'Poodle', 'Beagle', 'Shih Tzu', 'Chihuahua', 'Pomeranian', 'Husky', 'Other'],
        'Cat' => ['Persian', 'Siamese', 'British Shorthair', 'Maine Coon', 'Ragdoll', 'Bengal', 'American Shorthair', 'Scottish Fold', 'Domestic Shorthair', 'Other'],
        'Other' => ['Other'],
    ];
    return !isset($options[$species]) || in_array($breed, $options[$species], true);
}
function clientQuery(string $sql, string $types = '', array $values = []): array {
    global $mysqli;
    $statement = $mysqli->prepare($sql);
    if ($types !== '') {
        $parameters = [$types];
        foreach ($values as $index => &$value) $parameters[] = &$value;
        call_user_func_array([$statement, 'bind_param'], $parameters);
        unset($value);
    }
    $statement->execute();
    $result = $statement->get_result();
    $rows = $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();
    return $rows;
}

if (!isset($_SESSION['client_user_id'])) clientFail('Please sign in to continue.', 401);
$userId = (int) $_SESSION['client_user_id'];
$accountRows = clientQuery(
    "SELECT u.id, u.full_name, u.username, u.password_hash, u.profile_image, c.id AS client_id, c.email, c.phone, c.address
     FROM users u JOIN clients c ON c.account_user_id = u.id
     WHERE u.id = ? AND u.role = 'Client' AND u.active = 1 AND c.status = 'Active' LIMIT 1",
    'i', [$userId]
);
if (!$accountRows) {
    unset($_SESSION['client_user_id']);
    clientFail('Your client account is unavailable. Please contact the clinic.', 401);
}
$account = $accountRows[0];
$clientId = (int) $account['client_id'];
$resource = (string) ($_GET['resource'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = $method === 'POST' ? clientInput() : [];
if ($method !== 'GET') {
    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!isset($_SESSION['client_form_token']) || !hash_equals((string) $_SESSION['client_form_token'], $token)) clientFail('Your session expired. Reload this page and try again.', 419);
}

try {
    if ($method === 'GET') {
        if ($resource === 'token') clientRespond(['csrfToken' => (string) ($_SESSION['client_form_token'] ?? '')]);
        if ($resource === 'profile') clientRespond(['data' => [
            'full_name' => $account['full_name'], 'username' => $account['username'], 'email' => $account['email'],
            'phone' => $account['phone'], 'address' => $account['address'] ?? '', 'profile_image' => $account['profile_image'] ?? '',
        ]]);
        if ($resource === 'services') clientRespond(['data' => clientQuery('SELECT id, name, category FROM services WHERE active = 1 ORDER BY name')]);
        if ($resource === 'closures') {
            $month = (string) ($_GET['month'] ?? '');
            $monthDate = DateTime::createFromFormat('!Y-m', $month);
            if (!$monthDate || $monthDate->format('Y-m') !== $month) clientFail('Choose a valid calendar month.');
            $startDate = $monthDate->format('Y-m-01');
            $endDate = $monthDate->format('Y-m-t');
            clientRespond(['data' => clientQuery(
                'SELECT start_date, end_date, reason FROM appointment_closures
                 WHERE start_date <= ? AND end_date >= ? ORDER BY start_date, end_date',
                'ss',
                [$endDate, $startDate]
            )]);
        }
        if ($resource === 'calendar-availability') {
            $month = (string) ($_GET['month'] ?? '');
            $monthDate = DateTime::createFromFormat('!Y-m', $month);
            if (!$monthDate || $monthDate->format('Y-m') !== $month) clientFail('Choose a valid calendar month.');
            $startDate = $monthDate->format('Y-m-01');
            $endDate = $monthDate->format('Y-m-t');
            $settings = clientQuery('SELECT opening_time, closing_time, appointment_interval_minutes FROM clinic_settings WHERE id = 1')[0] ?? null;
            if (!$settings) clientFail('Clinic hours are unavailable.', 503);
            $opening = (int) substr((string) $settings['opening_time'], 0, 2) * 60 + (int) substr((string) $settings['opening_time'], 3, 2);
            $closing = (int) substr((string) $settings['closing_time'], 0, 2) * 60 + (int) substr((string) $settings['closing_time'], 3, 2);
            $interval = (int) $settings['appointment_interval_minutes'];
            $capacity = $interval > 0 ? max(0, intdiv(max(0, $closing - $opening), $interval)) : 0;
            $closures = clientQuery('SELECT start_date, end_date, reason FROM appointment_closures WHERE start_date <= ? AND end_date >= ? ORDER BY start_date, end_date', 'ss', [$endDate, $startDate]);
            $bookedRows = clientQuery(
                'SELECT DATE(appointment_at) AS appointment_date, COUNT(DISTINCT TIME(appointment_at)) AS booked
                 FROM appointments WHERE appointment_at >= ? AND appointment_at < DATE_ADD(?, INTERVAL 1 DAY)
                   AND status IN ("Pending", "Scheduled", "In Progress") GROUP BY DATE(appointment_at)',
                'ss', [$startDate, $endDate]
            );
            $bookedByDate = [];
            foreach ($bookedRows as $row) $bookedByDate[$row['appointment_date']] = (int) $row['booked'];
            $calendar = [];
            for ($day = clone $monthDate; $day->format('Y-m') === $month; $day->modify('+1 day')) {
                $date = $day->format('Y-m-d');
                $closure = null;
                foreach ($closures as $item) if ($item['start_date'] <= $date && $item['end_date'] >= $date) { $closure = $item; break; }
                $booked = $bookedByDate[$date] ?? 0;
                $calendar[$date] = ['booked' => $booked, 'available' => $closure ? 0 : max(0, $capacity - $booked), 'closed' => $closure !== null, 'reason' => $closure['reason'] ?? ''];
            }
            clientRespond(['data' => $calendar]);
        }
        if ($resource === 'availability') {
            $date = (string) ($_GET['date'] ?? '');
            $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
            $dateErrors = DateTime::getLastErrors();
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || $date < date('Y-m-d') || (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) clientFail('Choose today or a valid future date.');
            $settings = clientQuery('SELECT opening_time, closing_time, appointment_interval_minutes FROM clinic_settings WHERE id = 1')[0] ?? null;
            if (!$settings) clientFail('Clinic hours are unavailable.', 503);
            $closure = clientQuery(
                'SELECT reason FROM appointment_closures WHERE start_date <= ? AND end_date >= ? ORDER BY start_date LIMIT 1',
                'ss',
                [$date, $date]
            )[0] ?? null;
            if ($closure !== null) {
                clientRespond(['data' => [], 'closed' => true, 'message' => 'The clinic is closed on this date.' . ($closure['reason'] !== '' ? ' Reason: ' . $closure['reason'] : '')]);
            }
            $nextDate = (clone $parsedDate)->modify('+1 day')->format('Y-m-d');
            $reservedRows = clientQuery(
                'SELECT TIME_FORMAT(appointment_at, "%H:%i") AS slot_time FROM appointments
                 WHERE appointment_at >= ? AND appointment_at < ? AND status IN ("Pending", "Scheduled", "In Progress")',
                'ss', [$date . ' 00:00:00', $nextDate . ' 00:00:00']
            );
            $reserved = array_fill_keys(array_column($reservedRows, 'slot_time'), true);
            $opening = (int) substr((string) $settings['opening_time'], 0, 2) * 60 + (int) substr((string) $settings['opening_time'], 3, 2);
            $closing = (int) substr((string) $settings['closing_time'], 0, 2) * 60 + (int) substr((string) $settings['closing_time'], 3, 2);
            $interval = (int) $settings['appointment_interval_minutes'];
            $slots = [];
            for ($minute = $opening; $interval > 0 && $minute + $interval <= $closing; $minute += $interval) {
                $slot = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
                if (($date !== date('Y-m-d') || $slot > date('H:i')) && !isset($reserved[$slot])) $slots[] = $slot;
            }
            clientRespond(['data' => $slots]);
        }
        if ($resource === 'pets') {
            clientRespond(['data' => clientQuery(
                'SELECT p.id, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name, p.species, p.breed, p.gender, p.birth_date, p.notes, p.life_status, p.life_status,
                        CASE WHEN p.birth_date IS NULL THEN NULL ELSE TIMESTAMPDIFF(YEAR, p.birth_date, CURDATE()) END AS age
                 FROM pets p WHERE p.client_id = ? AND p.status = "Active" ORDER BY p.name', 'i', [$clientId]
            )]);
        }
        if ($resource === 'appointments') {
            clientRespond(['data' => clientQuery(
                'SELECT a.id, a.appointment_at, a.status, a.notes AS purpose, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet_name,
                        COALESCE(GROUP_CONCAT(DISTINCT selected_service.name ORDER BY selected_service.name SEPARATOR ", "), s.name, "To be assigned") AS service, COALESCE(st.full_name, "To be assigned") AS veterinarian
                 FROM appointments a JOIN pets p ON p.id = a.pet_id
                 LEFT JOIN services s ON s.id = a.service_id LEFT JOIN appointment_services aps ON aps.appointment_id = a.id
                 LEFT JOIN services selected_service ON selected_service.id = aps.service_id LEFT JOIN staff st ON st.id = a.staff_id
                 WHERE p.client_id = ? GROUP BY a.id, p.name, s.name, st.full_name ORDER BY a.appointment_at DESC', 'i', [$clientId]
            )]);
        }
        if ($resource === 'notifications') {
            clientRespond(['data' => clientQuery(
                'SELECT n.id, n.title, n.message, n.created_at, n.is_read
                 FROM client_notifications n
                 WHERE n.client_id = ?
                 ORDER BY n.created_at DESC, n.id DESC
                 LIMIT 30',
                'i',
                [$clientId]
            )]);
        }
        if ($resource === 'medical-records') {
            clientRespond(['data' => clientQuery(
                'SELECT r.id, r.recorded_on, r.diagnosis, r.treatment, r.prescription, r.notes,
                        CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet_name, COALESCE(u.full_name, "Clinic team") AS veterinarian
                 FROM medical_records r JOIN pets p ON p.id = r.pet_id
                 LEFT JOIN users u ON u.id = r.recorded_by WHERE p.client_id = ?
                 ORDER BY r.recorded_on DESC, r.id DESC', 'i', [$clientId]
            )]);
        }
        if ($resource === 'dashboard') {
            $counts = clientQuery(
                'SELECT (SELECT COUNT(*) FROM pets WHERE client_id = ? AND status = "Active" AND life_status = "Alive") AS pets,
                        (SELECT COUNT(*) FROM appointments a JOIN pets p ON p.id = a.pet_id WHERE p.client_id = ? AND a.appointment_at >= NOW() AND a.status IN ("Pending", "Scheduled", "In Progress")) AS upcoming,
                        (SELECT COUNT(*) FROM medical_records r JOIN pets p ON p.id = r.pet_id WHERE p.client_id = ?) AS visits',
                'iii', [$clientId, $clientId, $clientId]
            )[0];
            $recent = clientQuery(
                'SELECT r.recorded_on, r.diagnosis, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet_name FROM medical_records r JOIN pets p ON p.id = r.pet_id
                 WHERE p.client_id = ? ORDER BY r.recorded_on DESC, r.id DESC LIMIT 5', 'i', [$clientId]
            );
            $upcoming = clientQuery(
                'SELECT a.appointment_at, a.status, CONCAT("PET-", LPAD(p.id, 4, "0")) AS pet_code, p.name AS pet_name, COALESCE(GROUP_CONCAT(DISTINCT selected_service.name ORDER BY selected_service.name SEPARATOR ", "), s.name, "Visit") AS service
                 FROM appointments a JOIN pets p ON p.id = a.pet_id LEFT JOIN services s ON s.id = a.service_id
                 LEFT JOIN appointment_services aps ON aps.appointment_id = a.id LEFT JOIN services selected_service ON selected_service.id = aps.service_id
                 WHERE p.client_id = ? AND a.appointment_at >= NOW() AND a.status IN ("Pending", "Scheduled", "In Progress")
                 GROUP BY a.id, p.id, p.name, s.name ORDER BY a.appointment_at LIMIT 5', 'i', [$clientId]
            );
            clientRespond(['profile' => ['full_name' => $account['full_name'], 'email' => $account['email'], 'phone' => $account['phone']], 'counts' => $counts, 'recent' => $recent, 'upcoming' => $upcoming]);
        }
        clientFail('Unknown client page.', 404);
    }

    if ($method !== 'POST') clientFail('Method not allowed.', 405);
    $action = (string) ($input['action'] ?? '');
    if ($resource === 'pets' && $action === 'create') {
        $name = clientText($input, 'name', 100);
        $speciesChoice = clientText($input, 'species', 60);
        $species = $speciesChoice === 'Other' ? clientText($input, 'species_other', 60) : $speciesChoice;
        $breedChoice = clientText($input, 'breed', 100);
        $breed = $breedChoice === 'Other' ? clientText($input, 'breed_other', 100) : $breedChoice;
        if (!clientValidPetName($name)) clientFail('Pet name may contain letters only, with spaces, apostrophes, or hyphens between words.');
        if (!in_array($speciesChoice, ['Dog', 'Cat', 'Other'], true) || !clientValidateBreed($speciesChoice, $breedChoice)) clientFail('Choose a breed that matches the selected species.');
        $gender = clientText($input, 'gender', 20, false);
        $birthDate = clientText($input, 'birth_date', 10);
        $parsedBirthDate = DateTime::createFromFormat('!Y-m-d', $birthDate);
        if (!$parsedBirthDate || $parsedBirthDate->format('Y-m-d') !== $birthDate || $birthDate > date('Y-m-d')) clientFail('Enter a valid date of birth that is not in the future.');
        $parsedBirthDate = $birthDate === '' ? null : DateTime::createFromFormat('!Y-m-d', $birthDate);
        $birthDateErrors = DateTime::getLastErrors();
        if ($birthDate !== '' && (!$parsedBirthDate || $parsedBirthDate->format('Y-m-d') !== $birthDate || $birthDate > date('Y-m-d') || (is_array($birthDateErrors) && ($birthDateErrors['warning_count'] > 0 || $birthDateErrors['error_count'] > 0)))) clientFail('Enter a valid birth date that is not in the future.');
        $notes = clientText($input, 'notes', 2000, false);
        $statement = $mysqli->prepare('INSERT INTO pets (client_id, name, species, breed, gender, birth_date, notes) VALUES (?, ?, ?, ?, ?, NULLIF(?, ""), NULLIF(?, ""))');
        $statement->bind_param('issssss', $clientId, $name, $species, $breed, $gender, $birthDate, $notes);
        $statement->execute();
        clientRespond(['id' => (int) $mysqli->insert_id], 201);
    }
    if ($resource === 'appointments' && $action === 'create') {
        $petId = filter_var($input['pet_id'] ?? null, FILTER_VALIDATE_INT);
        $serviceIds = $input['service_ids'] ?? [];
        if (!is_array($serviceIds) || count($serviceIds) < 1 || count($serviceIds) > 30) clientFail('Select at least one service.');
        $serviceIds = array_values(array_unique(array_map(static function ($value): int {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) clientFail('Choose valid services.');
            return (int) $id;
        }, $serviceIds)));
        $appointmentDate = clientText($input, 'appointment_date', 10);
        $appointmentTime = clientText($input, 'appointment_time', 5);
        $appointmentAt = $appointmentDate . 'T' . $appointmentTime;
        $purpose = clientText($input, 'purpose', 1000);
        $dateTime = DateTime::createFromFormat('!Y-m-d\TH:i', $appointmentAt);
        if (!$petId || !$dateTime || $dateTime->format('Y-m-d\TH:i') !== $appointmentAt || $dateTime->getTimestamp() <= time()) clientFail('Choose a valid future date and time and pet.');
        if (!clientQuery('SELECT id FROM pets WHERE id = ? AND client_id = ? AND status = "Active" AND life_status = "Alive"', 'ii', [$petId, $clientId])) clientFail('Only living, active pets can be scheduled for appointments.', 403);
        foreach ($serviceIds as $selectedServiceId) if (!clientQuery('SELECT id FROM services WHERE id = ? AND active = 1', 'i', [$selectedServiceId])) clientFail('One of the selected services is unavailable.');
        $hours = clientQuery('SELECT opening_time, closing_time, appointment_interval_minutes FROM clinic_settings WHERE id = 1')[0] ?? null;
        $time = $dateTime->format('H:i');
        if (!$hours || $time < substr((string) $hours['opening_time'], 0, 5) || $time >= substr((string) $hours['closing_time'], 0, 5)) clientFail('Choose a time during clinic opening hours.');
        if (clientQuery('SELECT id FROM appointment_closures WHERE start_date <= ? AND end_date >= ? LIMIT 1', 'ss', [$appointmentDate, $appointmentDate])) {
            clientFail('The clinic is closed on that date. Please choose another day.');
        }
        $slot = (int) $hours['appointment_interval_minutes'];
        $offset = (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i') - ((int) substr((string) $hours['opening_time'], 0, 2) * 60 + (int) substr((string) $hours['opening_time'], 3, 2));
        $appointmentMinutes = (int) $dateTime->format('H') * 60 + (int) $dateTime->format('i');
        $closingMinutes = (int) substr((string) $hours['closing_time'], 0, 2) * 60 + (int) substr((string) $hours['closing_time'], 3, 2);
        if ($slot < 1 || $offset < 0 || $offset % $slot !== 0 || $appointmentMinutes + $slot > $closingMinutes) clientFail('Choose a time that matches the clinic appointment intervals.');
        $sqlDate = $dateTime->format('Y-m-d H:i:s');
        $lock = clientQuery('SELECT GET_LOCK(CONCAT("urbanfur_appointment_", DATE_FORMAT(?, "%Y%m%d%H%i")), 5) AS acquired', 's', [$sqlDate]);
        if ((int) ($lock[0]['acquired'] ?? 0) !== 1) clientFail('This time is being booked. Please choose another available time.');
        if (clientQuery('SELECT id FROM appointments WHERE appointment_at = ? AND status IN ("Pending", "Scheduled", "In Progress") LIMIT 1', 's', [$sqlDate])) clientFail('This appointment time is already booked. Please select another time.');
        $mysqli->begin_transaction();
        $primaryServiceId = $serviceIds[0];
        $statement = $mysqli->prepare('INSERT INTO appointments (pet_id, service_id, appointment_at, status, notes) VALUES (?, ?, ?, "Pending", ?)');
        $statement->bind_param('iiss', $petId, $primaryServiceId, $sqlDate, $purpose);
        $statement->execute();
        $appointmentId = (int) $mysqli->insert_id;
        $join = $mysqli->prepare('INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)');
        foreach ($serviceIds as $selectedServiceId) { $join->bind_param('ii', $appointmentId, $selectedServiceId); $join->execute(); }
        $mysqli->commit();
        clientRespond(['id' => $appointmentId], 201);
    }
    if ($resource === 'profile' && $action === 'update') {
        $name = clientText($input, 'full_name', 150);
        $username = clientText($input, 'username', 80);
        if (!clientValidPersonName($name)) clientFail('Name may contain letters only, with spaces, apostrophes, or hyphens between words.');
        if (!preg_match('/^(?:[A-Za-z0-9_.-]{3,80}|Walk-in [0-9]{6})$/', $username)) clientFail('Username must be 3–80 characters and use only letters, numbers, dots, dashes, or underscores.');
        $email = clientText($input, 'email', 190, false);
        $phone = clientText($input, 'phone', 40);
        if (!clientValidPhone($phone)) clientFail('Contact number must be exactly 11 digits in 09XXXXXXXXX format.');
        $address = clientText($input, 'address', 255, false);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) clientFail('Enter a valid email address.');
        $hasImage = array_key_exists('profile_image', $input);
        $image = $input['profile_image'] ?? null;
        if ($image !== null && $image !== '') {
            if (!is_string($image) || !preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/]+={0,2})$#', $image, $matches)) clientFail('Choose a PNG, JPG, or WebP profile image.');
            $decodedImage = base64_decode($matches[2], true);
            if ($decodedImage === false || strlen($decodedImage) > 1048576) clientFail('Profile images must be 1 MB or smaller.');
            $imageInfo = getimagesizefromstring($decodedImage);
            if (!is_array($imageInfo) || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg', 'image/webp'], true)) clientFail('The selected profile image could not be read.');
        } elseif ($image === '') {
            $image = null;
        }
        $mysqli->begin_transaction();
        $statement = $mysqli->prepare('UPDATE clients SET full_name = ?, email = NULLIF(?, ""), phone = ?, address = NULLIF(?, "") WHERE id = ? AND account_user_id = ?');
        $statement->bind_param('ssssii', $name, $email, $phone, $address, $clientId, $userId);
        $statement->execute();
        $userSql = $hasImage
            ? 'UPDATE users SET username = ?, full_name = ?, email = NULLIF(?, ""), phone = ?, profile_image = ? WHERE id = ? AND role = "Client"'
            : 'UPDATE users SET username = ?, full_name = ?, email = NULLIF(?, ""), phone = ? WHERE id = ? AND role = "Client"';
        $statement = $mysqli->prepare($userSql);
        if ($hasImage) $statement->bind_param('sssssi', $username, $name, $email, $phone, $image, $userId);
        else $statement->bind_param('ssssi', $username, $name, $email, $phone, $userId);
        $statement->execute();
        $mysqli->commit();
        clientRespond(['updated' => true]);
    }
    if ($resource === 'notifications' && $action === 'read') {
        clientQuery('UPDATE client_notifications SET is_read = 1 WHERE client_id = ? AND is_read = 0', 'i', [$clientId]);
        clientRespond(['updated' => true]);
    }
    if ($resource === 'profile' && $action === 'password') {
        $current = (string) ($input['current_password'] ?? '');
        $next = (string) ($input['new_password'] ?? '');
        if (!password_verify($current, (string) $account['password_hash'])) clientFail('Your current password is incorrect.', 403);
        if (mb_strlen($next, 'UTF-8') < 8 || mb_strlen($next, 'UTF-8') > 255) clientFail('Choose a password between 8 and 255 characters.');
        $hash = password_hash($next, PASSWORD_DEFAULT);
        $statement = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = "Client"');
        $statement->bind_param('si', $hash, $userId);
        $statement->execute();
        clientRespond(['updated' => true]);
    }
    clientFail('Unknown client action.', 404);
} catch (mysqli_sql_exception $exception) {
    $mysqli->rollback();
    if ($exception->getCode() === 1062) clientFail('That email or record is already in use.', 409);
    error_log('Client API database error: ' . $exception->getMessage());
    clientFail('The request could not be completed.', 500);
}
