<?php
require_once "../includes/conn.php";

function getArticleDetails($db, $aid) {
    $sql = "SELECT * FROM inw_inward_dtl WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getCustomerDetails($db, $cust_id) {
    $sql = "SELECT cust_name, cust_id, id, customer_spec 
            FROM adm_customer_master 
            WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getWorkflowDeptCodes($db, $wf_id) {
    $sql = "SELECT wf_code FROM adm_workflow_master WHERE wf_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $wf_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return explode(",", $res['wf_code']);
}
