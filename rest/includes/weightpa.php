<?php

if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['class_id'])) {
  $ok_array['reports'] = pdo_seleqt('
    select
      c.start_dttm + interval cast(week_id as signed) - 1 week as date,
      r.user_id,
      r.weight,
      r.physact_minutes
    from
      reports_with_fitbit_hybrid r
      inner join classes_aw c
        on r.class_id = c.class_id
    where r.class_id = ?
  ', $_GET['class_id']);
}

?>