<?php

function getRecipient($userId) {
  $qr = seleqt_one_record('
     select email
     from wrc_users
     where user_id = ?
  ', $userId);
  return $qr['email'];
}

function getSubject($messageId) {
  switch($messageId) {
     case 1:
        return PRODUCT_TITLE . ' - Password Reset';
        break;
     case 2:
        return PRODUCT_TITLE . ' - New Message';
        break;
     case 4:
        return PRODUCT_TITLE . ' - Registration Confirmation';
        break;
     case 5:
        return PRODUCT_TITLE . ' - You are now an instructor';
        break;
     case 6:
        switch(PRODUCT) {
           case 'dpp':
              return PRODUCT_TITLE . ' - Congratulations - You have earned your t-shirt!';
              break;
           case 'esmmwl':
              return PRODUCT_TITLE . ' - Attend your final class to earn a t-shirt';
              break;
           case 'esmmwl2':
              return PRODUCT_TITLE . ' - Attend your final class to earn a t-shirt';
              break;
        }
     case 7:
        return PROGRAM_NAME . ' ' . PRODUCT_TITLE;
        break;
     default:
        exit('Invalid message ID.');
  }
}

function getMessage($recipientId, $messageId, $participantId, $recipientEmail) {
  if($messageId == 1) {
     // This is called from reset.php
     $message = getResetPasswordText($recipientEmail, generate_email_reset($recipientEmail));
  }
  else if($messageId == 2) {
     // This is called from the message_participant function in config.php
     $message = newMessage($participantId);
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
     switch(PRODUCT) {
        case 'dpp':
           $message = earnedShirtPd();
           break;
        case 'esmmwl':
           $message = earnedShirtWl();
           break;
        case 'esmmwl2':
           $message = earnedShirtWl2();
           break;
     }
  }
  else if ($messageId == 7) {
     $qr = seleqt_one_record(
        "select fname, activation from wrc_users where user_id = ?",
        $recipientId
     );

     switch(PRODUCT) {
        case 'dpp':
           $message = welcomeEmailPd($qr['fname'], $recipientEmail, $qr['activation']);
           break;
        case 'esmmwl':
           $message = welcomeEmailWl($qr['fname'], $recipientEmail, $qr['activation'], false);
           break;
        case 'esmmwl2':
           $message = welcomeEmailWl2($qr['fname'], $recipientEmail, $qr['activation']);
           break;
     }
  }
  else {
     exit('Invalid message ID.');
  }

  return $message;
}

function getPostmarkTag($messageId) {
  switch($messageId) {
     case 1:
        return 'Password reset';
     case 2:
        return 'New message';
     case 6:
        return 'T-shirt earned';
     case 7:
        return 'Welcome';
  }
}

?>