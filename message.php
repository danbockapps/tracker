<?php

if(!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
   echo "Error: args not set";
}
else {
   require_once("config.php");
   require_once('Mail.php');
   $recipients = $argv[1];
   if(
      defined("EMAIL_LOGGER") &&
      isset($_SERVER['PWD']) &&
      strpos($_SERVER['PWD'], "/home/esmmwl") !== false
   ) {
      $recipients .= "," . EMAIL_LOGGER;
   }
   $headers["From"] = EMAIL_FROM;
   $headers["To"] = $argv[1];
   $headers["Subject"] = $argv[2];
   $mailmsg = $argv[3];

   /* SMTP server name, port, user/passwd */
   $smtpinfo["host"] = "mail.esmmweighless.com";
   $smtpinfo["port"] = "25";
   $smtpinfo["auth"] = true;
   $smtpinfo["username"] = "tracker+esmmweighless.com";
   $smtpinfo["password"] = EMAIL_PASSWORD;

   /* Suppress mail error messages */
   $origErrReportingLevel = error_reporting();
   error_reporting(E_ALL & ~E_STRICT);

   /* Create the mail object using the Mail::factory method */
   $mail_object =& Mail::factory("smtp", $smtpinfo);

   /* Ok send mail */
   $mail_object->send($recipients, $headers, $mailmsg);

   /* Reset error reporting level */
   error_reporting($origErrReportingLevel);
}

?>
