<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Students';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_string('action') === 'delete') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $statement = db()->prepare('DELETE FROM students WHERE id = :id');
            $statement->execute(['id' => $id]);
            flash('success', 'Student record deleted.');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) {
                flash('error', 'This student has allocation history and cannot be deleted. Vacate the current room first; history is retained.');
            } else {
                error_log($exception->getMessage());
                flash('error', 'The student could not be deleted.');
            }
        }
    }
    redirect('students.php');
}

$search = trim((string) ($_GET['search'] ?? ''));
$department = trim((string) ($_GET['department'] ?? ''));
$semester = filter_var($_GET['semester'] ?? '', FILTER_VALIDATE_INT);
$room = trim((string) ($_GET['room'] ?? ''));
$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = '(s.full_name LIKE :search_name OR s.usn LIKE :search_usn)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_usn'] = '%' . $search . '%';
}
if ($department !== '') {
    $conditions[] = 's.department = :department';
    $params['department'] = $department;
}
if ($semester !== false && $semester !== null && $semester >= 1 && $semester <= 12) {
    $conditions[] = 's.semester = :semester';
    $params['semester'] = $semester;
}
if ($room !== '') {
    $conditions[] = 'EXISTS (SELECT 1 FROM room_allocations a JOIN rooms r ON r.id = a.room_id WHERE a.student_id = s.id AND a.status = \'active\' AND r.room_number = :room)';
    $params['room'] = $room;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$statement = db()->prepare(
    "SELECT s.*, (SELECT r.room_number FROM room_allocations a JOIN rooms r ON r.id = a.room_id WHERE a.student_id = s.id AND a.status = 'active' LIMIT 1) AS room_number
     FROM students s $where ORDER BY s.full_name"
);
$statement->execute($params);
$students = $statement->fetchAll();
$departmentsStatement = db()->prepare('SELECT DISTINCT department FROM students ORDER BY department');
$departmentsStatement->execute();
$departments = $departmentsStatement->fetchAll(PDO::FETCH_COLUMN);
$roomsStatement = db()->prepare('SELECT room_number FROM rooms ORDER BY room_number');
$roomsStatement->execute();
$rooms = $roomsStatement->fetchAll(PDO::FETCH_COLUMN);
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><span class="eyebrow">STUDENT DIRECTORY</span><h1>Students</h1><p>Manage resident profiles and find the right record quickly.</p></div><a class="button button-primary" href="add_student.php"><span aria-hidden="true">＋</span> Add student</a></section>
<section class="panel directory-panel">
    <form class="filters" method="get" action="students.php"><label class="search-field"><span aria-hidden="true">⌕</span><input type="search" name="search" placeholder="Search name or USN" value="<?= e($search) ?>"></label><select name="department" aria-label="Filter by department"><option value="">All departments</option><?php foreach ($departments as $item): ?><option value="<?= e($item) ?>" <?= $department === $item ? 'selected' : '' ?>><?= e($item) ?></option><?php endforeach; ?></select><select name="semester" aria-label="Filter by semester"><option value="">All semesters</option><?php for ($term = 1; $term <= 8; $term++): ?><option value="<?= $term ?>" <?= (string) $semester === (string) $term ? 'selected' : '' ?>>Semester <?= $term ?></option><?php endfor; ?></select><select name="room" aria-label="Filter by room"><option value="">All rooms</option><?php foreach ($rooms as $item): ?><option value="<?= e($item) ?>" <?= $room === $item ? 'selected' : '' ?>>Room <?= e($item) ?></option><?php endforeach; ?></select><button class="button button-secondary" type="submit">Filter</button><a class="button button-quiet" href="students.php">Reset</a></form>
    <div class="table-summary"><strong><?= count($students) ?> student<?= count($students) === 1 ? '' : 's' ?></strong><span>Records matching your filters</span></div>
    <?php if (!$students): ?><div class="empty-state"><span class="empty-icon">♙</span><h3>No matching students</h3><p>Try another search or register a student.</p><a class="button button-secondary" href="add_student.php">Add student</a></div>
    <?php else: ?><div class="table-wrap"><table><thead><tr><th>STUDENT</th><th>DEPARTMENT</th><th>SEMESTER</th><th>PHONE</th><th>ROOM</th><th class="right">ACTIONS</th></tr></thead><tbody><?php foreach ($students as $student): ?><tr><td><a class="student-cell" href="student_details.php?id=<?= e($student['id']) ?>"><span class="table-avatar coral-avatar"><?= e(strtoupper(substr($student['full_name'], 0, 1))) ?></span><span><strong><?= e($student['full_name']) ?></strong><small><?= e($student['usn']) ?></small></span></a></td><td><?= e($student['department']) ?></td><td><?= e($student['semester']) ?></td><td><?= e($student['phone']) ?></td><td><?= $student['room_number'] ? e($student['room_number']) : '<span class="muted">Unassigned</span>' ?></td><td class="right action-cell"><a class="icon-button" title="View details" href="student_details.php?id=<?= e($student['id']) ?>">↗</a><a class="icon-button" title="Edit student" href="edit_student.php?id=<?= e($student['id']) ?>">✎</a><form method="post" action="students.php" data-confirm="Delete <?= e($student['full_name']) ?>? This cannot be undone."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($student['id']) ?>"><button class="icon-button danger-icon" type="submit" title="Delete student">⌫</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>