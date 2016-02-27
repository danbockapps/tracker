<?php
if(!isset($all_messagesphp_mode)) {
   $all_messagesphp_mode = "";
}
require_once($all_messagesphp_mode . "template.php");
generate_page(true, false);

function page_content() {
   if(isset($_POST['formsubmitted'])) {
      message_participant($_POST['recip_id'], $_POST['message_text']);
   }

   $qr = current_class_and_sg();
   access_restrict($qr);
   participant_nav($qr['class_id'], $qr['class_source']);

   if(am_i_instructor($_GET['user'])) {
      exit(err_text("You cannot view all messages for an instructor."));
   }

   $iqr = pdo_seleqt("
      select
         user_id,
         recip_id,
         u_name,
         r_name,
         message,
         create_dttm,
         week_id,
         start_dttm,
         feedback
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
      $recip_id = $_GET['user'];
   }
   if($recip_id == null) {
      echo err_text("You cannot send messages because your class does not " .
            "have an instructor assigned. Please " .
            "e-mail us at <a href=\"mailto:Administrator@ESMMWeighLess.com\">" .
            "Administrator@ESMMWeighLess.com</a>.");
   }
   else {
      ?>
      <script type="text/javascript" src="functions.js"></script>
      <form action="all_messages.php?user=<?php echo $_GET['user']; ?>"
            method="post">
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
         <?php
            if($msg['feedback']) {
               ?>(instructor feedback for <a href="report.php?user=<?php
                  echo $msg['recip_id'];
               ?>&week=<?php
                  echo $msg['week_id'];
               ?>"><?php
                  echo wrcdate($msg['start_dttm'] . " + " .
                        ($msg['week_id'] - 1) . " weeks");
               ?> report</a>)<?php
            }
         ?>
         <br />

         <?php
            // If sender==recipient (e.g. a SMART goal change), don't show "To:"
            if($msg['recip_id'] != $msg['user_id']) {
               ?>
               To:      <?php echo htmlentities($msg['r_name']); ?><br />
               <?php
            }
         ?>

         Date:    <?php echo date("D, n/j/Y g:i a",
               strtotime($msg['create_dttm'])); ?>
      </div>
      <div class="message">
         <?php echo nl2br(htmlentities($msg['message'])); ?>
      </div>
      <?php
   }

   // Mark all as read. If I am instructor, mark only those messages
   // from $_GET['user'] as read.
   $dbh = pdo_connect("esmmwl_update");
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
