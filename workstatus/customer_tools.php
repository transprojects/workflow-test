<?php
/**
 * Components: Customer Tools Panel
 * Cleaned and modularized based on your earlier conditions
 */

$cust = $customer['cust_name'];
$custId = $app_res['cust_id'];
$stage = $app_res['stage'];
$status = $app_res['status'];
$dept  = $app_res['department_id'];
$jcode = $journal_info['j_code'];
?>
<div class="action-buttons mt-3">

    <?php if ($stage == "FP" && in_array($custId, [33, 42, 43, 57, 58, 66, 76])): ?>
        <a class="btn btn-danger" 
           href="http://<?= $ceditor_ip ?>/Transforma/ce_editor/home/edit_file_users/<?= $app_res['activity'] ?>/<?= $app_res['pub_id'] ?>/<?= $_COOKIE['user_login_id']; ?>" 
           target="_blank">
            <i class="fa fa-edit"></i> CE Editor
        </a>
    <?php endif; ?>


    <?php if ($stage == "FP" && in_array($custId, [7, 57, 66])): ?>
        <button class="btn btn-danger" id="TexprecleanConversionButton">
            <i class="fa fa-folder-open"></i> Tex Preclean
        </button>
    <?php endif; ?>

    <?php
    // HTML / XML / PACKAGE Tools
    if (
        ($cust == "TSP" || $cust == "Transforma" || $cust == "EOS" || $custId == 80) &&
        $stage == "FP" &&
        ($status == "PRQC" || $status == "Proofing" || $status == "PAG")
    ):
    ?>
        <button class="btn btn-primary" id="3dtohtml"><i class="fa fa-file-code-o"></i> 3D to HTML</button>
        <button class="btn btn-primary" id="xmltohtml"><i class="fa fa-file-code-o"></i> XML to HTML</button>
        <button class="btn btn-primary" id="opfxmltohtml"><i class="fa fa-file-code-o"></i> OPF XML to HTML</button>

        <a class="btn btn-primary" href="<?= Proof_Viewing_URL . $app_res['id'] . "/" . $app_res['pub_id']; ?>" target="_blank">
            <i class="fa fa-eye"></i> HTML View
        </a>

        <?php if ($cust == "EOS"): ?>
            <button class="btn btn-primary" id="eos_fp_package"><i class="fa fa-file-archive-o"></i> Package creation</button>
        <?php else: ?>
            <button class="btn btn-primary" id="tsp_fp_package"><i class="fa fa-file-archive-o"></i> Package creation</button>
        <?php endif; ?>
    <?php endif; ?>


    <?php
    // XML Validation for PMC / ISSFIN
    $stagePMC = preg_replace("/PMC([0-9]+)/", "PMC", $stage);
    $stageISS = preg_replace("/ISSFIN([0-9]+)/", "ISSFIN", $stage);

    if ($custId == 57 && ($stagePMC == "PMC" || $stageISS == "ISSFIN")):
    ?>
        <button class="btn btn-dark" id="xmlCValidationButton"><i class="fa fa-check"></i> XML Validation</button>
    <?php endif; ?>

    <?php if ($custId == 66 && $stage == "FIN" && $dept == 5): ?>
        <button class="btn btn-success" id="ePub_validation_Creation"><i class="fa fa-book"></i> ePub Creation</button>
    <?php endif; ?>

</div>
