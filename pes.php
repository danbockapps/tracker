<?php
require_once("config.php");

if(!isset($_GET['user'])) {
  $_GET['user'] = 2211;
  $_GET['class'] = 295;
  $_GET['source'] = "a";
}

$uqr = seleqt_one_record("
  select pes
  from pes
  where
    user_id = ?
    and class_id = ?
    and class_source = ?
", array($_GET['user'], $_GET['class'], $_GET['source']));

$aqr = seleqt_one_record("
  select avg(pes) as avg
  from pes_static
", array());

echo json_encode(array(
   array("Name", "Score"),
   array("You", min(round($uqr['pes']), 100)),
   array("Avg", min(round($aqr['avg']), 100))
));

?>
