<?php
require_once("config.php");
session_start();
if(!am_i_admin()) {
   exit("You must be logged in as an administrator to see this page.");
}

if(!isset($_GET['report'])) {
   exit("No report specified.");
}

if($_GET['report'] == "attendance2") {
   require_get_vars("class");
   $qr = pdo_seleqt("
      select
         class_name,
         voucher_code,
         fname,
         lname,
         numclasses,
         numclasses_phase1,
         numclasses_phase2,
         address1,
         address2,
         city,
         state,
         zip,
         dob
      from attendance2
      where class_id = ?
   ", array($_GET['class']));
}
if($_GET['report'] == "attendance3") {
   require_get_vars("class");
   $qr = pdo_seleqt("
      select
         class_name,
         voucher_code,
         fname,
         lname,
         numclasses,
         numclasses_phase1,
         numclasses_phase2,
         full_participation,
         address1,
         address2,
         city,
         state,
         zip,
         dob,
         shirt_desc,
         refund_method,
         refund_email_address,
         refund_postal_address
      from attendance3
      where class_id = ?
   ", array($_GET['class']));
}
else if($_GET['report'] == $ini['client1']) {
   require_get_vars("voucher_code");
   $qr = pdo_seleqt("
      select *
      from " . $ini['client1_reports'] . "
      where Coupon_Code = ?
   ", array($_GET['voucher_code']));
}
else if($_GET['report'] == 'aso_codes') {
   $qr = pdo_seleqt("select * from aso_codes", null);
}
else if($_GET['report'] == 'addresschanges') {
   $qr = pdo_seleqt(
      "select * from wrc_addresschanges order by create_dttm desc",
      null
   );
}
else if($_GET['report'] == 'performance_file') {
   $qr = pdo_seleqt('select * from performance_file', null);
}

if(empty($qr)) {
   exit("No report returned.");
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="wrc.css" />
<style>
body {background-color:white;}
</style>
</head>
<body>

<table class="wrctable">
   <tr> <!-- header row -->
   <?php
      foreach($qr[0] as $key => $value) {
         ?><th><?php
            echo htmlentities($key);
         ?></th><?php
      }
   ?>
   </tr>
   <?php
      foreach($qr as $row) {
         ?><tr><?php
            foreach($row as $value) {
               ?><td><?php
                  echo htmlentities($value);
               ?></td><?php
            }
         ?></tr><?php
      }
   ?>
</table>


<?php

?>
</body>
</html>
