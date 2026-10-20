<?php
require_once __DIR__ . '/includes/auth.php';
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { flash('error', 'Choose a valid room.'); redirect('rooms.php'); }
$statement = db()->prepare('SELECT * FROM rooms WHERE id = :id');
$statement->execute(['id' => $id]);
$room = $statement->fetch();
if (!$room) { flash('error', 'Room not found.'); redirect('rooms.php'); }
$pageTitle = 'Edit room';
$errors = [];
$formAction = 'edit_room.php?id=' . $id;
$submitLabel = 'Save changes';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['room_number', 'room_type', 'floor', 'capacity', 'status'] as $field) $room[$field] = post_string($field);
    if ($room['room_number'] === '') $errors[] = 'Room number is required.';
    if (!in_array($room['room_type'], ['Single', 'Double', 'Triple', 'Dormitory'], true)) $errors[] = 'Choose a valid room type.';
    if (!in_array($room['status'], ['available', 'maintenance'], true)) $errors[] = 'Choose a valid room status.';
    if (!filter_var($room['floor'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]])) $errors[] = 'Floor must be from 0 to 50.';
    if (!filter_var($room['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]])) $errors[] = 'Capacity must be from 1 to 50.';
    $activeCount = db()->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = :id AND status = 'active'");
    $activeCount->execute(['id' => $id]);
    if ((int) $room['capacity'] < (int) $activeCount->fetchColumn()) $errors[] = 'Capacity cannot be lower than the number of students currently assigned.';
    if (!$errors) {
        try {
            $statement = db()->prepare('UPDATE rooms SET room_number=:room_number, room_type=:room_type, floor=:floor, capacity=:capacity, status=:status, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $statement->execute([
                'room_number' => $room['room_number'],
                'room_type' => $room['room_type'],
                'floor' => $room['floor'],
                'capacity' => $room['capacity'],
                'status' => $room['status'],
                'id' => $id,
            ]);
            flash('success', 'Room information updated.');
            redirect('rooms.php');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) $errors[] = 'That room number already exists.';
            else { error_log($exception->getMessage()); $errors[] = 'The room could not be updated.'; }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><a class="back-link" href="rooms.php">← Room directory</a><h1>Edit room</h1><p>Update room configuration and availability.</p></div></section>
<?php require __DIR__ . '/includes/room_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>