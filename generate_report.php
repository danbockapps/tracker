<?php
require_once("config.php");

session_start();
if(!am_i_admin()) {
   exit("You must be an administrator to view this page");
}

if(!isset($_GET['report'])) {
   exit("No report specified.");
}

if($_GET['report'] == "users_table") {
   $qr = pdo_seleqt("
      select user_id, date_added
      from wrc_users
   ", array());
}
else if($_GET['report'] == "date_test") {
   require_get_vars("start_date");
   $qr = pdo_seleqt("
      select user_id, date_added
      from wrc_users
      where date(date_added) > ?
   ", array($_GET['start_date']));
}

if(empty($qr)) {
   exit("No report returned. Parameters: " . print_r($_GET, true));
}

header("Content-Type: application/csv");
header("Content-Disposition: attachment; filename=" . $_GET['report'] . ".csv");
header("Pragma: no-cache");
echo array_to_csv($qr);
?>
