<?php
require_once('config.php');

$qr = pdo_seleqt("
   select
      u.fname,
      u.email,
      u.activation
   from
      wrc_users u
      left join enrollment_view e
         using(user_id)
      left join classes_aw c
         using(class_id)
   where
      u.activation is not null
      and c.start_dttm + interval 1 week - interval 30 minute < now()
      and c.start_dttm + interval 1 week + interval 30 minute > now()
", array());

logtxt('emailAfterFirstClass.php');
logtxt(print_r($qr, true));

foreach($qr as $row) {
   $msg =
      "Hello {$row['fname']},\n\n" .

      "This is a reminder to activate your My Progress Portal account. " .
      "Entering information in My Progress Portal and engaging with your " .
      "instructor through the portal is essential to your success in the " .
      "program.\n\n" . 

      "To activate My Progress Portal:\n" .
      "1. Go here: " . WEBSITE_URL . "/setpw.php?email=" .
            urlencode($row['email']) . "&key=" . $row['activation'] . "\n" .
      "2. Create a password; you will use this email address and the password " .
      "you create to login to My Progress Portal.\n" .
      "3. You can now login to My Progress Portal. Please make sure to " .
      "bookmark the site so you can access it easily in the future. You " .
      "can also find it through the My Progress Portal button on our website " .
      "home page, https://esmmpreventdiabetes.com. If you forget your " .
      "password, you can click on the link on the login page to have your " .
      "password emailed to you.\n\n" .

      "Please email administrator@esmmpreventdiabetes.com if you have issues " .
      "setting up your My Progress Portal account.\n\n" .

      "Thanks and we hope you enjoy the program!\n\n" .

      "Sincerely,\n" .
      "The Eat Smart, Move More, Prevent Diabetes Team";

   logtxt($msg);
   syncMail($row['email'], "Eat Smart, Move More, Prevent Diabetes - Activate your My Progress Portal", $msg);
}


?>

