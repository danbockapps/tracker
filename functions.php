<?php

function getAvgStepsFromDb($userId, $reportDateString) {
   $reportDate = date('Y-m-d', strtotime($reportDateString));
   $rangeStart = date('Y-m-d', strtotime($reportDate . ' - 7 day'));
   $rangeEnd =   date('Y-m-d', strtotime($reportDate . ' - 1 day'));

   $qr = seleqt_one_record('
      select avg(value) as avgsteps
      from fitbit
      where
         user_id = ?
         and metric = ?
         and date between ? and ?
   ', array($userId, 'activities-steps', $rangeStart, $rangeEnd));

   return round($qr['avgsteps']);
}

function getActiveMinutesFromDb($userId, $reportDateString) {
   $reportDate = date('Y-m-d', strtotime($reportDateString));
   $rangeStart = date('Y-m-d', strtotime($reportDate . ' - 7 day'));
   $rangeEnd =   date('Y-m-d', strtotime($reportDate . ' - 1 day'));

   $qr = seleqt_one_record('
      select sum(value) as minutesActive
      from fitbit
      where
         user_id = ?
         and metric in (?, ?)
         and date between ? and ?
   ', array(
      $userId,
      'activities-minutesVeryActive',
      'activities-minutesFairlyActive',
      $rangeStart,
      $rangeEnd
   ));

   return round($qr['minutesActive']);
}

function getWeightFromDb($userId, $reportDateString) {
   // Gets the correct weight for the date
   $reportDate = date('Y-m-d', strtotime($reportDateString));
   $rangeStart = date('Y-m-d', strtotime($reportDate . ' - 7 day'));
   $rangeEnd = date('Y-m-d', strtotime($reportDate . ' - 1 day'));

   $qr = pdo_seleqt('
      select value
      from fitbit
      where
         user_id = ?
         and metric = ?
         and date between ? and ?
      order by date desc
      limit 1
   ', array($userId, 'weight', $rangeStart, $rangeEnd));

   if(count($qr) == 1) {
      return $qr[0]['value'];
   }
   else {
      return null;
   }
}

function fitbitValue($userId, $reportDateString, $postVar, $value) {
   // Is $value the same as the Fitbit value? (if yes, we won't want
   // to insert it into the wrc_reports table).
   if($postVar == 'weight') {
      return getWeightFromDb($userId, $reportDateString) == $value;
   }
   else if($postVar == 'aerobic' || $postVar == 'physact') {
      return getActiveMinutesFromDb($userId, $reportDateString) == $value;
   }
   else if($postVar == 'avgsteps') {
      return getAvgStepsFromDb($userId, $reportDateString) == $value;
   }
   else {
      // It's a metric that doesn't sync with Fitbit.
      return false;
   }
}

function isUserCurrent($userId) {
   $qr = seleqt_one_record('
      select count(*) as count
      from
         enrollment_view e
         inner join current_classes c
            on e.class_id = c.class_id
      where e.user_id = ?
   ', $userId);

   // This is >0 i.e. truthy, if the user is in at least one current class.
   return $qr['count'];
}

function attendanceForClass($classId, $userId = null) {
   $queryArray = [$classId, $classId];
   if($userId) $queryArray[] = $userId;

   return pdo_seleqt('
      select
         a.user_id,
         u.fname,
         u.lname,
         a.week,
         a.present ' .
         ($userId ? ', a.attendance_type' : '')
         . '
      from
         wrc_attendance a
         inner join classes_aw c
            on a.class_id = c.class_id
            and a.class_source = c.class_source
         inner join wrc_users u
            on a.user_id = u.user_id
      where
         year(c.start_dttm) in (
            select year(start_dttm)
            from classes_aw
            where
               class_id = ?
               and class_source = "w"
         )
         and month(c.start_dttm) in (
            select month(start_dttm)
            from classes_aw
            where
               class_id = ?
               and class_source = "w"
         )
         ' . ($userId ? 'and a.user_id = ?' : '') . '
      order by date_entered
   ', $queryArray);
}

function attendanceSummary3ForClass($classId) {
   return pdo_seleqt('
      select
         a.user_id,
         u.fname,
         u.lname,
         a.lesson_id,
         a.attendance_type,
         a.attendance_date
      from
         attendance_summary3 a
         inner join wrc_users u
            on a.user_id = u.user_id
         inner join (
            select class_id
            from
               classes_aw c
               inner join (
                  select
                     month(start_dttm) as month,
                     year(start_dttm) as year
                  from classes_aw
                  where class_id = ?
               ) i on month(c.start_dttm) = i.month and year(c.start_dttm) = i.year
         ) c on a.class_id = c.class_id
      where a.user_id in (select user_id from enrollment_view where class_id = ?)
      order by
         lname,
         fname
   ', array($classId, $classId));
}

function participantsForClass($classId) {
   return pdo_seleqt("
      select
         e.user_id,
         u.fname,
         u.lname
      from
         " . ENR_VIEW . " e
         natural join wrc_users u
      where
         e.class_id = ?
         and e.class_source = 'w'
      order by
         fname,
         lname
   ", array($classId));
}

function indexedAttendanceArray($aqr, $qr) {
   $iqr = array();

   // Create indexed array
   // First, create an empty row for each participant
   foreach($qr as $row) {
      $iqr[$row['user_id']] = array();
   }

   // Next, populate the array.
   // If there are multiple entries for the same user and week, the earlier ones
   // will be overwritten in this loop by the latest, which is exactly what we want.
   foreach($aqr as $row) {
      $iqr[$row['user_id']][$row['week']] = $row['present'];
   }

   return $iqr;
}

function classInfo($classId) {
   return seleqt_one_record("
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
         c.class_id = ?
         and c.class_source = 'w'
   ", array($classId));
}

function attendanceEntryHeader($classId) {
   $cqr = classInfo($classId);
   ?>
   <h2>Attendance Entry</h2>

   <table>
      <tr>
         <td>
            <strong>Instructor:</strong>
         </td>
         <td>
            <?php echo htmlentities($cqr['fname'] . ' ' . $cqr['lname']); ?>
         </td>
      </tr>
      <tr>
         <td>
            <strong>Class time:</strong>
         </td>
         <td>
            <?php echo class_times($cqr['start_dttm']); ?>
         </td>
      </tr>
   </table>
   <?php
}

function removePassword($s) {
   // TODO if we ever use this with login requests, make it work
   return $s;
}

function ireportExists($userId, $classId, $lessonId) {
   $qr = seleqt_one_record('
      select count(*) as count
      from wrc_ireports
      where
         user_id = ? and
         class_id = ? and
         lesson_id = ?
   ', array($userId, $classId, $lessonId));
   return $qr['count'];
}

function nullIfBlank($x) {
   $trimmed = trim($x);
   if($trimmed == '') {
      return null;
   }
   else {
      return $trimmed;
   }
}

function attendancePhases($userId, $classId) {
   $afc = attendanceForClass($classId, $userId);
   $iqr = array();

   foreach($afc as $row) {
      $iqr[$row['week']] = $row['attendance_type'];
   }

   $returnable = array();

   foreach($iqr as $week => $present) {
      $returnable[$week <= 18 ? 'phase1' : 'phase2'] += min(1, $present);
   }

   return $returnable;
}

function refundCard($userId, $classId) {
   $attendancePhases = attendancePhases($userId, $classId);
   
  if(
    PRODUCT == 'dpp' &&
    !am_i_instructor() &&
    $attendancePhases['phase1'] >= 9 &&
    $attendancePhases['phase2'] >= 5
  ) {

    $qr = seleqt_one_record('
      select refund_method, refund_email_address, refund_postal_address, ifnc, amount
      from ' . ENR_VIEW . '
      where user_id = ? and class_id = ?
    ', array($userId, $classId));

    if($qr['ifnc'] == '1' && $qr['amount'] == 30) {
      ?>

      <div class="refund-box">
        <div>
          Your $30.00 refund cannot be put back on your credit card or directly
          in your bank account. You can receive your refund via:
        </div>

        <div>
          <input
            id="refund-method-paypal-button"
            type="radio"
            name="refundMethod"
            value="paypal"
            onclick="showHideRefundElements()"
          />
          <label for="refund-method-paypal-button">
            <span style="font-weight: bold">PayPal</span>
            <span style="font-style: italic">
              (select if you already have a PayPal account or are willing to
              create a PayPal account)
            <span>
          </label>
        </div>

        <div id="paypal-address">
          Please provide email address linked to your personal PayPal account (no business accounts):<br />
          <input id="paypal-address-input" />
        </div>

        <div>
          <input
            id="refund-method-check-button"
            type="radio"
            name="refundMethod"
            value="check"
            onclick="showHideRefundElements()"
          />
          <label for="refund-method-check-button">
            <span style="font-weight: bold">Check</span>
            <span style="font-style: italic">
              (select if you do not have a PayPal account and do not want to create
              a new account)
            </span>
          </label>
        </div>

        <div id="check-address">
          Please provide the full mailing address (including zip code) where you
          would like the check to be sent:<br />
          <input id="check-address-input" />
        </div>

        <button id="refund-button" onclick="submitRefund()">Save</button>
      </div>

      <script>
        var initialRefundValues = <?php echo json_encode($qr); ?>;
        if(initialRefundValues['refund_method'] === 'paypal')
          $('input[name=refundMethod][value=paypal]').prop('checked', true);
        else if(initialRefundValues['refund_method'] === 'check')
          $('input[name=refundMethod][value=check]').prop('checked', true);
        
        $('#paypal-address-input').val(initialRefundValues['refund_email_address']);
        $('#check-address-input').val(initialRefundValues['refund_postal_address']);

        showHideRefundElements();

        function showHideRefundElements() {
          var rt = $('input[name=refundMethod]:checked').val();
          if(rt === undefined) {
            $('#paypal-address').hide();
            $('#check-address').hide();
          }
          if(rt === 'paypal') {
            $('#paypal-address').show();
            $('#check-address').hide();
          }
          if(rt === 'check') {
            $('#paypal-address').hide();
            $('#check-address').show();
          }
        }

        function submitRefund() {
          $.post('rest/api.php?q=refund', {
            refundMethod: $('input[name=refundMethod]:checked').val(),
            refundEmailAddress: $('#paypal-address-input').val(),
            refundPostalAddress: $('#check-address-input').val(),
            classId: <?php echo $classId; ?>
          }, function(data) {
            if(data.responseString === 'OK') {
              alert('Your response has been saved.');
            }
          })
        }
      </script>
    <?php
    }
  }
}

function addressChangeCard() {
   if(PRODUCT == 'dpp' && !am_i_instructor()) {
      ?>
      <div class="wide-box">
         <h3>Change Your Address</h3>
         <div id="address-change" class="spacious-form"></div>

         <!-- Supposedly this (text/babel combined with loading babel.min.js)
         is not suitable for production; we should be using a preprocessor
         instead. But I'm skeptical there's any noticeable difference. -->
         <script type="text/babel" src="react/addresschange.js"></script>
      </div>
      <?php
   }
}

function generateAlisFile($path, $processCd, $sessionId, $transactionCount) {
   $xml = new SimpleXMLElement('<ALIS version="2.0"/>');
   $t = $xml->addChild('Transaction');
   $t->addAttribute('processCd', $processCd);
   $t->addAttribute('txnType', 'Balance');
   $t->addChild('SessionId', $sessionId);

   $processTime = $t->addChild('ProcessTime');
   $processTime->addAttribute('startDtTime', date("Y-m-d H:i:s.000"));
   $processTime->addAttribute('endDtTime', date("Y-m-d H:i:s.000"));

   $t->addChild('TransactionCount', $transactionCount);

   file_put_contents($path, $xml->asXML());
}

function getNumWeeks($classId, $classSource) {
   $weekqr = seleqt_one_record("
      select weeks
      from classes_aw
      where
         class_id = ?
         and class_source = ?
   ", array($classId, $classSource));

   return $weekqr['weeks'];
}

function getReports($userId, $startDttm) {
   return pdo_seleqt("
      select
         week_id,
         weight,
         aerobic_minutes,
         strength_minutes,
         physact_minutes,
         avgsteps,
         notes
      from
         reports_with_fitbit_hybrid r
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
         $userId,
         date('Y', strtotime($startDttm)),
         date('n', strtotime($startDttm))
   ));
}

function execLog($command) {
   echo "Running command: " . $command . "\n";
   $output = null;
   $retval = null;
   exec($command, $output, $retval);

   echo "Returned with status $retval and output:\n";
   print_r($output);
}

function printPasswordInstructions() {
   ?>
   <p>
      Passwords must be at least <?php echo MIN_PW_LEN; ?> characters long and
      contain at least one uppercase letter, one lowercase letter, one number,
      and one special character. Passwords must not contain 5 consecutive digits or single quotes.
   </p>
   <?php
}

function getPasswordErrors($password, $password2) {
   $error = array();

   if (empty($password)) {
      $error[] = '<p>Please enter your password. </p>';
   }
   else if(empty($password2)) {
      $error[] = '<p>Please enter your password twice. </p>';
   }
   else if($password !== $password2) {
      $error[] = "<p>Please enter the same password twice. </p>";
   }
   else if(strlen($password) < MIN_PW_LEN) {
      $error[] = "<p>Password must be at least " . MIN_PW_LEN . " characters. </p>";
   }
   else if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$%^&+=!*]).*$/', $password)) {
      $error[] = "<p>Password must contain at least one uppercase letter, one lowercase letter, one number, one special character.</p>";
   }
   else if (preg_match('/\d{5}/', $password)) {
      $error[] = "<p>Password must not contain 5 consecutive digits.</p>";
   }
   else if (strpos($password, "'") !== false) {
      $error[] = "<p>Password must not contain a single quote.</p>";
   }

   return $error;
}

function verifyPassword($password, $dbValue) {
   if(password_verify($password, $dbValue)) {
      return true;
   }
   else {
      // Password failed normal verification. Check to see if it's an old password
      // generated by the old hashing algorithm.
      // https://stackoverflow.com/questions/77656346/neither-crypt-nor-password-verify-works-on-php-8-1-on-our-users-existing-passwo/77659756#77659756
      $dbValue[27] = '.';
      return password_verify($password, $dbValue);
   }
}

?>
