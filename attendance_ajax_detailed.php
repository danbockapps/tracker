<?php
session_start();
require_once('config.php');
logtxt('attendance ajax detailed');
logtxt(print_r($_POST, true));
echo 'hello from attendance_ajax_detailed.php';
?>
