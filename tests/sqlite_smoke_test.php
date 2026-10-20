<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/bootstrap.php';

$applicationConnection = db();
$connection = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$connection->exec('PRAGMA foreign_keys = ON');
$connection->exec((string) file_get_contents(__DIR__ . '/../database/hostel_management.sql'));
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$tables = $connection->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['admin_users', 'students', 'rooms', 'room_allocations'] as $table) {
    $assert(in_array($table, $tables, true), "Missing table: $table");
}
$assert((int) $connection->query('PRAGMA foreign_keys')->fetchColumn() === 1, 'SQLite foreign-key checks are not enabled.');
$accountUsername = 'AuthTest' . bin2hex(random_bytes(4));
$accountEmail = strtolower($accountUsername) . '@example.test';
$plainPassword = 'SmokeTestPassword!2026';
$accountInsert = $connection->prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
$accountInsert->execute([
    'username' => $accountUsername,
    'email' => $accountEmail,
    'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
]);
$accountLookup = $connection->prepare('SELECT password_hash FROM admin_users WHERE lower(username) = :identity OR lower(email) = :identity');
$accountLookup->execute(['identity' => strtolower($accountUsername)]);
$passwordHash = $accountLookup->fetchColumn();
$assert(is_string($passwordHash) && $passwordHash !== $plainPassword, 'Account password was not stored as a hash.');
$assert(password_verify($plainPassword, $passwordHash), 'Hashed account password did not verify.');
$caseDuplicateRejected = false;
try {
    $accountInsert->execute([
        'username' => strtoupper($accountUsername),
        'email' => 'different-' . $accountEmail,
        'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    $caseDuplicateRejected = is_constraint_violation($exception);
}
$assert($caseDuplicateRejected, 'Case-insensitive duplicate account was not rejected.');
begin_immediate($connection);
$transactionWorked = false;
try {
    $connection->exec('CREATE TEMP TABLE transaction_probe (value INTEGER)');
    $transactionWorked = true;
} finally {
    rollback_immediate($connection);
}
$assert($transactionWorked, 'SQLite immediate transaction setup failed.');

$suffix = bin2hex(random_bytes(5));
try {
    $studentInsert = $connection->prepare('INSERT INTO students (full_name, usn, department, semester, phone, email, gender, address, guardian_name, guardian_phone, registration_date) VALUES (:full_name, :usn, :department, :semester, :phone, :email, :gender, :address, :guardian_name, :guardian_phone, :registration_date)');
    $studentInsert->execute([
        'full_name' => 'SQLite Test Student', 'usn' => 'TEST' . $suffix, 'department' => 'Testing',
        'semester' => 1, 'phone' => '9876543210', 'email' => $suffix . '@example.test', 'gender' => 'Other',
        'address' => 'Test address', 'guardian_name' => 'Test Guardian', 'guardian_phone' => '9876543211',
        'registration_date' => date('Y-m-d'),
    ]);
    $studentId = (int) $connection->lastInsertId();
    $assert($studentId > 0, 'Student insertion failed.');
    $studentInsert->execute([
        'full_name' => 'SQLite Test Student Two', 'usn' => 'TEST2' . $suffix, 'department' => 'Testing',
        'semester' => 1, 'phone' => '9876543212', 'email' => 'second-' . $suffix . '@example.test', 'gender' => 'Other',
        'address' => 'Test address', 'guardian_name' => 'Test Guardian', 'guardian_phone' => '9876543213',
        'registration_date' => date('Y-m-d'),
    ]);
    $secondStudentId = (int) $connection->lastInsertId();
    $studentInsert->execute([
        'full_name' => 'SQLite Test Student Three', 'usn' => 'TEST3' . $suffix, 'department' => 'Testing',
        'semester' => 1, 'phone' => '9876543214', 'email' => 'third-' . $suffix . '@example.test', 'gender' => 'Other',
        'address' => 'Test address', 'guardian_name' => 'Test Guardian', 'guardian_phone' => '9876543215',
        'registration_date' => date('Y-m-d'),
    ]);
    $thirdStudentId = (int) $connection->lastInsertId();

    $duplicateUsnRejected = false;
    try {
        $studentInsert->execute([
            'full_name' => 'Duplicate USN', 'usn' => 'TEST' . $suffix, 'department' => 'Testing',
            'semester' => 1, 'phone' => '9876543210', 'email' => 'duplicate-' . $suffix . '@example.test', 'gender' => 'Other',
            'address' => 'Test address', 'guardian_name' => 'Test Guardian', 'guardian_phone' => '9876543211',
            'registration_date' => date('Y-m-d'),
        ]);
    } catch (PDOException $exception) {
        $duplicateUsnRejected = is_constraint_violation($exception);
    }
    $assert($duplicateUsnRejected, 'Duplicate USN was not rejected.');

    $studentUpdate = $connection->prepare('UPDATE students SET full_name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $studentUpdate->execute(['name' => 'SQLite Test Student Updated', 'id' => $studentId]);
    $assert($studentUpdate->rowCount() === 1, 'Student update failed.');

    $roomInsert = $connection->prepare('INSERT INTO rooms (room_number, room_type, floor, capacity, status) VALUES (:number, :type, :floor, :capacity, :status)');
    $roomInsert->execute(['number' => 'T-' . $suffix, 'type' => 'Single', 'floor' => 0, 'capacity' => 1, 'status' => 'available']);
    $roomId = (int) $connection->lastInsertId();
    $assert($roomId > 0, 'Room insertion failed.');
    $duplicateRoomRejected = false;
    try {
        $roomInsert->execute(['number' => 'T-' . $suffix, 'type' => 'Single', 'floor' => 0, 'capacity' => 1, 'status' => 'available']);
    } catch (PDOException $exception) {
        $duplicateRoomRejected = is_constraint_violation($exception);
    }
    $assert($duplicateRoomRejected, 'Duplicate room number was not rejected.');
    $roomUpdate = $connection->prepare('UPDATE rooms SET capacity = :capacity, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $roomUpdate->execute(['capacity' => 2, 'id' => $roomId]);
    $assert($roomUpdate->rowCount() === 1, 'Room update failed.');

    $allocationInsert = $connection->prepare('INSERT INTO room_allocations (student_id, room_id, bed_number) VALUES (:student, :room, :bed)');
    $allocationInsert->execute(['student' => $studentId, 'room' => $roomId, 'bed' => 1]);
    $allocationId = (int) $connection->lastInsertId();
    $assert($allocationId > 0, 'Allocation insertion failed.');

    $duplicateRejected = false;
    try {
        $allocationInsert->execute(['student' => $studentId, 'room' => $roomId, 'bed' => 2]);
    } catch (PDOException $exception) {
        $duplicateRejected = is_constraint_violation($exception);
    }
    $assert($duplicateRejected, 'Duplicate active student allocation was not rejected.');

    $duplicateBedRejected = false;
    try {
        $allocationInsert->execute(['student' => $secondStudentId, 'room' => $roomId, 'bed' => 1]);
    } catch (PDOException $exception) {
        $duplicateBedRejected = is_constraint_violation($exception);
    }
    $assert($duplicateBedRejected, 'Duplicate active bed was not rejected.');

    $vacate = $connection->prepare("UPDATE room_allocations SET status = 'vacated', vacated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $vacate->execute(['id' => $allocationId]);
    $assert($vacate->rowCount() === 1, 'Vacating the allocation failed.');
    $allocationInsert->execute(['student' => $studentId, 'room' => $roomId, 'bed' => 2]);
    $activeAllocationId = (int) $connection->lastInsertId();
    $historyCount = $connection->prepare('SELECT COUNT(*) FROM room_allocations WHERE student_id = :student');
    $historyCount->execute(['student' => $studentId]);
    $assert((int) $historyCount->fetchColumn() === 2, 'Allocation history was not retained.');

    $secondRoomInsert = $connection->prepare('INSERT INTO rooms (room_number, room_type, floor, capacity, status) VALUES (:number, :type, :floor, :capacity, :status)');
    $secondRoomInsert->execute(['number' => 'C-' . $suffix, 'type' => 'Double', 'floor' => 1, 'capacity' => 2, 'status' => 'available']);
    $secondRoomId = (int) $connection->lastInsertId();
    begin_immediate($connection);
    $vacateActive = $connection->prepare("UPDATE room_allocations SET status = 'vacated', vacated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'active'");
    $vacateActive->execute(['id' => $activeAllocationId]);
    $allocationInsert->execute(['student' => $studentId, 'room' => $secondRoomId, 'bed' => 1]);
    $changedAllocationId = (int) $connection->lastInsertId();
    commit_immediate($connection);
    $assert($changedAllocationId > 0, 'Room change did not create the new allocation.');
    $historyCount->execute(['student' => $studentId]);
    $assert((int) $historyCount->fetchColumn() === 3, 'Room change did not retain the previous placements.');
    $allocationInsert->execute(['student' => $secondStudentId, 'room' => $secondRoomId, 'bed' => 2]);

    $capacityRejected = false;
    try {
        $allocationInsert->execute(['student' => $thirdStudentId, 'room' => $secondRoomId, 'bed' => 1]);
    } catch (PDOException $exception) {
        $capacityRejected = is_constraint_violation($exception);
    }
    $assert($capacityRejected, 'Occupied bed was not rejected after a room change.');

    $capacityReductionRejected = false;
    try {
        $roomUpdate->execute(['capacity' => 1, 'id' => $secondRoomId]);
    } catch (PDOException $exception) {
        $capacityReductionRejected = is_constraint_violation($exception);
    }
    $assert($capacityReductionRejected, 'Room capacity could be reduced below active occupancy.');

    foreach ([
        ['DELETE FROM students WHERE id = :id', $studentId],
        ['DELETE FROM rooms WHERE id = :id', $secondRoomId],
    ] as [$deleteSql, $recordId]) {
        $deleteRejected = false;
        try {
            $delete = $connection->prepare($deleteSql);
            $delete->execute(['id' => $recordId]);
        } catch (PDOException $exception) {
            $deleteRejected = is_constraint_violation($exception);
        }
        $assert($deleteRejected, 'A record with allocation history was deleted.');
    }

    $roomSummary = $connection->prepare("SELECT r.room_number, COUNT(a.id) AS occupied_beds, group_concat(s.full_name, ', ') AS residents FROM rooms r LEFT JOIN room_allocations a ON a.room_id = r.id AND a.status = 'active' LEFT JOIN students s ON s.id = a.student_id WHERE r.id = :id GROUP BY r.id");
    $roomSummary->execute(['id' => $roomId]);
    $assert((int) $roomSummary->fetch()['occupied_beds'] === 0, 'SQLite room occupancy query failed.');

    $dashboard = $connection->query("SELECT (SELECT COUNT(*) FROM students) AS students, (SELECT COUNT(*) FROM rooms) AS rooms, (SELECT COUNT(DISTINCT room_id) FROM room_allocations WHERE status = 'active') AS occupied_rooms")->fetch();
    $assert((int) $dashboard['students'] >= 1 && (int) $dashboard['rooms'] >= 1, 'Dashboard count query failed.');
} finally {
    unset($applicationConnection);
}

echo "SQLite smoke test passed ($checks checks); test records were isolated in memory.\n";