<?php
require_once("config.php");

$qr = pdo_seleqt("
   select
      u.user_id,
      u.fname,
      u.email,
      u.activation,
      e.class_id,
      e.class_source,
      e.voucher_code
   from
      wrc_users u
      natural join " . ENR_VIEW . " e
      natural join classes_aw c
   where
      e.welcome_sent is null
      and c.start_dttm <= now() - interval 1 hour
", array());

echo "Sending mail to " . count($qr) . " recipients.\n";

foreach($qr as $row) {
   sendById($row['user_id'], 7);

   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update " . ENR_TBL . "
      set welcome_sent = now()
      where
         tracker_user_id = ?
         and class_id = ?
         and class_source = ?
   ");
   $sth->execute(array(
      $row['user_id'],
      $row['class_id'],
      $row['class_source']
   ));

   echo "Sent mail to " . $row['email'] . ".\n";
}

?>
