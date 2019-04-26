<?php
require_once("template.php");

generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("<p>You must be an admin or instructor to view this page.</p>");
   }

   require('static_attendance_grid.php');

   $qr = participantsForClass($_GET['class_id']);
   attendanceEntryHeader($_GET['class_id']);

   ?>
   <p>
      Tip: For each lesson, fill out the entire <i>Class attended</i> column
      first, then <i>Date attended</i> second. When you set the date for a
      <i>Regular class</i> for one participant, the date will automatically be
      filled for all other participants who attended a regular class for that
      lesson.
   </p>
   <?php

   for($i=1; $i<=26; $i++) {
      echo '<hr /><h3>Lesson ' . $i . '</h3>';

      ?>
      <table lesson-id="<?php echo $i; ?>" cellspacing="0">
         <thead>
            <tr>
               <th>Participant</th>
               <th>Class attended</th>
               <th style="width:2em;"><!-- Spinner/checkmark --></th>
               <th>Date attended</th>
               <th style="width:2em;"><!-- Spinner/checkmark --></th>
               <th class="graybg">Weight</th>
               <th class="graybg">Minutes of<br/>Physical Activity</th>
               <th class="graybg"><!-- Submit button --></th>
               <th class="graybg" style="width:2em;"><!-- Spinner/checkmark --></th>
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
                  <td class="graybg">
                     <input type="text" name="attendance-weight" class="attendance-weight" />
                  </td>
                  <td class="graybg">
                     <input type="text" name="attendance-pa" class="attendance-pa" />
                  </td>
                  <td class="graybg">
                     <button class="attendance-submit ui-button ui-widget ui-corner-all">Submit</button>
                  </td>
                  <td class="status graybg">
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

$aqr = attendanceSummary3ForClass($_GET['class_id']);
?>

<script>
   var aqr = <?= json_encode($aqr, JSON_NUMERIC_CHECK) ?>;

   aqr.forEach(function(item) {
      // This might be kinda slow

      var row = $('table[lesson-id=' + item.week +
         '] tr[user-id=' + item.user_id + ']');

      row.find('select.attendance-type').val(item.attendance_type);

      if(item.attendance_date) {
         row.find('input.attendance-date').val(moment(item.attendance_date)
               .format('MM/DD/YYYY'));
      }
   });

   $('select.attendance-type[value=0]').each(function() {
      $(this).closest('tr').find('.attendance-date').prop('disabled', true);
   });

   $('select.attendance-type').selectmenu({
      change: function(event, ui) {
         submit(this, false);
      }
   });

   $('.attendance-date').datepicker({
      onSelect: function(dateText, inst) {
         $(this).change();
      }
   }).change(function() {
      submit(this, true);
   });

   function submit(inputElement, propagate) {
      var lessonId = $(inputElement).closest('table').attr('lesson-id');
      var userId = $(inputElement).closest('tr').attr('user-id');
      var statusCell = $(inputElement).parent().next();
      var attendanceType = parseInt($(inputElement).closest('tr').find('.attendance-type').val());
      var attendanceField = $(inputElement).closest('tr').find('.attendance-date');

      statusCell.children('i').addClass('hidden');
      statusCell.children('img').removeClass('hidden');

      var formattedAttendanceDate;

      // Clear out date if user is changing type to 0 (no class attended)
      if($(inputElement).hasClass('attendance-type')) {
         if(attendanceType === 0) {
            attendanceField.val('');
            attendanceField.prop("disabled", true);
            formattedAttendanceDate = null;
         }
         else {
            attendanceField.prop("disabled", false);
         }
      }

      var attendanceDate = attendanceField.val();

      if(attendanceDate) {
         formattedAttendanceDate = moment(attendanceDate).format('YYYY-MM-DD');
      }

      $.post('attendance_ajax.php', {
         user_id: userId,
         class_id: <?= htmlentities($_GET['class_id']) ?>,
         week: lessonId,
         attendance_type: attendanceType,
         attendance_date: formattedAttendanceDate
      }, function(data) {
         if(data === 'OK') {
            statusCell.children('img').addClass('hidden');
            statusCell.children('i').removeClass('hidden');
         }
      });

      if(
         $(inputElement).hasClass('attendance-date') &&
         attendanceType === 1 &&
         propagate
      ) {
         $('table[lesson-id=' + lessonId + ']').find('.attendance-date').each(function() {
            if(
               !$(this).val() &&
               parseInt($(this).closest('tr').find('.attendance-type').val()) === 1
            ) {
               $(this).val(attendanceDate);
               submit(this, false);
            }
         });
      }

   }

   $('.attendance-weight, .attendance-pa').spinner();

   $.get("rest/api.php?q=ireports&class_id=<?php echo $_GET['class_id']; ?>", function(data) {
      data.reports.forEach(function(item) {

         $('.attendance-weight').each(function(index, element) {
            if(
               Number(item.lesson_id) === Number($(element).closest('table').attr('lesson-id'))
               &&
               Number(item.user_id) === Number($(element).closest('tr').attr('user-id'))
            ) {
               $(element).val(item.weight);
            }
         });

         $('.attendance-pa').each(function(index, element) {
            if(
               Number(item.lesson_id) === Number($(element).closest('table').attr('lesson-id'))
               &&
               Number(item.user_id) === Number($(element).closest('tr').attr('user-id'))
            ) {
               $(element).val(item.physact_minutes);
            }
         });

      });
   });

   $('.attendance-submit').click(function(event) {
      var statusCell = $(this).parent().next();
      statusCell.children('i').addClass('hidden');
      statusCell.children('img').removeClass('hidden');

      $.post('rest/api.php?q=ireports', {
         user_id: $(this).closest('tr').attr('user-id'),
         class_id: <?= htmlentities($_GET['class_id']) ?>,
         lesson_id: $(this).closest('table').attr('lesson-id'),
         weight: $(this).closest('tr').find('.attendance-weight').val(),
         physact_minutes: $(this).closest('tr').find('.attendance-pa').val()
      }, function(data) {
         if(data.responseString === 'OK') {
            statusCell.children('img').addClass('hidden');
            statusCell.children('i').removeClass('hidden');
         }
      });
   });

</script>
