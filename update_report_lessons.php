<?php
require_once('config.php');

$qr = pdo_seleqt("
  select class_id
  from classes_aw
  order by class_id;
", []);

$dbh = pdo_connect($ini['db_prefix'] . "_update");

echo "Starting...\n";

foreach($qr as $row) {
  echo date("Y-m-d G:i:s") . " Starting class " . $row['class_id'] . "\n";

  $sth = $dbh->prepare("
    update
      wrc_reports r
      inner join reports_with_lessons rl using (user_id, class_id, week_id)
    set
      r.lesson = rl.lesson
    where
      r.lesson is null
      and rl.lesson is not null
      and class_id = " . $row['class_id']
  );
  $sth->execute();

  echo date("Y-m-d G:i:s") . " Done with class " . $row['class_id'] . "\n";
}

?>
