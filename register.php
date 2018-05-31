<?php
require_once("template.php");
generate_page(false, true);

function page_content() {
   $success = false; // Registration not successful... yet.

   foreach($_POST as $key => $post_item) {
      $_POST[$key] = trim($_POST[$key]);
   }

   if (isset($_POST['formsubmitted'])) {
      $error = array(); //Declare An Array to store any error message

      global $ini;

      $data_for_recaptcha = array(
         "secret"   => $ini['recaptcha_secret'],
         "response" => $_POST['g-recaptcha-response'],
         "remoteip" => $_SERVER['REMOTE_ADDR']
      );

      $recaptcha_response = httpPost(
         'https://www.google.com/recaptcha/api/siteverify',
         $data_for_recaptcha
      );

      if(!$recaptcha_response->success) {
         $error[] = "Recaptcha error. Please check the Recaptcha box.";
      }

      if (empty($_POST['first_name'])) { //if no name has been supplied
         $error[] = 'Please enter a first name.'; //add to array "error"
      } else {
         $name = $_POST['first_name']; //else assign it a variable
      }

      if (empty($_POST['last_name'])) { //if no name has been supplied
         $error[] = 'Please enter a last name.'; //add to array "error"
      } else {
         $name = $_POST['last_name']; //else assign it a variable
      }

      if (empty($_POST['email'])) {
         $error[] = 'Please enter your e-mail address.';
      }
      else if (!is_email_address($_POST['email'])) {
         $error[] = 'Please enter a valid e-mail address. ';
      }
      else if (email_already_in_db($_POST['email'])) {
         $error[] = 'There is already a registered user with that e-mail'
            . ' address. <a href="reset.php">Forgot your password?</a>';
      }
      else {
         $email = $_POST['email'];
      }

      if (empty($_POST['password'])) {
         $error[] = 'Please enter your password. ';
      }
      else if(empty($_POST['password2'])) {
         $error[] = 'Please enter your password twice. ';
      }
      else if(strlen($_POST['password']) < MIN_PW_LEN) {
         $error[] = "Password must be at least " . MIN_PW_LEN . " characters. ";
      }
      else if($_POST['password'] !== $_POST['password2']) {
         $error[] = "Please enter the same password twice. ";
      }
      else {
         $password = $_POST['password'];
      }

      if (empty($error)) { //send to Database if there's no error
         // Create a unique  activation code:
         $activation = md5(uniqid(rand(), true));

         if(pdo_insert_user(
            pwhash($_POST['password']),
            $activation,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            1,
            0,
            0,
            0
         )) {
            // Send the email:
            sendById(get_user_id($_POST['email']), 4);

            // Finish the page:
            echo cnf_text('
               Thank you for registering! A confirmation email has been sent to '
               . htmlentities($email) . '. Please follow the instructions in
               the e-mail
               to activate your account. <b>Please check your "spam" or ' .
               '"junk" folder</b> if you do not see the message in your inbox.
            ');
            $success = true;
         }
         else {
            echo err_text("There was a database error creating your account.");
         }

      }
      else { //If the "error" array contains error msg , display them
         foreach ($error as $key => $values) {
            echo err_text($values);
         }
      }
   } // End of the main Submit conditional.

   if(!$success) { // If registration is not yet successful, show form.

   ?>
   <h2>Create new account</h2>
   <form method="post" action="register.php" autocomplete="off">
      <p>
         <label for="first_name">First name: </label>
         <input type="text" name="first_name" id="first_name" <?php
         	if(isset($_POST['formsubmitted'])) {
               echo "value=\"" . htmlentities($_POST['first_name']) . "\"";
            }
         ?>/>
      </p>
      <p>
         <label for="last_name">Last name: </label>
         <input type="text" name="last_name" id="last_name" <?php
            if(isset($_POST['formsubmitted'])) {
               echo "value=\"" . htmlentities($_POST['last_name']) . "\"";
            }
         ?>/>
      </p>
      <p>
         <label for="email">E-mail address: </label>
         <input type="text" name="email" id="email" <?php
            if(isset($_POST['formsubmitted'])) {
               echo "value=\"" . htmlentities($_POST['email']) . "\"";
            }
         ?>/>
      </p>
      <p>
         <label for="password">Choose a password (at least 8 characters): </label>
         <input type="password" name="password" id="password" />
      </p>
      <p>
         <label for="password2">Re-enter password: </label>
         <input type="password" name="password2" id="password2" />
      </p>
      <p>
         <div class="g-recaptcha" data-sitekey="6LcTa1wUAAAAAOV6yJTBHGL-Du7Z_m0ELJyHAntE">
         </div>
      </p>
      <p>
         <input type="hidden" name="formsubmitted" value="TRUE" />
         <input type="submit" value="Submit" />
      </p>
   </form>
   <?php
   }
}

function httpPost($url, $data) {
   $curl = curl_init($url);
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   $response = curl_exec($curl);
   curl_close($curl);
   return json_decode($response);
}
?>
