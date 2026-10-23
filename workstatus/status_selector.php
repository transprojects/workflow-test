<?php
/**
 * Status Selector Component
 */
?>
<div class="row mt-4">

    <div class="col-lg-4">
        <label class="form-label">Choose Status <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-select" required>
            <option value="">- Select Status -</option>

            <?php foreach ($project_status_array as $id => $val): ?>
                <?php if ($val != "Query"): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($val) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-lg-4 d-none" id="assign_department_div">
        <label class="form-label">Assign Department</label>
        <select id="assign_department" name="assign_department" class="form-select">
            <option value="">- Select -</option>

            <?php foreach ($workflow as $dcode): ?>
                <?php if ($dcode != $app_res['department_id'] && $dcode != 15): ?>
                    <?php
                    $dept = getDepartmentName($db, $dcode);
                    ?>
                    <option value="<?= $dcode ?>"><?= htmlspecialchars($dept) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-lg-4 d-none" id="assign_user">
        <label class="form-label">Select User</label>
        <select id="a_user_id" name="a_user_id" class="form-select">
            <option value="">- Select -</option>
        </select>
    </div>

</div>
