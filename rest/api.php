<?php
header('Content-Type: application/json');

if($_SERVER['REQUEST_SCHEME'] == 'http') {
  exit('HTTPS required. Not detected.');
}

require_once('../config.php');

$contents = file_get_contents("php://input");
$post = json_decode($contents, true);

// Initialize array that will be returned if no error.
$ok_array = array(
  q => $_GET['q'],
  responseString => "OK"
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
  strlen($response)
);

debug($response);

// If the required file didn't already exit:
echo $response;

?>
