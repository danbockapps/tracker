<?php
require_once("template.php");

$aqr = pdo_seleqt("
   select
      user_id,
      week,
      present
   from wrc_attendance
   where
      class_id = ?
      and class_source = ?
", array($_GET['class_id'], $_GET['class_source']));

$iqr = array();

// create indexed array
foreach($aqr as $row) {
   $iqr[$row['user_id']][$row['week']] = $row['present'];
}

generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }
   
   ?><script>
   
   function submitAttendance(userId, week, present) {
      var cellId = '#td_' + userId + '_' + week;
      $(cellId + ' > img').removeClass('hidden');
      $(cellId + ' > a').addClass('hidden');
      $.post('attendance_ajax.php', {
         userId: userId,
         week: week,
         present: present
      }, function(data) {
         $(cellId + ' > img').addClass('hidden');

         if(data === 'OK') {
            var classToShow = present ? '.greenCheck' : '.blackBox';
            $(cellId).children(classToShow).removeClass('hidden');
         }
         else {
            alert('An error occurred.');
         }
      });
   }
   
   </script>
   
   <pre><?php
   global $iqr;
   print_r($iqr);
   ?></pre><?php

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
      where
         e.class_id = ?
         and e.class_source = ?
   ", array($_GET['class_id'], $_GET['class_source']));
   
   ?><h2>Attendance Entry</h2>
   
   <form method="POST" action="attendance_entry.php?class_id=<?php 
      echo $_GET['class_id'];
      ?>&class_source=<?php
      echo $_GET['class_source'];
      ?>">
   
      <table id="attendanceEntry">
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
                  ?><td id="td_<?php echo $row['user_id'] . '_' . $j ?>">
                     <!-- Black empty box -->
                     <a 
                        href="javascript:submitAttendance(<?php
                           echo $row['user_id'] . ',' . $j . ',1';
                        ?>)"
                        class="blackBox<?php
                           echo hideClass($row['user_id'], $j, 0);
                        ?>"
                     >
                        <i class="material-icons">&#xE3C1;</i>
                     </a>
                     
                     <img src="spinner.gif" class="hidden" />

                     <!-- Green check -->
                     <a
                        href="javascript:submitAttendance(<?php
                           echo $row['user_id'] . ',' . $j . ',0';
                        ?>)"
                        class="greenCheck<?php
                           echo hideClass($row['user_id'], $j, 1);
                        ?>"
                     >
                        <i class="material-icons">&#xE86C;</i>      
                     </a>
                     
                  </td><?php
                  
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

function hideClass($userId, $week, $present) {
   global $iqr;
   if($iqr[$userId][$week] != $present) {
      return ' hidden';
   }
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
