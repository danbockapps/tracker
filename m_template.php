<?php
session_start();

if(isset($_SESSION['user_id']) && !isset($_GET['user'])) {
   $_GET['user'] = $_SESSION['user_id'];
}

require_once("config.php");
process_mobile_desktop();
ob_start();
$registered = null;

function generate_page($require_logged_in, $require_logged_out, $shownav=true) {
   template_start($require_logged_in, $require_logged_out);

   // Need empty array - blog links will not be printed for mobile users.
   global $blogqr;
   $blogqr = array();
?>

<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>ESMMWL Weekly Tracker</title>
      <link rel="stylesheet" href="m_wrc.css" />
      <?php template_js(); ?>
   </head>

   <body>
      <?php template_logo_gc(); ?>

      <?php if(isset($_SESSION['user_id'])) { ?>
         <ul id="nav">
            <li><a href="m_reports.php">Weekly reports</a></li>
            <li><a href="m_all_messages.php">Messages</a></li>
         </ul>
      <?php } ?>

      <hr id="navend" /><?php
         page_content();
      ?>
      <div id="footer">
         <a href="<?php
            echo file_and_parameters() .
            (strpos(file_and_parameters(), "?") === false ? "?" : "&") .
            "mode=desktop";
         ?>">Switch to desktop site</a>
      </div>
   </body>
</html>
<?php
}
?>
