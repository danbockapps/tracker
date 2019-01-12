<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   if(isset($_POST['formsubmitted'])) {
      foreach(json_decode($_POST['recips']) as $recip) {
         message_participant($recip, $_POST['message_text']);
      }
      ?>Return to <a href="rosters.php">Rosters</a><?php
   }

   else if(!empty($_GET)) {
      $toqr = pdo_seleqt("
         select
            fname,
            lname
         from wrc_users
         where user_id in (" . join(",", array_keys($_GET['mm'])) . ")
         order by
            lname,
            fname
      ", null);

      ?>
      <script type="text/javascript" src="functions.js"></script>
      <form action="multi_message.php" method="post">
         <fieldset>
            <legend>Message to multiple participants</legend>
            <?php

            $to_string = "";
            foreach($toqr as $row) {
               $to_string .= $row['fname'] . " " . $row['lname'] . ", ";
            }
            /* Remove the last comma */
            $to_string = substr($to_string, 0, strlen($to_string) - 2);
            ?>To: <b><?php
            echo $to_string;
            ?></b><br />

            <textarea name="message_text" rows="4" cols="50"
               onkeydown="limit(this.form.message_text, 99999);"
               onkeyup="limit(this.form.message_text, 99999);"
            ></textarea><br />
            <input type="hidden" name="recips" value="<?php
               echo json_encode(array_keys($_GET['mm']));
            ?>" />
            <input type="hidden" name="formsubmitted" value="true" />
            <input type="submit" value="Send message" />

         </fieldset>
      </form>
      <?php
   }

   else {
      echo err_text("<p>You did not select any participants.</p>");
      ?><a href="rosters.php">Rosters</a><?php
   }
}

?>
