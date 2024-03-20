<?php
require_once("template.php");
require_once('emailTemplates.php');
generate_page(false, true);

function page_content() {
   if(empty($_GET) && empty($_POST)) {
      // 1. Show user an email input and button to click to send reset email.
      offer_to_reset();
   }
   else if(isset($_POST['reqsubmitted'])) {
      logtxt('Password reset request submitted: ' . $_POST['email']);

      // 2. Check for email address in db. If found, send reset email.
      send_reset_email();
   }
   else if(isset($_GET['key']) && isset($_GET['email'])) {
      // 3. Check key and show user password and password confirmation inputs.
      enter_new_password();
   }
   else if(isset($_POST['newpwsubmitted'])) {
      // 4. Hash password and update db.
      change_password();
   }
   else {
      exit("Unknown error.");
   }
}

function offer_to_reset() {
   ?>
   <p>
      If you have forgotten your password, enter your e-mail address below and
      instructions to reset it will be e-mailed to you.
   </p>
   <form action="reset.php" method="post" class="white-form">
   <p>
      <label for="email">E-mail address: </label>
      <input type="text" name="email" id="email" />
   </p>
   <p>
      <input type="hidden" name="reqsubmitted" value="TRUE" />
      <input type="submit" value="Submit" />
   </p>
   </form>

   <?php
}

function send_reset_email() {
   if(!email_already_in_db($_POST['email'])) {
      logtxt('Email not found: ' . $_POST['email']);
      exit("E-mail address not found.");
   }

   syncMailPostmark(
      $_POST['email'],
      PRODUCT_TITLE . ' - Password Reset',
      getResetPasswordText($_POST['email'], generate_email_reset($_POST['email'])),
      'Password reset'
   );

   echo cnf_text("<p>Password-changing instructions have been sent to your " .
         "email address. <b>Please check your \"spam\" or \"junk\" folder" .
         "</b> if you do not see the message in your inbox.</p>");
}

function enter_new_password() {
   // Decode email address. One urldecode is enough on some servers;
   // two are required on others.
   if(!is_email_address($_GET['email'])) {
      $_GET['email'] = urldecode($_GET['email']);
      if(!is_email_address($_GET['email'])) {
         $_GET['email'] = urldecode($_GET['email']);
      }
   }
   $qr = seleqt_one_record("
      select email_reset
      from wrc_users
      where email = ?
   ", array($_GET['email']));
   if($qr['email_reset'] !== $_GET['key']) {
      exit(
         "<p>Your key is invalid or out of date. Please follow the link in the " .
         "most recent password reset email you received. To have a new " .
         "password reset email sent, <a href=\"reset.php\">click here</a>.</p>"
      );
   }
   
   printPasswordInstructions();
   ?>
   <form action="reset.php" method="post" class="white-form">
      <p>
         <label for="password">Choose a password: </label>
         <input type="password" name="password" id="password" />
      </p>
      <p>
         <label for="password2">Re-enter password: </label>
         <input type="password" name="password2" id="password2" />
      </p>
      <p>
         <input type="hidden" name="newpwsubmitted" value="TRUE" />
         <input type="hidden" name="email" value="<?php
            echo htmlentities($_GET['email']);
         ?>" />
         <input type="hidden" name="key" value="<?php
            echo htmlentities($_GET['key']);
         ?>" />
         <input type="submit" />
      </p>
   </form>
   <?php
}

function change_password() {
   global $ini;

   $error = getPasswordErrors($_POST['password'], $_POST['password2']);

   if(!empty($error)) {
      foreach($error as $key => $values) {
         echo err_text($values);
      }
      exit();
   }
   
   if(!email_already_in_db($_POST['email'])) {
      exit("<p>E-mail address was not found.</p>");
   }
   $qr = seleqt_one_record("
      select email_reset
      from wrc_users
      where email = ?
   ", array($_POST['email']));
   if($qr['email_reset'] !== $_POST['key']) {
      exit("<p>Your key is invalid or out of date. Please try again.</p>");
   }

   // If we haven't exited yet, then change password.
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_users
      set
         password = ?,
         activation = null,
         email_reset = null
      where email = ?
   ");
   $sth->execute(array(password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['email']));

   echo "<p>Your password has been reset. You may now " .
      "<a href=\"login.php\">log in</a></p>";
}

?>
