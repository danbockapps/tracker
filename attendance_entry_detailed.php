<?php
require_once("template.php");

generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }

   require('static_attendance_grid.php');

   $qr = participantsForClass($_GET['class_id']);
   global $ctqr;
   $ctqr = seleqt_one_record(
      'select class_type from classes_aw where class_id = ?',
      $_GET['class_id']
   );
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
                     <?php
                        if($ctqr['class_type'] == 5) {
                           ?>
                           <input type="text" name="attendance-weight" class="attendance-weight" />
                           <?php
                        }
                        else {
                           ?>
                           <span class="weight-span"></span>
                           <?php
                        }
                     ?>
                  </td>
                  <td class="graybg">
                     <?php
                        if($ctqr['class_type'] == 5) {
                           ?>
                           <input type="text" name="attendance-pa" class="attendance-pa" />
                           <?php
                        }
                        else {
                           ?>
                           <span class="pa-span"></span>
                           <?php
                        }
                     ?>
                  </td>
                  <td class="graybg">
                     <?php
                        if($ctqr['class_type'] == 5) {
                           ?>
                           <button class="attendance-submit ui-button ui-widget ui-corner-all">Submit</button>
                           <?php
                        }
                     ?>
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
      var weightSpan = $(inputElement).closest('tr').find('.weight-span');
      var paSpan = $(inputElement).closest('tr').find('.pa-span');

      statusCell.children('i').addClass('hidden');
      statusCell.children('img').removeClass('hidden');

      weightSpan.empty();
      paSpan.empty();

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

      $.post('rest/api.php?q=attendance', {
         user_id: userId,
         class_id: <?= htmlentities($_GET['class_id']) ?>,
         week: lessonId,
         attendance_type: attendanceType,
         attendance_date: formattedAttendanceDate
      }, function(data) {
         if(data.responseString === 'OK') {
            statusCell.children('img').addClass('hidden');
            statusCell.children('i').removeClass('hidden');

            if(data.weight) weightSpan.html(Math.round(data.weight));
            if(data.physact_minutes) paSpan.html(Math.round(data.physact_minutes));
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

   var classType = <?php echo $ctqr['class_type']; ?>;

   if(classType === 5)
      populateIReports();
   else
      populateWeightPa();

   function populateIReports() {
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
   }

   function populateWeightPa() {
      // Get the weight and PA data and populate the column wherever they're within 3.5 days
      $.get("rest/api.php?q=weightpa&class_id=<?php echo $_GET['class_id']; ?>", function(data) {
         $('.attendance-date').each(function(index, element) {
            if(element.value) {
               var attendanceDate = new Date(element.value)
               data.reports.forEach(function(report) {
                  var reportDate = new Date(report.date);
                  if(
                     (report.weight || report.physact_minutes) &&
                     report.user_id === Number($(element).closest('tr').attr('user-id')) &&
                     Math.abs(attendanceDate - reportDate) < 1000 * 60 * 60 * 24 * 3.5
                  ) {
                     $(element).closest('tr').find('.weight-span').html(Math.round(report.weight));
                     $(element).closest('tr').find('.pa-span').html(Math.round(report.physact_minutes));
                  }
               })
            }
         });
      });
   }

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
