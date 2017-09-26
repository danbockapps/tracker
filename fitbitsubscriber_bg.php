<?php
/* This gets launched by fitbitsubscriber.php, then runs in bg */

require_once('config.php');

logtxt(print_r($argv, true));

$userId = $argv[1];
logtxt('(bg) User id is: ' . $userId);
$category = $argv[2];
logtxt('(bg) Category is: ' . $category);

if($category == 'activities') {
   getStepsFromFitbitAndInsert($userId);
   getMfaFromFitbitAndInsert($userId);
   getMvaFromFitbitAndInsert($userId);
}
else if($category == 'body') {
   getWeightFromFitbitAndInsert($userId);
}

logtxt('Starting refresh of static table.');
exec(MYSQL_COMMAND . ' < refreshReportsWithFitbitStatic.sql');
logtxt('Done with refresh of static table.');

?>
