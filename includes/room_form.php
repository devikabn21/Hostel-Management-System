<form class="form-panel compact-form" method="post" action="<?= e($formAction) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php if (isset($room['id'])): ?><input type="hidden" name="id" value="<?= e($room['id']) ?>"><?php endif; ?>
    <?php if ($errors): ?><div class="form-errors" role="alert"><strong>Please correct the following:</strong><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="form-section"><div><span class="eyebrow">ROOM DETAILS</span><h2>Inventory information</h2></div><div class="form-grid">
        <label>Room number <span>*</span><input name="room_number" maxlength="20" required value="<?= e($room['room_number'] ?? '') ?>"></label>
        <label>Room type <span>*</span><select name="room_type" required><?php foreach (['Single', 'Double', 'Triple', 'Dormitory'] as $type): ?><option <?= ($room['room_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
        <label>Floor <span>*</span><input name="floor" type="number" min="0" max="50" required value="<?= e($room['floor'] ?? '0') ?>"></label>
        <label>Bed capacity <span>*</span><input name="capacity" type="number" min="1" max="50" required value="<?= e($room['capacity'] ?? '2') ?>"></label>
        <label>Room status <span>*</span><select name="status"><option value="available" <?= ($room['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available for allocation</option><option value="maintenance" <?= ($room['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Under maintenance</option></select></label>
    </div></div>
    <div class="form-actions"><a class="button button-quiet" href="rooms.php">Cancel</a><button class="button button-primary" type="submit"><?= e($submitLabel) ?> <span>→</span></button></div>
</form>