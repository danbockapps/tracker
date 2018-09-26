<?php
/*
PLEASE NOTE: Throughout this file and the attendance table in the database, the
word "week" is a misnomer. Attendance is tracked by lesson, which may or may
not correspond 1:1 to weeks. TODO: change varibles and fields named "week"
here and in the database to "lesson".
*/

require_once("template.php");

$requireLoggedIn = true;
if(ENVIRONMENT == 'dev') {
   $requireLoggedIn = false;
}
generate_page($requireLoggedIn, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor() && ENVIRONMENT != 'dev') {
      exit("You must be an admin or instructor to view this page.");
   }

   $qr = participantsForClass($_GET['class_id']);
   $iqr = indexedAttendanceArray(attendanceForClass($_GET['class_id']), $qr);
   attendanceEntryHeader($_GET['class_id']);

   for($i=1; $i<=26; $i++) {
      echo '<hr /><h3>Lesson ' . $i . '</h3>';
      
      ?>
      <table lesson-id="<?php echo $i; ?>">
         <thead>
            <tr>
               <th>Participant</th>
               <th>Class attended</th>
               <th style="width:2em;"><!-- Spinner/checkmark --></th>
               <th>Date attended</th>
               <th style="width:2em;"><!-- Spinner/checkmark --></th>
            </tr>
         </thead>
         <tbody>
            <?php
            foreach($qr as $row) {
               ?>
               <tr user-id="<?php echo $row['user_id']; ?>">
                  <td><?php echo $row['fname'] . ' ' . $row['lname']; ?></td>
                  <td>
                     <select class="attendance-type">
                        <option value="0">No class attended</option>
                        <option value="1">Regular class</option>
                        <option value="2">Make-up class</option>
                     </select>
                  </td>
                  <td class="status">
                     <img src="spinner.gif" class="hidden" />
                     <i class="material-icons hidden entryPoint greenCheck">&#xE86C;</i>
                  </td>
                  <td>
                     <input type="text" class="attendance-date ui-widget ui-widget-content ui-corner-all" />
                  </td>
                  <td class="status">
                     <img src="spinner.gif" class="hidden" />
                     <i class="material-icons hidden entryPoint greenCheck">&#xE86C;</i>
                  </td>
               </tr>
               <?php
            }
            ?>
         </tbody>
      </table>


      <?php
   }
}
?>

<script>
   $('select.attendance-type').selectmenu({
      change: function(event, ui) {
         submit(this);
      }
   });

   $('.attendance-date').datepicker({
      onSelect: function(dateText, inst) {
         submit(this);
      }
   });

   function submit(inputElement) {
      var lessonId = $(inputElement).closest('table').attr('lesson-id');
      var userId = $(inputElement).closest('tr').attr('user-id');
      var statusCell = $(inputElement).parent().next();
      var attendanceType = $(inputElement).closest('tr').find('.attendance-type').val();
      var attendanceDate = $(inputElement).closest('tr').find('.attendance-date').val();

      statusCell.children('i').addClass('hidden');
      statusCell.children('img').removeClass('hidden');

      $.post('attendance_ajax_detailed.php', {
         lessonId: lessonId,
         userId: userId,
         attendanceType: attendanceType,
         attendanceDate: attendanceDate
      }, function(data) {
         console.log(data);
         statusCell.children('img').addClass('hidden');
         statusCell.children('i').removeClass('hidden');
      });
   }
</script>
