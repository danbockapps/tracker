<?php
if(!isset($reportphp_mode)) {
   $reportphp_mode = "";
}
require_once($reportphp_mode . "template.php");
require_once("reportComponent.php");
generate_page(true, false);

function page_content() {
   global $ini;
   $qr = current_class_and_sg();
   access_restrict($qr);
   participant_nav($qr['class_id'], $qr['class_source']);

   if(!isset($_GET['week'])) {
      exit(err_text("<p>No week specified.</p>"));
   }

   if(empty($qr)) {
      exit(err_text("<p>User is not registered for a class.</p>"));
   }

   global $report_date;
   $report_date = $qr['start_dttm'] . " + " . ($_GET['week'] - 1) . " weeks";

   if(strtotime($report_date) > strtotime(date(DATE_RSS) . " + 1 week")) {
      exit(err_text("Too early to create report for " .
            htmlentities(wrcdate($report_date))));
   }

   ?>
   <script>
      function delStratConfirm() {
         return confirm('<p>Are you sure you want to delete this strategy ' +
               'from your list? This will delete it from all weeks.</p>');
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
         exit(err_text("<p>A database error occurred deleting the strategy.</p>"));
      }
   }


   if(isset($_POST['formsubmitted'])) {
      foreach($_POST as $key => $post_item) {
         if(!is_array($_POST[$key])) {
            $_POST[$key] = trim($_POST[$key]);
         }
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
            exit(err_text("<p>A database error occurred creating the report.</p>"));
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
               echo err_text("<p>A database error occurred with the strategy (1).</p>");
            }
            $last_insert_id = $dbh->lastInsertId("wrc_strategies");

            $sth = $dbh->prepare("
               insert into wrc_strategy_user
               (user_id, strategy_id) values (?, ?)
            ");
            if(!$sth->execute(array($_GET['user'], $last_insert_id))) {
               echo err_text("<p>A database error occurred with the strategy (2).</p>");
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
               echo err_text("<p>A database error occurred with the strategy (3).</p>");
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

   <?php
      reportComponent(array(
         "classId" => $qr['class_id'], 
         "classSource" => $qr['class_source'],
         "userId" => $_GET['user'],
         "week" => $_GET['week']
      ));
   ?>

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

?>

<script>
$(document).on('blur', 'input[pattern]', function(e){
   if(!new RegExp($(this).attr('pattern'), 'g').test($(this).val())){
      $(this).get(0).setCustomValidity('A1c must be under 20.00, with up to 2 decimal places.');

      // "Additionally you must call the reportValidity method on the same
      // element or nothing will happen."
      // https://developer.mozilla.org/en-US/docs/Web/API/HTMLObjectElement/setCustomValidity
      $(this).get(0).reportValidity();

      $("#reportsubmit").prop('disabled', true)
   }
   else {
      $(this).get(0).setCustomValidity('');
      $(this).get(0).reportValidity();
      $("#reportsubmit").prop('disabled', false)
   }
});
</script>

