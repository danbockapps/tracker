<?php
session_start();

require_once('config.php');
define(REDIRECT_URI, WEBSITE_URL . '/connect_to_fitbit.php');

if (!isset($_GET['code'])) {

   if(!isset($_SESSION['user_id'])) {
      exit('Error: not logged in.');
   }

   debug('Redirecting to Fitbit server from ' . uriWithQueryString());
   $_SESSION['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
   authorizationRequest();
   die('Redirect');
}
else {
   // We have the code, so we can get a token
   debug('Code received at ' . uriWithQueryString());

   handleSubdomainCookieProblem();

   $tokens = getTokens($_GET['code']);
   $accessToken = $tokens->access_token;
   $refreshToken = $tokens->refresh_token;

   saveTokensToDatabase($_SESSION['user_id'], $accessToken, $refreshToken);

   getStepsFromFitbit($_SESSION['user_id']);
   subscribeToFitbitSteps($_SESSION['user_id'], $accessToken);

   $httpReferer = $_SESSION['HTTP_REFERER'];
   unset($_SESSION['HTTP_REFERER']);
   header('Location: ' . $httpReferer);
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

   // Body parameters
   $params = array();
   $params['code'] = $code;
   $params['grant_type'] = 'authorization_code';
   $params['redirect_uri'] = REDIRECT_URI;

   return fitbitTokenRequest($params);
}

function subscribeToFitbitSteps($userId, $accessToken) {
   $category = 'activities';
   $subId = "$userId+$category";

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

?>
