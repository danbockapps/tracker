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
      loginLog($_POST['email']);
      $error = array(); //this array will store all error messages

      if (empty($_POST['email'])) {
         loginLog('Email is empty.');
         $error[] = 'You forgot to enter your email.';
      }
      else if (!is_email_address($_POST['email'])) {
         loginLog('Email address is invalid.');
         $error[] = 'Your email address is invalid.';
      }
      else {
         loginLog('Email is set.');
      	$email = $_POST['email'];
      }

      if (empty($_POST['password'])) {
         loginLog('Password is empty.');
         $error[] = 'Please enter your password ';
      } else {
         loginLog('Password is set.');
         $password = $_POST['password'];
      }

      if (!email_already_in_db($_POST['email'])) {
         loginLog('Email is not in database.');
      	$error[] = noCurrentClassText();
      }

      if (empty($error)) { //if the array is empty, it means no error found
         loginLog('No errors found so far. Authenticating...');
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
            loginLog('Account not activated.');
            $error[] = "Your account has not been activated. Please " .
                     "follow the instructions sent to your e-mail address or " .
                     "<a href=\"reset.php\">click here</a> to reset your account.";
         }

         else {
            loginLog('Account is activated.');
            // Account is activated. Check password.
            $salt = substr($result['password'], 7, 21);
            $in_hashd_passwd = crypt($password, BLOWFISH_PRE . $salt . BLOWFISH_SUF);

            if($result['password'] === $in_hashd_passwd) {
               loginLog('Password is correct. Logging in...');
               // Login successful
               $_SESSION['user_id'] = $result['user_id'];
               $_SESSION['envt'] = ENVIRONMENT;

               if(isset($_GET['request_uri'])) {
                  loginLog('Redirecting to ' . $_GET['request_uri'] . '.');
                  header("Location: " . $_GET['request_uri']);
                  exit();
               }
               else {
                  loginLog('Redirecting to my_home_page.');
                  header("Location: " . my_home_page());
                  exit();
               }
            }

            else {
               loginLog('Password is incorrect.');
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

   global $loginphp_mode;
   ?>

   <form action="<?php echo $loginphp_mode; ?>login.php?<?php echo http_build_query($_GET); ?>" method="post">
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
<?php
}

function loginLog($s) {
   logtxt('LOGIN ATTEMPT: ' . $s);
}
?>
