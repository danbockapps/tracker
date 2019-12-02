<?php

function getStepsFromFitbitAndInsert($userId) {
   $response = getDataFromFitbit($userId, 'activities-steps');
   insertFitbitData($userId, 'activities-steps', $response);
}

function getMfaFromFitbitAndInsert($userId) {
   $response = getDataFromFitbit($userId, 'activities-minutesFairlyActive');
   insertFitbitData($userId, 'activities-minutesFairlyActive', $response);
}

function getMvaFromFitbitAndInsert($userId) {
   $response = getDataFromFitbit($userId, 'activities-minutesVeryActive');
   insertFitbitData($userId, 'activities-minutesVeryActive', $response);
}

function getWeightFromFitbitAndInsert($userId) {
   $response = getDataFromFitbit($userId, 'body-log-weight');
   insertFitbitData($userId, 'weight', $response);
}

function getDataFromFitbit(
   $userId,
   $metric
) {
   $urlMetric = str_replace('-', '/', $metric);

   $url =
      'https://api.fitbit.com/1/user/-/' .
      $urlMetric .
      '/date/' .
      getStartDateForFitbit($userId, $metric) .
      '/' .
      date('Y-m-d') .
      '.json';

   return sendRequestToFitbit($url, $userId, false);
}

function sendRequestToFitbit($url, $userId, $doNotRefresh = false) {
   $qr = seleqt_one_record('
      select
         fitbit_access_token,
         fitbit_refresh_token
      from wrc_users
      where user_id = ?
   ', array($userId));

   debug('Sending data request to Fitbit: ');
   debug($url);
   $c = curl_init($url);

   curl_setopt(
      $c,
      CURLOPT_HTTPHEADER,
      array(
         'Authorization: Bearer ' . $qr['fitbit_access_token'],
         'Accept-Language: en_US'
      )
   );
   curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

   $response = curl_exec($c);
   $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
   curl_close($c);

   debug('Fitbit response to ' . $metric . ' request for user ' . $userId . ':');
   debug($httpCode);
   debug($response);

   if($httpCode == 200) {
      return $response;
   }

   else if(
      $httpCode == 401 &&
      json_decode($response)->errors[0]->errorType == 'expired_token' &&
      $doNotRefresh == false
   ) {
      debug('Trying to refresh token...');
      if(refreshFitbitToken($userId, $qr['fitbit_refresh_token'])) {
         debug('Refreshed token. Sending another request.');
         return sendRequestToFitbit($url, $userId, true);
      }
   }

   else {
      logtxt('ERROR: Error getting data from Fitbit.');
      debug('doNotRefresh: ' . $doNotRefresh);
      exit('ERROR: Error getting data from Fitbit.');
   }
}

function insertFitbitData($userId, $metric, $response) {
   $stepsArray = json_decode($response)->$metric;

   if(count($stepsArray) > 0) {
      $sqlValues = array();
      $dbh = pdo_connect(DB_PREFIX . '_insert');

      foreach($stepsArray as $day) {
         $sqlValues[] =
            '(' .
            implode(',', array(
               $dbh->quote($userId),
               $dbh->quote(getDateFromResponse($metric, $day)),
               $dbh->quote($metric),
               $dbh->quote(getValueFromResponse($metric, $day))
            )) .
            ')';
      }

      $sql = 'insert into wrc_fitbit (user_id, date, metric, value) values ' .
         implode(',', $sqlValues);

      debug($sql);

      $sth = $dbh->prepare($sql);
      debug('Return value from inserting steps: ' . $sth->execute());
   }
   else {
      debug('There is no data to insert.');
   }
}

function getDateFromResponse($metric, $day) {
   if(substr($metric, 0, 10) == 'activities') {
      return $day->dateTime;
   }
   else if($metric == 'weight') {
      return $day->date;
   }
}

function getValueFromResponse($metric, $day) {
   if(substr($metric, 0, 10) == 'activities') {
      return $day->value;
   }
   else if($metric == 'weight') {
      return $day->weight;
   }
}

function refreshFitbitToken($userId, $refreshToken) {
   if(strlen($refreshToken) == 0) {
      logtxt('ERROR: Refresh token length is 0.');
      return false;
   }

   // Body parameters
   $params = array();
   $params['grant_type'] = 'refresh_token';
   $params['refresh_token'] = $refreshToken;

   $tokens = fitbitTokenRequest($params);
   $accessToken = $tokens->access_token;
   $refreshToken = $tokens->refresh_token;

   if(strlen($accessToken) > 0 ) {
      saveTokensToDatabase($userId, $accessToken, $refreshToken);
      return true;
   }
   else {
      logtxt('ERROR: Access token length is 0.');
      return false;
   }
}

function fitbitTokenRequest($params) {
   debug('Sending Fitbit token request with parameters:');
   debug(print_r($params, true));

   $curl = curl_init('https://api.fitbit.com/oauth2/token');
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt(
      $curl,
      CURLOPT_HTTPHEADER,
      array(
         'Authorization: Basic ' . base64_encode(FITBIT_CLIENT_ID . ':' .
            FITBIT_CLIENT_SECRET),
         'Content-Type: application/x-www-form-urlencoded'
      )
   );

   $response = curl_exec($curl);
   curl_close($curl);

   debug('Response to token request:');
   debug($response);

   return json_decode($response);
}

function saveTokensToDatabase($userId, $accessToken, $refreshToken) {
   $dbh = pdo_connect(DB_PREFIX . '_update');
   $sth = $dbh->prepare('
      update wrc_users set
         fitbit_access_token = ?,
         fitbit_refresh_token = ?
      where user_id = ?
   ');
   $dbArray = array(
      $accessToken,
      $refreshToken,
      $userId
   );

   if($sth->execute($dbArray)) {
      debug('Successfully inserted tokens into database:');
      debug(print_r($dbArray, true));
   }
   else {
      exit('Database error with Fitbit tokens.');
   }
}

function getStartDateForFitbit($userId, $metric) {
   if($metric == 'body-log-weight') {
      // hack to correct inconsistent Fitbit naming
      $metric = 'weight';
   }

   $qr = seleqt_one_record('
      select max(date) as date
      from wrc_fitbit
      where
         user_id = ?
         and metric = ?
         and value > 0
   ', array($userId, $metric));

   if(substr($metric, 0, 10) == 'activities') {
      if($qr['date'] != null) {
         return $qr['date'];
      }
      else {
         $class = current_class_by_user($userId);
         return date('Y-m-d', strtotime($class['start_dttm'] . ' -8 day'));
      }
   }

   else if($metric == 'weight') {
      if($qr['date'] != null ) {
         $candidate = strtotime($qr['date']);
      }
      else {
         $class = current_class_by_user($userId);
         $candidate = strtotime($class['start_dttm']);
      }

      return date('Y-m-d', max($candidate, strtotime('-30 day')));
   }

}

function isConnectedToFitbit($userId) {
   $qr = seleqt_one_record('
      select fitbit_access_token
      from wrc_users
      where user_id = ?
   ', array($userId));

   return $qr['fitbit_access_token'] == null ? false : true;
}

function deleteAllSubscriptions($userId) {
   if(isConnectedToFitbit($userId)) {
      deleteSubscription($userId, 'body');
      deleteSubscription($userId, 'activities');

      // Delete tokens from database
      saveTokensToDatabase($userId, null, null);
   }
}

function deleteSubscription($userId, $category) {
   $subscriptionId = $userId . '_' . $category;
   $url =
      'https://api.fitbit.com/1/user/-/' .
      $category .
      '/apiSubscriptions/' .
      $subscriptionId .
      '.json';

   debug($url);

   $deleteCurl = curl_init($url);

   curl_setopt($deleteCurl, CURLOPT_CUSTOMREQUEST, 'DELETE');
   curl_setopt(
      $deleteCurl,
      CURLOPT_HTTPHEADER,
      array('Authorization: Bearer ' . getAccessToken($userId))
   );
   curl_setopt($deleteCurl, CURLOPT_RETURNTRANSFER, true);
   $deleteResponse = curl_exec($deleteCurl);
   debug(curl_getinfo($deleteCurl, CURLINFO_HTTP_CODE));
   debug($deleteResponse);
   curl_close($deleteCurl);
}

function getAccessToken($userId) {
   $qr = pdo_seleqt("
      select fitbit_access_token
      from wrc_users
      where user_id = ?
   ", array($userId));

   return $qr[0]['fitbit_access_token'];
}

function authorizationRequest() {
   $params = array(
      'response_type' => 'code',
      'client_id'     => FITBIT_CLIENT_ID,
      'redirect_uri'  => REDIRECT_URI,
      'scope'         => 'activity weight'
   );

   $auth_url = 'https://www.fitbit.com/oauth2/authorize?' .
      http_build_query($params, null, '&');
   header('Location: ' . $auth_url);
}

function getTokens($code) {
   // Get 2 tokens: access token and refresh token.

   // Body parameters
   $params = array();
   $params['code'] = $code;
   $params['grant_type'] = 'authorization_code';
   $params['redirect_uri'] = REDIRECT_URI;

   return fitbitTokenRequest($params);
}

function subscribeToFitbit($userId, $category, $accessToken) {
   $subId = $userId . '_' . $category;

   $url =
      'https://api.fitbit.com/1/user/-/' .
      $category .
      '/apiSubscriptions/' .
      $subId .
      '.json';

   debug('Subscribing... ' . $url);

   $subscribeCurl = curl_init($url);
   curl_setopt($subscribeCurl, CURLOPT_POST, true);
   curl_setopt(
      $subscribeCurl,
      CURLOPT_HTTPHEADER,
      array('Authorization: Bearer ' . $accessToken)
   );
   curl_setopt($subscribeCurl, CURLOPT_RETURNTRANSFER, true);
   $subscribeResponse = curl_exec($subscribeCurl);
   $httpCode = curl_getinfo($subscribeCurl, CURLINFO_HTTP_CODE);
   curl_close($subscribeCurl);

   debug('Subscribe response: ');
   debug($httpCode);
   debug($subscribeResponse);
}

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

function attendanceForClass($classId) {
   return pdo_seleqt('
      select
         a.user_id,
         u.fname,
         u.lname,
         a.week,
         a.present
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
      order by date_entered
   ', array($classId, $classId));
}

function attendanceSummary3ForClass($classId) {
   return pdo_seleqt('
      select
         a.user_id,
         u.fname,
         u.lname,
         a.week,
         a.attendance_type,
         a.attendance_date
      from
         attendance_summary3 a
         inner join wrc_users u
            on a.user_id = u.user_id
      where a.class_id = ?
      order by
         lname,
         fname
   ', $classId);
}

function participantsForClass($classId) {
   return pdo_seleqt("
      select
         e.user_id,
         u.fname,
         u.lname,
         e.shirtchoice
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

function phase1attendance($userId, $classId) {
  if(PRODUCT != 'dpp') return 0;

  $qr = seleqt_one_record('
    select numclasses_phase1
    from attendance3
    where tracker_user_id = ? and class_id = ?
  ', array($userId, $classId));

  return $qr['numclasses_phase1'];
}

function phase2attendance($userId, $classId) {
  if(PRODUCT != 'dpp') return 0;
  
  $qr = seleqt_one_record('
    select numclasses_phase2
    from attendance3
    where tracker_user_id = ? and class_id = ?
  ', array($userId, $classId));

  return $qr['numclasses_phase2'];
}

function refundCard($userId, $classId) {
  if(
    PRODUCT == 'dpp' &&
    phase1attendance($userId, $classId) >= 9 &&
    phase2attendance($userId, $classId) >= 5
  ) {

    $qr = seleqt_one_record('
      select refund_method, refund_email_address, refund_postal_address, ifnc, amount
      from ' . ENR_VIEW . '
      where user_id = ? and class_id = ?
    ', array($userId, $classId));

    if($qr['ifnc'] == '1' && $qr['amount'] == 30) {
      ?>

      <div class="refund-box">
        <p>
          Your $30.00 refund cannot be put back on your credit card or directly
          in your bank account. You can receive your refund via:
        </p>

        <p>
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
        </p>

        <p id="paypal-address">
          Please provide email address linked to your PayPal account:<br />
          <input id="paypal-address-input" />
        </p>

        <p>
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
        </p>

        <p id="check-address">
          Please provide the full mailing address (including zip code) where you
          would like the check to be sent:<br />
          <input id="check-address-input" />
        <p>

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

?>
