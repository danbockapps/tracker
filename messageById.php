<?php
if(!isset($argv[1]) || !isset($argv[2])) {
   exit('Error: args not set.');
}

require_once('config.php');
require_once('Mail.php');

$nonHiddenRecipient = getNonHiddenRecipient();
$recipients = getRecipients();
$headers = getHeaders();
$msg = getMessage($argv[1], $argv[2], $argv[3], $nonHiddenRecipient);

/* Suppress mail error messages */
$origErrReportingLevel = error_reporting();
error_reporting(E_ALL & ~E_STRICT);

/* Create the mail object using the Mail::factory method */
if(isset($ini['sendmail_path'])) {
   // Works on new server
   $params['sendmail_path'] = $ini['sendmail_path'];
   $mail_object =& Mail::factory('sendmail', $params);
}
else {
   // Works on old server
   $smtpinfo = getSmtpinfo();
   $mail_object =& Mail::factory("smtp", $smtpinfo);
}

/* Ok send mail */
$mail_object->send($recipients, $headers, $msg);

/* Reset error reporting level */
error_reporting($origErrReportingLevel);

function getNonHiddenRecipient() {
   global $argv;
   $qr = seleqt_one_record('
      select email
      from wrc_users
      where user_id = ?
   ', $argv[1]);
   return $qr['email'];
}

function getRecipients() {
   global $nonHiddenRecipient;
   $recipients = $nonHiddenRecipient;
   if(EMAIL_LOGGER != '') {
      $recipients .= "," . EMAIL_LOGGER;
   }
   return $recipients;
}

function getHeaders() {
   global $nonHiddenRecipient;
   $headers["From"] = EMAIL_FROM;
   $headers["To"] = $nonHiddenRecipient;
   $headers["Subject"] = getSubject();
   return $headers;
}

function getSubject() {
   global $argv;
   switch($argv[2]) {
      case 1:
         return PRODUCT_TITLE . ' - Password Reset';
         break;
      case 2:
         return PRODUCT_TITLE . ' - New Message';
         break;
      case 3:
         return PRODUCT_TITLE . ' - New Instructor Feedback';
         break;
      case 4:
         return PRODUCT_TITLE . ' - Registration Confirmation';
         break;
      case 5:
         return PRODUCT_TITLE . ' - You are now an instructor';
         break;
      default:
         exit('Invalid message ID.');
   }
}

function getMessage($recipientId, $messageId, $participantId, $recipientEmail) {
   if($messageId == 1) {
      // This is called from reset.php
      $email_reset_key = generate_email_reset($recipientEmail);

      $message = "To reset your password, please click on this link:\n"
         . WEBSITE_URL . "/reset.php?email=" . urlencode($recipientEmail)
         . "&key=$email_reset_key\n\n"
         . "If you did not make this request, please disregard this message."
         . " Your password has not been changed.";
   }
   else if($messageId == 2) {
      // This is called from the message_participant function in config.php
      $message = "You have received a new message.\n";
      $message .= "Click here to see it: " . WEBSITE_URL;
      $message .= "/all_messages.php?user=$participantId";
   }
   else if($messageId == 3) {
      // This is called from report.php
      $message = "You have received instructor feedback.\n";
      $message .= "Click here to see it: " . WEBSITE_URL;
   }
   else if($messageId == 4) {
      // This is called from register.php

      $qr = seleqt_one_record('
         select activation
         from wrc_users
         where user_id = ?
      ', $recipientId);

      $message = " To activate your account, please click on this link:\n";
      $message .= WEBSITE_URL . '/activate.php?email='
               . urlencode($recipientEmail) . "&key=" . $qr['activation'];
   }
   else if($messageId == 5) {
      // This is called from addinstructor() in admin.php
      $message = "You are now registered as an instructor in the Eat " .
               "Smart, Move More, Weigh Less My Dashboard " .
               "application. Log in here:\n" . WEBSITE_URL;
   }
   else {
      exit('Invalid message ID.');
   }

   return $message;
}

function getSmtpinfo() {
   global $ini;
   $smtpinfo["host"] = $ini['smtphost'];
   $smtpinfo["port"] = $ini['smtpport'];
   $smtpinfo["auth"] = true;
   $smtpinfo["username"] = $ini['smtpusername'];
   $smtpinfo["password"] = EMAIL_PASSWORD;
   return $smtpinfo;
}
?>
