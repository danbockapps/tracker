<?php
if(!isset($all_messagesphp_mode)) {
   $all_messagesphp_mode = "";
}
require_once($all_messagesphp_mode . "template.php");
require_once("reportComponent.php");
generate_page(true, false);

function page_content() {
   global $ini;

   if(isset($_POST['formsubmitted'])) {
      message_participant(
         $_POST['recip_id'],
         $_POST['message_text'],
         $_GET['user']
      );
   }

   $qr = current_class_and_sg();
   access_restrict($qr);
   participant_nav($qr['class_id'], $qr['class_source']);

   if(am_i_instructor($_GET['user'])) {
      exit(err_text("<p>You cannot view all messages for an instructor.</p>"));
   }

   $iqr = pdo_seleqt("
      select
         user_id,
         recip_id,
         u_name,
         r_name,
         message,
         create_dttm
      from msgfdbk
      where
         user_id = ? or
         recip_id = ?
   ", array($_GET['user'], $_GET['user']));

   if($_GET['user'] == $_SESSION['user_id']) {
      // user is the participant
      global $gcqr;
      $recip_name = $gcqr[0]['instr_name'];
      $recip_id = $gcqr[0]['instructor_id'];
   }
   else {
      // user is the instructor or an admin
      $recip_name = full_name($_GET['user']);
      $recip_id = htmlentities($_GET['user']);

      $lrwww = seleqt_one_record("
         select week_id
         from last_reports_with_weights_weeks
         where class_id = ? and class_source = ? and user_id = ?
      ", array($qr['class_id'], $qr['class_source'], $_GET['user']));

      global $report_date;
      $report_date = $qr['start_dttm'] . " + " . ($lrwww['week_id'] - 1) . " weeks";

      reportComponent(array(
         "classId" => $qr['class_id'],
         "classSource" => $qr['class_source'],
         "userId" => $_GET['user'],
         "week" => $lrwww['week_id']
      ));
   }

   if($recip_id == null) {
      echo err_text("<p>You cannot send messages because your class does not " .
            "have an instructor assigned. Please " .
            "e-mail us at <a href=\"" . ADMIN_EMAIL . "\">" . ADMIN_EMAIL .
            "</a>.</p>");
   }
   else {
      ?>
      <script type="text/javascript" src="functions.js"></script>
      <form action="all_messages.php?user=<?php
               echo htmlentities($_GET['user']);
            ?>"
            method="post"
            class="white-form clear-left"
            style="clear: left;"><!-- TODO delete this after 7/12/2024 -->
         <fieldset>
            <legend>Compose new message</legend>
            To: <b><?php echo htmlentities($recip_name); ?></b><br />
            <textarea name="message_text" rows="4" style="width: 100%"
               onkeydown="limit(this.form.message_text, 99999);"
               onkeyup="limit(this.form.message_text, 99999);"
            ></textarea><br />
            <input type="hidden" name="recip_id" value="<?php echo $recip_id; ?>" />
            <input type="hidden" name="formsubmitted" value="true" />
            <input type="submit" value="Send message" />
         </fieldset>
      </form>
      <?php
   }

   foreach($iqr as $msg) {
      ?>
      <div class="<?php
         if($msg['recip_id'] == $msg['user_id']) {
            echo "sgmsg";
         }
         else if($msg['recip_id'] == $_GET['user']) {
            echo "fdbk";
         }
         else {
            echo "msg";
         }
      ?>_header">
         From: <b><?php echo htmlentities($msg['u_name']); ?></b>
         <br />

         <?php
            // If sender==recipient (e.g. a SMART goal change), don't show "To:"
            if($msg['recip_id'] != $msg['user_id']) {
               ?>
               To: <?php echo htmlentities($msg['r_name']); ?><br />
               <?php
            }
         ?>

         Date: <?php echo date("D, n/j/Y g:i a", strtotime($msg['create_dttm'])); ?>
      </div>
      <div class="fade">
         <?php echo nl2br(htmlentities($msg['message'])); ?>
      </div>
      <?php
   }

   ?>
   <script>
      $('.message').linkify({target: '_blank'});
   </script>
   <?php

   // Mark all as read. If I am instructor, mark only those messages
   // from $_GET['user'] as read.
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_messages
      set mread = true
      where recip_id = ?
   " . ($_SESSION['user_id'] == $_GET['user'] ? "" : "and user_id = ?"));
   if($_SESSION['user_id'] == $_GET['user']) {
      $sth->execute(array($_SESSION['user_id']));
   }
   else {
      $sth->execute(array($_SESSION['user_id'], $_GET['user']));
   }

}

?>
