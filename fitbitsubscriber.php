<?php
require('config.php');

logtxt('Fitbit Subscriber receiving data...');
logtxt('$_GET:');
logtxt(print_r($_GET, true));

if(isset($_GET['verify'])) {
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

   list($userId, $category) = explode('+', $subscriptionId);
   
   logtxt('User id is: ' . $userId);
   logtxt('Category is: ' . $category);

   // Fitbit says don't send your API request until after you've
   // responded to the subscription notification with the 204.
   // Stack Overflow Q on how to do that:
   // https://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response
   ob_start();
   logtxt('Responding with 204.');
   header('Content-Encoding: none');
   header('Content-Lenth: 0');
   header('HTTP/1.0 204 No Content');
   ob_end_flush();
   ob_flush();
   flush();

   getStepsFromFitbit($userId);

}

?>
