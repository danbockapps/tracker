<?php

/*
This script should be run by cron daily. It checks to see if any classes have
a phase 1 end date of today. If so, it sets status=0 for each participant in
each of those classes who has not met the phase 1 attendance requirement (9).
*/

require_once('config.php');

$dbh = pdo_connect($ini['db_prefix'] . "_update");
$sth = $dbh->prepare('
   update registrants
   set status="0"
   where class_id in (
      select class_id
      from classes_deadline_today
   ) and tracker_user_id not in (
      select user_id
      from attendance_sum2
      where
         numclasses >= 9 and
         /* TODO this should require a match of month AND year */
         month in (
            select month
            from classes_deadline_today
         ) and
         year in (
            select year
            from classes_deadline_today
         )
   )
');
$sth->execute();


/* Also deactivate SHP members who have lost eligibility */
$sth = $dbh->prepare('
   update registrants
   set status="0"
   where
      class_id in (
         select class_id
         from classes_deadline_today
      )
      and user_id in (
         select registrant_id
         from shp_members_base
      )
      and user_id not in (
         select user_id
         from shp_members_current
      )
');
$sth->execute();
      

?>
