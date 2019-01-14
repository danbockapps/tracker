<?php
if($_SERVER['REQUEST_METHOD'] == 'GET') {
  getAttendance();
}
else {
  exit('ERROR: Unsupported request method.');
}

if(!am_i_instructor() && !am_i_admin()) {
  exit('ERROR: Not instructor or admin');
}

function getAttendance() {
  if(!isset($_GET['class_id'])) {
    exit('Error: no class ID');
  }

  global $ok_array;
  $ok_array['attendance'] = attendanceForClass($_GET['class_id']);
}

?>