<?php
if(!isset($reportphp_mode)) {
   $reportphp_mode = "";
}
require_once($reportphp_mode . "template.php");
generate_page(true, false);

function page_content() {
   global $ini;
   $qr = current_class_and_sg();
   access_restrict($qr);
   participant_nav($qr['class_id'], $qr['class_source']);

   if(!isset($_GET['week'])) {
      exit(err_text("No week specified."));
   }

   if(empty($qr)) {
      exit(err_text("User is not registered for a class."));
   }

   $report_date = $qr['start_dttm'] . " + " . ($_GET['week'] - 1) . " weeks";

   if(strtotime($report_date) > strtotime(date(DATE_RSS) . " + 1 week")) {
      exit(err_text("Too early to create report for " .
            htmlentities(wrcdate($report_date))));
   }

   ?>
   <script>
      function delStratConfirm() {
         return confirm('Are you sure you want to delete this strategy ' +
               'from your list? This will delete it from all weeks.');
      }
   </script>
   <?

   // Delete row from strategy table if so requested
   if(isset($_GET['delete']) && $_GET['user'] == $_SESSION['user_id']) {
      $dbh = pdo_connect($ini['db_prefix'] . "_delete");
      $sth = $dbh->prepare("
            delete from wrc_strategy_user
            where
               user_id = ?
               and strategy_id = ?
         ");
      if($sth->execute(array($_GET['user'], $_GET['delete']))) {
         echo cnf_text("Strategy deleted.");
      }
      else {
         exit(err_text("A database error occurred deleting the strategy."));
      }
   }


   if(isset($_POST['formsubmitted'])) {
      foreach($_POST as $key => $post_item) {
         if(!is_array($_POST[$key])) {
            $_POST[$key] = trim($_POST[$key]);
         }
      }

      if(isset($_POST['notes'])) {
         sendById($_GET['user'], 3);
      }

      // If record doesn't exist, create it.
      $uqr = seleqt_one_record("
         select count(*) as count
         from wrc_reports
         where
            user_id = ?
            and class_id = ?
            and class_source = ?
            and week_id = ?
      ", array($_GET['user'], $qr['class_id'], $qr['class_source'], $_GET['week']));
      if($uqr['count'] == 0) {
         $dbh = pdo_connect($ini['db_prefix'] . "_insert");
         $sth = $dbh->prepare("
            insert into wrc_reports (
               user_id,
               class_id,
               class_source,
               week_id
            ) values (?, ?, ?, ?)
         ");
         if(!$sth->execute(array(
            $_GET['user'],
            $qr['class_id'],
            $qr['class_source'],
            $_GET['week']
         ))) {
            exit(err_text("A database error occurred creating the report."));
         }
      }

      if(isset($_POST['strategy'])) {
         // Insert rows into strategy table where needed
         $selects = array();
         foreach(array_keys($_POST['strategy']) as $row) {
            $selects[] = "select " . $row . " as strategy_id";
         }

         $dbh = pdo_connect($ini['db_prefix'] . "_inssel");
         $sth = $dbh->prepare("
            insert into wrc_strategy_report
            select
               ? as user_id,
               ? as class_id,
               ? as class_source,
               ? as week_id,
               strategy_id,
               null as num_days
            from (
               " . implode(" union ", $selects) . "
            ) posted_strategies
            where strategy_id not in (
               select strategy_id
               from wrc_strategy_report
               where
                  user_id = ?
                  and class_id = ?
                  and class_source = ?
                  and week_id = ?
            )
         ");
         $sth->execute(array(
            $_GET['user'],
            $qr['class_id'],
            $qr['class_source'],
            $_GET['week'],
            $_GET['user'],
            $qr['class_id'],
            $qr['class_source'],
            $_GET['week']
         ));

         // Update strategy table with posted values
         $whens = array();
         foreach($_POST['strategy'] as $key => $row) {
            if($row >= 0) {
               // If -1 is posted, leave as null in database
               $whens[] = "when " . $key . " then " . $row;
            }
         }

         if(count($whens) > 0) {
            $dbh = pdo_connect($ini['db_prefix'] . "_update");
            $sth = $dbh->prepare("
               update wrc_strategy_report set
                  num_days = case strategy_id
                     " . implode(" ", $whens) . "
                  end
               where
                  user_id = ?
                  and class_id = ?
                  and class_source = ?
                  and week_id = ?
            ");
            $sth->execute(array(
               $_GET['user'],
               $qr['class_id'],
               $qr['class_source'],
               $_GET['week']
            ));
         }

         // New custom strategy
         if(
               isset($_POST['newstrat_desc']) &&
               $_POST['newstrat_desc'] != "" &&
               $_GET['user'] == $_SESSION['user_id']
         ) {
            $dbh = pdo_connect($ini['db_prefix'] . "_inssel");
            $sth = $dbh->prepare("
               insert into wrc_strategies
               (strategy_description) values (?)
            ");
            if(!$sth->execute(array($_POST['newstrat_desc']))) {
               echo err_text("A database error occurred with the strategy (1).");
            }
            $last_insert_id = $dbh->lastInsertId("wrc_strategies");

            $sth = $dbh->prepare("
               insert into wrc_strategy_user
               (user_id, strategy_id) values (?, ?)
            ");
            if(!$sth->execute(array($_GET['user'], $last_insert_id))) {
               echo err_text("A database error occurred with the strategy (2).");
            }

            $sth = $dbh->prepare("
               insert into wrc_strategy_report
               (user_id, class_id, class_source, week_id, strategy_id, num_days)
               values (?, ?, ?, ?, ?, ?)
            ");
            if(!$sth->execute(array(
                  $_GET['user'],
                  $qr['class_id'],
                  $qr['class_source'],
                  $_GET['week'],
                  $last_insert_id,
                  (
                     $_POST['newstrat_numdays'] >= 0 ?
                     $_POST['newstrat_numdays'] :
                     null
                  )
            ))) {
               echo err_text("A database error occurred with the strategy (3).");
            }
         }
      }
   }

   ?>
   <script type="text/javascript">
      function calcBmi() {
         if(
            $("input[name=height_feet]").length &&
            // if the height_feet input exists
            $.trim($("input[name=height_feet]").val()) != ""
         ) {
            $("#height_inches").text(
               Math.floor($("input[name=height_feet]").val()) * 12 +
               Math.max(0, Math.min($("input[name=height_inches]").val(), 12))
            );
         }
         if(
            $.isNumeric($("#height_inches").html()) &&
            $.isNumeric($("input[name=weight]").val())
         ) {
            var bmi = $("input[name=weight]").val() * 703.06957964 /
                  ($("#height_inches").html() * $("#height_inches").html());
            $("#bmi_num").text(bmi.toFixed(2));
         }
      }

      function submitButtonText() {
         if($("input[name=newstrat_desc]").val() == "") {
            $("#reportsubmit").attr("value", "Submit changes");
         }
         else {
            $("#reportsubmit").attr("value", "Submit changes and new strategy");
         }
      }

      var warnMessage = "You have unsaved changes on this page.";
      $(document).ready(function() {
          $('input:not(:button,:submit),textarea,select').change(function () {
              window.onbeforeunload = function () {
                  if (warnMessage != null) return warnMessage;
              };
          });
          $('input:submit').click(function(e) {
              warnMessage = null;
          });
      });
   </script>
   <h2>
      Report for the week of
      <?php echo htmlentities(wrcdate($report_date)); ?>
   </h2>
   <form id="report_form"
         action="report.php?week=<?php
            echo htmlentities($_GET['week']);
         ?>&user=<?php
            echo htmlentities($_GET['user']);
         ?>"
         method="post">
      <fieldset style="margin-bottom: 0.3em">
         <table>
         <?php
            $err_count = 0;
            $err_count += report_var(
               "weight",
               $qr['class_id'],
               $qr['class_source'],
               "weight",
               "Weight (pounds)",
               "instructions",
               "Try to weigh in at the same day and time on the same " .
                     "scale every week.",
               "Weight instructions",
               true
            );

            if(
               isset($_POST['formsubmitted']) &&
               isset($_POST['height_feet'])
            ) {
               if(
                  (
                     isset($_POST['height_inches']) && (
                        $_POST['height_inches'] >= 12 ||
                        !is_numeric($_POST['height_inches'])
                     )
                  )
                  ||
                  (
                     isset($_POST['height_feet']) &&
                     !is_numeric($_POST['height_feet'])
                  )
               ) {
                  $err_count++;
                  echo err_text("Invalid value for height.");
               }
               else if(!is_int($_POST['height_feet'] + 0)) {
                  $err_count++;
                  echo err_text("Invalid value for height feet.");
               }
               else {
                  // height is submitted and values are valid.
                  $height_total = $_POST['height_feet'] * 12;
                  if(isset($_POST['height_inches'])) {
                     $height_total += $_POST['height_inches'];
                  }
                  $dbh = pdo_connect($ini['db_prefix'] . "_update");
                  $sth = $dbh->prepare("
                     update wrc_users
                     set height_inches = ?
                     where user_id = ?
                  ");
                  if($sth->execute(array($height_total, $_GET['user']))) {
                     // Success
                  }
                  else {
                     $err_count++;
                     echo err_text("There was a database error saving your height.");
                  }
               }
            }

            $first_class = first_class();
            $last_class = last_class($qr['class_id'], $qr['class_source']);
            if($first_class) {
               $hiqr = seleqt_one_record("
                  select height_inches
                  from wrc_users
                  where user_id = ?
               ", array($_GET['user']));
               if($hiqr['height_inches'] != null) {
                  $height_feet = round(($hiqr['height_inches'] - 6 )/ 12);
                  $height_inches = $hiqr['height_inches'] - $height_feet * 12;
               }
               ?>
               <tr><td>
                  <table style="width: 100%">
                     <tr style="width: 100%">
                        <td style="text-align: left">
                           Height
                        </td>
                        <td style="text-align: right">
                           Feet:
                        </td>
                     </tr>
                  </table>
               </td><td>
                  <?php
                     if($_SESSION['user_id'] == $_GET['user']) {
                        ?>
                        <input size="3" type="text" name="height_feet"<?php
                           if(isset($height_feet)) {
                              echo " value=\"" . zero_blank(
                                    $height_feet) . "\"";
                           }
                        ?> onkeyup="calcBmi();" />
               </td></tr>
               <tr><td style="text-align: right">
                  Inches:
               </td><td>
                        <input size="3" type="text" name="height_inches"<?php
                           if(isset($height_inches)) {
                              echo " value=\"" . zero_blank(
                                    $height_inches) . "\"";
                           }
                        ?> onkeyup="calcBmi();" />
                        <?php
                     }
                     else {
                        ?><b><?php
                        echo isset($height_feet) ? htmlentities(zero_blank($height_feet)) : "";
                        ?></b></td></tr><tr><td style="text-align:right">Inches:</td><td><b><?php
                        echo isset($height_inches) ? htmlentities(zero_blank($height_inches)) : "";
                        ?></b><?php
                     }
                  ?>
               </td></tr>
               <?php
            }

            global $ini;
            if($ini['product'] == 'esmmwl') {
               $err_count += report_var(
                  "aerobic",
                  $qr['class_id'],
                  $qr['class_source'],
                  "aerobic_minutes",
                  "Minutes of aerobic activity",
                  "instructions",
                  "Use this field to record the number of minutes you spent " .
                     "engaged in aerobic activities like walking, gardening, " .
                     "or using an elliptical machine over the past week.",
                  "Aerobic activity instructions",
                  true
               );

               $err_count += report_var(
                  "strength",
                  $qr['class_id'],
                  $qr['class_source'],
                  "strength_minutes",
                  "Minutes of strength training",
                  "instructions",
                  "Use this field to record the number of minutes you spent " .
                     "engaged in strength training activities like yoga, lifting " .
                     "weights, using stretch bands, or doing push-ups over the " .
                     "past week.",
                  "Strength training instructions",
                  true
               );
            }

            else if($ini['product'] == 'dpp') {
               $err_count += report_var(
                  "physact",
                  $qr['class_id'],
                  $qr['class_source'],
                  "physact_minutes",
                  "Minutes of physical activity",
                  null,
                  null,
                  null,
                  true
               );
            }

            if($first_class || $last_class) {
               $err_count += report_var(
                  ($first_class ? "syst_start" : "syst_end"),
                  $qr['class_id'],
                  $qr['class_source'],
                  ($first_class ? "syst_start" : "syst_end"),
                  "Systolic blood pressure",
                  "instructions",
                  "Use this field to record the top number in your blood " .
                     "pressure reading. You will only be recording your " .
                     "blood pressure for Eat Smart, Move More, Weigh Less " .
                     "twice: once at the beginning and once at the end of " .
                     "the 15 weeks. Try " .
                     "to use the same monitor both times. High blood " .
                     "pressure is a risk factor for stroke and heart " .
                     "disease. Healthy eating and physical activity can help " .
                     "you achieve and maintain a healthy blood pressure.",
                  "Systolic blood pressure instructions",
                  false
               );

               $err_count += report_var(
                  ($first_class ? "dias_start" : "dias_end"),
                  $qr['class_id'],
                  $qr['class_source'],
                  ($first_class ? "dias_start" : "dias_end"),
                  "Diastolic blood pressure",
                  "instructions",
                  "Use this field to record the bottom number in your " .
                     "blood pressure reading. You will only be recording " .
                     "your blood pressure for Eat Smart, Move More, Weigh " .
                     "Less twice: once at the beginning and once at the end " .
                     "of the 15 weeks. " .
                     "Try to use the same monitor both times. High blood " .
                     "pressure is a risk factor for stroke and heart " .
                     "disease. Healthy eating and physical activity can help " .
                     "you achieve and maintain a healthy blood pressure.",
                  "Diastolic blood pressure instructions",
                  false
               );

               $err_count += report_var(
                  ($first_class ? "waist_start" : "waist_end"),
                  $qr['class_id'],
                  $qr['class_source'],
                  ($first_class ? "waist_start" : "waist_end"),
                  "Waist circumference",
                  "instructions",
                  "To measure your waist circumference, position a " .
                     "measuring tape around your waist (right above your " .
                     "belly button), take a deep breath, and then exhale. " .
                     "Take the measurement after you have exhaled. You will " .
                     "only be recording your waist circumference for Eat " .
                     "Smart, Move More, Weigh Less twice: once at the " .
                     "beginning and once " .
                     "at the end of the 15 weeks. Try to measure on the same " .
                     "place on your waist both times. Your waist " .
                     "circumference is a measure that has been linked to " .
                     "your risk for heart disease, high blood pressure, " .
                     "diabetes and certain cancers.",
                  "Waist circumference instructions",
                  false
               );
            }

            if($ini['product'] == 'dpp') {
               $err_count += report_var(
                  'a1c',
                  $qr['class_id'],
                  $qr['class_source'],
                  'a1c',
                  'A1C level &#40;&#37;&#41;',
                  null,
                  null,
                  null,
                  true
               );
            }
         ?>
         </table>
      </fieldset>

      <?php if(!$ini['hide_strategies']) { ?>

      <script type="text/javascript">
         $(function() {
             $("#showhide_strat").showhide({
                target_obj: $('#strategy_fieldset'),
                default_open: true,
                use_cookie: true,
                cookie_name: "showhide_strat"
             });
          });
      </script>
      <a href="#" id="showhide_strat" style="color:#000">Strategies</a>
      <?php
         popup(
            "instructions",
            "These are weight management strategies you will work on during " .
            "your Eat Smart, Move More, Weigh Less class. Being more " .
            "mindful of these behaviors " .
            "and reporting your progress on them to your " .
            "instructor helps reinforce these strategies so they will stick " .
            "with you for a lifetime. If you do not want to track these " .
            "strategies click the plus button on the left and they will " .
            "contract. If you change your mind and decide to track them " .
            "click the plus button again and they will come back.",
            "Strategies instructions"
         );
      ?>
      <fieldset id="strategy_fieldset">
         <b>On how many days of the week of this report did you:</b>
         <?php
            $sqr = pdo_seleqt("
               select
                  s.strategy_id,
                  s.strategy_description,
                  s.custom,
                  sr.num_days
               from
                  wrc_strategies s
                  natural left join wrc_strategy_user su
                  left join (
                     select *
                     from wrc_strategy_report
                     where
                        user_id = ? and
                        class_id = ? and
                        class_source = ? and
                        week_id = ?
                  ) sr
                     on s.strategy_id = sr.strategy_id
               where
                  !s.custom or
                  su.user_id = ?
            ", array(
               $_GET['user'],
               $qr['class_id'],
               $qr['class_source'],
               $_GET['week'],
               $_GET['user']
            ));
         ?>
         <table>
            <?php
            foreach ($sqr as $strat) {
               ?><tr><td><?php
                  echo htmlentities($strat['strategy_description']);
                  if($strat['custom'] && $_GET['user'] == $_SESSION['user_id']) {
                     // delete link
                     ?> <a  style="font-size:small" href="report.php?user=<?php
                        echo htmlentities($_GET['user']);
                     ?>&week=<?php
                        echo htmlentities($_GET['week']);
                     ?>&delete=<?php
                        echo htmlentities($strat['strategy_id']);
                     ?>" onclick="return delStratConfirm();">delete</a><?php
                  }
               ?></td><td>
                  <?php
                     strat_numdays_dd(
                        "strategy[" . $strat['strategy_id'] . "]",
                        $strat['num_days'] == null ? -1 : $strat['num_days']
                     );
                  ?>
               </td></tr><?php
            }
            if($_GET['user'] == $_SESSION['user_id']) {
               ?>
               <tr>
                  <td>
                     Custom strategy:
                     <input size="20" maxlength="100" type="text"
                           name="newstrat_desc" onKeyUp="submitButtonText();" />
                     <?php
                        popup(
                           "instructions",
                           "Use this field to create custom strategies that " .
                           "address specific behaviors you want to adopt in " .
                           "your life. Custom strategies can help track " .
                           "behaviors that indicate progress towards your " .
                           "SMART goal or strategies that you discover in " .
                           "class that you want to continue tracking " .
                           "throughout the program. The custom strategies " .
                           "you enter " .
                           "will appear in your other reports so that you " .
                           "can continue to track your improvement on them " .
                           "throughout your Eat " .
                           "Smart, Move More, Weigh Less class. If you want " .
                           "to delete a custom strategy just click the " .
                           "delete link to the right of the strategy.",
                           "Custom strategies"
                        );
                     ?>
                  </td>
                  <td>
                     <?php
                        strat_numdays_dd("newstrat_numdays");
                     ?>
                  </td>
               </tr>
               <?php
            }
            ?>
         </table>
      </fieldset>

      <?php } ?>

      <?php
         $nqr = pdo_seleqt("
            select
               notes,
               fdbk_dttm
            from wrc_reports
            where
               user_id = ? and
               class_id = ? and
               class_source = ? and
               week_id = ?
         ", array($_GET['user'], $qr['class_id'], $qr['class_source'], $_GET['week']));

         if(
            $_SESSION['user_id'] != $_GET['user'] || (
               isset($nqr[0]['notes']) && $nqr[0]['notes'] != ""
            )
         ) {
            ?><fieldset style="margin-top:1em"><table><?php
               $err_count += report_var(
                  "notes",
                  $qr['class_id'],
                  $qr['class_source'],
                  "notes",
                  "Instructor feedback<br /><span class='small'>(" .
                        rstr_date($nqr[0]['fdbk_dttm']) . ")</span>",
                  "",
                  "",
                  "",
                  true,
                  false,
                  true
               );
            ?></table></fieldset><?php
         }
      ?>
      <br />
      <input type="hidden" name="formsubmitted" value="true" />
      <input type="submit" id="reportsubmit" value="Submit changes" />
   </form>

   <div id="bmi">
   BMI:
   <span id="bmi_num">
      <?php
         $bmiqr = pdo_seleqt("
            select
               week_id,
               height_inches,
               weight
            from
               wrc_users
               natural join (
                  select
                     user_id,
                     week_id,
                     weight,
                     class_id,
                     class_source
                  from wrc_reports
                  where week_id in (
                     select max(week_id)
                     from wrc_reports
                     where
                        weight > 0
                        and user_id = ?
                        and week_id <= ?
                  )
               ) current_report
               natural join current_classes
            where user_id = ?;
         ", array($_GET['user'], $_GET['week'], $_GET['user']));
         if(isset($bmiqr[0]['height_inches']) && $bmiqr[0]['height_inches'] != 0) {
            echo round($bmiqr[0]['weight'] * 703.06957964 /
                  ($bmiqr[0]['height_inches'] * $bmiqr[0]['height_inches']), 2);
         }
         else {
            ?>
            Please enter your height in your <a href="report.php?week=1&user=<?php
               echo htmlentities($_GET['user']);
            ?>">week 1 report</a> and weight in this report in order to
            calculate BMI.
            <?php
         }
         ?>
      </span>
      <?php
      popup(
         "what is BMI?",
         "BMI stands for <b>b</b>ody <b>m</b>ass <b>i</b>ndex. To measure " .
         "BMI you divide an " .
         "individual’s body weight by the square of his/her height. BMI " .
         "is used to estimate an individual’s level of body fat. BMIs " .
         "between 25 and 30 are considered overweight and BMIs greater " .
         "than 30 are considered obese.",
         "What is BMI?"
      );
   ?>
   </div>
   <div style="display:none" id="height_inches">
      <?php
         if(isset($bmiqr[0]['height_inches'])) {
            echo htmlentities($bmiqr[0]['height_inches']);
         }
      ?>
   </div>
   <div style="clear:left"></div>
   <?php
   if(
      isset($_POST['formsubmitted']) &&
      $err_count == 0 &&
      !(
         isset($_POST['newstrat_desc']) &&
         $_POST['newstrat_desc'] != ""
      )
   ) {
      header("Location: reports.php?user=" . getNumericOnly('user'));
   }
}

function first_class() {
   return $_GET['week'] == 1;
}

function last_class($class_id, $class_source) {
   $qr = seleqt_one_record("
      select weeks
      from classes_aw
      where
         class_id = ?
         and class_source = ?
   ", array($class_id, $class_source));
   return $_GET['week'] == $qr['weeks'];
}

function report_var (
   $post_var,          //  x in $_POST['x']
   $class_id,          //  id of participant's current class
   $class_source,      //  "a" or "w"
   $db_col,            //  name of the column in the database
   $label,             //  to display next to the input box
   $popup_link,        //  text for the link to the alert. null for no alert.
   $popup_text,        //  text for the alert
   $popup_title,       //  title for the alert
   $rept_enrf,         //  report true, enrollment false
   $req_num=true,      //  required to be numeric
   $inst_input=false   //  instructor inputs this field.
) {
   global $ini;

   $err_count = 0;
   if(isset($_POST['formsubmitted']) && isset($_POST[$post_var])) {
      if(
         $req_num &&
         $_POST[$post_var] != "" &&
         !is_numeric($_POST[$post_var])
      ) {
         $err_count++;
         echo err_text($label . " must be numeric.");
      }
      else {
         // form is submitted, post_var is set and is numeric if required.
         $dbh = pdo_connect($ini['db_prefix'] . "_update");
         if($rept_enrf) {
            // update wrc_reports table
            $sth = $dbh->prepare("
               update wrc_reports
               set
                  " . $db_col . " = ?,
                  " . ($inst_input ? "fdbk_dttm" : "create_dttm") . " = now()
               where
                  user_id = ? and
                  class_id = ? and
                  class_source = ? and
                  week_id = ?
            ");
            $db_array = array(
               $_POST[$post_var],
               $_GET['user'],
               $class_id,
               $class_source,
               $_GET['week']
            );
         }
         else {
            // update wrc_enrollment table
            $sth = $dbh->prepare("
               update " . ENR_TBL . "
               set " . $db_col . " = ?
               where
                  user_id = ?
                  and class_id = ?
                  and class_source = ?
            ");
            $db_array = array(
               $_POST[$post_var],
               $_GET['user'],
               $class_id,
               $class_source
            );
         }

         if($sth->execute($db_array)) {
            // success
         }
         else {
            $err_count++;
            echo err_text("A database error occurred with " . $label . ".");
         }
      }
   }
   // show output
   ?>
   <tr><td>
      <?php echo $label; ?>:
   </td><td>
      <?php
         if($rept_enrf) {
            $cvqr = pdo_seleqt("
               select " . $db_col . "
               from wrc_reports
               where
                  user_id = ? and
                  class_id = ? and
                  class_source = ? and
                  week_id = ?
            ", array($_GET['user'], $class_id, $class_source, $_GET['week']));
         }
         else {
            $cvqr = pdo_seleqt("
               select " . $db_col . "
               from " . ENR_TBL . "
               where
                  user_id = ? and
                  class_id = ? and
                  class_source = ?
            ", array($_GET['user'], $class_id, $class_source));
         }
         if($_SESSION['user_id'] == $_GET['user']) {
            // participant is looking at his own report
            if($inst_input) {
               readonly($cvqr, $db_col);
            }
            else {
               report_input($post_var, $cvqr, $db_col);
               if($popup_link) {
                  popup($popup_link, $popup_text, $popup_title);
               }
            }
         }
         else {
            // instructor or admin is looking at participant's report.
            if($inst_input) {
               report_input($post_var, $cvqr, $db_col, true);
            }
            else {
               readonly($cvqr, $db_col);
            }
         }
      ?>
   </td></tr>
   <?php
   return $err_count;
}

function report_input($post_var, $cvqr, $db_col, $textarea=false) {
   if($textarea) {
      ?>
      <textarea rows="6" cols="60" name="<?php echo $post_var; ?>"><?php
            if(isset($cvqr[0][$db_col])) {
               echo $cvqr[0][$db_col];
            }
         ?></textarea>
      <?php
   }
   else {
      ?>
      <input type="text" size="3" name="<?php echo $post_var; ?>"
         value="<?php
            if(isset($cvqr[0][$db_col])) {
               echo zero_blank($cvqr[0][$db_col]);
            }
         ?>"
         onkeyup="calcBmi();"
      />
      <?php
   }
}

function readonly($cvqr, $db_col) {
   ?><b><?php
   if(isset($cvqr[0][$db_col])) {
      echo htmlentities($cvqr[0][$db_col]);
   }
   ?></b><?php
}

function strat_numdays_dd($form_name, $selected = -1) {
   if($_GET['user'] == $_SESSION['user_id']) {
      // Participant viewing her own report
      ?><select name="<?php
         echo $form_name;
      ?>"><option value="-1"<?php
         if($selected == -1) {
            ?> selected<?php
         }
      ?>></option><?php
         for($i=0; $i<=7; $i++) {
            ?><option value="<?php
               echo $i;
            ?>"<?php
               if($i == $selected) {
                  ?> selected<?php
               }
            ?>><?php
               echo $i;
            ?> day<?php
               if($i != 1) {
                  ?>s<?php
               }
            ?></option><?php
         }
      ?></select><?php
   }
   else {
      // Instructor or admin viewing participant's report
      if($selected > -1) {
         ?><b><?php
            echo $selected;
         ?> day<?php
            if($selected != 1) {
               ?>s<?php
            }
         ?></b><?php
      }
   }
}
?>
