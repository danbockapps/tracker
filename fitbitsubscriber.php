<?php
require('config.php');

logtxt('Fitbit Subscriber receiving data...');

logtxt(print_r($argv, true));

if(isset($_GET['verify'])) {
   logtxt('$_GET:');
   logtxt(print_r($_GET, true));
   if($_GET['verify'] == FITBIT_SVC) {
      logtxt('Responding to verify with 204.');
      header('HTTP/1.0 204 No Content');
   }
   else {
      logtxt('Responding to verify with 404.');
      header('HTTP/1.0 404 Not Found');
   }
}

else {
   $entityBody = file_get_contents('php://input');
   logtxt('Entity body:');
   logtxt($entityBody);

   $subscriptionId = json_decode($entityBody)[0]->subscriptionId;
   logtxt('Subscription id is ' . $subscriptionId);

   list($userId, $category) = explode('_', $subscriptionId);
   
   logtxt('User id is: ' . $userId);
   logtxt('Category is: ' . $category);

   $cmd = "php-cli fitbitsubscriber_bg.php $userId $category >/dev/null &";
   logtxt($cmd);
   exec($cmd);

   logtxt('Responding with 204.');
   header('HTTP/1.0 204 No Content');
}

?>
