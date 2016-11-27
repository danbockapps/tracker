<?php
/*
PLEASE NOTE: Throughout this file and the attendance table in the database, the
word "week" is a misnomer. Attendance is tracked by lesson, which may or may
not correspond 1:1 to weeks. TODO: change varibles and fields named "week"
here and in the database to "lesson".
*/

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
   order by date_entered
", array($_GET['class_id'], $_GET['class_source']));

$iqr = array();

// Create indexed array
// If there are multiple entries for the same user and week, the earlier ones
// will be overwritten in this loop by the latest, which is exactly what we want.
foreach($aqr as $row) {
   $iqr[$row['user_id']][$row['week']] = $row['present'];
}

generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }

   ?><script>

   var iqr = <?php global $iqr; echo json_encode($iqr, JSON_NUMERIC_CHECK); ?>;

   function shirtRequirementsMet(userId) {
      <?php if(PRODUCT == 'dpp') { ?>
         // Return true if participant attended 9 of the first 16
         var outOf16 = 0;
         for(var i=1; i<=16; i++) {
            if(!iqr[userId][i]) {
               // Change nonexistent to zero
               iqr[userId][i] = 0;
            }
            outOf16 += iqr[userId][i];
         }

         return outOf16 >= 9;


      <?php } else if(PRODUCT == 'esmmwl') { ?>
         // Return true if participant attended all of the first 15
         var outOf15 = 0;
         for(var i=1; i<=15; i++) {
            if(!iqr[userId][i]) {
               // Change nonexistent to zero
               iqr[userId][i] = 0;
            }
            outOf15 += iqr[userId][i];
         }

         return outOf15 >= 15;


      <?php } ?>
   }

   function submitAttendance(userId, week, present) {
      var cellId = '#td_' + userId + '_' + week;
      $(cellId + ' > img').removeClass('hidden');
      $(cellId + ' > a').addClass('hidden');
      $.post('attendance_ajax.php', {
         user_id: userId,
         class_id: <?php echo htmlentities($_GET['class_id']); ?>,
         class_source: '<?php echo htmlentities($_GET['class_source']); ?>',
         week: week,
         present: present
      }, function(data) {
         $(cellId + ' > img').addClass('hidden');

         if(data === 'OK') {
            var classToShow = present ? '.greenCheck' : '.blackBox';
            $(cellId).children(classToShow).removeClass('hidden');

            // Change attendanceSum cell
            var sumCell = $(cellId).siblings('.attendanceSum');
            var delta = present ? 1 : -1;
            sumCell.fadeOut('fast', function() {
               $(this).html(parseInt(sumCell.html(), 10) + delta).fadeIn('slow');

               // Update client-side array
               if(!iqr[userId][week]) {
                  // Change nonexistent to zero
                  iqr[userId][week] = 0;
               }
               iqr[userId][week] += delta;

               // Show or hide shirt dropdown
               if(shirtRequirementsMet(userId)) {
                  $(this).siblings('.participantName').children('.shirtChoice').removeClass('hidden');
               }
               else {
                  $(this).siblings('.participantName').children('.shirtChoice').addClass('hidden');
               }
            });
         }
         else {
            var classToShow = present ? '.blackBox' : '.greenCheck';
            $(cellId).children(classToShow).removeClass('hidden');
            alert('An error occurred.');
         }
      });
   }

   </script>

   <?php

   $qr = pdo_seleqt("
      select
         e.user_id,
         u.fname,
         u.lname,
         coalesce(a.numclasses, 0) as numclasses
      from
         " . ENR_VIEW . " e
         natural join wrc_users u
         natural left join attendance_sum a
      where
         e.class_id = ?
         and e.class_source = ?
      order by
         lname,
         fname
   ", array($_GET['class_id'], $_GET['class_source']));

   $cqr = seleqt_one_record("
      select
         c.class_id,
         c.start_dttm,
         u.fname,
         u.lname
      from
         classes_aw c
         left join wrc_users u
            on c.instructor_id = u.user_id
      where
         c.class_id = ?
         and c.class_source = ?
   ", array($_GET['class_id'], $_GET['class_source']));

   if(PRODUCT == 'dpp') {
      $numLessons = 24;
   }
   else {
      $numLessons = 15;
   }

   ?><h2>Attendance Entry</h2>

   <table>
      <tr>
         <td>
            <strong>Instructor:</strong>
         </td>
         <td>
            <?php echo htmlentities($cqr['fname'] . ' ' . $cqr['lname']); ?>
         </td>
      </tr>
      <tr>
         <td>
            <strong>Class time:</strong>
         </td>
         <td>
            <?php echo class_times($cqr['start_dttm']); ?>
         </td>
      </tr>
   </table>

   <table id="attendanceEntry">
      <thead>
      <tr>
         <th class="participantName">
            Participant Name
         </th>
         <th class="attendanceSum">
         </th>
         <?php
            for($i=1; $i<=$numLessons; $i++) {
               ?><th class="checkboxCell<?php
                  if($i >= 17) echo ' phase2';
               ?>"><?php
                  echo $i;
               ?></th><?php
            }
         ?>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach($qr as $row) {
         ?><tr user-id="<?php
            echo htmlentities($row['user_id']);
         ?>" id="userRow<?php
            echo htmlentities($row['user_id']);
         ?>"><td class="participantName"><a href="reports.php?user=<?php
            echo htmlentities($row['user_id']);
         ?>"><?php
            echo htmlentities($row['fname'] . ' ' . $row['lname']);
         ?></a><div class="shirtChoice">Shirt choice: <select>
               <option value=""></option>
               <?php
                  global $ini;
                  foreach($ini['shirtColors'] as $shirtColor) {
                     foreach($ini['shirtSizes'] as $shirtSize) {
                        $shirtChoice = $shirtColor . ' ' . $shirtSize;
                        ?><option value="<?php
                           echo $shirtChoice;
                        ?>"><?php
                           echo $shirtChoice;
                        ?></option><?php
                     }
                  }
               ?>
            </select>
            <img src="spinner.gif" style="height:13px" class="spinner hidden" />
            <i class="material-icons checkmark" style="font-size:small">&#xE86C;</i>
            </div>
         </td>
         <td class="attendanceSum"><?php
            echo htmlentities($row['numclasses']);
         ?></td><?php
            for($j=1; $j<=$numLessons; $j++) {
               ?><td class="checkboxCell<?php
                  if($j >= 17) echo ' phase2';
               ?>" id="td_<?php
                  echo htmlentities($row['user_id']) . '_' . $j;
               ?>">
                  <!-- Black empty box -->
                  <a
                     href="javascript:submitAttendance(<?php
                        echo htmlentities($row['user_id']) . ',' . $j . ',1';
                     ?>)"
                     class="blackBox<?php
                        echo htmlentities(hideClass($row['user_id'], $j, 0));
                     ?>"
                  >
                     <i class="material-icons">&#xE3C1;</i>
                  </a>

                  <img src="spinner.gif" class="hidden" />

                  <!-- Green check -->
                  <a
                     href="javascript:submitAttendance(<?php
                        echo htmlentities($row['user_id']) . ',' . $j . ',0';
                     ?>)"
                     class="greenCheck<?php
                        echo htmlentities(hideClass($row['user_id'], $j, 1));
                     ?>"
                  >
                     <i class="material-icons">&#xE86C;</i>
                  </a>
               </td><?php
            }
         ?></tr><script>
            var userIdThisRow = <?php echo htmlentities($row['user_id']); ?>;
            var shirtChoiceDiv = $('#userRow' + userIdThisRow + ' .shirtChoice');

            //Make sure user is in iqr
            if(!iqr[userIdThisRow]) {
               iqr[userIdThisRow] = [];
            }

            if(shirtRequirementsMet(userIdThisRow)) {
               shirtChoiceDiv.removeClass('hidden');
            }
            else {
               shirtChoiceDiv.addClass('hidden');
            }
         </script><?php
      }

      ?>
      </tbody>
   </table>

   <script>
      $('#attendanceEntry tr:odd').addClass('alt');

      $('.shirtChoice select').each(function() {
         if($(this).val() === '') {
            // hide checkmark
            $(this).siblings('.checkmark').addClass('hidden');
         }
      });

      $('.shirtChoice select').change(function() {
         var thisSelect = $(this);
         thisSelect.siblings('.spinner').removeClass('hidden');
         thisSelect.siblings('.checkmark').addClass('hidden');
         $.post('shirt.php', {
            user_id: thisSelect.closest('tr').attr("user-id"),
            class_id: <?php echo $_GET['class_id']; ?>,
            shirt_choice: thisSelect.val()
         }, function(data) {
            thisSelect.siblings('.spinner').addClass('hidden');
            if(data === 'OK') {
               thisSelect.siblings('.checkmark').removeClass('hidden');
            }
            else {
               alert('A database error occurred while selecting a t-shirt.');
            }
         });
      });
   </script>

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

function shirtRequirementsMet($userId) {
   global $iqr;
   if(PRODUCT == 'dpp') {
      if(array_sum(array_slice($iqr[$userId], 0, 16)) >= 9) {
         // Participant has attended at least 9 of the first 16 lessons
         return true;
      }
      else {
         return false;
      } 
   }
   else if(PRODUCT == 'esmmwl') {
      if(array_sum($iqr[$userId]) >= 15) {
         return true;
      }
      else {
         return false;
      }
   }
}

?>

