<?php
require_once __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('allocations.php');
verify_csrf();
$studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
$roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
$bedNumber = filter_var($_POST['bed_number'] ?? null, FILTER_VALIDATE_INT);
if (!$studentId || !$roomId || !$bedNumber || $bedNumber < 1) { flash('error', 'Select a student, room, and valid bed number.'); redirect('allocations.php'); }
$connection = db();
try {
    begin_immediate($connection);
    $roomStatement = $connection->prepare('SELECT id, capacity, status FROM rooms WHERE id = :id');
    $roomStatement->execute(['id' => $roomId]);
    $room = $roomStatement->fetch();
    if (!$room || $room['status'] !== 'available' || $bedNumber > (int) $room['capacity']) throw new DomainException('Choose a valid bed in a room available for allocation.');
    $countStatement = $connection->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = :room_id AND status = 'active'");
    $countStatement->execute(['room_id' => $roomId]);
    if ((int) $countStatement->fetchColumn() >= (int) $room['capacity']) throw new DomainException('This room has reached its capacity.');
    $insert = $connection->prepare('INSERT INTO room_allocations (student_id, room_id, bed_number) VALUES (:student_id, :room_id, :bed_number)');
    $insert->execute(['student_id' => $studentId, 'room_id' => $roomId, 'bed_number' => $bedNumber]);
    commit_immediate($connection);
    flash('success', 'Room allocation created.');
} catch (DomainException $exception) {
    rollback_immediate($connection);
    flash('error', $exception->getMessage());
} catch (PDOException $exception) {
    rollback_immediate($connection);
    if (is_constraint_violation($exception)) flash('error', 'The student already has an allocation or that bed is occupied.');
    else { error_log($exception->getMessage()); flash('error', 'The allocation could not be saved. Please try again.'); }
}
redirect('allocations.php');