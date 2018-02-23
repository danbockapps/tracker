<?php
// This is a hack, but it allows for not touching the dev/test/prod section
// of config.php.
//if(strpos($_SERVER['argv'][0], "trunk") === false) {
  // test or prod
//  $_SERVER['HTTP_HOST'] = "esmmweighless.com";
//  $_SERVER['REQUEST_URI'] = $_SERVER['argv'][0];
//}
// else dev
// end hack

require_once("config.php");

$aqr = pdo_seleqt("
   select voucher_code
   from bcbs_voucher_codes
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
         select *
         from bcbs_report
         where Coupon_Code = ?
      ", array($row['voucher_code'])))
   );

   generateControlFile($row['voucher_code'], $dataFileName);
}


// List of active ASO codes

file_put_contents(
   "/home/" .
   exec('whoami') .
   "/aso/upload/ESMMWL_List_of_active_ASO_codes_" . date("Y-m-d_H-i-s")  . ".csv",
   array_to_csv(pdo_seleqt("select * from aso_codes", ""))
);

function generateControlFile($voucherCode, $dataFileName) {
  $xml = new SimpleXMLElement('<ALIS version="2.0"/>');
  $t = $xml->addChild('Transaction');
  $t->addChild('SessionID', $dataFileName);

  $processTime = $t->addChild('ProcessTime');
  $processTime->addAttribute('startDtTime', date("Y-m-d H:i:s"));
  $processTime->addAttribute('endDtTime', date("Y-m-d H:i:s"));

  $qr = seleqt_one_record(
    'select count(*) as count from bcbs_report where Coupon_Code = ?',
    array($voucherCode)
  );

  $t->addChild('TransactionCount', $qr['count']);

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
