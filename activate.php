<?php
require_once("config.php");

function correct_key($email, $key) {
   $result = pdo_seleqt("
      select activation
      from wrc_users
      where email = ?
   ", array($email));
   if($result[0]['activation'] === $key || $result[0]['activation'] == null) {
      return true;
   }
   else {
      return false;
   }
}

// Decode email address
if(!is_email_address($_GET['email'])) {
   $_GET['email'] = urldecode($_GET['email']);
   if(!is_email_address($_GET['email'])) {
      $_GET['email'] = urldecode($_GET['email']);
   }
}

if (
   !isset($_GET['email']) ||
   !isset($_GET['key']) ||
   !email_already_in_db($_GET['email']) ||
   !is_email_address($_GET['email']) ||
   strlen($_GET['key']) != 32
) {
   echo err_text("There is a problem with the URL. Please follow the link sent
      to your email address.");
}
else if (!correct_key($_GET['email'], $_GET['key'])) {
   echo err_text("The key in the URL is incorrect. Please follow the link sent
      to your email address.");
}
else {
   pdo_nullify_activation($_GET['email']);
   echo cnf_text('Your account is now active. You may now
         <a href="login.php">log in</a>');
}
?>
