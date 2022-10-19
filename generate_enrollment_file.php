
<?php
require_once('config.php');

$qr = pdo_seleqt('
   select
      2 as type,
      claim_id,
      date_format(reg_date, "%m/%d/%Y") as reg_date
   from enrollment_view
   where voucher_code = "FIBCBSNC"
', array());

$dataFileName = 'Value_Based_Benefits_Enrollment_NCSU_' . date('mdY_His') . '.txt';
$file = fopen($dataFileName, 'w');
fwrite($file, '1|NCSU|ESMM|' . date('m/d/Y') . "|\n");

foreach($qr as $row) {
   $claim_id = $row['claim_id'];
   $reg_date = $row['reg_date'];
   fwrite($file, "2|$claim_id|$reg_date|\n");
}

fwrite($file, '3|' . (count($qr) + 2) . "|\n");
fclose($file);

$xmlFileName = 'NCSU_BCBSNC_ENRL_VNDR_UAT_'.date('Ymd_His').'_CONTROL.xml';

generateAlisFile(
   $xmlFileName,
   '18367',
   $dataFileName,
   count($qr) + 2
);

execLog('gpg -r mftpsvc --encrypt --trust-model always ' . $dataFileName);
execLog('gpg -r mftpsvc --encrypt --trust-model always ' . $xmlFileName);
execLog("scp $dataFileName.gpg $xmlFileName.gpg NCSU_PSFTP@mftp.bcbsnc.com:/");


?>
