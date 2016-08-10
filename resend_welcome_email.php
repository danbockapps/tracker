<?php
ob_start();
session_start();
require_once("config.php");

if(am_i_admin()) {
   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update " . ENR_TBL . "
      set
         welcome_sent = null
      where
         tracker_user_id = ?
         and class_id = ?
         and class_source = ?
   ");
   $sth->execute(array(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['class_source']
   ));
}
?>
