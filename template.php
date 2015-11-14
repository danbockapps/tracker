<?php
session_start();
require_once("config.php");
process_mobile_desktop();
ob_start();
$registered = null;

function generate_page($require_logged_in, $require_logged_out, $shownav=true) {
   template_start($require_logged_in, $require_logged_out);
   global $blogqr;
   $blogqr = pdo_seleqt("
      select
         post_title,
         guid
      from esmmwl_wpnew.wp_posts
      where
         post_type='post'
         and post_status='publish'
      order by post_date desc
      limit 3
   ", array());
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <title>ESMMWL Weekly Tracker</title>
      <link
         rel="stylesheet"
         href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css"
      />
      <link
         rel="stylesheet"
         href="wrc.css"
      />
      <?php template_js(); ?>
      <?php if(ENVIRONMENT != "prod") { ?>
      <script type='text/javascript'>
         (function (d, t) {
            var bh = d.createElement(t), s = d.getElementsByTagName(t)[0];
            bh.type = 'text/javascript';
            bh.src = '//www.bugherd.com/sidebarv2.js?apikey=pu1w3t4arxzklg33syqjpw';
            s.parentNode.insertBefore(bh, s);
         })(document, 'script');
      </script>
      <?php } ?>

   </head>
   <body>
      <div id="container">
         <?php
         template_logo_gc();
         if($shownav) {
         ?>
         <div id="navbar">
            <ul>
               <?php
               if(isset($_SESSION['user_id'])) {
                  global $am_i_instructor;
                  if($am_i_instructor) {
                     ?>
                     <li><a href="rosters.php?instr=<?php
                        echo $_SESSION['user_id'];
                     ?>">Rosters</a></li>
                     <li><a href="attendance.php?instr=<?php
                        echo $_SESSION['user_id'];
                     ?>">Attendance</a></li>
                     <?php
                  }
                  else {
                     global $registered;
                     if($registered) {
                        ?>
                        <li><a href="reports.php?user=<?php
                           echo $_SESSION['user_id'];
                        ?>">Weekly reports</a></li>
                        <li><a href="all_messages.php?user=<?php
                           echo $_SESSION['user_id'];
                        ?>">Messages</a></li><?php
                     }
                  }
                  if(am_i_admin()) {
                     ?>
                     <li><a href="admin.php">Admin</a></li>
                     <?php
                  }
               }
               else {
                  // Not logged in.
               ?>
                  <li><a href="login.php">Login</a></li>
               <?php
               }
               ?>
            </ul>
         </div>
         <?php
            // end if($shownav)
            }
         ?>
         <hr id="navend" /><?php
            page_content();
         ?>
      </div>
      <?php
      if(file_exists(mobilize_path($_SERVER['SCRIPT_FILENAME']))) {
         ?>
         <div id="footer">
            <a href="<?php
               echo file_and_parameters() .
               (strpos(file_and_parameters(), "?") === false ? "?" : "&") .
               "mode=mobile";
            ?>">Switch to mobile site</a>
         </div>
         <?php
      }
      ?>
   </body>
</html>
<?php
}
?>
