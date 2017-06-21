<?php
require('config.php');

logtxt('Fitbit Subscriber receiving data...');
logtxt('$_SERVER:');
logtxt(print_r($_SERVER, true));
logtxt('$_GET:');
logtxt(print_r($_GET, true));

if(isset($_GET['verify'])) {
   if($_GET['verify'] == FITBIT_SVC) {
      logtxt('Responding with 204.');
      header('HTTP/1.0 204 No Content');
   }
   else {
      logtxt('Responding with 404.');
      header('HTTP/1.0 404 Not Found');
   }
}

else {
   $entityBody = file_get_contents('php://input');
   logtxt('Entity body:');
   logtxt($entityBody);
   logtxt('Responding with 204.');
   header('HTTP/1.0 204 No Content');
}

?>
