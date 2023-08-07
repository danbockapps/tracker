<?php

/**
 * Attendance entry for ESMMWL and ESMMWL2
 */

require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("<p>You must be an admin or instructor to view this page.</p>");
   }

   $qr = participantsForClass($_GET['class_id']);
   $iqr = indexedAttendanceArray(attendanceForClass($_GET['class_id']), $qr);
   attendanceEntryHeader($_GET['class_id']);

   if(PRODUCT == 'esmmwl2') {
      $numLessons = 12;
   }
   else {
      $numLessons = 15;
   }

   ?>

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
         ?></a>
         </td>
         <td class="attendanceSum"><?php
            echo numclasses($row['user_id'], $iqr);
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

      $('.entryPoint').click(function() {
         var userId = $(this).closest('tr').attr('user-id');
         var lessonId = $(this).closest('td').attr('lesson-id');
         var present = Number($(this).hasClass('blackBox'));
         var cell = $(this).closest('td');
         submitAttendance(userId, lessonId, present, cell);
      });

      function submitAttendance(userId, lessonId, present, cell) {
         cell.children('img').removeClass('hidden');
         cell.children('.entryPoint').addClass('hidden');
         $.post('rest/api.php?q=attendance', {
            user_id: userId,
            class_id: <?php echo htmlentities($_GET['class_id']); ?>,
            class_source: '<?php echo htmlentities($_GET['class_source']); ?>',
            lesson_id: lessonId,
            present: present
         }, function(data) {
            cell.children('img').addClass('hidden');

            if(data.responseString === 'OK') {
               var classToShow = present ? '.greenCheck' : '.blackBox';
               cell.children(classToShow).removeClass('hidden');

               // Change attendanceSum cell
               var sumCell = cell.siblings('.attendanceSum');
               var delta = present ? 1 : -1;
               sumCell.fadeOut('fast', function() {
                  $(this).html(parseInt(sumCell.html(), 10) + delta).fadeIn('slow');

                  // Update client-side array
                  iqr[userId][lessonId] = present ? 1 : 0;
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

function numclasses($user_id, $iqr) {
   $count = 0;

   foreach($iqr[$user_id] as $entryItem) {
      $count += $entryItem;
   }

   return $count;
}

?>

