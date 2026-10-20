<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Allocations';
$activeStatement = db()->prepare("SELECT a.id, a.student_id, a.bed_number, a.allocated_at, s.full_name, s.usn, s.department, r.room_number, r.floor FROM room_allocations a JOIN students s ON s.id = a.student_id JOIN rooms r ON r.id = a.room_id WHERE a.status = 'active' ORDER BY r.floor, r.room_number, a.bed_number");
$activeStatement->execute();
$active = $activeStatement->fetchAll();
$availableRoomsStatement = db()->prepare("SELECT r.id, r.room_number, r.room_type, r.floor, r.capacity, r.capacity - COUNT(a.id) AS remaining FROM rooms r LEFT JOIN room_allocations a ON a.room_id = r.id AND a.status = 'active' WHERE r.status = 'available' GROUP BY r.id HAVING remaining > 0 ORDER BY r.floor, r.room_number");
$availableRoomsStatement->execute();
$availableRooms = $availableRoomsStatement->fetchAll();
$unassignedStatement = db()->prepare("SELECT s.id, s.full_name, s.usn FROM students s WHERE NOT EXISTS (SELECT 1 FROM room_allocations a WHERE a.student_id = s.id AND a.status = 'active') ORDER BY s.full_name");
$unassignedStatement->execute();
$unassigned = $unassignedStatement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div><span class="eyebrow">BED PLACEMENT</span><h1>Room allocations</h1><p>Assign open beds, review current residents, and retain placement history.</p></div>
</section>
<div class="allocation-layout">
    <section class="panel allocate-panel">
        <div class="panel-heading"><div><span class="eyebrow">NEW PLACEMENT</span><h2>Allocate a bed</h2></div><span class="step-number">01</span></div>
        <?php if (!$unassigned): ?>
            <div class="empty-inline">All registered students already have an active allocation.</div>
        <?php elseif (!$availableRooms): ?>
            <div class="empty-inline">No available beds. Add a room or vacate a current allocation.</div>
        <?php else: ?>
            <form method="post" action="allocate_room.php" class="allocation-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Student<select name="student_id" required><option value="">Select student</option><?php foreach ($unassigned as $student): ?><option value="<?= e($student['id']) ?>"><?= e($student['full_name']) ?> · <?= e($student['usn']) ?></option><?php endforeach; ?></select></label>
                <label>Room<select name="room_id" required><?php foreach ($availableRooms as $room): ?><option value="<?= e($room['id']) ?>" data-capacity="<?= e($room['capacity']) ?>">Room <?= e($room['room_number']) ?> · <?= e($room['room_type']) ?> · <?= e($room['remaining']) ?> bed<?= (int) $room['remaining'] === 1 ? '' : 's' ?> free</option><?php endforeach; ?></select></label>
                <label>Bed number<input type="number" name="bed_number" min="1" max="50" required value="1"></label>
                <button class="button button-primary button-full" type="submit">Confirm allocation <span>→</span></button>
            </form>
        <?php endif; ?>
    </section>
    <aside class="panel allocation-summary">
        <span class="eyebrow">BED AVAILABILITY</span><h2>Open spaces</h2>
        <?php if (!$availableRooms): ?><p class="muted">There are no open beds right now.</p>
        <?php else: foreach ($availableRooms as $room): ?>
            <div class="availability-row"><span class="room-symbol">⌂</span><span><strong><?= e($room['room_number']) ?></strong><small><?= e($room['room_type']) ?> · Floor <?= e($room['floor']) ?></small></span><b><?= e($room['remaining']) ?><small> / <?= e($room['capacity']) ?></small></b></div>
        <?php endforeach; endif; ?>
    </aside>
</div>
<section class="panel active-allocations">
    <div class="panel-heading"><div><span class="eyebrow">CURRENT RESIDENTS</span><h2>Active allocations <span class="count-chip"><?= count($active) ?></span></h2></div></div>
    <?php if (!$active): ?>
        <div class="empty-state"><span class="empty-icon">⇄</span><h3>No active allocations</h3><p>Choose a student and room above to create the first placement.</p></div>
    <?php else: ?>
        <div class="table-wrap"><table><thead><tr><th>STUDENT</th><th>DEPARTMENT</th><th>ROOM</th><th>BED</th><th>ALLOCATED</th><th class="right">ACTIONS</th></tr></thead><tbody>
        <?php foreach ($active as $item): ?>
            <tr>
                <td><a class="student-cell" href="student_details.php?id=<?= e($item['id']) ?>"><span class="table-avatar mint-avatar"><?= e(strtoupper(substr($item['full_name'], 0, 1))) ?></span><span><strong><?= e($item['full_name']) ?></strong><small><?= e($item['usn']) ?></small></span></a></td>
                <td><?= e($item['department']) ?></td><td><?= e($item['room_number']) ?></td><td>Bed <?= e($item['bed_number']) ?></td><td><?= e(date('M j, Y', strtotime($item['allocated_at']))) ?></td>
                <td class="right action-cell"><a class="button button-small button-quiet" href="change_room.php?id=<?= e($item['id']) ?>">Change</a><form method="post" action="vacate_room.php" data-confirm="Vacate <?= e($item['full_name']) ?> from room <?= e($item['room_number']) ?>? The history will be kept."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="allocation_id" value="<?= e($item['id']) ?>"><button class="button button-small button-quiet" type="submit">Vacate</button></form></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>