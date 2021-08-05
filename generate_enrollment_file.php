<?php
require_once('config.php');

$qr = pdo_seleqt('
   select
      2 as type,
      concat(subscriber_id, member_number) as subscriber_id,
      date_format(reg_date, "%m/%d/%Y") as reg_date
   from enrollment_view
', array());


$file = fopen('Value_Based_Benefits_Enrollment_NCSU_' . date('mdY_His') . '.txt', 'w');
fwrite($file, '1|NCSU|ESMM|' . date('m/d/Y') . "|\n");

foreach($qr as $row) {
   fputcsv($file, $row, '|');
}

fwrite($file, '3|' . count($qr));
fclose($file);

?>
