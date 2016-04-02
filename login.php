<?php
if(!isset($loginphp_mode)) {
   $loginphp_mode = "";
}
require_once($loginphp_mode . "template.php");
generate_page(false, true);

function page_content() {
   if(isset($_SESSION['user_id'])) {
      header("Location: index.php");
   }

   if (isset($_POST['formsubmitted'])) {
      $error = array(); //this array will store all error messages

      if (empty($_POST['email'])) {
         $error[] = 'You forgot to enter your email.';
      }
      else if (!is_email_address($_POST['email'])) {
         $error[] = 'Your email address is invalid.';
      }
      else {
      	$email = $_POST['email'];
      }

      if (empty($_POST['password'])) {
         $error[] = 'Please enter your password ';
      } else {
         $password = $_POST['password'];
      }

      if (!email_already_in_db($_POST['email'])) {
      	$error[] = "There is no registered account with that e-mail address.";
      }

      if (empty($error)) { //if the array is empty, it means no error found
         $result = seleqt_one_record("
            select
               user_id,
               password,
               activation
            from wrc_users
            where
               email = ?
         ", array($email));

         if($result['activation'] != null) {
            $error[] = "Your account has not been activated. Please " .
                     "follow the instructions sent to your e-mail address or " .
                     "<a href=\"reset.php\">click here</a> to reset your account.";
         }

         else {
            // Account is activated. Check password.
            $salt = substr($result['password'], 7, 21);
            $in_hashd_passwd = crypt($password, BLOWFISH_PRE . $salt . BLOWFISH_SUF);

            if($result['password'] === $in_hashd_passwd) {
               // Login successful
               $_SESSION['user_id'] = $result['user_id'];
               $_SESSION['envt'] = ENVIRONMENT;
               header("Location: " . my_home_page());
            }

            else {
               $msg_error = "Incorrect password. If you have forgotten your " .
                  "password, <a href=\"reset.php\">click here</a> to reset it.";
            }
         }
      }

      foreach ($error as $values) {
      	echo '<li class="error">'.$values.'</li>';
      }

      if(isset($msg_error)) {
      	echo '<li class="error">'.$msg_error.' </li>';
      }
   } // End of the main Submit conditional.

   ?>

   <form action="login.php" method="post">
      <fieldset>
         <legend>Login</legend>

         <p>Enter your email address and password below.</p>

         <div>
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" size="25"  <?php
               if(isset($_POST['formsubmitted']) && isset($msg_error)) {
                  echo "value=\"" . htmlspecialchars($_POST['email']) . "\"";
               }
         ?>/>
         </div>

         <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" size="25" />
         </div>

         <div>
            <input type="hidden" name="formsubmitted" value="TRUE" />
            <input type="submit" value="Login" />
         </div>
      </fieldset>
   </form>
   <p>
      <a href="reset.php">Forgot your password?</a>
   </p>
<?php } ?>
