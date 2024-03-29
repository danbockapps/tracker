<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   global $ini;
   if(!am_i_admin()) {
      echo "You must be logged in as an administrator to see this page.";
   }
   else {
      if(isset($_POST['submitted_addinstructor'])) {
         echo addinstructor();
      }
      if(isset($_POST['submitted_rminstructor'])) {
         echo rminstructor();
      }
      if(isset($_POST['submitted_addadmin'])) {
         echo addadmin();
      }
      if(isset($_POST['submitted_rmadmin'])) {
         echo rmadmin();
      }

      $all_instructors = pdo_seleqt("
         select
            user_id,
            concat(fname, ' ', lname, ' (', email, ')') as label
         from wrc_users
         where instructor = 1
         order by lname
      ", null);
   ?>

<script>
   $(function() {
      $("#ni_name").autocomplete({
          source: "all_participants.php",
          minLength: 2
          /* Set the hidden input ni_id to the user_id returned. */
          /* Problem: After you select from the jQuery drop-down,
           * and the hidden field gets a value, you can change the text
           * in the text input and the hidden value will not change.
           */

          /* Solution: Forget the hidden field. PHP will parse the
           * value of the text input. */
      });
   });
   $(function() {
      $("#na_name").autocomplete({
         source: "all_participants.php",
         minLength: 2
      });
   });
   $(function() {
      $("#pl_name").autocomplete({
         source: "all_participants.php?current=1",
         minLength: 2
      });
   });

</script>

<h2>Site administration</h2>

<!-- ---------------------------------------------------------------------
                                                     ADD NEW INSTRUCTOR
---------------------------------------------------------------------- -->
<?php
// Possible future enhancement: use this if condition to show only those
// users who are not already instructors.
if(true) {
   ?>
   <a href="#" class="showhide_closed">Add new instructor</a>
   <form class="admin white-form" action="admin.php" method="post"
      onsubmit="return confirm('Are you sure you want to add this instructor?');">
      <fieldset>
         <!-- Populated by jQuery -->
         <label for="ni_name">Name (enter at least two letters): </label>
         <input type="text" id="ni_name" name="ni_name" />

         <input type="hidden" name="submitted_addinstructor" value="true" />
         <input type="submit" value="Make instructor" />
      </fieldset>
   </form>
   <?php
}
else {
   echo "<i>There are no users to make instructors.</i>";
}

?>

<!-- ---------------------------------------------------------------------
                                                     REMOVE INSTRUCTOR
---------------------------------------------------------------------- -->
<a href="#" class="showhide_closed">Remove instructor</a>
<form class="admin white-form" action="admin.php" method="post"
      onsubmit="return confirm('Are you sure you want to remove this instructor?');">
   <fieldset>
      <?php
      if(instructor_selector($all_instructors, "instructor_to_rm")) {
         ?>
         <input type="hidden" name="submitted_rminstructor" value="true" />
         <input type="submit" value="Remove" />
         <?php
      }
      ?>
   </fieldset>
</form>

<!-- ---------------------------------------------------------------------
                                                             ADD NEW ADMIN
---------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Add new admin</a>
<form class="admin white-form" action="admin.php" method="post"
      onsubmit="return confirm('Are you sure you want to add this admin?');">
   <fieldset>
      <!-- Populated by jQuery -->
      <label for="na_name">Name (enter at least two letters): </label>
      <input type="text" id="na_name" name="na_name" />

      <input type="hidden" name="submitted_addadmin" value="true" />
      <input type="submit" value="Make admin" />
   </fieldset>
</form>

<!-- ---------------------------------------------------------------------
                                                              REMOVE ADMIN
---------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Remove admin</a>
<form class="admin white-form" action="admin.php" method="post"
      onsubmit="return confirm('Are you sure you want to remove this admin?');">
   <fieldset>
      <select name="admin_to_rm">
         <?php
            $all_admins = pdo_seleqt("
               select
                  user_id,
                  concat(fname, ' ', lname, ' (', email, ')') as label
               from wrc_users
               where administrator = 1
               order by lname
            ", null);
            foreach($all_admins as $row) {
               echo "<option value='" . $row['user_id'] . "'>" .
                     htmlentities($row['label']) . "</option>";
            }
         ?>
      </select>
      <input type="hidden" name="submitted_rmadmin" value="true" />
      <input type="submit" value="Remove" />
   </fieldset>
</form>

<!-- --------------------------------------------------------------------------
                                                             PARTICIPANT LOOKUP
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Participant lookup</a>
<div>
   <!-- Div is for the purposes of the showhide function -->
   <form class="admin white-form" action="admin.php" method="get">
      <!-- Method=get because this does not change anything in the db -->
      <fieldset>
         <!-- Populated by jQuery -->
         <label for="pl_name">Name (enter at least two letters): </label>
         <input type="text" id="pl_name" name="pl_name" />

         <input type="hidden" name="submitted_partlookup" value="true" />
         <input type="submit" value="Lookup participant" />
      </fieldset>
   </form>

   <?php
   if(isset($_GET['submitted_partlookup'])) {
      $plcqr = seleqt_one_record("
         select count(*) as count
         from wrc_users
         where concat(fname, ' ', lname, ' (', email, ')') = ?
      ", array($_GET['pl_name']));
      if($plcqr['count'] != 1) {
         echo htmlentities($_GET['pl_name']);
         ?>: User not found.<?php
      }
      else {
         $plqr = seleqt_one_record("
            select
               u.user_id,
               u.fname,
               u.lname,
               u.email,
               u.activation,
               e.class_id,
               e.class_source,
               last_login_by_user.last_login,
               c.start_dttm,
               concat(instr.fname, ' ', instr.lname) as instr_name,
               u.email_reset
            from
               wrc_users u
               natural left join " . ENR_VIEW . " e
               natural left join current_classes_for_rosters c
               natural left join (
                  select
                     user_id,
                     max(pv_dttm) as last_login
                  from wrc_pageviews
                  group by user_id
               ) last_login_by_user
               left join wrc_users instr
                  on c.instructor_id = instr.user_id
            where
               concat(u.fname, ' ', u.lname, ' (', u.email, ')') = ?
               and c.start_dttm in (
                  select max(c.start_dttm)
                  from
                     " . ENR_VIEW . " e
                     natural join current_classes_for_rosters c
                     natural join wrc_users u
                  where concat(u.fname, ' ', u.lname, ' (', u.email, ')') = ?
               )
         ", array($_GET['pl_name'], $_GET['pl_name']));
         ?>

         <script>
            var plFname = <?php echo json_encode($plqr['fname']); ?>;
            var plLname = <?php echo json_encode($plqr['lname']); ?>;
            var plUserId = <?php echo json_encode($plqr['user_id']); ?>;
         </script>

         <script type="text/babel" src="react/ChangeName.js"></script>
         <div id="change-name" class="spacious-form white-form"></div>
         <table class="pl">
            <tr>
               <td style="font-weight: bold">E-mail address</td>
               <td>
                  <?php
                     echo htmlentities($plqr['email']);
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">Account activated</td>
               <td>
                  <?php
                     echo $plqr['activation'] == null ? "Yes" : "No";
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">Last login</td>
               <td>
                  <?php
                     echo htmlentities(rstr_date($plqr['last_login']));
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">Class</td>
               <td>
                  <?php
                     if($plqr['start_dttm'] == null) {
                        echo "Not registered for a class";
                     }
                     else {
                        echo class_times($plqr['start_dttm']) . " ";
                        ?>
                           <button onclick="
                              $.ajax({
                                 type: 'POST',
                                 url: 'resend_welcome_email.php',
                                 data: {
                                    user_id: <?php echo
                                       htmlentities($plqr['user_id']); ?>,
                                    class_id: <?php echo
                                       htmlentities($plqr['class_id']); ?>,
                                    class_source: '<?php echo
                                       htmlentities($plqr['class_source']); ?>'
                                 }
                              });
                              alert('The welcome e-mail will be re-sent within' +
                                    ' the next 15 minutes.');
                           ">
                              Resend welcome email
                           </button>
                        <?php
                     }
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">Instructor</td>
               <td>
                  <?php
                     echo $plqr['instr_name'] == null ?
                           "n/a" :
                           htmlentities($plqr['instr_name']);
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">
                  Password reset link<br />
                  <?php
                     popup(
                        "instructions",
                        "The user can go to this address to reset her " .
                        "password. The user can also have this link " .
                        "e-mailed to her by clicking the \"Forgot your " .
                        "password\" link on the login page. <b>Be very " .
                        "careful with this address</b>, as it can be used " .
                        "to gain access to the user's account.\n\n" .
                        "Resetting her password via this link will also " .
                        "activate a user's account if it has not been " .
                        "activated.",
                        "Password reset link"
                     )
                  ?>
               </td>
               <td style="font-size: 80%">
                  <?php
                     if($plqr['start_dttm'] == null) {
                        ?><i>
                           Password reset link is available only for
                           currently-registered participants.
                        </i><?php
                     }
                     else {
                        $email_reset_key = generate_email_reset($plqr['email']);
                        echo WEBSITE_URL . "/reset.php?email=" .
                              urlencode($plqr['email']) . "&key=" .
                              $email_reset_key;
                     }
                  ?>
               </td>
            </tr>
         </table>
         <?php
      }
   }
   ?>
</div>

<!-- --------------------------------------------------------------------------
                                                                   CHANGE EMAIL
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Change user email address</a>
<div id="change-email" class="spacious-form"></div>
<script type="text/babel" src="react/ChangeEmail.js"></script>

<!-- --------------------------------------------------------------------------
                                                         NEW ATTENDANCE REPORTS
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">New attendance reports</a>
<?php
   $aqr = pdo_seleqt("
      select
         c.class_id,
         c.class_type,
         c.start_dttm,
         u.fname,
         u.lname
      from
         classes_aw c
         left join wrc_users u
            on c.instructor_id = u.user_id
      where
         c.start_dttm > '2015-01-01 00:00:00'
      order by
         year(c.start_dttm) desc,
         month(c.start_dttm) desc,
         c.class_type > 1, /* true for SHP, false for public */
         c.start_dttm desc
   ", array());
?>

<form action="download_report.php" method="get" class="attendance-reports-section white-form">
   <?php
      for($i=0; $i<count($aqr); $i++) {
         $row = $aqr[$i];


         // // // SET FIRSTS AND LASTS // // //

         $firstOfMonth = false;
         $firstOfYear = false;
         $lastOfMonth = false;
         $lastOfYear = false;

         if($i == 0 || !yearSame($row['start_dttm'], $aqr[$i-1]['start_dttm'])) {
            $firstOfYear = true;
            $firstOfMonth = true;
         }
         else if(!monthSame($row['start_dttm'], $aqr[$i-1]['start_dttm'])) {
            $firstOfMonth = true;
         }

         if($i == count($aqr) || !yearSame($row['start_dttm'], $aqr[$i+1]['start_dttm'])) {
            $lastOfYear = true;
            $lastOfMonth = true;
         }
         else if(!monthSame($row['start_dttm'], $aqr[$i+1]['start_dttm'])) {
            $lastOfMonth = true;
         }

         // // // DONE SETTING FIRSTS AND LASTS // // //

         $year = date('Y', strtotime($row['start_dttm']));
         $month = date('F', strtotime($row['start_dttm']));

         if($firstOfYear) { ?>
            <div class="attendance-reports-header">
               <?php echo $year; ?>

               <a
                  href="#"
                  class="selectYearLink inline-link"
                  data-year="<?php echo $year; ?>"
               >
                  Select all
               </a>

               <a
                  href="#"
                  class="selectYearLink inline-link"
                  data-year="<?php echo $year; ?>"
                  data-deselect="true"
               >
                  Deselect all
               </a>
            </div>

            <div class="attendance-reports-section"><?php
         }

         if($firstOfMonth) { ?>
            <div class="attendance-reports-header">
               <?php echo $month; ?>

               <a
                  href="#"
                  class="selectMonthLink inline-link"
                  data-year="<?php echo $year; ?>"
                  data-month="<?php echo $month; ?>"
               >
                  Select all
               </a>

               <a
                  href="#"
                  class="selectMonthLink inline-link"
                  data-year="<?php echo $year; ?>"
                  data-month="<?php echo $month; ?>"
                  data-deselect="true"
               >
                  Deselect all
               </a>
            </div>

            <div class="attendance-reports-section"><?php
         }

         ?><input
            type="checkbox"
            name="class[<?php echo $row['class_id']; ?>]"
            data-year="<?php echo date('Y', strtotime($row['start_dttm'])); ?>"
            data-month="<?php echo date('F', strtotime($row['start_dttm'])); ?>"
         ><strong><?php
            echo $row['class_id'] . ". ";
         ?></strong><?php
            echo class_times($row['start_dttm']) . " (" . $row['fname'] . " " .
                  $row['lname'] . ") ";
         ?><a href="view_report.php?report=<?php
            echo (PRODUCT == 'dpp' ? 'attendance3' : 'attendance2');
         ?>&class=<?php
            echo $row['class_id'];
         ?>" class="inline-link">Web view</a><br /><?php

         
         if($lastOfMonth) {
            echo '</div> <!-- close attendance-reports-section for month -->';
         }
         if($lastOfYear) {
            echo '</div> <!-- close attendance-reports-section for year -->';
         }

      }

      if(PRODUCT == 'dpp') {
         ?><input type="hidden" name="report" value="attendance3" /><?php
      }
      else {
         ?><input type="hidden" name="report" value="attendance2" /><?php
      }
   ?>

   <input type="submit" value="Download report" />
</form>

<script>
function checkCheckboxesByMonthYear(month, year, deselect) {
   // Select all checkboxes with the specified data-month and data-year attributes
   $(`input[type="checkbox"][data-month="${month}"][data-year="${year}"]`).prop('checked', !deselect);
}

function checkCheckboxesByYear(year, deselect) {
   $(`input[type="checkbox"][data-year="${year}"]`).prop('checked', !deselect);
}

$('.selectMonthLink').on('click', function(event) {
   event.preventDefault(); // Prevent the default link behavior
   const month = $(this).data('month'); // Get the month from data-month attribute
   const year = $(this).data('year');   // Get the year from data-year attribute
   const deselect = $(this).data('deselect'); // Get the deselect value from data-deselect attribute
   checkCheckboxesByMonthYear(month, year, deselect ?? false); // Call the function with the selected month and year
});

$('.selectYearLink').on('click', function(event) {
   event.preventDefault();
   const year = $(this).data('year');
   const deselect = $(this).data('deselect');
   checkCheckboxesByYear(year, deselect ?? false);
});
</script>



<!-- --------------------------------------------------------------------------
                                                                CLIENT1 REPORTS
--------------------------------------------------------------------------- -->

<?php if(isset($ini['client1'])) { ?>

<a href="#" class="showhide_closed"><?php echo $ini['client1']; ?> reports</a>
<?php
   $bqr = pdo_seleqt("
      select voucher_code
      from
   " . $ini['client1_voucher_codes'], array());
?>
<ul>
   <li>
      <a href="view_report.php?report=aso_codes">
         List of ASO Codes
      </a>
   </li>
   <li>
      <a href="download_report.php?report=all_aso_participants">
         All ASO participants (download)
      </a>
   </li>
   <li style='margin-bottom: 1em;'>
      <a href="download_report.php?report=asoncms">
         All ASONCMS participants (download)
      </a>
   </li>
   <?php
      foreach($bqr as $row) {
         ?><li><a href="view_report.php?report=<?php
            echo $ini['client1'];
         ?>&voucher_code=<?php
            echo $row['voucher_code'];
         ?>"><?php
            echo $row['voucher_code'];
         ?></a></li><?php
      }
   ?>
</ul>

<?php } ?>

<!-- --------------------------------------------------------------------------
                                           LIKE CLIENT1 BUT FOR NON-ASO REPORTS
--------------------------------------------------------------------------- -->

<?php if(isset($ini['client1'])) { ?>

<a href="#" class="showhide_closed">Non-ASO voucher code reports</a>
<?php
   $nasoqr = pdo_seleqt("
      select distinct voucher_code
      from " . ENR_VIEW . "
      where voucher_code not like 'ASO%'
   ", array());
?>
<ul>
   <?php
      foreach($nasoqr as $row) {
         ?><li><?php
            echo $row['voucher_code'];
         ?> <a href="view_report.php?report=<?php
            echo $ini['client1'];
         ?>&voucher_code=<?php
            echo $row['voucher_code'];
         ?>">web view</a> <a href="download_report.php?report=<?php
            echo $ini['client1'];
         ?>&voucher_code=<?php
            echo $row['voucher_code'];
         ?>">download</a></li><?php
      }
   ?>
</ul>

<?php } ?>


<!-- --------------------------------------------------------------------------
                                                                  OTHER REPORTS
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Other reports</a>
<ul>
   <?php if(PRODUCT == 'dpp') { ?>
      <li>
         <a href="download_report.php?report=cdc_report_online">
            CDC report - online
         </a>
      </li>
      <li>
         <a href="download_report.php?report=cdc_report_onsite">
            CDC report - onsite
         </a>
      </li>
      <li>
         <a href="view_report.php?report=addresschanges">
            Address changes
         </a>
      </li>
      <li>
         <a href="view_report.php?report=performance_file">
            Performance file
         </a>
      </li>
   <?php } ?>
</ul>

<!-- --------------------------------------------------------------------------
                                                                INSTRUCTOR VIEW
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Instructor view</a>
<ul>
<?php
   $ivqr = pdo_seleqt("
      select
         u.user_id,
         u.fname,
         u.lname,
         c.count
      from
         wrc_users u
         left join (
            select
               instructor_id,
               count(*) as count
            from current_classes_for_rosters
            group by instructor_id
         ) c
            on u.user_id = c.instructor_id
      where u.instructor = 1
      order by
         u.lname,
         u.fname
   ", array());

   foreach($ivqr as $row) {
      ?><li<?php
         if($row['count']) echo ' style = "font-weight: bold;"';
      ?>><?php
         echo $row['fname'] . ' ' . $row['lname'] . ' ';
      ?><a href="rosters.php?instr=<?php
         echo $row['user_id'];
      ?>">rosters</a> <a href="attendance_class_list.php?instr=<?php
         echo $row['user_id'];
      ?>">attendance</a></li><?php
   }
?>
</ul>

<!-- --------------------------------------------------------------------------
                                                                         SHIRTS
--------------------------------------------------------------------------- -->
<a href="#" class="showhide_closed">T-shirt inventory</a>
<ul>
<script>
$(function() {
   $('.blackBox, .greenCheck').click(function() {
      $(this).hide()
      $(this).siblings('img').show()
      var $this = $(this)

      var shirtId = $(this).closest('div').attr('shirt-id');
      var instock = Number($(this).hasClass('blackBox'))

      $.post('rest/api.php?q=shirtstock', {
         shirt_id: shirtId,
         instock: instock
      }, function(data) {
         if(data.responseString === 'OK') {
            $this.siblings('img').hide()
            $this.siblings(instock ? '.greenCheck' : '.blackBox').show()
         }
      })
   })
})
</script>

<?php

$shirtqr = pdo_seleqt('select * from shirts order by shirt_desc', array());
foreach($shirtqr as $shirt) {

   if($shirt['shirt_instock']) {
      $blackBoxStyle = "display: none";
      $greenCheckStyle = "";
   }
   else {
      $blackBoxStyle = "";
      $greenCheckStyle = "display: none";
   }

   ?>
   <div class="shirt-stock-div" shirt-id="<?= $shirt['shirt_id'] ?>">

      <i class="material-icons blackBox"
         style="<?= $blackBoxStyle ?>">&#xE3C1;</i>

      <img src="spinner.gif" style="display: none" />

      <i class="material-icons greenCheck"
         style="<?= $greenCheckStyle ?>">&#xE86C;</i>

      <span><?= $shirt['shirt_desc'] ?></span>
   </div>
   <?php
}

?>
</ul>

   <?php
   }
}
function is_user($f_l_e_combo) {
   $qr = seleqt_one_record("
      select count(*) as count
      from wrc_users
      where
         concat(fname, ' ', lname, ' (', email, ')') = ?
   ", array($f_l_e_combo));
   return ($qr['count'] == 1);
}

function addinstructor() {
   global $ini;
   if(!is_user($_POST['ni_name'])) {
      return err_text("User not found.");
   }
   else {
      $dbh = pdo_connect($ini['db_prefix'] . "_update");
      $sth = $dbh->prepare("
         update wrc_users
         set instructor = 1
         where concat(fname, ' ', lname, ' (', email, ')') = ?
      ");
      if($sth->execute(array($_POST['ni_name']))) {
         $eqr = seleqt_one_record("
            select user_id
            from wrc_users
            where concat(fname, ' ', lname, ' (', email, ')') = ?
         ", array($_POST['ni_name']));
         sendById($eqr['user_id'], 5);

         return cnf_text($_POST['ni_name'] . " is now an instructor.");
      }
      else {
         return err_text("Database error 001.");
      }
   }
}

function addadmin() {
   global $ini;
   if(!is_user($_POST['na_name'])) {
      return err_text("User not found.");
   }
   else {
      $dbh = pdo_connect($ini['db_prefix'] . "_update");
      $sth = $dbh->prepare("
         update wrc_users
         set administrator = 1
         where concat(fname, ' ', lname, ' (', email, ')') = ?
      ");
      if($sth->execute(array($_POST['na_name']))) {
         return cnf_text($_POST['na_name'] . " is now an administrator.");
      }
      else {
         return err_text("Database error.");
      }
   }
}

function instructor_selector($all_instructors, $select_name) {
   if(empty($all_instructors)) {
      ?><i>There are no instructors.</i> <?php
      return false;
   }
   else {
      ?>
      <select name="<?php echo $select_name; ?>">
         <?php
            foreach($all_instructors as $row) {
               echo "<option value='" . $row['user_id'] . "'>".
                     htmlentities($row['label']) . "</option>";
            }
         ?>
      </select>
      <?php
      return true;
   }
}

function rminstructor() {
   global $ini;
   $qr = seleqt_one_record("
      select count(*) as count
      from classes_aw
      where instructor_id = ?
   ", array($_POST['instructor_to_rm']));
   if($qr['count'] != 0) {
      return err_text("There are classes assigned to this instructor. " .
               "Please assign them to other instructors before " .
               "removing this instructor.");
   }
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_users
      set instructor = 0
      where user_id = ?
   ");
   if($sth->execute(array($_POST['instructor_to_rm']))) {
      return cnf_text("Instructor removed.");
   }
   else {
      return err_text("A database error occurred.");
   }
}

function rmadmin() {
   global $ini;
   $dbh = pdo_connect($ini['db_prefix'] . "_update");
   $sth = $dbh->prepare("
      update wrc_users
      set administrator = 0
      where user_id = ?
   ");
   if($sth->execute(array($_POST['admin_to_rm']))) {
      return cnf_text("Admin removed.");
   }
   else {
      return err_text("A database error occurred.");
   }
}

function yearSame($dateString1, $dateString2) {
   return date('Y', strtotime($dateString1)) == date('Y', strtotime($dateString2));
}

function monthSame($dateString1, $dateString2) {
   // returns true if month AND year are the same
   return date('Y F', strtotime($dateString1)) == date('Y F', strtotime($dateString2));
}

?>
