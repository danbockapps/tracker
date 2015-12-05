<?php
require_once('config.php');
logtxt(print_r($_POST, true));

$dbh = pdo_connect('esmmwl_insert');
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
?>
