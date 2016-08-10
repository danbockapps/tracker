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

if(count($qr) > 0) {
   $w1   = file_get_contents("welcome_t1.txt");
   $w1n1 = file_get_contents("welcome_t1n1.txt");
   $w1n2 = file_get_contents("welcome_t1n2.txt");
   $w1o1 = file_get_contents("welcome_t1o1.txt");
   $w1o2 = file_get_contents("welcome_t1o2.txt");
   $w2   = file_get_contents("welcome_t2.txt");
   $w3   = file_get_contents("welcome_t3.txt");
   $w3b  = file_get_contents("welcome_t3b.txt");
   $w4   = file_get_contents("welcome_t4.txt");
}

foreach($qr as $row) {
   $msg = "Hello " . $row['fname'] . "," . $w1;

   if($row['activation'] == null) {
      // Old participant already has an activated Tracker account
      $msg .= $w1o1 . WEBSITE_URL . $w1o2;
   }
   else {
      // New participant needs to activate her Tracker account
      $msg .= $w1n1 . WEBSITE_URL . "/setpw.php?email=" .
            urlencode($row['email']) . "&key=" . $row['activation'] . $w1n2;
   }

   $msg .= $w2;

   if(stripos($row['voucher_code'], "ASO") === false) {
      // Non-client1 participant
      $msg .= $w3 . $w4;
   }
   else {
      // client1 participant
      $msg .= "*" . $w3 . $w3b . $w4;
   }

   sendmail(
      $row['email'],
      "Eat Smart, Move More, Weigh Less Weekly Tracker",
      $msg
   );
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
