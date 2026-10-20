<form class="form-panel" method="post" action="<?= e($formAction) ?>" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if (isset($student['id'])): ?><input type="hidden" name="id" value="<?= e($student['id']) ?>"><?php endif; ?>
    <?php if ($errors): ?><div class="form-errors" role="alert"><strong>Please review the highlighted information.</strong><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="form-section"><div><span class="eyebrow">01 / STUDENT</span><h2>Personal information</h2></div><div class="form-grid">
        <label>Full name <span>*</span><input name="full_name" maxlength="150" required value="<?= e($student['full_name'] ?? '') ?>"></label>
        <label>USN <span>*</span><input name="usn" maxlength="30" required value="<?= e($student['usn'] ?? '') ?>"></label>
        <label>Department <span>*</span><input name="department" maxlength="100" required value="<?= e($student['department'] ?? '') ?>"></label>
        <label>Semester <span>*</span><select name="semester" required><?php for ($term = 1; $term <= 8; $term++): ?><option value="<?= $term ?>" <?= (string) ($student['semester'] ?? '') === (string) $term ? 'selected' : '' ?>>Semester <?= $term ?></option><?php endfor; ?></select></label>
        <label>Phone number <span>*</span><input name="phone" maxlength="20" required value="<?= e($student['phone'] ?? '') ?>"></label>
        <label>Email address <span>*</span><input name="email" type="email" maxlength="190" required value="<?= e($student['email'] ?? '') ?>"></label>
        <label>Gender <span>*</span><select name="gender" required><option value="">Choose gender</option><?php foreach (['Female', 'Male', 'Other'] as $option): ?><option <?= ($student['gender'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
        <label>Registration date <span>*</span><input name="registration_date" type="date" required value="<?= e($student['registration_date'] ?? date('Y-m-d')) ?>"></label>
        <label class="full-span">Home address <span>*</span><textarea name="address" rows="3" required><?= e($student['address'] ?? '') ?></textarea></label>
    </div></div>
    <div class="form-section"><div><span class="eyebrow">02 / EMERGENCY CONTACT</span><h2>Guardian information</h2></div><div class="form-grid">
        <label>Guardian name <span>*</span><input name="guardian_name" maxlength="150" required value="<?= e($student['guardian_name'] ?? '') ?>"></label>
        <label>Guardian phone <span>*</span><input name="guardian_phone" maxlength="20" required value="<?= e($student['guardian_phone'] ?? '') ?>"></label>
    </div></div>
    <div class="form-actions"><a class="button button-quiet" href="students.php">Cancel</a><button class="button button-primary" type="submit"><?= e($submitLabel) ?> <span aria-hidden="true">→</span></button></div>
</form>