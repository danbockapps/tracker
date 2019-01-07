<?php
if(!isset($_GET['class_id'])) {
  exit('Error: no class ID');
}

$ok_array['reports'] = pdo_seleqt('
  select
    user_id,
    week_id,
    weight,
    physact_minutes
  from wrc_reports
  where class_id = ?
', $_GET['class_id']);

?>