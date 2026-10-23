<input type="hidden" name="aid" value="<?= $aid ?>">
<?php foreach ($app_res as $key => $val): ?>
    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
<?php endforeach; ?>
