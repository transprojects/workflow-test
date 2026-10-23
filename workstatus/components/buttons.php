<div class="action-buttons">

    <a class="btn btn-orange" 
       href="<?= $_COOKIE['set_url'] ?>/sendquery/<?= $aid ?>" 
       target="_blank">
       <i class="fa fa-envelope"></i> View Queries
    </a>

    <button class="btn btn-outline-danger"
            data-bs-toggle="modal"
            data-bs-target="#instructionsModal"
            onclick="loadInstructions(<?= $aid ?>)">
        View Instructions
    </button>

    <button class="btn btn-outline-danger"
            data-bs-toggle="modal"
            data-bs-target="#specModal"
            onclick="loadSpecs(<?= $aid ?>)">
        View Specs
    </button>

    <?php if ($app_res['manuscript_report_status'] == '1'): ?>
        <a class="btn btn-success"
           href="http://<?= $ceditor_ip ?>/Transforma/ce_editor/home/edit_file_users/<?= $app_res['activity'] ?>/<?= $app_res['pub_id'] ?>/<?= $_COOKIE['user_login_id'] ?>"
           target="_blank">
            <i class="fa fa-edit"></i> PE
        </a>
    <?php endif; ?>

</div>
