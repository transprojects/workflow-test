<?php

// ===============================
// GENERAL HELPERS
// ===============================

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function isWordFile($app) {
    $type = strtolower($app['inw_type']);
    return in_array($type, ['word', 'docx', 'doc', 'latex']);
}

function startsWith($value, $prefix) {
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function cleanStage($stage) {
    return preg_replace('/[0-9]+/', '', $stage);
}

function customerIn(array $allowed, $id) {
    return in_array((int)$id, $allowed);
}

function deptIn(array $allowed, $dept) {
    return in_array((int)$dept, $allowed);
}

// ===============================
// COMPLEX WORKFLOW CONDITIONS
// ===============================

function isCustomerSpecial($cust_id) {
    $special = [33, 37, 42, 43, 57, 66, 76, 77, 79];
    return in_array((int)$cust_id, $special);
}

function isStage($app, $stageCode) {
    return cleanStage($app['stage']) === $stageCode;
}

function debug($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

// ===============================
// VALIDATION UTILS
// ===============================

function safeInt($value) {
    return intval($value ?? 0);
}

function safeStr($value) {
    return trim($value ?? "");
}
