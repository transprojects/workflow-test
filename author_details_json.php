<?php
/* Returns the article's current corresponding author details as JSON, so Start Work can refresh the
 * author fields after "Fetch Author Details" without reloading the page. Signed-in users only. */
$ag_json = true;
require_once __DIR__ . '/includes/ajax_guard.php';
include_once __DIR__ . '/dbc.php';                 // $db (mysqli)

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(array('ok' => false, 'error' => 'Invalid article.')); exit; }

$res = mysqli_query($db, "SELECT corresponding_author, corresponding_mail, corresponding_other_author, article_title FROM inw_inward_dtl WHERE id = " . $id . " LIMIT 1");
$row = $res ? mysqli_fetch_assoc($res) : null;
if (!$row) { http_response_code(404); echo json_encode(array('ok' => false, 'error' => 'Article not found.')); exit; }

echo json_encode(array(
    'ok'     => true,
    'author' => (string) $row['corresponding_author'],
    'mail'   => (string) $row['corresponding_mail'],
    'other'  => (string) $row['corresponding_other_author'],
    'title'  => (string) $row['article_title'],
));
