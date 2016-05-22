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
         user_id,
         start_dttm,
         fname,
         lname,
         last_login,
         wt_chg,
         last_message_from,
         last_message_to,
         class_id
      from
         " . ENR_TBL . "
         natural join current_classes
         natural join wrc_users
         natural left join (
            select distinct user_id
            from wrc_messages
            where
               mread = ?
               and recip_id = ?
         ) m
         natural left join (
            select
               user_id,
               class_id,
               class_source,
               weight - starting_weight as wt_chg
            from
               wrc_reports r
               natural join (
                  select
                     user_id,
                     class_id,
                     class_source,
                     max(week_id) as week_id
                  from wrc_reports
                  where weight > 0
                  group by
                     user_id,
                     class_id,
                     class_source
               ) current_weight_limiter
               natural left join (
                  select
                     user_id,
                     class_id,
                     class_source,
                     weight as starting_weight
                  from wrc_reports
                  where week_id=1
               ) starting_weights
         ) weight_changes
      where
         instructor_id = ?
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
            <th>Weekly<br />tracker</th>
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
                           message entire class
                        </a>
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
                     <?php echo htmlentities($qr[$i]['lname'] . ", " .
                           $qr[$i]['fname']); ?>
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
                  <td class="center">
                     <a target="_blank" href="reports.php?user=<?php
                        echo $qr[$i]['user_id'];
                     ?>">
                        <img src="report.png" />
                     </a>
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
?>
