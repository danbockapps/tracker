<?php
session_start();
require_once('config.php');
define(DEBUG, true);
define(REDIRECT_URI, WEBSITE_URL . '/connect_to_fitbit.php');

if (!isset($_GET['code'])) {
   debug('Redirecting to Fitbit server from ' . uriWithQueryString());
   $_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
   authorizationRequest();
   die('Redirect');
}
else {
   // We have the code, so we can get a token
   debug('Code received at ' . uriWithQueryString());

   // need this when making actual requests...
   // $headerText = 'Authorization: Bearer ' . getTokens($_GET['code']);

   handleSubdomainCookieProblem();

   $tokens = getTokens($_GET['code']);
   $accessToken = $tokens->access_token;
   $refreshToken = $tokens->refresh_token;

   debug('Access token: ' . $accessToken);
   debug('Refresh token: ' . $refreshToken);

   saveTokensToDatabase($accessToken, $refreshToken);
   header('Location: ' . $_SESSION['HTTP_REFERER']);
}

function handleSubdomainCookieProblem() {
   if(!isset($_SESSION['user_id'])) {
      // We are on the wrong subdomain. Have to switch.
      debug('No session found. Switching subdomains...');

      // If this is infinite redirection, abort.
      if($_SESSION['stopInfiniteRedirect']) {
         debug('Nevermind - aborting with error code SUBDOMAIN.');
         $_SESSION['stopInfiniteRedirect'] = false;
         exit('Error code: SUBDOMAIN');
      }

      // First set a breadcrumb to prevent infinite redirect
      $_SESSION['stopInfiniteRedirect'] = true;

      // Then figure out where to redirect
      if(substr($_SERVER['SERVER_NAME'], 0, 3) === 'www') {
         debug('Server starts with www. Removing that.');
         $newLocation = str_replace('www.', '', $_SERVER['SCRIPT_URI']);
      }
      else {
         debug('Server does not start with www. Adding that.');
         $newLocation =
            str_replace('https://', 'https://www.', $_SERVER['SCRIPT_URI']);
      }

      $newLocation .= '?' . $_SERVER['QUERY_STRING'];
      debug('Redirecting to new location: ' . $newLocation);
      header('Location: ' . $newLocation);
      die('You have been redirected to another subdomain.');
   }
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

   $params = array();
   $params['code'] = $code;
   $params['grant_type'] = 'authorization_code';
   $params['redirect_uri'] = REDIRECT_URI;

   $curl = curl_init('https://api.fitbit.com/oauth2/token');
   curl_setopt($curl, CURLOPT_POST, true);
   curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
   curl_setopt(
      $curl,
      CURLOPT_HTTPHEADER,
      array(
         'Authorization: Basic ' . base64_encode(FITBIT_CLIENT_ID . ':' . FITBIT_CLIENT_SECRET),
         'Content-Type: application/x-www-form-urlencoded'
      )
   );

   $response = curl_exec($curl);
   curl_close($curl);

   debug('Response to token request:');
   debug($response);

   return json_decode($response);
}

function saveTokensToDatabase($accessToken, $refreshToken) {
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
      $_SESSION['user_id']
   );

   if($sth->execute($dbArray)) {
      debug('Successfully inserted tokens into database:');
      debug(print_r($dbArray, true));
   }
   else {
      exit('Database error with Fitbit tokens.');
   }
}

function debug($s) {
   if(DEBUG) {
      logtxt($s);
   }
}

?>
