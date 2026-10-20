<?php
require_once __DIR__ . '/includes/auth.php';
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) { flash('error', 'Choose a valid student record.'); redirect('students.php'); }
$statement = db()->prepare('SELECT * FROM students WHERE id = :id');
$statement->execute(['id' => $id]);
$student = $statement->fetch();
if (!$student) { flash('error', 'Student record not found.'); redirect('students.php'); }
$pageTitle = 'Edit student';
$errors = [];
$formAction = 'edit_student.php?id=' . $id;
$submitLabel = 'Save changes';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['full_name', 'usn', 'department', 'semester', 'phone', 'email', 'gender', 'address', 'guardian_name', 'guardian_phone', 'registration_date'] as $field) $student[$field] = post_string($field);
    if ($student['full_name'] === '' || $student['usn'] === '' || $student['department'] === '' || $student['address'] === '' || $student['guardian_name'] === '') $errors[] = 'Complete all required text fields.';
    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!valid_phone($student['phone']) || !valid_phone($student['guardian_phone'])) $errors[] = 'Enter valid phone numbers.';
    if (!in_array($student['gender'], ['Female', 'Male', 'Other'], true)) $errors[] = 'Choose a valid gender.';
    if (!filter_var($student['semester'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]])) $errors[] = 'Semester must be from 1 to 8.';
    $date = DateTime::createFromFormat('Y-m-d', $student['registration_date']);
    if (!$date || $date->format('Y-m-d') !== $student['registration_date']) $errors[] = 'Enter a valid registration date.';
    if (!$errors) {
        try {
            $statement = db()->prepare('UPDATE students SET full_name=:full_name, usn=:usn, department=:department, semester=:semester, phone=:phone, email=:email, gender=:gender, address=:address, guardian_name=:guardian_name, guardian_phone=:guardian_phone, registration_date=:registration_date, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $statement->execute([
                'full_name' => $student['full_name'],
                'usn' => $student['usn'],
                'department' => $student['department'],
                'semester' => $student['semester'],
                'phone' => $student['phone'],
                'email' => $student['email'],
                'gender' => $student['gender'],
                'address' => $student['address'],
                'guardian_name' => $student['guardian_name'],
                'guardian_phone' => $student['guardian_phone'],
                'registration_date' => $student['registration_date'],
                'id' => $id,
            ]);
            flash('success', 'Student information updated.');
            redirect('student_details.php?id=' . $id);
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) $errors[] = 'That USN is already registered to another student.';
            else { error_log($exception->getMessage()); $errors[] = 'The student could not be updated.'; }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><a class="back-link" href="student_details.php?id=<?= e($id) ?>">← Student details</a><h1>Edit student</h1><p>Update the resident profile and emergency contact.</p></div></section>
<?php require __DIR__ . '/includes/student_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>