<?php
CRYPT_BLOWFISH or die ('No Blowfish found.');
define("BLOWFISH_PRE", "$2y$05$");
define("BLOWFISH_SUF", "$");

$ini = parse_ini_file('auth.ini');

define("EMAIL_FROM", $ini['email_from']);
define("EMAIL_LOGGER", $ini['email_logger']);
define("EMAIL_PASSWORD", $ini['email_password']);
define("MIN_PW_LEN", $ini['min_pw_len']);
define('ENVIRONMENT', $ini['environment']);
define('PRODUCT', $ini['product']);
define('DATABASE_NAME', $ini['database_name']);
define('WEBSITE_URL', $ini['website_url']);
define('ENR_TBL', $ini['enrollment_table']);
define('ADMIN_EMAIL', $ini['admin_email']);

validate_product();

// added this so the old natural joins work
define('ENR_VIEW', $ini['enrollment_view']);

if(PRODUCT == 'dpp') {
   define('PROGRAM_NAME', 'Eat Smart, Move More, Prevent Diabetes');
   define('PRODUCT_TITLE', 'My Progress Portal');
}
else if(PRODUCT == 'esmmwl') {
   define('PROGRAM_NAME', 'Eat Smart, Move More, Weigh Less');
   define('PRODUCT_TITLE', 'Weekly Tracker');
}
else if(PRODUCT == 'esmmwl2') {
   define('PROGRAM_NAME', 'Eat Smart, Move More, Weigh Less 2');
   define('PRODUCT_TITLE', 'Weekly Tracker 2');
}

if (get_magic_quotes_gpc() === 1) {
   // Strip slashes on ESMMWL server
   foreach($_POST as $key => $post_item) {
      if(!is_array($_POST[$key])) {
         $_POST[$key] = stripslashes($_POST[$key]);
      }
   }
}

function is_email_address($email) {
   return preg_match(
      "/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@" .
      "([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",
      $email
   );
}

function pdo_connect($db_user) {
   global $ini;
   $password = $ini[$db_user . '_password'];

   try {
      $dbh = new PDO(
         "mysql:host=" . $ini['db_host'] . ";dbname=" . DATABASE_NAME,
         $db_user,
         $password
      );
      $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   }
   catch(PDOException $e) {
      echo $e->getMessage();
   }

   return $dbh;
}

function pdo_seleqt($query, $qs) {
   if(!is_array($qs)) {
      $qs = array($qs);
   }
   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_select");
   $sth = $dbh->prepare($query);
   $sth->setFetchMode(PDO::FETCH_ASSOC);
   $sth->execute($qs);
   return $sth->fetchAll();
}

function email_already_in_db($email, $include_noreg=true) {
   if(!$include_noreg) {
      $noreg_clause = " and password != 'TRACKER_NO_REG'";
   }
   else {
      $noreg_clause = "";
   }
   $email_row = pdo_seleqt("
      select count(*) as count
      from wrc_users
      where email = ?
   " . $noreg_clause, array($email));
   return $email_row[0]['count'];
}

function pdo_insert_user(
   $password,
   $activation,
   $fname,
   $lname,
   $email,
   $participant,
   $instructor,
   $administrator,
   $research
) {
   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_insert");
   $data = array(
      "user_id" => next_user_id(),
      "password" => $password,
      "activation" => $activation,
      "fname" => $fname,
      "lname" => $lname,
      "email" => $email,
      "participant" => $participant,
      "instructor" => $instructor,
      "administrator" => $administrator,
      "research" => $research
   );
   $sth = $dbh->prepare("
      insert into wrc_users (
         user_id,
         password,
         activation,
         fname,
         lname,
         email,
         participant,
         instructor,
         administrator,
         research,
         date_added
      )
      values (
         :user_id,
         :password,
         :activation,
         :fname,
         :lname,
         :email,
         :participant,
         :instructor,
         :administrator,
         :research,
         now()
      )
   ");
	return $sth->execute($data);
}

function pdo_nullify_activation($email) {
   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_users
      set activation = null
      where email = ?
   ");
   $sth->execute(array($email));
}

function change_email($old_email, $new_email) {
   if(!is_email_address($new_email) || email_already_in_db($new_email))
      return false;

   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_users
      set email = ?
      where email = ?
      limit 1
   ");
   $sth->execute(array($new_email, $old_email));
   return true;
}

function sendmail($to, $subject, $body) {
   // All mail sent by the app should go through this function or the next.
   exec("php-cli message.php '$to' '$subject' '$body' > /dev/null &");
}

function sendById($recipientId, $messageId) {
   if(!is_numeric($recipientId)) {
      exit("Error: recipient ID is not numeric.");
   }
   if(!is_numeric($messageId)) {
      exit("Error: message ID is not numeric.");
   }

   $executable = "php-cli messageById.php $recipientId $messageId > /dev/null &";
   exec($executable);
}

function full_name($user_id) {
   $result = pdo_seleqt("
      select
         fname,
         lname
      from wrc_users
      where user_id = ?
   ", array($user_id));
   return htmlentities($result[0]['fname'] . " " . $result[0]['lname']);
}

function seleqt_one_record($query, $qs) {
   $qr = pdo_seleqt($query, $qs);
   if(count($qr) == 1) {
      return $qr[0];
   }
   else {
      throw new Exception("Unexpected records returned.");
   }
}

function am_i_admin() {
   if(!isset($_SESSION['user_id'])) {
      return false;
   }
   else {
      $qr = seleqt_one_record("
         select administrator
         from wrc_users
         where user_id = ?
      ", array($_SESSION['user_id']));
      return $qr['administrator'];
   }
}

function am_i_instructor($instr_id = null) {
   if($instr_id == null) {
      // Is the current user an instructor?
      if(!isset($_SESSION['user_id'])) {
         // If you're not logged in, you're not an instructor.
         return false;
      }
      else {
         $instr_id = $_SESSION['user_id'];
      }
   }
   $qr = seleqt_one_record("
      select instructor
      from wrc_users
      where user_id = ?
   ", array($instr_id));
   return $qr['instructor'];
}

function pwhash($password) {
   $allowed_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
   $salt = "";

   for($i=0; $i<21; $i++) { // 21 is standard salt length
      $salt .= $allowed_chars[mt_rand(0,strlen($allowed_chars)-1)];
   }
   return crypt($password, BLOWFISH_PRE . $salt . BLOWFISH_SUF);
}

function err_text($text) {
   return '<div class="error">Error: ' . $text . "</div>";
}

function cnf_text($text) {
   return '<div class="confirmation">' . $text . "</div>";
}

function current_class_and_sg($close_tags="") {
   if(!isset($_GET['user'])) {
      exit(err_text("No user specified.") . $close_tags);
   }
   $qr = pdo_seleqt("
      select
         class_id,
         class_source,
         start_dttm,
         smart_goal,
         instructor_id
      from
         " . ENR_VIEW . "
         natural join current_classes
      where
         user_id = ? and

         /* most recent class for this participant */
         start_dttm in (
            select max(start_dttm)
            from
               " . ENR_VIEW . "
               natural join current_classes
            where user_id = ?
         )
   ", array($_GET['user'], $_GET['user']));
   if(count($qr) > 1) {
      throw new Exception("Unexpected records returned.");
   }
   if(count($qr) == 0) {
      return array();
   }
   return $qr[0];
}

function print_smart_goal($qr, $change=false) {
   ?>
   <h2>
      SMART goal
      <?php
         sg_popup();
      ?>
   </h2>

   <?php
   sg_text($qr, $change);
   return $qr;
}

function sg_popup() {
   popup(
      "How&nbsp;to&nbsp;set&nbsp;a&nbsp;SMART&nbsp;goal",
      "SMART Goals are <b>S</b>pecific, <b>M</b>easurable, " .
      "<b>A</b>ttainable, <b>R</b>ealistic, and <b>T</b>imely. Your " .
      "SMART goal should say specifically what you " .
      "are going to achieve. You should be able to measure whether " .
      "you have achieved it. Things you can measure include miles " .
      "walked or run, pants sizes, pounds, etc. Your SMART goal " .
      "should be something attainable that you can realistically " .
      "accomplish. Losing more than two pounds a week or more than 10 " .
      "percent of your body-weight over the course of the program is " .
      "neither realistic nor attainable. Lastly, you should know when " .
      "you plan to achieve your SMART goal. \"By the end of the " .
      PROGRAM_NAME . " program\" is a timely marker. " .
      "Setting a goal for a specific event or date is timely as well; " .
      "for example, maybe you want to be able to hike 10 miles with " .
      "your kids during your family vacation in April.",
      "How to set a SMART goal"
   );
}

function sg_text($qr, $change) {
   ?>
   <p>
   <?php

   if(!isset($qr['smart_goal']) || trim($qr['smart_goal']) == "") {
      ?><span class="nodata">Your SMART goal is not set.</span><?php
   }
   else {
      echo nl2br(htmlentities($qr['smart_goal'])); // nl2br = newline to <br />
   }

   if($change) {
      ?>
      <span class='small'>
         <a href='smartgoal.php?user=<?php echo htmlentities($_GET['user']); ?>'>
            change
         </a>
      </span>
      <?php
   }
   ?>
   </p>
   <?php
}

function wrcdate($date_string) {
   return date("l, F j, Y", strtotime($date_string));
}

function wrcdttm($dttm_string) {
   return date("l, F j, Y \a\\t g:i a", strtotime($dttm_string));
}

function access_restrict($qr, $close_tags="") {
   if(!isset($qr['instructor_id'])) {
      $qr['instructor_id'] = null;
   }
   if(
      $_SESSION['user_id'] != $_GET['user'] &&
      $_SESSION['user_id'] != $qr['instructor_id'] &&
      !am_i_admin()
   ) {
      exit(err_text("You are not allowed to view this page.") . $close_tags);
   }
}

function class_times($start_string) {
   $d = strtotime($start_string);
   return date('l\s\ \a\t\ g:i\ A\ \s\t\a\r\t\i\n\g\ M\ j\,\ Y', $d);
}

function mobile_page() {
   return strpos($_SERVER['REQUEST_URI'], "m_") !== false;
}

function popup($link_text, $detail_text, $title_text = "untitled") {
   if(mobile_page()) {
      ?>
      <a class="popup" href="javascript:alert('<?php
         echo $title_text . "\\n\\n" . $detail_text;
      ?>');">
         <?php echo $link_text; ?>
      </a>
      <?php
   }
   else {
      global $div_id;
      // Pop-ups would look slightly better if they were in divs rather than
      // spans, but they would have to be in a different part of the page
      // to make that work.
      //
      // Update: this has been worked around in CSS.
      ?>
      <span id="<?php echo ++$div_id; ?>" title="<?php echo $title_text; ?>">
         <?php echo nl2br($detail_text); ?>
      </span>
      <script>
         $( "#<?php echo $div_id; ?>" ).dialog({ autoOpen: false, width: 500 });
      </script>
      <a
         class="popup"
         href="javascript:$( '#<?php echo $div_id; ?>' ).dialog( 'open' );void(0);"
      >
         <?php echo $link_text; ?>
      </a>
      <?php
   }
}

function zero_blank($var) {
   return (is_numeric($var) && $var == 0 ? "" : $var);
}

function my_home_page() {
   if(!isset($_SESSION['user_id'])) {
      return "login.php";
   }
   else if(am_i_admin()) {
      return "admin.php";
   }
   else if(am_i_instructor()) {
      return "rosters.php?instr=" . $_SESSION['user_id'];
   }
   else {
      return "reports.php?user=" . $_SESSION['user_id'];
   }
}

function participant_nav($class_id, $class_source) {
   if(am_i_instructor() || am_i_admin()) {
      ?>
      <div id="partnav">
         You are viewing: <b><?php echo full_name($_GET['user']); ?></b>
         (<a href="mailto:<?php
            echo get_email_address($_GET['user']);
         ?>"><?php
            echo get_email_address($_GET['user']);
         ?></a>)
         <a href="reports.php?user=<?php
            echo htmlentities($_GET['user']);
         ?>">reports</a>
         <a href="all_messages.php?user=<?php
            echo htmlentities($_GET['user']);
         ?>">messages</a>
         <br />
         <?php
            $sqr = seleqt_one_record("
               select smart_goal
               from " . ENR_VIEW . "
               where
                  class_id = ?
                  and class_source = ?
                  and user_id = ?
            ", array($class_id, $class_source, $_GET['user']));
            if($sqr['smart_goal'] == null) {
               ?><i>No SMART goal</i><?php
            }
            else {
               ?>
               &quot;<?php echo htmlentities($sqr['smart_goal']); ?>&quot;
               <?php
            }
            ?>
      </div>
      <?php
   }
}

function message_participant($recip_id, $msg_text) {
   if(strlen($msg_text) > 99999) {
      exit(err_text("Your message is too long."));
   }

   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_insert");
   $sth = $dbh->prepare("
      insert into wrc_messages (user_id, recip_id, message, create_dttm)
      values (?, ?, ?, now())
   ");
   if($sth->execute(array($_SESSION['user_id'], $recip_id, $msg_text))) {
      // Message inserted into database.
      sendById($recip_id, 2);

      $eqr = seleqt_one_record("
         select
            fname,
            lname
         from wrc_users
         where user_id = ?
      ", array($recip_id));
      echo cnf_text(
         "Message sent to " .
         htmlentities($eqr['fname'] . " " . $eqr['lname']) .
         "."
      );

      // Update wrc_users table, last_message_from and last_message_to fields
      global $ini;
      $dbhf = pdo_connect($ini['db_prefix'] . "_update");
      $sthf = $dbhf->prepare("
         update wrc_users
         set last_message_from = now()
         where user_id = ?
      ");
      $sthf->execute(array($_SESSION['user_id']));

      $dbht = pdo_connect($ini['db_prefix'] . "_update");
      $stht = $dbht->prepare("
         update wrc_users
         set last_message_to = now()
         where user_id = ?
      ");
      $stht->execute(array($recip_id));
   }
   else {
      exit(err_text("Your message was not sent. A database error occurred."));
   }
}

function generate_email_reset($email) {
   $eqr = seleqt_one_record("
      select email_reset
      from wrc_users
      where email = ?
   ", array($email));

   if($eqr['email_reset'] != null) {
      return $eqr['email_reset'];
   }
   else {
      $email_reset_key = md5(uniqid(rand(), true));
      global $ini;
      $dbh = pdo_connect($ini['db_prefix'] . "_update");
      $sth = $dbh->prepare("
            update wrc_users
            set email_reset = ?
            where email = ?
         ");
      $sth->execute(array($email_reset_key, $email));
      return $email_reset_key;
   }
}


function rstr_date($date_string) {
   if(strtotime($date_string) < strtotime("2012-10-01 12:00:00")) {
      return "none";
   }
   else {
      return date("D, n/j/Y g:i a", strtotime($date_string));
   }
}

function relative_time($time = false, $limit = 31536000, $format = 'Y-m-d') {
   if (empty($time) || (!is_string($time) && !is_numeric($time))) {
      return null;
   }
   elseif (is_string($time)) $time = strtotime($time);

   $now = time();
   $relative = '';

   if ($time === $now) $relative = 'now';
   elseif ($time > $now) $relative = 'in the future';
   else {
      $diff = $now - $time;

      if ($diff >= $limit) $relative = date($format, $time);
      elseif ($diff < 60) {
         $relative = '<1 min ago';
      } elseif (($minutes = round($diff/60)) < 60) {
         $relative = $minutes.' min'.(((int)$minutes === 1) ? '' : 's').' ago';
      } elseif (($hours = round($diff/(60*60))) < 24) {
         $relative = $hours.' hour'.(((int)$hours === 1) ? '' : 's').' ago';
      } else {
         $days = round($diff/(60*60*24));
         $relative = $days . " day".(((int)$days === 1) ? "" : "s")." ago";
      }
   }
   return $relative;
}

function template_start($require_logged_in, $require_logged_out) {
   if(isset($_SESSION['user_id']) && $_SESSION['envt'] != ENVIRONMENT) {
      // You logged into test but now you're in prod, or vice versa.
      header("Location: logout.php");
   }
   if($require_logged_in && !isset($_SESSION['user_id'])) {
      // You need to be logged in, but you're not.
      header("Location: login.php");
      exit();
   }
   else if($require_logged_out & isset($_SESSION['user_id'])) {
      // You need to be logged out. Consider it done.
      session_start();
      session_destroy();
   }
}

function template_js() {
   global $ini;
   ?>
   <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js">
   </script>
   <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/jquery-ui.min.js">
   </script>
   <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.3.1/jquery.cookie.min.js">
   </script>
   <script type="text/javascript" src="jquery.showhide.pack.js">
   </script>
   <script type="text/javascript">
      $(function() {
         $('.showhide_closed').each(function(i) {
            var cookieName = $(this).attr('cookie-name');
            if(!cookieName) {
               cookieName = $(this).text();
            }
            $(this).showhide({
               default_open: false,
               use_cookie: true,
               cookie_name: cookieName
            });
         });
      });
   </script>
   <script type="text/javascript">
      if (window.XMLHttpRequest) { // code for normal browsers
         x=new XMLHttpRequest();
      }
      else { // code for IE6, IE5
         x=new ActiveXObject("Microsoft.XMLHTTP");
      }
      x.onreadystatechange=function() {};
      x.open(
         "GET",
         "pv_log.php?w=" + screen.width + "&h=" + screen.height +
            "&r=<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>",
         true
      );
      x.send();
   </script>

   <?php if(ENVIRONMENT == "prod" && $ini['product'] == "esmmwl") { ?>
      <script type="text/javascript">
         var _gaq = _gaq || [];
         _gaq.push(['_setAccount', 'UA-37552350-1']);
         _gaq.push(['_trackPageview']);

         (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
         })();
      </script>
   <?php } ?>


   <?php
}

function template_logo_gc() {
   global $blogqr;
   global $am_i_instructor;
   global $ini;
   $am_i_instructor = am_i_instructor();
   ?>
   <a href="<?php echo my_home_page(); ?>">
      <img
         id="logo"
         src="<?php echo $ini['logo_url']; ?>"
      />
   </a>

   <div id="blogwidget">
      <?php if ($blogqr) { ?>
      <div id="blogtitle">
         Recent posts from the <br />
         <a
            style="color:white"
            href="https://www.esmmweighless.com/blog"
            target="_blank"
         >
            Eat Smart, Move More, Weigh Less Blog
         </a>
      </div>
      <div id="bloglinks">
         <?php
         foreach($blogqr as $row) {
            echo "<p>";
            echo "<a href='" . $row['guid'] . "' target='_blank'>";

            // Hack to replace special characters in the Wordpress database
            // See http://www.snipe.net/2008/12/fixing-curly-quotes-and-em-dashes-in-php/
            // Use the ord() function to discover characters' ASCII codes.
            // (150 is some kind of dash)
            echo str_replace(
               array(chr(150), chr(133)),
               array("-",      "..."),
               $row['post_title']
            );

            echo "</a><br />";
            echo "</p>";
         }
         ?>
      </div>
      <?php } ?>
   </div>

   <div id="whoami">
      <h1><?php echo PRODUCT_TITLE; ?></h1>
      <?php
         if(isset($_SESSION['user_id'])) {
            ?>
            <p id="loggedinas">
               Logged in as <?php
                  echo full_name($_SESSION['user_id']);
               ?>.
               <a href="logout.php">Log out</a>
            </p>
            <?php
            if(!$am_i_instructor) {
               global $gcqr;
               $gcqr = pdo_seleqt("
                  select
                     c.start_dttm,
                     c.instructor_id,
                     case
                        when u.lname is null then ' (No instructor)'
                        else concat(u.fname, ' ', u.lname)
                     end as instr_name
                  from
                     " . ENR_VIEW . " e
                     natural join current_classes c
                     left join wrc_users u
                        on c.instructor_id = u.user_id
                  where
                     e.user_id = ? and

                     /* most recent class for this participant */
                     c.start_dttm in (
                        select max(start_dttm)
                        from
                           " . ENR_VIEW . "
                           natural join current_classes
                        where user_id = ?
                     )
               ", array($_SESSION['user_id'], $_SESSION['user_id']));

               global $registered;
               if(count($gcqr) > 1) {
                  throw new Exception("A database error with class reg.");
               }
               else if(count($gcqr) == 1) {
                  $registered = true;
                  ?>
                  <p id="yourclass">
                     Your class:
                     <?php echo class_times($gcqr[0]['start_dttm']); ?>
                     with
                     <?php echo $gcqr[0]['instr_name']; ?>
                  </p>
                  <?php
               }
               else {
                  $registered = false;
               }
            }
         }
         else {
            echo "<p>Not logged in.</p>";
         }
      ?>
   </div>
   <?php
}

function linkify($date_string, $week_no, $warn) {
   ?>
   <a href='report.php?week=<?php echo $week_no; ?>&user=<?php
      echo htmlentities($_GET['user']); ?>'<?php
         if($warn) {
            ?> onclick="return oldreport_confirm();"<?php
         }
      ?>><?php echo $date_string; ?></a>
   <?php
}

function file_and_parameters() {
   // htmlspecialchars was used here. You can't use htmlspecialchars on a URL!
   return substr(
      $_SERVER['REQUEST_URI'],
      strrpos($_SERVER['REQUEST_URI'], "/") + 1
   );
}

function remove_mode() {
   if(strpos(file_and_parameters(), "mode") !== false) {
      return substr(
         file_and_parameters(),
         0,
         strpos(file_and_parameters(), "mode") - 1
      );
   }
   else {
      return file_and_parameters();
   }
}

function process_mobile_desktop() {
   process_requested_mode();
   if(isset($_SESSION['mode'])) {
      if($_SESSION['mode'] == "mobile") {
         $mode_to_return = "mobile";
      }
      else if($_SESSION['mode'] == "desktop") {
         $mode_to_return = "desktop";
      }
      else {
         throw new Exception("Unexpected mode value: " . $_SESSION['mode']);
      }
   }
   else {
      if(mobile_browser_in_use()) {
         $mode_to_return = "mobile";
      }
      else {
         $mode_to_return = "desktop";
      }
   }
   if(substr(file_and_parameters(), 0, 2) == "m_" && $mode_to_return == "desktop") {
      header("Location: " . substr(remove_mode(), 2));
   }
   else if (
      substr(file_and_parameters(), 0, 2) != "m_" &&
      $mode_to_return == "mobile" &&
      file_exists(mobilize_path($_SERVER['SCRIPT_FILENAME']))
   ) {
      header("Location: m_" . remove_mode());
   }
}

function process_requested_mode() {
   if(isset($_GET['mode'])) {
      $_SESSION['mode'] = $_GET['mode'];
   }
}

function mobile_browser_in_use() {
   $useragent=$_SERVER['HTTP_USER_AGENT'];
   if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
      return true;
   }
   else {
      return false;
   }
}

function mobilize_path($path) {
   $last_slash_pos = strrpos($path, "/");
   $returnable =
      substr($path, 0, $last_slash_pos + 1) .
      "m_" .
      substr($path, $last_slash_pos + 1);
   return $returnable;
}

function get_user_id($email_address) {
   $qr = seleqt_one_record("
      select user_id
      from wrc_users
      where email = ?
   ", array($email_address));
   return $qr['user_id'];
}

function get_email_address($user_id) {
   $qr = seleqt_one_record("
      select email
      from wrc_users
      where user_id = ?
   ", $user_id);
   return $qr['email'];
}

function create_enrollment_record(
   $npid,
   $class_id,
   $class_source,
   $voucher_code,
   $referrer,
   $subscriber_id,
   $member_number
) {
   if(PRODUCT == 'dpp') {
      throw new Exception('Unsupported action for this product.');
   }

   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_insert");
   $sth = $dbh->prepare("
      insert into " . ENR_TBL . " (
         class_id,
         tracker_user_id,
         voucher_code,
         class_source,
         referrer,
         subscriber_id,
         member_number
      )
      values (?, ?, ?, ?, ?, ?, ?)
   ");
   try {
      $sth->execute(array(
         $class_id,
         $npid,
         $voucher_code,
         $class_source,
         $referrer,
         $subscriber_id,
         $member_number
      ));
      return true;
   }
   catch(PDOException $e) {
      return false;
   }
}

function class_valid($class_id, $class_source) {
   $qr = seleqt_one_record("
      select count(*) as count
      from classes_aw
      where
         class_id = ?
         and class_source = ?
   ", array($class_id, $class_source));
   return $qr['count'] == 1;
}

function remove_participant_from_class(
   $email,   /* String: e-mail address of the participant to remove from the class */
   $class_id /* Integer: ID of the class from which to remove the participant */
) {
   $result = rmpart($class_id, get_user_id($email), "a");
   if(strpos($result, "Error") === false) {
      return true;
   }
   else {
      return $result;
   }
}

function rmpart($class_id, $user_id, $class_source) {
   if(PRODUCT == 'dpp') {
      throw new Exception('Unsupported action for this product.');
   }
   else if(class_valid($class_id, $class_source) && $user_id > 0) {
      global $ini;
      $dbh0 = pdo_connect($ini['db_prefix'] . "_delete");
      $dbh1 = pdo_connect($ini['db_prefix'] . "_delete");
      $sth0 = $dbh0->prepare("
         delete from wrc_reports
         where
            class_id = ?
            and user_id = ?
            and class_source = ?
      ");
      $sth1 = $dbh1->prepare("
         delete from " . ENR_TBL . "
         where
            class_id = ?
            and tracker_user_id = ?
            and class_source = ?
      ");
      $q0 = array($class_id, $user_id, $class_source);
      $q1 = array($class_id, $user_id, $class_source);
      if($sth0->execute($q0) && $sth1->execute($q1)) {
         return cnf_text("Participant removed.");
      }
      else {
         return err_text("A database error occurred.");
      }
   }
   else {
      return err_text("Invalid class or participant.");
   }
}

function require_get_vars($getvars) {
   if(!is_array($getvars)) {
      $getvars = array($getvars);
   }
   foreach($getvars as $getvar) {
      if(!isset($_GET[$getvar])) {
         exit("Error: invalid GET variables.");
      }
   }
}

function array_to_csv($qr) {
   $csv = '';

   /* Header row */
   foreach($qr[0] as $key => $value) {
      $csv .= "\"" . str_replace("\"", "", $key) . "\",";
   }

   /* Header row: remove last comma and add newline */
   $csv = substr($csv, 0, -1) . "\n";

   /* Data rows */
   foreach($qr as $row) {
      foreach($row as $value) {
         $csv .= "\"" . str_replace("\"", "", $value) . "\",";
      }
      /* Remove last comma and add newline */
      $csv = substr($csv, 0, -1) . "\n";
   }

   return $csv;
}

function logtxt($string) {
  global $ini;
  file_put_contents(
    $ini['logfile'],
    date("Y-m-d G:i:s") . " " . $_SERVER['REMOTE_ADDR'] . " " .
        $_SESSION['user_id'] . " " . $string . "\n",
    FILE_APPEND
  );
}

// Is user either the instructor of the class or an admin?
function can_access_class($class_id, $class_source) {
   if(am_i_admin()) {
      return true;
   }

   $qr = seleqt_one_record("
      select instructor_id
      from classes_aw
      where
         class_id = ?
         and class_source = ?
   ", array($class_id, $class_source));

   if($qr['instructor_id'] == $_SESSION['user_id']) {
      return true;
   }
   else {
      return false;
   }
}

function validate_product() {
   // Exit if product in auth.ini is not a valid product (esmmwl or dpp).
   // (only happens if the developer has screwed something up)
   global $ini;
   $valid_products = ['esmmwl', 'dpp', 'esmmwl2'];
   $product_is_valid = false;

   foreach($valid_products as $s) {
      if($s == PRODUCT) {
         $product_is_valid = true;
      }
   }

   if(!$product_is_valid) {
      exit('Invalid value for product in auth.ini: ' . PRODUCT);
   }
}

function getNumericOnly($getIndex) {
   $getVar = $_GET[$getIndex];

   if(is_numeric($getVar)) {
      return $getVar;
   }
   else {
      exit('Invalid parameter in URL.');
   }
}

function next_user_id() {
   $qr = seleqt_one_record("
      select greatest(wrc_max_user_id, registrants_max_user_id) + 1 as next_user_id
      from (
         select max(user_id) as wrc_max_user_id
         from wrc_users
      ) one
      cross join (
         select max(user_id) as registrants_max_user_id
         from " . ENR_TBL . "
      ) two
   ", null);

   return $qr['next_user_id'];
}

function currentPhaseForClass($class_id, $class_source) {
   $qr = seleqt_one_record('
      select phase1_end
      from classes_aw
      where
         class_id = ?
         and class_source = ?
   ', array($class_id, $class_source));

   if(strtotime($qr['phase1_end']) > time()) {
      return "Phase 1";
   }
   else {
      return "Phase 2";
   }
}

function goalWeight($userId, $classId, $classSource) {
   if(PRODUCT == 'dpp') {
      $gwqr = pdo_seleqt('
         select weight
         from first_reports_with_weights
         where
            user_id = ?
            and class_id = ?
            and class_source = ?
      ', array($userId, $classId, $classSource));

      if(count($gwqr) == 1) {
         return $gwqr[0]['weight'] * .95;
      }
   }
}

function goalWeightCard($userId, $classId, $classSource) {
   /*
   An MPP-only feature. If PRODUCT is not set to 'dpp', this does nothing.
   */

   if(PRODUCT == 'dpp') {
      $goalWeight = goalWeight($userId, $classId, $classSource);

      if($goalWeight) {
         ?>

         <div id="goalweight">
            <div style="font-size: 1.17em">
               Your goal weight at the end of
               <?php echo currentPhaseForClass($classId, $classSource); ?>
               is
               <span style="font-weight: bold">
                  <?php echo round($goalWeight, 1); ?>
               </span>
               pounds.<br />
            </div>
            <div style="font-style: italic; margin-top: 0.5em">
               A 5% weight loss decreases your risk of diabetes.
            </div>
         </div>

         <?php
      } // end if goalWeight
   } // end if product == mpp
}

?>
