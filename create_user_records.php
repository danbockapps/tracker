<?php

require_once('config.php');

$qr = pdo_seleqt('
   select *
   from registrants
   where
      paid != "0"
      and email not in (
         select email
         from wrc_users
      )
   order by user_id asc;
', array());

foreach($qr as $row) {
   if(!email_already_in_db($row['email'])) {
      $dbh = pdo_connect($ini['db_prefix'] . '_insert');
      $sth = $dbh->prepare('
         insert into wrc_users(
            user_id,
            password,
            activation,
            fname,
            lname,
            email,
            participant,
            instructor,
            administrator,
            research,
            date_added
         ) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())
      ');

      $sth->execute(array(
         next_user_id(),
         'TRACKER_NO_REG',
         md5(uniqid(rand(), true)),
         $row['fname'],
         $row['lname'],
         $row['email'],
         1,
         0,
         0,
         0
      ));
   }
}

/* set tracker_user_id in registrants/enrollment table */
$dbh = pdo_connect($ini['db_prefix'] . '_update');
$sth = $dbh->prepare('
   update
      ' . ENR_TBL . ' r
      inner join wrc_users u
         on r.email = u.email
   set r.tracker_user_id = u.user_id
   where
      r.tracker_user_id is null
      and r.paid != "0"
');
$sth->execute();


?>
