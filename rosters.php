<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }
   if(!isset($_GET['instr'])) {
      $_GET['instr'] = $_SESSION['user_id'];
   }
   if(!am_i_instructor($_GET['instr'])) {
      exit("The specified user is not an instructor.");
   }

   if(!isset($_GET['sort'])) {
      /* By default, sort by class start. */
      $_GET['sort'] = "cs";
   }
   $orderby = "";
   if(isset($_GET['rev']) && $_GET['rev'] == "true") {
      if($_GET['sort'] == "cs") {
         $orderby = "order by start_dttm, lname desc, fname desc";
      }
      else if($_GET['sort'] == "n") {
         $orderby = "order by lname desc, fname desc";
      }
      else if($_GET['sort'] == "fn") {
         $orderby = "order by fname desc, lname desc";
      }
      else if($_GET['sort'] == "ll") {
         $orderby = "order by last_login";
      }
      else if($_GET['sort'] == "wc") {
         $orderby = "order by -wt_chg";
      }
      else if($_GET['sort'] == "ltp") {
         $orderby = "order by last_message_to";
      }
      else if($_GET['sort'] == "lfp") {
         $orderby = "order by last_message_from";
      }
   }
   else {
      if($_GET['sort'] == "cs") {
         $orderby = "order by start_dttm desc, lname, fname";
      }
      else if($_GET['sort'] == "n") {
         $orderby = "order by lname, fname";
      }
      else if($_GET['sort'] == "fn") {
         $orderby = "order by fname, lname";
      }
      else if($_GET['sort'] == "ll") {
         $orderby = "order by last_login desc";
      }
      else if($_GET['sort'] == "wc") {
         $orderby = "order by -wt_chg desc";
      }
      else if($_GET['sort'] == "ltp") {
         $orderby = "order by last_message_to desc";
      }
      else if($_GET['sort'] == "lfp") {
         $orderby = "order by last_message_from desc";
      }
   }

   $qr = pdo_seleqt("
      select
         case
            when m.user_id is null then false
            else true
         end as unread,
         e.user_id,
         c.start_dttm,
         u.fname,
         u.lname,
         u.last_login,
         lrww.weight - frww.weight as wt_chg,
         (lrww.weight - frww.weight) / frww.weight as wt_chg_pct,
         u.last_message_from,
         u.last_message_to,
         e.class_id,
         u.email
      from
         " . ENR_VIEW . " e
         inner join current_classes_for_rosters c
            on e.class_id = c.class_id
            and e.class_source = c.class_source 
         inner join wrc_users u
            on e.user_id = u.user_id
         left join (
            select distinct user_id
            from wrc_messages
            where
               mread = ?
               and recip_id = ?
         ) m
            on e.user_id = m.user_id
         left join first_reports_with_weights frww
            on e.user_id = frww.user_id
            and e.class_id = frww.class_id
            and e.class_source = frww.class_source
         left join last_reports_with_weights lrww
            on e.user_id = lrww.user_id
            and e.class_id = lrww.class_id
            and e.class_source = lrww.class_source
      where
         c.instructor_id = ?
   " . $orderby, array(0, $_GET['instr'], $_GET['instr']));
   ?>
   <h2>
      Rosters for instructor: <?php echo full_name($_GET['instr']); ?>
   </h2>
   <?php
   if(count($qr) == 0) {
      ?><i>This instructor has no classes with students.</i><?php
   }
   else {
   ?>
      <form action="multi_message.php" method="get" id="multi_message">
      <table class="wrctable rosters">
         <tr>
            <th><!-- Header for checkboxes --></th>
            <th>
               <?php rosters_th_link("Last", "n"); ?>
               <?php rosters_th_link("First", "fn"); ?>
            </th>
            <?php rosters_th("Class start", "cs"); ?>
            <?php rosters_th("Last login", "ll"); ?>
            <?php rosters_th("Last message<br />to participant", "ltp"); ?>
            <?php rosters_th("Last message<br />from participant", "lfp"); ?>
            <?php rosters_th("Weight<br />change", "wc"); ?>
            <?php rosters_th("Percent<br />change", "pc"); ?>
            <th>Messages</th>
         </tr>
         <?php
            for($i = 0; $i < count($qr); $i++) {
               if(
                  $_GET['sort'] == "cs" && (
                     $i == 0 ||
                     $qr[$i]['start_dttm'] != $qr[$i-1]['start_dttm']
                  )
               ) {
                  ?>

                  <tr class="header_row">
                     <td class="center" colspan="9">
                        <span><?php echo class_times($qr[$i]['start_dttm']); ?></span>
                     </td>
                  </tr>

                  <tr>
                     <td colspan="9">
                        <a href="javascript:mailClass(<?php
                           echo $qr[$i]['class_id'];
                        ?>)">
                           message entire class</a>
                        &nbsp;
                        <a href="#" class="showEmailLink">
                           show email addresses
                        </a>
                     </td>
                  </tr>

                  <tr class="emailList" style="display:none">
                     <td colspan="9">
                        <p style="font-style:italic">
                           This list can be copied and pasted into your email client.
                        </p>
                        <p>
                           <?php printEmailAddresses($qr[$i]['class_id'], $qr); ?>
                        </p>
                     </td>
                  </tr>

                  <?php
               }
               ?>

               <tr<?php if($i % 2 == 1) { echo " class='alt'"; } ?>>
                  <td>
                     <input type="checkbox" name="mm[<?php
                        echo $qr[$i]['user_id'];
                     ?>]" class="class<?php
                        echo $qr[$i]['class_id'];
                     ?>" />
                  </td>
                  <td>
                     <a target="_blank" href="reports.php?user=<?php
                        echo $qr[$i]['user_id'];
                     ?>">
                        <?php echo htmlentities($qr[$i]['lname'] . ", " .
                              $qr[$i]['fname']); ?>
                     </a>
                  </td>
                  <td>
                     <?php echo rstr_date($qr[$i]['start_dttm']); ?>
                  </td>
                  <td
                     title="<?php echo rstr_date($qr[$i]['last_login']); ?>"
                     class="center"
                  >
                     <?php echo relative_time($qr[$i]['last_login']); ?>
                  </td>
                  <td
                     title="<?php echo rstr_date($qr[$i]['last_message_to']); ?>"
                     class="center"
                  >
                     <?php echo relative_time($qr[$i]['last_message_to']); ?>
                  </td>
                  <td
                     title="<?php echo rstr_date($qr[$i]['last_message_from'])?>"
                     class="center"
                  >
                     <?php echo relative_time($qr[$i]['last_message_from']); ?>
                  </td>
                  <td class="center<?php
                     if($qr[$i]['wt_chg'] < 0) {
                        echo " green";
                     }
                  ?>">
                     <?php
                        if($qr[$i]['wt_chg'] >= 0 && $qr[$i]['wt_chg'] != null) {
                           echo "+";
                        }
                        echo $qr[$i]['wt_chg'];
                     ?>
                  </td>
                  <td class="center<?php
                     if(isset($qr[$i]['wt_chg_pct']) && $qr[$i]['wt_chg_pct'] <= -.05) {
                        echo " green";
                     }
                  ?>">
                     <?php
                        if(isset($qr[$i]['wt_chg_pct']))
                           echo round($qr[$i]['wt_chg_pct'] * 100, 1) . '%';
                     ?>
                  </td>
                  <td class="center">
                      <a target="_blank" href="all_messages.php?user=<?php
                         echo $qr[$i]['user_id'];
                      ?>">
                         <img src="message<?php
                            echo ($qr[$i]['unread'] ? "" : "_bw");
                         ?>.png" />
                      </a>
                   </td>
               </tr>
               <?php
            }
         ?>

      </table>
      <input type="submit" value="Message participant(s)"></input>
      </form>
      <p>
         <img src="message.png" /> = New unread messages
      </p>

      <script>
         function mailClass(class_id) {
            $("input:checkbox").prop("checked", false);
            $(".class" + class_id).prop("checked", true);
            $("#multi_message").submit();
         }

         $('.showEmailLink').click(function(e) {
            $(this).parent().parent().parent().find('.emailList').show();
            e.preventDefault();
         });
      </script>
      <?php
   }
}

function rosters_th($header, $sort_get_var) {
   ?><th><?php
   rosters_th_link($header, $sort_get_var);
   ?></th><?php
}

function rosters_th_link($header, $sort_get_var) {
   ?><a href="rosters.php?instr=<?php
      echo htmlentities($_GET['instr']);
   ?>&sort=<?php
      echo $sort_get_var;
      if(
         isset($_GET['sort']) &&
         $_GET['sort'] == $sort_get_var &&
         !isset($_GET['rev'])
      ) {
         ?>&rev=true<?php
      }
   ?>"><?php
      echo $header;
   ?> <img src="sort_icon.png" /></a><?php
}

function printEmailAddresses($classId, $qr) {
   $emailArray = array();

   foreach($qr as $row) {
      if($row['class_id'] == $classId) {
         $emailArray[] = $row['email'];
      }
   }

   echo implode(',', $emailArray);
}
?>
