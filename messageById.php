<?php
if(!isset($argv[1]) || !isset($argv[2])) {
   exit('Error: args not set.');
}

require_once('config.php');

$recipient = getRecipient($argv[1]);
$subject = getSubject($argv[2]);
$msg = getMessage($argv[1], $argv[2], $argv[3], $recipient);
syncMail($recipient, $subject, $msg);

?>
