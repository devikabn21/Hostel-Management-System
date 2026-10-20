<?php
require_once __DIR__ . '/includes/auth.php';
$id = filter_var($_GET['id'] ?? $_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { flash('error', 'Choose a valid allocation.'); redirect('allocations.php'); }
$statement = db()->prepare("SELECT a.id, a.student_id, a.room_id, a.bed_number, s.full_name, s.usn, r.room_number FROM room_allocations a JOIN students s ON s.id = a.student_id JOIN rooms r ON r.id = a.room_id WHERE a.id = :id AND a.status = 'active'");
$statement->execute(['id' => $id]);
$allocation = $statement->fetch();
if (!$allocation) { flash('error', 'Active allocation not found.'); redirect('allocations.php'); }
$pageTitle = 'Change room';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
    $bedNumber = filter_var($_POST['bed_number'] ?? null, FILTER_VALIDATE_INT);
    if (!$roomId || !$bedNumber || $bedNumber < 1) $errors[] = 'Choose a valid room and bed number.';
    if (!$errors) {
        $connection = db();
        try {
            begin_immediate($connection);
            $lock = $connection->prepare('SELECT id, capacity, status FROM rooms WHERE id = :id');
            $lock->execute(['id' => $roomId]);
            $room = $lock->fetch();
            if (!$room || $room['status'] !== 'available' || $bedNumber > (int) $room['capacity']) throw new DomainException('Choose a valid bed in a room available for allocation.');
            $count = $connection->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = :room_id AND status = 'active' AND id <> :allocation_id");
            $count->execute(['room_id' => $roomId, 'allocation_id' => $id]);
            if ((int) $count->fetchColumn() >= (int) $room['capacity']) throw new DomainException('This room has reached its capacity.');
            $vacate = $connection->prepare("UPDATE room_allocations SET status = 'vacated', vacated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'active'");
            $vacate->execute(['id' => $id]);
            if ($vacate->rowCount() !== 1) throw new DomainException('This allocation has already changed. Refresh and try again.');
            $insert = $connection->prepare('INSERT INTO room_allocations (student_id, room_id, bed_number) VALUES (:student_id, :room_id, :bed_number)');
            $insert->execute(['student_id' => $allocation['student_id'], 'room_id' => $roomId, 'bed_number' => $bedNumber]);
            commit_immediate($connection);
            flash('success', 'Room changed; the previous allocation remains in the history.');
            redirect('allocations.php');
        } catch (DomainException $exception) {
            rollback_immediate($connection);
            $errors[] = $exception->getMessage();
        } catch (PDOException $exception) {
            rollback_immediate($connection);
            if (is_constraint_violation($exception)) $errors[] = 'That bed is already occupied. Choose another bed.';
            else { error_log($exception->getMessage()); $errors[] = 'The room change could not be saved.'; }
        }
    }
}
$availableRoomsStatement = db()->prepare("SELECT r.id, r.room_number, r.room_type, r.floor, r.capacity, r.capacity - COUNT(a.id) AS remaining FROM rooms r LEFT JOIN room_allocations a ON a.room_id = r.id AND a.status = 'active' WHERE r.status = 'available' GROUP BY r.id HAVING remaining > 0 ORDER BY r.floor, r.room_number");
$availableRoomsStatement->execute();
$availableRooms = $availableRoomsStatement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><a class="back-link" href="allocations.php">← Room allocations</a><h1>Change room</h1><p>Move <?= e($allocation['full_name']) ?> from room <?= e($allocation['room_number']) ?>. The old placement will remain in history.</p></div></section>
<form class="form-panel compact-form" method="post" action="change_room.php?id=<?= e($id) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="allocation_id" value="<?= e($id) ?>"><?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?><div class="form-section"><div><span class="eyebrow">NEW PLACEMENT</span><h2><?= e($allocation['full_name']) ?> <small class="muted">· <?= e($allocation['usn']) ?></small></h2></div><?php if (!$availableRooms): ?><p class="empty-inline">No available rooms have open beds. Add a room or vacate another allocation first.</p><?php else: ?><div class="form-grid"><label>Room <span>*</span><select name="room_id" required><?php foreach ($availableRooms as $room): ?><option value="<?= e($room['id']) ?>"><?= e($room['room_number']) ?> · <?= e($room['room_type']) ?> · <?= e($room['remaining']) ?> bed(s) free</option><?php endforeach; ?></select></label><label>Bed number <span>*</span><input name="bed_number" type="number" min="1" max="50" required value="1"></label></div><?php endif; ?></div><div class="form-actions"><a class="button button-quiet" href="allocations.php">Cancel</a><?php if ($availableRooms): ?><button class="button button-primary" type="submit">Change room <span>→</span></button><?php endif; ?></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>