<?php
require_once("config.php");
require_once('Mail.php');

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
   $msg = "Hello " . $row['fname'] . ",

";

   $msg .= 'We hope you enjoyed your first Eat Smart, Move More, Prevent Diabetes class.
 
As you heard from your instructor, the next step in your Eat Smart, Move More, Prevent Diabetes journey is to get started using My Progress Portal. Entering information in My Progress Portal and engaging with your instructor through My Progress Portal is essential to your success in the program. 

To activate My Progress Portal:
1. Go here: ';

   $msg .= WEBSITE_URL . "/setpw.php?email=" .
            urlencode($row['email']) . "&key=" . $row['activation'] . '
';

   $msg .= '2. Create a password; you will use this email address and the password you create to login to My Progress Portal.
3. You can now login to My Progress Portal. Please make sure to bookmark the site so you can access easily it in the future. You can also find it through the My Progress Portal button on our website home page, https://esmmpreventdiabetes.com. If you forget your password, you can click on the link on the login page to have your password emailed to you.

Your instructor will be sending out more detailed instructions throughout the week. 

Please email administrator@esmmpreventdiabetes.com if you have issues setting up My Progress Portal account.
 
 Thanks and hope that you enjoy the program!
 
 Sincerely,
The Eat Smart, Move More, Prevent Diabetes Team';


   $recipients = $row['email'];
   $recipients .= ',danbock@gmail.com';
   $headers["From"] = EMAIL_FROM;
   $headers["To"] = $row['email'];
   $headers["Subject"] = "Eat Smart, Move More, Prevent Diabetes Team - My Progress Portal";

   /* Suppress mail error messages */
   $origErrReportingLevel = error_reporting();
   error_reporting(E_ALL & ~E_STRICT);

   $params['sendmail_path'] = $ini['sendmail_path'];
   $mail_object =& Mail::factory('sendmail', $params);

   /* Ok send mail */
   $mail_object->send($recipients, $headers, $msg);

   /* Reset error reporting level */
   error_reporting($origErrReportingLevel);




/*
   sendmail(
      $row['email'],
      "Eat Smart, Move More, Weigh Less Weekly Tracker",
      $msg
   );
*/
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update " . ENR_TBL . "
      set welcome_sent = now()
      where
         user_id = ?
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
