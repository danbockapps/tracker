<?php
require_once('config.php');

$qr = pdo_seleqt('select * from performance_file', array());

$path = 'Value_Based_Benefits_Performance_NCSU_'.date('mdY_His').'.csv';
$file = fopen($path, 'w');
fwrite($file, array_to_csv($qr));
fclose($file);

generateAlisFile(
  'NCSU_BCBSNC_PERFORMANCE_VNDR_'.DATE('Ymd_His').'_CONTROL.xml',
  '10333',
  $path,
  count($qr)
);

?>
