<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Add student';
$student = [];
$errors = [];
$formAction = 'add_student.php';
$submitLabel = 'Save student';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (['full_name', 'usn', 'department', 'semester', 'phone', 'email', 'gender', 'address', 'guardian_name', 'guardian_phone', 'registration_date'] as $field) {
        $student[$field] = post_string($field);
    }
    if ($student['full_name'] === '' || $student['usn'] === '' || $student['department'] === '' || $student['address'] === '' || $student['guardian_name'] === '') $errors[] = 'Complete all required text fields.';
    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!valid_phone($student['phone']) || !valid_phone($student['guardian_phone'])) $errors[] = 'Enter valid phone numbers.';
    if (!in_array($student['gender'], ['Female', 'Male', 'Other'], true)) $errors[] = 'Choose a valid gender.';
    if (!filter_var($student['semester'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]])) $errors[] = 'Semester must be from 1 to 8.';
    $date = DateTime::createFromFormat('Y-m-d', $student['registration_date']);
    if (!$date || $date->format('Y-m-d') !== $student['registration_date']) $errors[] = 'Enter a valid registration date.';
    if (!$errors) {
        try {
            $statement = db()->prepare('INSERT INTO students (full_name, usn, department, semester, phone, email, gender, address, guardian_name, guardian_phone, registration_date) VALUES (:full_name, :usn, :department, :semester, :phone, :email, :gender, :address, :guardian_name, :guardian_phone, :registration_date)');
            $statement->execute($student);
            flash('success', 'Student registered successfully.');
            redirect('students.php');
        } catch (PDOException $exception) {
            if (is_constraint_violation($exception)) $errors[] = 'That USN is already registered.';
            else { error_log($exception->getMessage()); $errors[] = 'The student could not be saved. Please try again.'; }
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading"><div><a class="back-link" href="students.php">← Student directory</a><h1>Register a student</h1><p>Enter the student and guardian details below.</p></div></section>
<?php require __DIR__ . '/includes/student_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>