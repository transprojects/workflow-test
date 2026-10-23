<?php
/**
 * Validation Forms Component
 * Checklist + PRQC Feedback + XML/QC Result Blocks
 */
?>

<div id="validation_wrapper" class="mt-4 d-none">

    <h4 class="section-title">Checklist Form</h4>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Details</th>
                <th width="10%">Yes</th>
                <th width="10%">NA</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM jbm_checklist 
                    WHERE (stage = ? OR stage = 'BOTH') 
                    AND dept_id = ?
                    ORDER BY display_order ASC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("si", $app_res['stage'], $app_res['department_id']);
            $stmt->execute();
            $clist = $stmt->get_result();

            while ($row = $clist->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><input type="radio" name="checklist[<?= $row['cl_id'] ?>]" value="Yes" checked></td>
                    <td><input type="radio" name="checklist[<?= $row['cl_id'] ?>]" value="NA"></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>


    <h4 class="section-title">Feedback Form</h4>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="25%">Name</th>
                <th>Feedback</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM prqc_report_categories 
                    WHERE stage LIKE ? 
                    ORDER BY display_order ASC";
            $stmt = $db->prepare($sql);
            $stg = "%".$app_res['stage']."%";
            $stmt->bind_param("s", $stg);
            $stmt->execute();
            $pcats = $stmt->get_result();

            while ($row = $pcats->fetch_assoc()):
            ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($row['name']) ?>
                        <input type="hidden" name="prqc_cats[]" value="<?= $row['id'] ?>">
                    </td>
                    <td>
                        <textarea class="form-control" name="reason[<?= $row['id'] ?>]" rows="2"></textarea>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>
