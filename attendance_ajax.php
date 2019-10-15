<?php
session_start();
require_once('config.php');

if(can_access_class($_POST['class_id'], 'w')) {
   $phase1before = phase1attendance($_POST['user_id'], $_POST['class_id']);

   $dbh = pdo_connect($ini['db_prefix'] . '_insert');
   $sth = $dbh->prepare('
      insert into wrc_attendance (
         user_id,
         class_id,
         class_source,
         week,
         present,
         attendance_type,
         attendance_date
      ) values (?, ?, ?, ?, ?, ?, ?)
   ');
   if($sth->execute(array(
      $_POST['user_id'],
      $_POST['class_id'],
      'w',
      $_POST['week'],
      $_POST['present'],
      $_POST['attendance_type'],
      nullIfBlank($_POST['attendance_date'])
   ))) {
      echo('OK');

      $phase1after = phase1attendance($_POST['user_id'], $_POST['class_id']);

      if($phase1before == 8 && $phase1after == 9) {
        // 9 classes in phase 1 - participant has earned t-shirt.
        sendById($_POST['user_id'], 6);
      }
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
