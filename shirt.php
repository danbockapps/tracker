<?php
session_start();
require_once('config.php');
if(!can_access_class($_POST['class_id'], 'w')) {
   exit('ERROR');
}

$dbh = pdo_connect($ini['db_prefix'] . "_update");

$sth = $dbh->prepare("
   update " . ENR_TBL . "
   set
      shirtchoice = ?
   where
      tracker_user_id = ?
      and class_id = ?
");

if($sth->execute(array($_POST['shirt_choice'], $_POST['user_id'], $_POST['class_id']))) {
   echo 'OK';
}
else {
   logtxt("Error selecting shirt choice:");
   logtxt($sth->errorCode());
   logtxt($sth->errorInfo());
   echo 'ERROR';
}


?>
