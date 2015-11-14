<?php
ob_start();
session_start();
require_once("config.php");

if(am_i_admin()) {
   $dbh = pdo_connect("esmmwl_update");
   $sth = $dbh->prepare("
      update wrc_enrollment
      set
         welcome_sent = null
      where
         user_id = ?
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
