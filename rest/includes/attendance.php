<?php
if($_SERVER['REQUEST_METHOD'] == 'GET') {
  getAttendance();
}
else if($_SERVER['REQUEST_METHOD'] == 'POST') {
  postAttendance();
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
  $ok_array['attendance'] = attendanceSummary3ForClass($_GET['class_id']);
}

function postAttendance() {
  // Copied from attendance_ajax.php on 2/23/2020
  if(can_access_class($_POST['class_id'], 'w')) {
    $phase1before = phase1attendance($_POST['user_id'], $_POST['class_id']);
 
    $dbh = pdo_connect(DB_PREFIX . '_insert');
    $sth = $dbh->prepare('
      insert into wrc_attendance (
        user_id,
        class_id,
        class_source,
        week,
        present,
        attendance_type,
        attendance_date
      ) values (?, ?, ?, ?, ?, ?, ?)
    ');
    if($sth->execute(array(
      $_POST['user_id'],
      $_POST['class_id'],
      'w',
      $_POST['week'],
      $_POST['present'],
      $_POST['attendance_type'],
      nullIfBlank($_POST['attendance_date'])
    ))) {
      global $ok_array;
      $ok_array['weight'] = 0;

      $phase1after = phase1attendance($_POST['user_id'], $_POST['class_id']);

      if($phase1before == 8 && $phase1after == 9) {
        // 9 classes in phase 1 - participant has earned t-shirt.
        logtxt('sending...');
        sendById($_POST['user_id'], 6, -1, true);
      }
    }
    else {
      logtxt('ERROR: unknown database error.');
      exit('ERROR: unknown database error.');
    }
  }
  else {
    logtxt(
      'ERROR: User ID ' .
      $_SESSION['user_id'] .
      ' cannot access class ID ' .
      $_POST['class_id'] . '.'
    );
    exit('ERROR: access error.');
  }
}

?>
