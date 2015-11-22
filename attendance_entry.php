<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }

   
   if(isset($_POST['formsubmitted'])) {
      print_r($_POST);
   }
   
   $qr = pdo_seleqt("
      select
         e.user_id,
         u.fname,
         u.lname,
         c.weeks
      from
         wrc_enrollment e
         natural join wrc_users u
         natural join classes_aw c
      where e.class_id = ?
   ", $_GET['class_id']);
   
   ?><h2>Attendance Entry</h2>
   
   <form method="POST" action="attendance_entry.php?class_id=<?php echo $_GET['class_id']; ?>">
   
      <table>
         <tr>
            <th>
               Name
            </th>
            <?php
               for($i=1; $i<=$qr[0]['weeks']; $i++) {
                  ?><th><?php
                  echo $i;
                  ?></th><?php
               }
            ?>
         </tr>
         <?php
         foreach($qr as $row) {
            ?><tr><td><?php
               echo $row['fname'] . ' ' . $row['lname'];
            ?></td><?php
               for($j=1; $j<=$qr[0]['weeks']; $j++) {
                  ?><td><input type="checkbox" name="x[<?php
                     echo $row['user_id'];
                  ?>][<?php
                     echo $j;
                  ?>]" /></td><?php
               }
            ?></tr><?php
         }
         
         ?>
      </table>
      <input type="hidden" name="formsubmitted" value="true" />
      <input type="submit" value="Save" />
      
      
   </form>
   
   <?php
   

         
         
}
   function admin_user() {
      if(am_i_admin() && isset($_GET['instr'])) {
         return "?instr=" . $_GET['instr'];
      }
      else {
         return "";
      }
   }

?>
