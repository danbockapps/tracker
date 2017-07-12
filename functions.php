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
   $metric,
   $doNotRefresh = false // avoid infinite loop
) {
   $urlMetric = str_replace('-', '/', $metric);

   $qr = seleqt_one_record('
      select
         fitbit_access_token,
         fitbit_refresh_token
      from wrc_users
      where user_id = ?
   ', array($userId));

   $url =
      'https://api.fitbit.com/1/user/-/' .
      $urlMetric .
      '/date/' .
      getStartDateForFitbit($userId, $metric) .
      '/' .
      date('Y-m-d') .
      '.json';

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
      if(refreshFitbitToken($userId, $qr['fitbit_refresh_token'])) {
         return getDataFromFitbit($userId, $metric, true);
      }
   }

   else {
      logtxt('ERROR: Error getting data from Fitbit.');
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


function deleteSubscription($category, $subscriptionId, $userId) {
   $url =
      'https://api.fitbit.com/1/user/-/' .
      $category .
      '/apiSubscriptions/' .
      $subscriptionId .
      '.json';

   echo $url . "\n";

   $deleteCurl = curl_init($url);

   curl_setopt($deleteCurl, CURLOPT_CUSTOMREQUEST, 'DELETE');
   curl_setopt(
      $deleteCurl,
      CURLOPT_HTTPHEADER,
      array('Authorization: Bearer ' . getAccessToken($userId))
   );
   curl_setopt($deleteCurl, CURLOPT_RETURNTRANSFER, true);
   $deleteResponse = curl_exec($deleteCurl);
   echo curl_getinfo($deleteCurl, CURLINFO_HTTP_CODE) . "\n";
   echo $deleteResponse;
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
   $rangeStart = date('Y-m-d', strtotime($reportDate . ' - 6 day'));

   $qr = pdo_seleqt('
      select value
      from fitbit
      where
         user_id = ?
         and metric = ?
         and date between ? and ?
      order by date desc
      limit 1
   ', array($userId, 'weight', $rangeStart, $reportDate));

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

function getAvgStepsArray($userId, $reportsArray, $startDttm, $numWeeks) {
   $qr = pdo_seleqt('
      select
         week_number,
         avg(value) as average_steps
      from (
         select
            floor(datediff(date, ?) / 7) + 1 as week_number,
            value
         from fitbit
         where
            metric = ?
            and user_id = ?
      ) fitbit_with_week_numbers
      group by week_number
   ', array($startDttm, 'activities-steps', $userId));

   if(empty($qr)) {
      return $reportsArray;
   }

   else {
      // Create indexed array
      $indexed = array();
      for($i=0; $i<count($qr); $i++) {
         $indexed[$qr[$i]['week_number']] = $qr[$i]['average_steps'];
      }

      for($i=0; $i<$numWeeks; $i++) {
         if(!($reportsArray[$i] > 0)) {
            $reportsArray[$i] = $indexed[$i];
         }
      }
   }

   return $reportsArray;
}

?>
