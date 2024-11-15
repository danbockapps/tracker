<?php
session_start();
require_once("config.php");

process_mobile_desktop();
ob_start();
$registered = null;

function generate_page($require_logged_in, $require_logged_out, $shownav=true) {
   global $ini;
   template_start($require_logged_in, $require_logged_out);
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
         href="wrc.css?a=1114a"
      />
      <link
         rel="stylesheet"
         href="universal.css"
      />

      <?php if(PRODUCT == 'esmmwl') { ?>
         <link
            rel="stylesheet"
            href="dashboard.css?a=1114"
         />
      <?php } ?>

      <?php if(PRODUCT == 'dpp') { ?>
         <link
            rel="stylesheet"
            href="purple.css"
         />
         <link
            rel="stylesheet"
            href="portal.css?a=1114"
         />
         <link
            href="https://fonts.googleapis.com/css?family=Lato:300,400,400i,700,700i"
            rel="stylesheet"
         />
      <?php } ?>

      <?php if(PRODUCT == 'esmmwl2') { ?>
         <link
            rel="stylesheet"
            href="teal.css?a=1114"
         />
      <?php } ?>
      
      <?php template_js(); ?>
   </head>
   <body>
      <div id="container" class="loginPage">
         <div class="headerDiv">
            <?php
            template_logo_gc();
            
            ?>
            <hr id="navend" />
         </div><!--end headerDiv-->

         <?php
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
                           ?>">Messages</a></li>
                           <li><a target="_blank" href="printable_export.php?user=<?php
                              echo $_SESSION['user_id'];
                           ?>">Print report</a></li>
                           <?php
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
               <a href="https://<?php
                  echo $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?mode=mobile";
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
