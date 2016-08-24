<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   $qr = current_class_and_sg();
   access_restrict($qr);

   if(empty($qr)) {
      ?><p>
         Welcome to the <?php echo PRODUCT_TITLE; ?>
         application. You are not registered for a current class. Please
         e-mail us at <a href="mailto:<?php echo ADMIN_EMAIL; ?>">
         <?php echo ADMIN_EMAIL; ?></a> if you believe this is in error.
      </p><?php
   }
   else {
      participant_nav($qr['class_id'], $qr['class_source']);
      ?>
      <div id="smartgoal" class="card">
         <h3>SMART Goal</h3>

         <div style="min-height:220px; padding:0 10px 10px 10px;">
            <?php sg_text($qr, $_GET['user'] == $_SESSION['user_id']); ?>
         </div>

         <div class="cardfooter">
            <?php sg_popup(); ?>
         </div>
      </div>

      <?php
      $weekqr = seleqt_one_record("
         select weeks
         from classes_aw
         where
            class_id = ?
            and class_source = ?
      ", array($qr['class_id'], $qr['class_source']));
      $num_weeks = $weekqr['weeks'];

      /*
      8/24/2016: This query now pulls reports for any class that started in
      the same month as the participant's current class. So if the
      participant transfers from one class to another that starts in the
      same month, no data has to be transferred. (If the participant
      transfers to another class in a different month, the data disappears
      from their view, as intended.)
      */

      $qr2 = pdo_seleqt("
         select
            week_id,
            weight,
            aerobic_minutes,
            strength_minutes,
            physact_minutes,
            notes
         from
            wrc_reports r
            inner join classes_aw c 
               on r.class_id = c.class_id
               and r.class_source = c.class_source
         where
            r.user_id = ?
            and year(c.start_dttm) = ?
            and month(c.start_dttm) = ?
         order by r.create_dttm /* reports created earlier will be overwritten
                                   in the next step by reports created later. */
      ", array(
            $_GET['user'],
            date('Y', strtotime($qr['start_dttm'])),
            date('n', strtotime($qr['start_dttm']))
      ));

      $reports_empty = true;
      foreach($qr2 as $row) {
         if($row['weight'] != 0) {
            $reports_empty = false;
         }
         $reports['weight'][$row['week_id']-1] = $row['weight'];
         $reports['aerobic'][$row['week_id']-1] = $row['aerobic_minutes'];
         $reports['strength'][$row['week_id']-1] = $row['strength_minutes'];
         $reports['physact'][$row['week_id']-1] = $row['physact_minutes'];
         $reports['notes'][$row['week_id']-1] = $row['notes'];
      }
      ?>
      <script type="text/javascript" src="https://www.google.com/jsapi"></script>
      <script>
         google.load("visualization", "1", {packages:["corechart"]});
         <?php if(!$reports_empty) { ?>
            google.setOnLoadCallback(drawChart);
         <?php } ?>
         function drawChart() {
            var data = google.visualization.arrayToDataTable([
               ['Week', 'Weight'],
               <?php
                  for($i=0; $i<$num_weeks; $i++) {
                     echo "['" . date("n/j", strtotime($qr['start_dttm'] .
                           " + $i weeks")) . "'," .
                           (isset($reports['weight'][$i]) &&
                           $reports['weight'][$i] != 0 ?
                           $reports['weight'][$i] : "null") . "]";
                     if($i != $num_weeks - 1) {
                        echo ",";
                     }
                  }
               ?>
            ]);

            var options = {
               width: 462,
               height: 236,
               pointSize: 6,
               colors: ['<?php
                  if(PRODUCT ==   'dpp') {
               ?>#80298f<?php
                  }
                  else {
               ?>#8ec63f<?php
                  }
               ?>'],
               backgroundColor: '<?php
                  if(PRODUCT == 'dpp') {
               ?>#eee<?php
                  }
                  else {
               ?>#eaffd0<?php
                  }
               ?>',
               legend: {position: 'none'}
            };

            var chart = new google.visualization.LineChart(
               document.getElementById('timeseries')
            );
            chart.draw(data, options);
         }
      </script>

      <div id="chart_div" class="card">
         <h3>Weight Tracker</h3>
         <div id="timeseries">
            <!-- text appears only when there's no graph -->
            <span class="nodata">
               Your weight tracker graph will appear after you enter your
               weight in your first weekly report (below).
            </span>
         </div>
      </div>

      <div id="pes" class="card">
         <h3>
            Engagement Score
         </h3>
         <script>
            $(function() {
               $.getJSON(
                  "pes.php",
                  {
                     user:<?php
                        echo htmlentities($_GET['user']);
                     ?>, class:<?php
                        echo htmlentities($qr['class_id']);
                     ?>, source:"<?php
                        echo htmlentities($qr['class_source']);
                     ?>"
                  }
               )
               .done(function(rawdata) {
                  $("#scorenote").append(rawdata[1][1]);

                  var data = google.visualization.arrayToDataTable(rawdata);
                  new google.visualization.ColumnChart(
                     document.getElementById('pes_ajax')
                  ).draw(
                     data,
                     {
                        width: 189,
                        height: 200,
                        legend: { position: "none" },
                        colors: ["<?php
                           if(PRODUCT ==   'dpp') {
                        ?>#80298f<?php
                           }
                           else {
                        ?>#8ec63f<?php
                           }
                        ?>"],
                        backgroundColor: "<?php
                           if(PRODUCT ==   'dpp') {
                        ?>#eee<?php
                           }
                        else {
                        ?>#eaffd0<?php
                           }
                        ?>",
                        vAxis: {viewWindow: {min: 0}}
                     }
                  );
                  $("#pes_ajax").css("padding-left", "18px");
               })
               .fail(function() {
                  $("#pes_ajax").html("An error occurred.");
               });
            });
         </script>
         <div id="scorenote" style="text-align:center; font-weight:bold; font-size:1.5em">
         </div>
         <div id="pes_ajax">
            <img src="spinner.gif" style="display:block; margin:114px auto" />
         </div>
         <div class="cardfooter">
            <?php
            popup(
               "What is an Engagement Score?",
               "Your Engagement Score tells you how active you are on the " .
               "Weekly Tracker compared to the average Eat Smart, Move More, " .
               "Weigh Less participant. We think that how active you are on " .
               "the Weekly Tracker is strongly associated with how engaged " .
               "you are with the Eat Smart, Move More, Weigh Less program; " .
               "therefore higher Engagement Scores should predict greater " .
               "success in achieving your Eat Smart, Move More, Weigh Less " .
               "goals. Weekly Tracker Engagement Scores can be increased by " .
               "doing things like: 1) setting SMART goals, 2) tracking " .
               "strategies, 3) submitting weekly weight and minutes of " .
               "physical activity and 4) sending messages to your " .
               "instructor. We hope that the ability to see your Engagement " .
               "Score will encourage you to work to increase it, resulting " .
               "in greater engagement and ultimately greater success with " .
               "the program!",
               "What is an Engagement Score?"
            );
            ?>
         </div>
      </div>

      <script type="text/javascript">
         function oldreport_confirm() {
            return confirm('WARNING: The date of the report you are ' +
                  'about to edit is more than a week in the past. ' +
                  'Continue?');
         }
      </script>
      <div id="tracker">
      <h2 style="text-align:center">
         Weekly Tracker
      </h2>
      <table class="wrctable" style="width:100%">
         <tr>
            <th>Date</th>
            <th>Weight</th>
            <th>Net <br /> loss/gain</th>

            <?php
               /**************************************************************/
               /* COLUMN HEADERS FOR DIFFERENT PRODUCTS
               ***************************************************************/

               global $ini;
               if($ini['product'] == 'esmmwl') {
                  ?>
                  <th>Minutes of <br /> aerobic activity</th>
                  <th>Minutes of <br /> strength training</th>
                  <?php
               }
               else if($ini['product'] == 'dpp') {
                  ?>
                  <th>Minutes of <br /> physical activity</th>
                  <?php
               }
            ?>

            <th>Instructor <br /> feedback</th>
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
            echo htmlentities(linkify(
               wrcdate($qr['start_dttm'] . " + $i weeks"), $i+1, $warn
            ));
         }
         else {
            // The report is more than a week in the future.
            $link = false;
            echo wrcdate(htmlentities($qr['start_dttm']) . " + $i weeks");
         }
         ?><td class="center"><?php
         echo (isset($reports['weight'][$i]) ?
               zero_blank($reports['weight'][$i]) : "");
         ?></td><td class="center"><?php
         echo (isset($reports['weight'][$i]) && isset($reports['weight'][0])
               && $reports['weight'][$i] != 0 ?
               round($reports['weight'][$i] - $reports['weight'][0], 1) : "");
         ?></td>

         <?php
            /**************************************************************/
            /* TABLE CELLS FOR DIFFERENT PRODUCTS
            ***************************************************************/

            if($ini['product'] == 'esmmwl') {
               ?>
               <td class="center"><?php
               echo (isset($reports['aerobic'][$i]) ?
                     zero_blank($reports['aerobic'][$i]) : "");
               ?></td>
               <td class="center"><?php
               echo (isset($reports['strength'][$i]) ?
                     zero_blank($reports['strength'][$i]) : "");
               ?></td>
               <?php
            }
            else if($ini['product'] == 'dpp') {
               ?>
               <td class="center"><?php
               echo (isset($reports['physact'][$i]) ?
                     zero_blank($reports['physact'][$i]) : "");
               ?></td>
               <?php
            }
         ?>


         <td class="center"><?php
            if($link) {
               if(isset($reports['notes'][$i]) && $reports['notes'][$i] != "") {
                  echo linkify("<img src='chat.png' />", $i+1, false);
               }
               else if($am_i_instructor) {
                  echo linkify("<img src='chat_bw.png' />", $i+1, false);
               }
            }
         ?></td></tr><?php
      }
      ?>
      </table>
      </div>
      <p id="xfernote">
         If you transferred classes, the dates above are representative of
         your new class, and may differ from your previous class.
      </p>
      <?php
   }
}
?>
