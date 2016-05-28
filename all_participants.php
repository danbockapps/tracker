<?php
ob_start();
session_start();
require_once("config.php");

if(am_i_admin()) {
   if(isset($_GET['current'])) {
      $limiter = " natural join " . ENR_VIEW . " natural join current_classes ";
   }
   else {
      $limiter = "";
   }

   echo json_encode(pdo_seleqt("
      select
         user_id as id,
         concat(fname, ' ', lname, ' (', email, ')') as label
      from wrc_users " . $limiter . "
      where concat_ws(' ', fname, lname) like ?
   ", array("%" . $_GET['term'] . "%")));
}
?>
