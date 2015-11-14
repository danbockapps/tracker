<?php
ob_start();
session_start();
require_once("config.php");

if(am_i_admin()) {
   echo json_encode(pdo_seleqt("
      select
         user_id as id,
         concat(fname, ' ', lname, ' (', email, ')') as label
      from
         wrc_users
         natural join wrc_enrollment
         natural join current_classes
      where concat_ws(' ', fname, lname) like ?
   ", array("%" . $_GET['term'] . "%")));
}
?>
