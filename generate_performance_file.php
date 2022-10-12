<?php
require_once('config.php');

if(isset($argv[1]) && isset($argv[2])) {
   $month = $argv[1];
   $year = $argv[2];
}
else {
   $monthAgo = strtotime('-1 month');
   $month = date('n', $monthAgo);
   $year = date('Y', $monthAgo);
}

echo "Executing query for month $month and year $year.\n\n";

$qr = pdo_seleqt('select
   First_Name,
   Last_Name,
   Email,
   Zipcode,
   Gender,
   Race,
   Ethnicity,
   Age,
   Education_Level,
   Member_State,
   BCBS_Subscriber_ID,
   Referred_By,
   Provider_Name,
   Provider_State,
   Date_Joined,
   Class_Start,
   Class_End,
   Attendance_CurrentMonth,
   Termination,
   CDC_Risk_Score,
   Height,
   Beginning_Weight,
   Current_Weight,
   Ending_Weight,
   Beginning_BMI,
   Current_BMI,
   Ending_BMI,
   Beginning_Waist_Circumference,
   Ending_Waist_Circumference,
   Program_Goal,
   Beginning_HbA1c,
   Ending_HbA1c,
   Beginning_Fasting_Glucose,
   Ending_Fasting_Glucose,
   Syst_Start,
   Syst_End,
   Dias_Start,
   Dias_End,
   Physical_Activity_Minutes_Avg,
   Steps_Per_Week_Avg,
   NPS_Score
from performance_file where attendance_month = ? and attendance_year = ?', array($month, $year));

$dataFileName = 'Value_Based_Benefits_Performance_NCSU_'.date('mdY_His').'.csv';
$file = fopen($dataFileName, 'w');
fwrite($file, array_to_csv($qr));
fclose($file);

$xmlFileName = 'NCSU_BCBSNC_PERFORMANCE_VNDR_'.DATE('Ymd_His').'_CONTROL.xml';

generateAlisFile(
  $xmlFileName,
  '10333',
  $dataFileName,
  count($qr)
);

execLog('gpg -r mftpsvc --encrypt --trust-model always ' . $dataFileName);
execLog('gpg -r mftpsvc --encrypt --trust-model always ' . $xmlFileName);
execLog("scp $dataFileName.gpg $xmlFileName.gpg NCSU_PSFTP@mftp.bcbsnc.com:/");

?>
