<?php
require_once("config.php");
ini_set('error_log', '/home/esmmwl/aso/error_log');

$bcbsReportFields = "
   First_Name,
   Last_Name,
   Email,
   Referred_By,
   Date_Joined,
   Class_Start,
   Class_End,
   Coupon_Code,
   BCBS_Subscriber_ID,
   Member_Number,
   Attendance,
   Beginning_Weight,
   Ending_Weight,
   Height,
   Beginning_BMI,
   Ending_BMI,
   Beginning_Waist_Circumference,
   Ending_Waist_Circumference,
   Tracker_Activity_Score,
   Program_Goals
";

$aqr = pdo_seleqt("
   select voucher_code
   from bcbs_voucher_codes
   where upper(voucher_code) in (
      select upper(code)
      from aso_codes
   )
", array());

foreach($aqr as $row) {
   $dataFileName = 
      "ESMMWL_" .
      $row['voucher_code'] .
      "_" .
      date("Y-m-d_H-i-s") .
      ".csv";

   file_put_contents(
      "/home/esmmwl/aso/upload/" . $dataFileName,
      array_to_csv(pdo_seleqt("
         select $bcbsReportFields
         from bcbs_report
         where Coupon_Code = ?
      ", array($row['voucher_code'])))
   );

   generateControlFile($row['voucher_code'], $dataFileName);
}

// *** Special file for ASONCMS* codes ***

$dataFileName = "ESMMWL_ASONCMS_" . date("Y-m-d_H-i-s") . ".csv";

file_put_contents("/home/esmmwl/aso/upload/" . $dataFileName, array_to_csv(pdo_seleqt("
   select $bcbsReportFields from bcbs_report where Coupon_Code like 'ASONCMS%'
", array())));

generateControlFile('ASONCMS', $dataFileName);

// *** End special file for ASONCMS* codes ***


// List of active ASO codes

file_put_contents(
   "/home/esmmwl/aso/upload/ESMMWL_List_of_active_ASO_codes_" . date("Y-m-d_H-i-s")  . ".csv",
   array_to_csv(pdo_seleqt("select * from aso_codes where code != 'ASONCMSMailing'", ""))
);

function generateControlFile($voucherCode, $dataFileName) {
  $xml = new SimpleXMLElement(
    '<?xml version="1.0" encoding="iso-8859-1"?><ALIS version="1.1"/>');
  $t = $xml->addChild('Transaction');
  $t->addAttribute('processCd', '1984');
  $t->addAttribute('txnType', 'Balance');
  $t->addChild('SessionId', $dataFileName);

  $processTime = $t->addChild('ProcessTime');
  $processTime->addAttribute('startDtTime', date("Y-m-d H:i:s.000"));
  $processTime->addAttribute('endDtTime', date("Y-m-d H:i:s.000"));

  $qr = seleqt_one_record(
    'select count(*) as count from bcbs_report where Coupon_Code = ?',
    array($voucherCode)
  );

  $t->addChild('TransactionCount', $qr['count']);
  $t->addChild('MonetaryAmount', 0);

  file_put_contents(
    '/home/esmmwl/aso/upload/ESMMWL_BCBSNC_' .
      $voucherCode .
      '_MemberProgress_' .
      date('ymd_His') .
      '_CONTROL.xml',
    $xml->asXML()
  );

}

?>
