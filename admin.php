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
   <form class="admin" action="admin.php" method="post"
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
<form class="admin" action="admin.php" method="post"
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
<form class="admin" action="admin.php" method="post"
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
<form class="admin" action="admin.php" method="post"
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
   <form class="admin" action="admin.php" method="get">
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
         <table class="pl">
            <tr>
               <td style="font-weight: bold">First name</td>
               <td>
                  <?php
                     echo htmlentities($plqr['fname']);
                  ?>
               </td>
            </tr>
            <tr>
               <td style="font-weight: bold">Last name</td>
               <td>
                  <?php
                     echo htmlentities($plqr['lname']);
                  ?>
               </td>
            </tr>
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
                                                             ATTENDANCE REPORTS
--------------------------------------------------------------------------- -->


<!-- Commenting out old attendance reports for now. Will someday be deleted.

<a href="#" class="showhide_closed">Attendance reports</a>
<?php
   /* Classes will show up here on the Monday after they end */
   $aqr = pdo_seleqt("
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
         c.start_dttm > '2014-01-01 00:00:00'
         and datediff(
            now(),
            c.start_dttm - interval dayofweek(c.start_dttm) day
         ) - 2 >= c.weeks * 7
      order by c.start_dttm desc
   ", array());
?>

<form action="download_report.php" method="get">
   <?php
      foreach($aqr as $row) {
         ?><input type="checkbox" name="class[<?php
            echo $row['class_id'];
         ?>]"><?php
            echo class_times($row['start_dttm']) . " (" . $row['fname'] . " " .
                  $row['lname'] . ") ";
         ?><a href="view_report.php?report=attendance&class=<?php
            echo $row['class_id'];
         ?>"> web view</a><br /><?php
      }
   ?>
   <input type="hidden" name="report" value="attendance" />
   <input type="submit" value="Download report" />
</form>

-->

<!-- --------------------------------------------------------------------------
                                                         NEW ATTENDANCE REPORTS
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">New attendance reports</a>
<?php
   $aqr = pdo_seleqt("
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
         c.start_dttm > '2015-01-01 00:00:00'
      order by c.start_dttm desc
   ", array());
?>

<form action="download_report.php" method="get" class="attendance-reports-section">
   <?php
      for($i=0; $i<count($aqr); $i++) {
         $row = $aqr[$i];


         // // // SET FIRSTS AND LASTS // // //

         $firstOfMonth = false;
         $firstOfYear = false;
         $lastOfMonth = false;
         $lastOfYear = false;

         if($i == 0) {
            $firstOfMonth = true;
            $firstOfYear = true;
         }
         else {
            if(!monthSame($row['start_dttm'], $aqr[$i-1]['start_dttm'])) {
               $firstOfMonth = true;
            }
            if(!yearSame($row['start_dttm'], $aqr[$i-1]['start_dttm'])) {
               $firstOfYear = true;
            }
         }

         if($i == count($aqr)) {
            $lastOfMonth = true;
            $lastOfYear = true;
         }
         else {
            if(!monthSame($row['start_dttm'], $aqr[$i+1]['start_dttm'])) {
               $lastOfMonth = true;
            }
            if(!yearSame($row['start_dttm'], $aqr[$i+1]['start_dttm'])) {
               $lastOfYear = true;
            }
         }

         // // // DONE SETTING FIRSTS AND LASTS // // //


         // showhide saves cookies for which sections the user has open, so it
         // can have the same ones open when the user closes and reopens the
         // browser. That feature is going to be buggy in this section as it
         // saves cookies based on section name, and this section may have
         // multiple sections with the same name (e.g. "January"). This is a
         // very low-priority bug.

         if($firstOfYear) {
            echo '<a href="#" class="showhide_closed">' .
                    date('Y', strtotime($row['start_dttm'])) .
                 '</a>';
            echo '<div class="attendance-reports-section">';
         }

         if($firstOfMonth) {
            echo '<a href="#" class="showhide_closed">' .
                    date('F', strtotime($row['start_dttm'])) .
                 '</a>';
            echo '<div class="attendance-reports-section">';
         }

         ?><input type="checkbox" name="class[<?php
            echo $row['class_id'];
         ?>]"><?php
            echo class_times($row['start_dttm']) . " (" . $row['fname'] . " " .
                  $row['lname'] . ") ";
         ?><a href="view_report.php?report=attendance2&class=<?php
            echo $row['class_id'];
         ?>"> web view</a><br /><?php

         if($lastOfMonth) {
            echo '</div> <!-- close attendance-reports-section for month -->';
         }
         if($lastOfYear) {
            echo '</div> <!-- close attendance-reports-section for year -->';
         }

      }
   ?>
   <input type="hidden" name="report" value="attendance2" />
   <input type="submit" value="Download report" />
</form>

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
                                                                  OTHER REPORTS
--------------------------------------------------------------------------- -->

<a href="#" class="showhide_closed">Other reports</a>
<ul>
   <li>
      <a href="download_report.php?report=results">
         Deidentified participant results report
      </a>
   </li>
   <?php if(PRODUCT == 'dpp') { ?>
      <li>
         <a href="download_report.php?report=cdc">
            CDC report
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
         user_id,
         fname,
         lname
      from wrc_users
      where instructor = 1
      order by
         lname,
         fname
   ", array());

   foreach($ivqr as $row) {
      ?><li><?php
         echo $row['fname'] . ' ' . $row['lname'] . ' ';
      ?><a href="rosters.php?instr=<?php
         echo $row['user_id'];
      ?>">rosters</a> <a href="attendance_class_list.php?instr=<?php
         echo $row['user_id'];
      ?>">attendance</a></li><?php
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
