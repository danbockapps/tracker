<?php
session_start();
require_once("config.php");

$qr = current_class_and_sg();
access_restrict($qr);
$numWeeks = getNumWeeks($qr['class_id'], $qr['class_source']);

$qr2 = getReports($_GET['user'], $qr['start_dttm']);

foreach($qr2 as $row) {
  if($row['weight'] != 0) {
     $reports_empty = false;
  }
  $reports['weight'][$row['week_id']-1] = $row['weight'];
  $reports['aerobic'][$row['week_id']-1] = $row['aerobic_minutes'];
  $reports['strength'][$row['week_id']-1] = $row['strength_minutes'];
  $reports['physact'][$row['week_id']-1] = $row['physact_minutes'];
  $reports['avgsteps'][$row['week_id']-1] = $row['avgsteps'];
}


for($i=0; $i<$numWeeks; $i++) {
  ?>
  <h2><?= wrcdate($qr['start_dttm'] . " + $i weeks") ?></h2>
  <p>
    Weight: <?= $reports['weight'][$i] ?><br/>
    Aerobic minutes: <?= $reports['aerobic'][$i] ?><br/>
    Strength minutes: <?= $reports['strength'][$i] ?><br/>
    Physical activity minutes: <?= $reports['physact'][$i] ?><br/>
    Average steps: <?= round($reports['avgsteps'][$i], 0) ?><br/>
  </p>
  <?php
}

?>