<?php
require_once("m_template.php");
generate_page(true, false);

function page_content() {
   $qr = current_class_and_sg();
   access_restrict($qr);
   participant_nav($qr['class_id'], $qr['class_source']);

   if(empty($qr)) {
      ?><p>
         Welcome to the <?php echo PRODUCT_TITLE; ?>
         application. You are not registered for a current class. Please
         e-mail us at <a href="<?php echo ADMIN_EMAIL; ?>">
         <?php echo ADMIN_EMAIL; ?></a> if you believe this is in error.
      </p><?php
   }
   else {
      ?><div id="smartgoal">
         <h2>SMART Goal</h2><?php
      sg_text($qr, $_GET['user'] == $_SESSION['user_id']);
      ?></div>

      <?php

      goalWeightCard($_GET['user'], $qr['class_id'], $qr['class_source']);

      $weekqr = seleqt_one_record("
         select weeks
         from classes_aw
         where
            class_id = ?
            and class_source = ?
      ", array($qr['class_id'], $qr['class_source']));
      $num_weeks = $weekqr['weeks'];

      /* 8/24/2016: see note in reports.php */

      $qr2 = pdo_seleqt("
         select
            r.week_id,
            r.weight
         from
            wrc_reports r
            inner join classes_aw c
               on r.class_id = c.class_id
               and r.class_source = c.class_source
         where
            r.user_id = ?
            and year(c.start_dttm) = ?
            and month(c.start_dttm) = ?
         order by r.create_dttm
      ", array(
            $_GET['user'],
            date('Y', strtotime($qr['start_dttm'])),
            date('n', strtotime($qr['start_dttm']))
      ));


      foreach($qr2 as $row) {
         $reports['weight'][$row['week_id']-1] = $row['weight'];
      }

      ?>
      <script type="text/javascript">
         function oldreport_confirm() {
            return confirm('WARNING: The date of the report you are ' +
                  'about to edit is more than a week in the past. ' +
                  'Continue?');
         }
      </script>
      <h2 id="reports">
         Weekly Tracker
      </h2>
      <table class="wrctable">
         <tr>
            <th>Date</th>
            <th>Weight</th>
         </tr>
      <?php
      $am_i_instructor = am_i_instructor();
      for($i=0; $i<$num_weeks; $i++) {
         ?><tr <?php echo ($i%2==0 ? "class='alt'" : ""); ?>><td><?php
         if(
            strtotime($qr['start_dttm'] . " + $i weeks")
            <
            strtotime(date(DATE_RSS) . " + 1 week")
         ) {
            // The report is in the past or within a week in the future.
            $link = true;
            if(
               strtotime($qr['start_dttm'] . " + $i weeks")
               <
               strtotime(date(DATE_RSS) . " - 1 week")
            ) {
               $warn = true;
            }
            else {
               $warn = false;
            }
            echo linkify(
               wrcdate($qr['start_dttm'] . " + $i weeks"), $i+1, $warn
            );
         }
         else {
            // The report is more than a week in the future.
            $link = false;
            echo wrcdate(htmlentities($qr['start_dttm']) . " + $i weeks");
         }
         ?><td class="center"><?php
         echo (isset($reports['weight'][$i]) ?
               zero_blank(htmlentities($reports['weight'][$i])) : "");
         ?></td></tr><?php
      }
      ?>
      </table>
      <p>
         If you transferred classes, the dates above are representative of
         your new class, and may differ from your previous class.
      </p>
      <?php
   }
}
?>
