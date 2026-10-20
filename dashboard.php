<?php
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Dashboard';
$countsStatement = db()->prepare(
    "SELECT
        (SELECT COUNT(*) FROM students) AS students,
        (SELECT COUNT(*) FROM rooms) AS rooms,
        (SELECT COUNT(DISTINCT room_id) FROM room_allocations WHERE status = 'active') AS occupied_rooms,
        (SELECT COUNT(*) FROM rooms r WHERE r.status = 'available' AND (SELECT COUNT(*) FROM room_allocations a WHERE a.room_id = r.id AND a.status = 'active') < r.capacity) AS available_rooms,
        (SELECT COUNT(*) FROM room_allocations WHERE status = 'active') AS residents"
);
$countsStatement->execute();
$counts = $countsStatement->fetch();
$recentStatement = db()->prepare(
    "SELECT s.id, s.full_name, s.usn, s.department, s.registration_date,
        (SELECT r.room_number FROM room_allocations a JOIN rooms r ON r.id = a.room_id WHERE a.student_id = s.id AND a.status = 'active' LIMIT 1) AS room_number
     FROM students s ORDER BY s.registration_date DESC, s.id DESC LIMIT 6"
);
$recentStatement->execute();
$recent = $recentStatement->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="eyebrow">TUESDAY, <?= e(date('F j, Y')) ?></span><h1>Good day, <?= e($_SESSION['admin_name'] ?? 'Administrator') ?></h1><p>Here is what is happening across your hostel today.</p></div><a class="button button-primary" href="add_student.php"><span aria-hidden="true">＋</span> Register student</a></section>
<section class="stats-grid" aria-label="Hostel statistics">
    <article class="stat-card stat-coral"><div class="stat-top"><span class="stat-icon">♙</span><span class="stat-caption">PEOPLE</span></div><strong><?= e($counts['students']) ?></strong><span class="stat-label">Registered students</span></article>
    <article class="stat-card stat-blue"><div class="stat-top"><span class="stat-icon">▦</span><span class="stat-caption">CAPACITY</span></div><strong><?= e($counts['rooms']) ?></strong><span class="stat-label">Total rooms</span></article>
    <article class="stat-card stat-mint"><div class="stat-top"><span class="stat-icon">⌂</span><span class="stat-caption">IN USE</span></div><strong><?= e($counts['occupied_rooms']) ?></strong><span class="stat-label">Occupied rooms <small><?= e($counts['residents']) ?> residents</small></span></article>
    <article class="stat-card stat-yellow"><div class="stat-top"><span class="stat-icon">↗</span><span class="stat-caption">READY</span></div><strong><?= e($counts['available_rooms']) ?></strong><span class="stat-label">Rooms with open beds</span></article>
</section>
<div class="dashboard-grid">
    <section class="panel recent-panel"><div class="panel-heading"><div><span class="eyebrow">LATEST ARRIVALS</span><h2>Recent registrations</h2></div><a class="text-link" href="students.php">All students <span aria-hidden="true">→</span></a></div>
        <?php if (!$recent): ?><div class="empty-state"><span class="empty-icon">♙</span><h3>No students yet</h3><p>New registrations will appear here.</p><a class="button button-secondary" href="add_student.php">Add first student</a></div>
        <?php else: ?><div class="table-wrap"><table><thead><tr><th>STUDENT</th><th>DEPARTMENT</th><th>ROOM</th><th>REGISTERED</th></tr></thead><tbody><?php foreach ($recent as $student): ?><tr><td><a class="student-cell" href="student_details.php?id=<?= e($student['id']) ?>"><span class="table-avatar"><?= e(strtoupper(substr($student['full_name'], 0, 1))) ?></span><span><strong><?= e($student['full_name']) ?></strong><small><?= e($student['usn']) ?></small></span></a></td><td><?= e($student['department']) ?></td><td><?= $student['room_number'] ? e($student['room_number']) : '<span class="muted">Unassigned</span>' ?></td><td><?= e(date('M j, Y', strtotime($student['registration_date']))) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
    <aside class="panel actions-panel"><span class="eyebrow">SHORTCUTS</span><h2>Quick actions</h2><a class="action-link" href="add_student.php"><span class="action-symbol coral-bg">＋</span><span><strong>Register student</strong><small>Add a new hostel resident</small></span><b>→</b></a><a class="action-link" href="add_room.php"><span class="action-symbol blue-bg">▦</span><span><strong>Add a room</strong><small>Expand room inventory</small></span><b>→</b></a><a class="action-link" href="allocations.php"><span class="action-symbol mint-bg">⇄</span><span><strong>Allocate a bed</strong><small>View rooms and placements</small></span><b>→</b></a><a class="action-link" href="students.php"><span class="action-symbol yellow-bg">⌕</span><span><strong>Find a student</strong><small>Search the student directory</small></span><b>→</b></a></aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>