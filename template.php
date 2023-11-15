<?php
session_start();
require_once("config.php");

process_mobile_desktop();
ob_start();
$registered = null;

function generate_page($require_logged_in, $require_logged_out, $shownav=true) {
   global $ini;
   template_start($require_logged_in, $require_logged_out);
   if($ini['blogposts_table']) {
      global $blogqr;
      $blogqr = pdo_seleqt("
         select
            post_title,
            guid
         from " . $ini['blogposts_table'] . "
         where
            post_type='post'
            and post_status='publish'
         order by post_date desc
         limit 3
      ", array());
   }
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <title><?php echo PRODUCT_TITLE; ?></title>
      <link
         href="https://fonts.googleapis.com/icon?family=Material+Icons"
         rel="stylesheet"
      />
      <link
         rel="stylesheet"
         href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css"
      />
      <link
         rel="stylesheet"
         href="wrc.css?v=1"
      />
      <link
         rel="stylesheet"
         href="universal.css"
      />

      <?php if(PRODUCT == 'esmmwl') { ?>
         <link
            rel="stylesheet"
            href="dashboard.css"
         />
      <?php } ?>

      <?php if(PRODUCT == 'dpp') { ?>
         <link
            rel="stylesheet"
            href="purple.css"
         />
         <link
            rel="stylesheet"
            href="portal.css"
         />
         <link
            href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700,700i"
            rel="stylesheet"
         />
      <?php } ?>

      <?php if(PRODUCT == 'esmmwl2') { ?>
         <link
            rel="stylesheet"
            href="teal.css"
         />
      <?php } ?>
      
      <?php template_js(); ?>
   </head>
   <body>
      <div id="container" <?php containerClasses(); ?>>
         <div class="headerDiv">
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

                        <li><a href="attendance_class_list.php?instr=<?php
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
                  }
                  ?>
               </ul>
            </div>
            <?php
               // end if($shownav)
               }
            ?>
            <hr id="navend" />
         </div><!--end headerDiv-->
         <div class="contentDiv">
            <?php
               page_content();
               logtxt("template.php: served " . $_SERVER['SCRIPT_NAME'] . " ?" . $_SERVER['QUERY_STRING']);
            ?>
         </div><!--end contentDiv-->
      </div><!--end container-->

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

function containerClasses() {
   if(PRODUCT == 'dpp' && strpos($_SERVER['REQUEST_URI'], 'login.php') != false) {
      echo 'class = "loginPage"';
   }
}

?>
