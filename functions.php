<?php

function getStepsFromFitbit($userId, $doNotRefresh = false) {
   $metric = 'activities-steps';
   $urlMetric = 'activities/steps';

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
      array('Authorization: Bearer ' . $qr['fitbit_access_token'])
   );
   curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

   $response = curl_exec($c);
   $httpCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
   curl_close($c);

   debug('Fitbit response to steps request for user ' . $userId . ':');
   debug($httpCode);
   debug($response);

   if($httpCode == 200) {
      $stepsArray = json_decode($response)->$metric;
      $sqlValues = array();
      $dbh = pdo_connect(DB_PREFIX . '_insert');

      foreach($stepsArray as $day) {
         $sqlValues[] =
            '(' .
            implode(',', array(
               $dbh->quote($userId),
               $dbh->quote($day->dateTime),
               $dbh->quote($metric),
               $dbh->quote($day->value)
            )) .
            ')';
      }

      $sql = 'insert into wrc_fitbit (user_id, date, metric, value) values ' .
         implode(',', $sqlValues);

      debug($sql);

      $sth = $dbh->prepare($sql);
      debug('Return value from inserting steps: ' . $sth->execute());
   }

   else if(
      $httpCode == 401 &&
      json_decode($response)->errors[0]->errorType == 'expired_token' &&
      $doNotRefresh == false
   ) {
      if(refreshFitbitToken($userId, $qr['fitbit_refresh_token'])) {
         getStepsFromFitbit($userId, true);
      }
   }

   else {
      logtxt('ERROR: Error getting data from Fitbit.');
      exit('ERROR: Error getting data from Fitbit.');
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
   $qr = seleqt_one_record('
      select max(date) as date
      from wrc_fitbit
      where
         user_id = ?
         and metric = ?
         and value > 0
   ', array($userId, $metric));

   if($qr['date'] != null) {
      return $qr['date'];
   }
   else {
      $class = current_class_by_user($userId);
      return date('Y-m-d', strtotime($class['start_dttm'] . ' -8 day'));
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

function subscribeToFitbitSteps($userId, $accessToken) {
   $category = 'activities';
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
   $rangeStart = date('Y-m-d', strtotime($reportDate . ' - 8 day'));
   $rangeEnd =   date('Y-m-d', strtotime($reportDate . ' - 1 day'));

   //TODO create a view with only the latest value from each date, and use that here.

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
?>
