<?php
session_start();
require_once('config.php');
logtxt(htmlentities(print_r($_POST, true)));

if(can_access_class($_POST['class_id'], $_POST['class_source'])) {
   $dbh = pdo_connect($ini['db_prefix'] . '_insert');
   $sth = $dbh->prepare('
      insert into wrc_attendance (user_id, class_id, class_source, week, present)
      values (?, ?, ?, ?, ?)
   ');
   if($sth->execute(array(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['class_source'],
      $_POST['week'],
      $_POST['present']
   ))) {
      echo('OK');
   }
   else {
      logtxt('ERROR: unknown database error.');
      echo('ERROR');
   }
}
else {
   logtxt(
      'ERROR: User ID ' .
      $_SESSION['user_id'] .
      ' cannot access class ID ' .
      $_POST['class_id'] . '.'
   );
   echo('ERROR');
}
?>
