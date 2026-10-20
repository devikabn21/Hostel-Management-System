<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Rooms';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_string('action') === 'delete') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $statement = db()->prepare('DELETE FROM rooms WHERE id = :id');
            $statement->execute(['id' => $id]);
            flash('success', 'Room deleted.');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) flash('error', 'This room has allocation history and cannot be deleted.');
            else { error_log($exception->getMessage()); flash('error', 'The room could not be deleted.'); }
        }
    }
    redirect('rooms.php');
}
$roomsStatement = db()->prepare("SELECT r.*, COUNT(a.id) AS occupied_beds, group_concat(s.full_name, ', ') AS residents FROM rooms r LEFT JOIN room_allocations a ON a.room_id = r.id AND a.status = 'active' LEFT JOIN students s ON s.id = a.student_id GROUP BY r.id ORDER BY r.floor, r.room_number");
$roomsStatement->execute();
$rooms = $roomsStatement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="eyebrow">ROOM DIRECTORY</span><h1>Rooms</h1><p>Track room inventory, bed capacity, and current occupancy.</p></div><a class="button button-primary" href="add_room.php"><span>＋</span> Add room</a></section>
<section class="room-grid"><?php if (!$rooms): ?><div class="panel empty-state"><span class="empty-icon">▦</span><h3>No rooms added</h3><p>Create your first room to begin assigning beds.</p><a class="button button-secondary" href="add_room.php">Add a room</a></div><?php else: foreach ($rooms as $room): $remaining = (int) $room['capacity'] - (int) $room['occupied_beds']; ?><article class="room-card"><div class="room-card-top"><span class="room-symbol">⌂</span><span class="badge <?= $room['status'] === 'maintenance' ? 'badge-yellow' : ($remaining > 0 ? 'badge-green' : 'badge-gray') ?>"><?= $room['status'] === 'maintenance' ? 'Maintenance' : ($remaining > 0 ? 'Available' : 'Full') ?></span></div><div class="room-title"><span>ROOM <?= e($room['room_number']) ?></span><h2><?= e($room['room_type']) ?></h2><p>Floor <?= e($room['floor']) ?> <span>•</span> <?= e($room['capacity']) ?> bed<?= (int) $room['capacity'] === 1 ? '' : 's' ?></p></div><div class="occupancy"><div class="occupancy-copy"><span>Occupancy</span><strong><?= e($room['occupied_beds']) ?> / <?= e($room['capacity']) ?></strong></div><div class="meter"><span style="width: <?= min(100, (int) $room['occupied_beds'] / max(1, (int) $room['capacity']) * 100) ?>%"></span></div><small><?= $room['residents'] ? e($room['residents']) : 'No students assigned' ?></small></div><div class="room-card-actions"><a href="edit_room.php?id=<?= e($room['id']) ?>">✎ Edit room</a><form method="post" action="rooms.php" data-confirm="Delete room <?= e($room['room_number']) ?>? Only rooms with no allocation history can be deleted."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($room['id']) ?>"><button type="submit">Delete</button></form></div></article><?php endforeach; endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>