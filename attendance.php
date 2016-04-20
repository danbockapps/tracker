<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   /* Duplication of rosters.php code */
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }
   if(!isset($_GET['instr'])) {
      $_GET['instr'] = $_SESSION['user_id'];
   }
   if(!am_i_instructor($_GET['instr'])) {
      exit("The specified user is not an instructor.");
   }

   if(isset($_POST['formsubmitted'])) {
      $success = true;
      $aqr = pdo_seleqt("
         select *
         from latest_addresses
      ", array());

      foreach($_POST['user_id'] as $key => $user_id) {

         /* UPDATE ADDRESSES */

         foreach($aqr as $row) {
            if($row['user_id'] == $user_id) {
               /* Is new record required in wrc_addresses? */
               if(
                  $row['address1'] != $_POST['address1'][$key] ||
                  $row['address2'] != $_POST['address2'][$key] ||
                  $row['city']     != $_POST['city'][$key]     ||
                  $row['state']    != $_POST['state'][$key]    ||
                  $row['zip']      != $_POST['zip'][$key]
               ) {
                  /*insert*/
                  $dbh = pdo_connect("esmmwl_insert");
                  $sth = $dbh->prepare("
                     insert into wrc_addresses (
                        user_id,
                        address1,
                        address2,
                        city,
                        state,
                        zip
                     )
                     values (?, ?, ?, ?, ?, ?);
                  ");
                  if(!$sth->execute(array(
                     $user_id,
                     $_POST['address1'][$key],
                     $_POST['address2'][$key],
                     $_POST['city'][$key],
                     $_POST['state'][$key],
                     $_POST['zip'][$key]
                  ))) {
                     $success = false;
                     echo err_text("Address error with user " .
                                   htmlentities($user_id));
                  }
               }
            }
         }

         /* UPDATE ENROLLMENT TABLE */

         $dbh = pdo_connect("esmmwl_update");
         $sth = $dbh->prepare("
            update wrc_enrollment
            set
               numclasses = ?,
               shirtsize = ?,
               shirtcolor = ?
            where
               user_id = ?
               and class_id = ?
               and class_source = ?
         ");
         if(!$sth->execute(array(
            ($_POST['numclasses'][$key] == "--" ? null : $_POST['numclasses'][$key]),
            ($_POST['shirtsize'][$key] == "--" ? null : $_POST['shirtsize'][$key]),
            ($_POST['shirtcolor'][$key] == "--" ? null : $_POST['shirtcolor'][$key]),
            $_POST['user_id'][$key],
            $_POST['class_id'][$key],
            $_POST['class_source'][$key]
         ))) {
            $success = false;
            echo err_text("Error with user " .
                          htmlentities($_POST['user_id'][$key]));
         }
      }
      if($success) {
         echo cnf_text("Attendance data saved.");
      }
   } //End of formsubmitted conditional

   $class_ended_days_ago = 14;

   global $qr;
   $qr = pdo_seleqt("
      select
         u.user_id,
         u.fname,
         u.lname,
         e.class_id,
         e.class_source,
         e.numclasses,
         e.shirtsize,
         e.shirtcolor,
         c.start_dttm,
         zr.address1,
         zr.address2,
         zr.city,
         zr.state,
         zr.zip
      from
         wrc_users u
         natural join wrc_enrollment e
         natural join classes_aw c
         natural left join latest_addresses zr
      where
         c.instructor_id = ?
         and c.start_dttm + interval (c.weeks-1) week < now()
         and c.start_dttm + interval c.weeks week + interval ? day > now()
      order by
         c.start_dttm,
         u.lname,
         u.fname
   ", array($_GET['instr'], $class_ended_days_ago));

   ?>
   <script type="text/javascript">
      function refreshShirts() {
         $(".shirt").each(function(index) {
            if($(".numclasses:eq(" + index + ")").val() == 15) {
               $(this).css("visibility", "visible");
            }
            else {
               $(this).css("visibility", "hidden");
            }
         });
      }
      $(function() {
         refreshShirts();
      });
   </script>

   <h2>
      Attendance for instructor: <?php echo full_name($_GET['instr']); ?>
   </h2>

   <?php
      if(count($qr) == 0) {
         echo "There are no recently-ended classes for this instructor.";
      }
   ?>

   <form method="post" action="attendance.php?instr=<?php
      echo htmlentities($_GET['instr']);
   ?>">
      <?php
      for($i=0; $i<count($qr); $i++) {
         if(first_of_class($qr, $i)) {
            class_showhide($qr[$i]['start_dttm']);
            table_header();
         }
         ?>
         <tr>
            <td>
               <input
                  type="hidden"
                  name="user_id[<?php echo $i; ?>]"
                  value="<?php echo $qr[$i]['user_id']; ?>"
               />
               <input
                  type="hidden"
                  name="class_id[<?php echo $i; ?>]"
                  value="<?php echo $qr[$i]['class_id']; ?>"
               />
               <input
                  type="hidden"
                  name="class_source[<?php echo $i; ?>]"
                  value="<?php echo $qr[$i]['class_source']; ?>"
               />
               <?php
               echo htmlentities($qr[$i]['lname'] . ", " . $qr[$i]['fname']);
               ?>
            </td>
            <td class="center">
               <select
                  class="numclasses"
                  name="numclasses[<?php echo $i; ?>]"
                  onchange="refreshShirts()"
               >
                  <?php
                  opt("numclasses", "--", $i);
                  for($j=0; $j<=15; $j++) {
                     opt("numclasses", "$j", $i);
                  }
                  ?>
               </select>
            </td>
            <td>
               <span class="shirt">
                  Size:
                  <select name="shirtsize[<?php echo $i; ?>]">
                     <?php
                        opt("shirtsize", "--", $i);
                        opt("shirtsize", "S", $i);
                        opt("shirtsize", "M", $i);
                        opt("shirtsize", "L", $i);
                        opt("shirtsize", "XL", $i);
                        opt("shirtsize", "XXL", $i);
                     ?>
                  </select>
                  Color:
                  <select name="shirtcolor[<?php echo $i; ?>]">
                     <?php
                        opt("shirtcolor", "--", $i);
                        opt("shirtcolor", "Black", $i);
                        opt("shirtcolor", "Brown", $i);
                        opt("shirtcolor", "Navy", $i);
                        opt("shirtcolor", "Gray", $i);
                        opt("shirtcolor", "Pink", $i);
                        opt("shirtcolor", "Purple", $i);
                        opt("shirtcolor", "Orange", $i);
                     ?>
                  </select>
               </span>
            </td>
            <td>
               <input
                  type="text"
                  name="address1[<?php echo $i; ?>]"
                  size="15"
                  value="<?php echo $qr[$i]['address1']; ?>"
               />
               <input
                  type="text"
                  name="address2[<?php echo $i; ?>]"
                  size="6"
                  value="<?php echo $qr[$i]['address2']; ?>"
               />
               <input
                  type="text"
                  name="city[<?php echo $i; ?>]"
                  size="6"
                  value="<?php echo $qr[$i]['city']; ?>"
               />
               <input
                  type="text"
                  name="state[<?php echo $i; ?>]"
                  size="1"
                  maxlength="2"
                  value="<?php echo $qr[$i]['state']; ?>"
               />
               <input
                  type="text"
                  name="zip[<?php echo $i; ?>]"
                  size="3"
                  maxlength="10"
                  value="<?php echo $qr[$i]['zip']; ?>"
               />
            </td>
         </tr>
         <?php
         if(last_of_class($qr, $i)) {
            ?></table><?php
         }
      }
   ?>
   <input type="hidden" name="formsubmitted" value="true" />
   <input type="submit" value="Save" />
   </form>
<?php
}

function opt($col, $val, $i) {
   global $qr;
   ?><option value="<?php
   echo $val;
   ?>"<?php
   if(is_null($qr[$i][$col]) && $val == "--" || $qr[$i][$col] == $val) {
      ?> selected<?php
   }
   ?>><?php
   echo $val;
   ?></option>
   <?php
}

function first_of_class($qr, $i) {
   if($i == 0) {
      return true;
   }
   else if($qr[$i]['start_dttm'] != $qr[$i-1]['start_dttm']) {
      return true;
   }
   else {
      return false;
   }
}

function last_of_class($qr, $i) {
   if($i == count($qr) - 1) {
      return true;
   }
   else if($qr[$i]['start_dttm'] != $qr[$i+1]['start_dttm']) {
      return true;
   }
   else {
      return false;
   }
}

function class_showhide($start_dttm) {
   ?>
   <a href="#" class="showhide_closed">Class starting
   <?php
   echo wrcdttm($start_dttm);
   ?>
   </a>
   <?php
}

function table_header() {
   ?>
   <table class="wrctable" style="margin-bottom:1em">
      <tr>
         <th>Name</th>
         <th>Classes<br />attended</th>
         <th>T-shirt order</th>
         <th>Address</th>
      </tr>
   <?php
}

?>
