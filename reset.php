<?php
require_once("template.php");
generate_page(false, true);

function page_content() {
   if(empty($_GET) && empty($_POST)) {
      // 1. Show user an email input and button to click to send reset email.
      offer_to_reset();
   }
   else if(isset($_POST['reqsubmitted'])) {
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
   <form action="reset.php" method="post">
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
   if(!email_already_in_db($_POST['email'], false)) {
      exit("E-mail address not found.");
   }

   sendmail2(get_user_id($_POST['email']), 'RESET_PASSWORD');

   echo cnf_text("Password-changing instructions have been sent to your " .
         "email address. <b>Please check your \"spam\" or \"junk\" folder" .
         "</b> if you do not see the message in your inbox.");
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
         "Your key is invalid or out of date. Please follow the link in the " .
         "most recent password reset email you received. To have a new " .
         "password reset email sent, <a href=\"reset.php\">click here</a>."
      );
   }
   ?>
   <form action="reset.php" method="post">
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
   if($_POST['password'] !== $_POST['password2']) {
      exit("Your password entries did not match.");
   }
   if(strlen($_POST['password']) < MIN_PW_LEN) {
      exit("Password must be at least " . MIN_PW_LEN . " characters. ");
   }
   if(!email_already_in_db($_POST['email'])) {
      exit("E-mail address not found.");
   }
   $qr = seleqt_one_record("
      select email_reset
      from wrc_users
      where email = ?
   ", array($_POST['email']));
   if($qr['email_reset'] !== $_POST['key']) {
      exit("Your key is invalid or out of date. Please try again.");
   }

   // If we haven't exited yet, then change password.
   $dbh = pdo_connect("esmmwl_update");
   $sth = $dbh->prepare("
      update wrc_users
      set
         password = ?,
         activation = null,
         email_reset = null
      where email = ?
   ");
   $sth->execute(array(pwhash($_POST['password']), $_POST['email']));

   echo "Your password has been reset. You may now " .
      "<a href=\"login.php\">log in</a>";
}

?>
