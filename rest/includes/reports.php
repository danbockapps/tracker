<?php
if(!isset($_SESSION['user_id'])) {
   http_response_code(401); // Unauthorized
   exit;
}

$qr = current_class_by_user($_SESSION['user_id']);

$qr2 = getReports($_SESSION['user_id'], $qr['start_dttm']);

$ok_array['data'] = $qr2;
?>