<?php
if(!isset($argv[1]) || !isset($argv[2])) {
   exit('Error: args not set.');
}

require_once('config.php');
require_once('emailTemplates.php');

$recipient = getRecipient();
$subject = getSubject();
$msg = getMessage($argv[1], $argv[2], $argv[3], $recipient);

if($argv[2] == 6 || $argv[2] == 2) {
   syncMailHtml($recipient, $subject, $msg);
}
else {
   syncMail($recipient, $subject, $msg);
}

function getRecipient() {
   global $argv;
   $qr = seleqt_one_record('
      select email
      from wrc_users
      where user_id = ?
   ', $argv[1]);
   return $qr['email'];
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
      case 6:
         return PRODUCT_TITLE . ' - Congratulations - You have earned your t-shirt!';
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
      $message = newMessage();
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
   else if($messageId == 6) {
      // Just attended 9th class
      $message = earnedShirt();
   }
   else {
      exit('Invalid message ID.');
   }

   return $message;
}

?>
