<?php
require_once("config.php");
session_start();
if(!am_i_admin()) {
   exit("You must be logged in as an administrator to see this page.");
}

if(!isset($_GET['report'])) {
   exit("No report specified.");
}

if($_GET['report'] == "attendance") {
   require_get_vars("class");
   $qr = pdo_seleqt("
      select
         class_name,
         voucher_code,
         fname,
         lname,
         numclasses,
         address1,
         address2,
         city,
         state,
         zip,
         dob
      from attendance
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

if(empty($qr)) {
   exit("No report returned. Parameters: " . print_r($_GET, true));
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
            echo $key;
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
