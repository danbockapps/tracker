<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'], $_POST['classId'])) {
  $dbh = pdo_connect(DB_PREFIX . '_update');
  $sth = $dbh->prepare('
    update ' . ENR_TBL . '
    set
      refund_method = ?,
      refund_email_address = ?,
      refund_postal_address = ?
    where
      tracker_user_id = ?
      and class_id = ?
  ');

  $ok_array['result'] = $sth->execute(array(
    $_POST['refundMethod'],
    $_POST['refundEmailAddress'],
    $_POST['refundPostalAddress'],
    $_SESSION['user_id'],
    $_POST['classId']
  ));
}

?>
