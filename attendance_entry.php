<?php
/*
PLEASE NOTE: Throughout this file and the attendance table in the database, the
word "week" is a misnomer. Attendance is tracked by lesson, which may or may
not correspond 1:1 to weeks. TODO: change varibles and fields named "week"
here and in the database to "lesson".
*/

require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }

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

   $qr = pdo_seleqt("
      select
         e.user_id,
         u.fname,
         u.lname,
         coalesce(a.numclasses, 0) as numclasses,
         e.shirtchoice
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


   $iqr = array();

   // Create indexed array
   // First, create an empty row for each participant
   foreach($qr as $row) {
      $iqr[$row['user_id']] = array();
   }

   // Next, populate the array.
   // If there are multiple entries for the same user and week, the earlier ones
   // will be overwritten in this loop by the latest, which is exactly what we want.
   foreach($aqr as $row) {
      $iqr[$row['user_id']][$row['week']] = $row['present'];
   }

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
               ?><th class="checkboxCell"><?php
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
         ?>"><td class="participantName"><a href="reports.php?user=<?php
            echo htmlentities($row['user_id']);
         ?>"><?php
            echo htmlentities($row['fname'] . ' ' . $row['lname']);
         ?></a><div class="shirtChoice hidden">Shirt choice: <select>
               <option value=""></option>
               <?php
                  global $ini;
                  foreach($ini['shirtColors'] as $shirtColor) {
                     foreach($ini['shirtSizes'] as $shirtSize) {
                        $shirtChoice = $shirtColor . ' ' . $shirtSize;
                        ?><option <?php
                           if($shirtChoice == $row['shirtchoice']) echo 'selected ';
                        ?>value="<?php
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
               ?>" lesson-id="<?php
                  echo $j;
               ?>">
                  <!-- Black empty box -->
                  <i class="material-icons hidden entryPoint blackBox">&#xE3C1;</i>

                  <img src="spinner.gif" class="hidden" />

                  <!-- Green check -->
                  <i class="material-icons hidden entryPoint greenCheck">&#xE86C;</i>
               </td><?php
            }
         ?></tr><?php
      }

      ?>
      </tbody>
   </table>

   <script>
      var iqr = <?php echo json_encode($iqr, JSON_NUMERIC_CHECK); ?>;

      $('#attendanceEntry tr:odd').addClass('alt');

      $('.checkboxCell').each(function() {
         if($(this).attr('lesson-id') >= 17) {
            $(this).addClass('phase2');
         }
      });

      $('.entryPoint').each(function() {
         var userId = $(this).closest('tr').attr('user-id');
         var lessonId = $(this).closest('td').attr('lesson-id');
         if(
            $(this).hasClass('greenCheck') && iqr[userId][lessonId]
            ||
            $(this).hasClass('blackBox') && !iqr[userId][lessonId]
         ) {
            $(this).removeClass('hidden');
         }
      });

      $('.shirtChoice').each(function() {
         var userIdThisRow = $(this).closest('tr').attr('user-id');

         if(shirtRequirementsMet(userIdThisRow)) {
            $(this).removeClass('hidden');
         }
         else {
            $(this).addClass('hidden');
         }

      });

      $('.entryPoint').click(function() {
         var userId = $(this).closest('tr').attr('user-id');
         var lessonId = $(this).closest('td').attr('lesson-id');
         var present = Number($(this).hasClass('blackBox'));
         var cell = $(this).closest('td');
         submitAttendance(userId, lessonId, present, cell);
      });

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

      function shirtRequirementsMet(userId) {
         <?php if(PRODUCT == 'dpp') { ?>
            // Return true if participant attended 9 of the first 16
            var outOf16 = 0;
            for(var i=1; i<=16; i++) {
               outOf16 += iqr[userId][i] ? 1 : 0;
            }

            return outOf16 >= 9;


         <?php } else if(PRODUCT == 'esmmwl') { ?>
            // Return true if participant attended all of the first 15
            var outOf15 = 0;
            for(var i=1; i<=15; i++) {
               outOf15 += iqr[userId][i] ? 1 : 0;
            }

            return outOf15 >= 15;


         <?php } ?>
      }

      function submitAttendance(userId, week, present, cell) {
         cell.children('img').removeClass('hidden');
         cell.children('.entryPoint').addClass('hidden');
         $.post('attendance_ajax.php', {
            user_id: userId,
            class_id: <?php echo htmlentities($_GET['class_id']); ?>,
            class_source: '<?php echo htmlentities($_GET['class_source']); ?>',
            week: week,
            present: present
         }, function(data) {
            cell.children('img').addClass('hidden');

            if(data === 'OK') {
               var classToShow = present ? '.greenCheck' : '.blackBox';
               cell.children(classToShow).removeClass('hidden');

               // Change attendanceSum cell
               var sumCell = cell.siblings('.attendanceSum');
               var delta = present ? 1 : -1;
               sumCell.fadeOut('fast', function() {
                  $(this).html(parseInt(sumCell.html(), 10) + delta).fadeIn('slow');

                  // Update client-side array
                  iqr[userId][week] = present ? 1 : 0;

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
               cell.children(classToShow).removeClass('hidden');
               alert('An error occurred.');
            }
         });
      }

   </script>

   <?php
}

?>

