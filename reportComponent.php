<?php

function reportComponent($classId, $classSource) {
  ?>

  <form id="report_form"
          action="report.php?week=<?php
              echo htmlentities($_GET['week']);
          ?>&user=<?php
              echo htmlentities($_GET['user']);
          ?>"
          method="post"
          class="white-form">
        <fieldset style="margin-bottom: 0.3em">
          <table id="metric-entry">
          <?php
              $err_count = 0;
              $err_count += report_var(
                "weight",
                $classId,
                $classSource,
                "weight",
                "Weight (pounds)",
                "instructions",
                "Try to weigh in at the same day and time on the same " .
                      "scale every week.",
                "Weight instructions",
                true,
                true,
                getWeightFromDb($_GET['user'], $report_date)
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
              $last_class = last_class($classId, $classSource);
              $p1end_class = PRODUCT == 'dpp' && isP1End($classId, $_GET['week']);

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
              if($ini['product'] == 'esmmwl' || $ini['product'] == 'esmmwl2') {
                $err_count += report_var(
                    "aerobic",
                    $classId,
                    $classSource,
                    "aerobic_minutes",
                    "Minutes of aerobic activity",
                    "instructions",
                    "Use this field to record the number of minutes you spent " .
                      "engaged in aerobic activities like walking, gardening, " .
                      "or using an elliptical machine over the past week.",
                    "Aerobic activity instructions",
                    true,
                    true,
                    getActiveMinutesFromDb($_GET['user'], $report_date)
                );

                $err_count += report_var(
                    "strength",
                    $classId,
                    $classSource,
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
                    $classId,
                    $classSource,
                    "physact_minutes",
                    "Minutes of physical activity",
                    null,
                    null,
                    null,
                    true,
                    true,
                    getActiveMinutesFromDb($_GET['user'],  $report_date)
                );
              }

              if($first_class || $last_class || $p1end_class) {
                if($first_class) {
                    $suffix = "_start";
                }
                else if ($last_class) {
                    $suffix = "_end";
                }
                else if ($p1end_class) {
                    $suffix = "_mid";
                }

                $err_count += report_var(
                    "syst" . $suffix,
                    $classId,
                    $classSource,
                    "syst" . $suffix,
                    "Systolic blood pressure",
                    "instructions",
                    "Use this field to record the top number in your blood " .
                      "pressure reading. You will only be recording your " .
                      "blood pressure for " . PROGRAM_NAME .
                      " twice: once at the beginning and once at the end of " .
                      "the 15 weeks. Try " .
                      "to use the same monitor both times. High blood " .
                      "pressure is a risk factor for stroke and heart " .
                      "disease. Healthy eating and physical activity can help " .
                      "you achieve and maintain a healthy blood pressure.",
                    "Systolic blood pressure instructions",
                    false
                );

                $err_count += report_var(
                    "dias" . $suffix,
                    $classId,
                    $classSource,
                    "dias" . $suffix,
                    "Diastolic blood pressure",
                    "instructions",
                    "Use this field to record the bottom number in your " .
                      "blood pressure reading. You will only be recording " .
                      "your blood pressure for " . PROGRAM_NAME .
                      " twice: once at the beginning and once at the end " .
                      "of the 15 weeks. " .
                      "Try to use the same monitor both times. High blood " .
                      "pressure is a risk factor for stroke and heart " .
                      "disease. Healthy eating and physical activity can help " .
                      "you achieve and maintain a healthy blood pressure.",
                    "Diastolic blood pressure instructions",
                    false
                );

                $err_count += report_var(
                    "waist" . $suffix,
                    $classId,
                    $classSource,
                    "waist" . $suffix,
                    "Waist circumference",
                    "instructions",
                    "To measure your waist circumference, position a " .
                      "measuring tape around your waist (right above your " .
                      "belly button), take a deep breath, and then exhale. " .
                      "Take the measurement after you have exhaled. You will " .
                      "only be recording your waist circumference for " .
                      PROGRAM_NAME . " twice: once at the " .
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
                    $classId,
                    $classSource,
                    'a1c',
                    'A1c level',
                    'instructions',
                    'You will not enter this every week. Please enter your ' .
                    'most recent A1c. When you have your A1c tested again ' .
                    'during this program, please enter your updated A1c in ' .
                    'the week the test occurred.',
                    'A1c instructions',
                    true
                );
              }

              $err_count += report_var(
                'avgsteps',
                $classId,
                $classSource,
                'avgsteps',
                'Average steps per day',
                'instructions',
                'Enter your average steps per day for ' . stepsDateRange($report_date) . '.',
                'Average Steps instructions',
                true, // report true
                true, // required numeric
                getAvgStepsFromDb($_GET['user'], $report_date)
              );
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
              "your " . PROGRAM_NAME . " class. Being more " .
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
                order by s.display_order
              ", array(
                $_GET['user'],
                $classId,
                $classSource,
                $_GET['week'],
                $_GET['user']
              ));
          ?>
          <table>
              <?php
              foreach ($sqr as $strat) {
                ?><tr><td class="strategy<?=$strat['strategy_id']?>"><?php
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
                            "throughout your " . PROGRAM_NAME .
                            " class. If you want " .
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

        <br />
        <input type="hidden" name="formsubmitted" value="true" />
        <input type="submit" id="reportsubmit" value="Submit changes" />
    </form>

  <?php
}

?>