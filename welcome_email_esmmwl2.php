<?php
require_once("config.php");
require_once("Mail.php");

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
   $format = file_get_contents('welcome_esmmwl2.txt');
}

foreach($qr as $row) {
   $url = WEBSITE_URL;

   if($row['activation'] != null) {
      $url .= '/setpw.php?email=' . urlencode($row['email']) . '&key=' .
            $row['activation'];
   }

   $msg = sprintf($format, $row['fname'], $url);

   $recipients = $row['email'];
   $recipients .= ',danbock@gmail.com';
   $headers["From"] = EMAIL_FROM;
   $headers["To"] = $row['email'];
   $headers["Subject"] = "Eat Smart, Move More, Weigh Less My Dashboard 2";

   /* Suppress mail error messages */
   $origErrReportingLevel = error_reporting();
   error_reporting(E_ALL & ~E_STRICT);

   $params['sendmail_path'] = $ini['sendmail_path'];
   $mail_object =& Mail::factory('sendmail', $params);

   /* Ok send mail */
   $mail_object->send($recipients, $headers, $msg);

   /* Reset error reporting level */
   error_reporting($origErrReportingLevel);



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
