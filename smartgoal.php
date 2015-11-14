<?php
if(!isset($smartgoalphp_mode)) {
   $smartgoalphp_mode = "";
}
require_once($smartgoalphp_mode . "template.php");
generate_page(true, false);

function page_content() {
   $qr = current_class_and_sg();
   access_restrict($qr);

   if(isset($_POST['submitted'])) {
      if(strlen($_POST['new_smart_goal']) > 99999) {
         // This should be pretty rare.
         exit("Your SMART goal is too long.");
      }

      $dbh = pdo_connect("esmmwl_update");
      $sth = $dbh->prepare("
         update wrc_enrollment
         set smart_goal = ?
         where
            user_id = ?
            and class_id = ?
            and class_source = ?
      ");
      if($sth->execute(array(
         $_POST['new_smart_goal'],
         $_GET['user'],
         $qr['class_id'],
         $qr['class_source']
      ))) {
         header("Location: reports.php?user=" . $_GET['user']);
      }
      else {
         echo err_text("A database error occurred.");
      }
   }

   global $smartgoalphp_mode;
   if($smartgoalphp_mode == "") {
      // User is loading smartgoal.php
      print_smart_goal($qr);
   }
   else {
      // User is loading m_smartgoal.php
      ?>
      <div id="smartgoal">
      <h2>SMART Goal</h2><?php
      sg_text($qr, false);
      ?></div>
      <?php
   }
   ?>
   <script type="text/javascript" src="functions.js"></script>
   <h2>
      <?php
         if(!isset($qr['smart_goal']) || trim($qr['smart_goal']) == "") {
            ?>Enter<?php
         }
         else {
            ?>Change<?php
         }
      ?>
      SMART Goal
   </h2>
   <?php
   if(empty($qr)) {
      ?><p>
      You must register for a class before you can set a SMART goal.
      </p><?php
   }
   else {
      ?>
      <form action="smartgoal.php?user=<?php echo $_GET['user']; ?>" method="post"
         onsubmit="return confirm('Are you sure you want to change your SMART goal?');">
         <textarea name="new_smart_goal" rows="4" style="width: 100%"
            onkeydown="limit(this.form.new_smart_goal, 99999);"
            onkeyup="limit(this.form.new_smart_goal, 99999);"
         ><?php
            echo $qr['smart_goal'];
         ?></textarea>
         <br />
         <input type="hidden" name="submitted" value="true" />
         <input type="submit" value="Submit" />
      </form>
      <?php
   }
}

?>
