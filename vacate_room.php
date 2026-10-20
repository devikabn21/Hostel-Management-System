<?php
require_once __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('allocations.php');
verify_csrf();
$allocationId = filter_var($_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$allocationId) { flash('error', 'Choose a valid active allocation.'); redirect('allocations.php'); }
$connection = db();
try {
	begin_immediate($connection);
	$statement = $connection->prepare("UPDATE room_allocations SET status = 'vacated', vacated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'active'");
	$statement->execute(['id' => $allocationId]);
	$changed = $statement->rowCount() === 1;
	commit_immediate($connection);
	flash($changed ? 'success' : 'error', $changed ? 'Student vacated successfully; allocation history was retained.' : 'Active allocation not found.');
} catch (PDOException $exception) {
	rollback_immediate($connection);
	error_log($exception->getMessage());
	flash('error', 'The allocation could not be vacated. Please try again.');
}
redirect('allocations.php');