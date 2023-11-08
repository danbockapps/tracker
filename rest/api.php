<?php
// TODO return proper HTTP statuses instead of 200 for everything
session_start();
header('Content-Type: application/json');

if($_SERVER['REQUEST_SCHEME'] == 'http') {
  exit('HTTPS required. Not detected.');
}

require_once('../config.php');

// Parameters sent by js fetch. These don't show up in $_POST.
$fetchPost = json_decode(file_get_contents('php://input'), true);

// Initialize array that will be returned.
$ok_array = array(
  'q' => $_GET['q'],
  'responseString' => "OK"
);

$start_time = microtime(true);
require('includes/' . $_GET['q'] . ".php");
$end_time = microtime(true);

$response = json_encode($ok_array, JSON_NUMERIC_CHECK);

logtxt(
  number_format($end_time - $start_time, 4) .
  " " .
  json_encode($_GET) .
  " " .
  removePassword($contents) .
  " " .
  json_encode($fetchPost) .
  " " .
  strlen($response)
);

debug($response);

// If the required file didn't already exit:
echo $response;

?>
