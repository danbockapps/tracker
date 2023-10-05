<?php
require_once("template.php");

// Decode email address
if(!is_email_address($_GET['email'])) {
   $_GET['email'] = urldecode($_GET['email']);
   if(!is_email_address($_GET['email'])) {
      $_GET['email'] = urldecode($_GET['email']);
   }
}

generate_page(false, false);

function page_content() {
   global $ini;
   if(
      !isset($_GET['email']) ||
      !isset($_GET['key']) ||
      !email_already_in_db($_GET['email']) ||
      !is_email_address($_GET['email']) ||
      strlen($_GET['key']) != 32
   ) {
      echo err_text("<p>There is a problem with the URL. Please follow the link sent
            to your email address.</p>");
   }

   else if(isset($_POST['formsubmitted'])) {
      $back = " <p>Please click your browser's back button and try again.</p>";

      $error = getPasswordErrors($_POST['password'], $_POST['password2']);

      if(!empty($error)) {
         foreach($error as $key => $values) {
            echo err_text($values . $back);
         }
         exit();
      }

      else {
         // Send to database
         $dbh = pdo_connect($ini['db_prefix'] . "_update");
         $sth = $dbh->prepare("
            update wrc_users
            set
               password = ?,
               activation = null
            where
               email = ?
               and activation = ?
         ");
         if($sth->execute(array(
            pwhash($_POST['password']),
            $_GET['email'],
            $_GET['key']
         ))) {
            echo cnf_text("<p>Your account has been created. You may now
                  <a href='login.php'>log in</a></p>");
         }
         else {
            echo err_text("<p>A database error occurred.</p>");
         }
      }
   }

   else {
      // Form not submitted yet
      $qr = seleqt_one_record("
         select
            fname,
            lname,
            password,
            activation
         from wrc_users
         where email = ?
      ", array($_GET['email']));


      if($qr['activation'] == null) {
         // Account is already activated.
         header("Location: " . my_home_page());
      }
      else if($qr['activation'] != $_GET['key']) {
         echo err_text("<p>There is a problem with your key. Please follow the link sent
               to your email address.</p>");
      }

      else {
         ?>
         <form action="setpw.php?<?php
            echo http_build_query($_GET);
         ?>" method="post" class="white-form">
            <h2>Choose your password</h2>
            <table>
               <tr>
                  <td>
                     <b>Name: </b>
                  </td>
                  <td>
                     <?php echo htmlentities($qr['fname'] . " " . $qr['lname']); ?>
                  </td>
               </tr>
               <tr>
                  <td>
                     <b>E-mail address: </b>
                  </td>
                  <td>
                     <?php echo htmlentities($_GET['email']); ?>
                  </td>
               </tr>
               <tr>
                  <td>
                     <?php printPasswordInstructions(); ?>
                     <b>Choose a password (at least 8 characters):</b>
                  </td>
                  <td>
                     <input type="password" name="password" id="password" />
                  </td>
               </tr>
               <tr>
                  <td>
                     <b>Re-enter password:</b>
                  </td>
                  <td>
                     <input type="password" name="password2" id="password2" />
                  </td>
               </tr>
               <tr>
                  <td colspan="2" align="center">
                     <input type="hidden" name="formsubmitted" value="TRUE" />
                     <input type="submit" value="Submit" />
                  </td>
            </table>
         </form>
         <?php
      }
   }
}

?>
