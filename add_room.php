<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Add room';
$room = [];
$errors = [];
$formAction = 'add_room.php';
$submitLabel = 'Save room';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['room_number', 'room_type', 'floor', 'capacity', 'status'] as $field) $room[$field] = post_string($field);
    if ($room['room_number'] === '') $errors[] = 'Room number is required.';
    if (!in_array($room['room_type'], ['Single', 'Double', 'Triple', 'Dormitory'], true)) $errors[] = 'Choose a valid room type.';
    if (!in_array($room['status'], ['available', 'maintenance'], true)) $errors[] = 'Choose a valid room status.';
    if (!filter_var($room['floor'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]])) $errors[] = 'Floor must be from 0 to 50.';
    if (!filter_var($room['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]])) $errors[] = 'Capacity must be from 1 to 50.';
    if (!$errors) {
        try {
            $statement = db()->prepare('INSERT INTO rooms (room_number, room_type, floor, capacity, status) VALUES (:room_number, :room_type, :floor, :capacity, :status)');
            $statement->execute($room);
            flash('success', 'Room added to inventory.');
            redirect('rooms.php');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) $errors[] = 'That room number already exists.';
            else { error_log($exception->getMessage()); $errors[] = 'The room could not be saved.'; }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><a class="back-link" href="rooms.php">← Room directory</a><h1>Add a room</h1><p>Add room details and available bed capacity.</p></div></section>
<?php require __DIR__ . '/includes/room_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>