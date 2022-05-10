<?php

/**
 * Called from attendance_entry.php (for ESMMWL and ESMMWL2) and
 * attendance_entry_detailed.php (for MPP)
 */

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
    $perfectCountBefore = getPerfectCount($_POST['user_id'], $_POST['class_id']);
 
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
      $_POST['lesson_id'],
      $_POST['present'],
      $_POST['attendance_type'],
      nullIfBlank($_POST['attendance_date'])
    ))) {
      addMetricsToOkArray();

      $phase1after = phase1attendance($_POST['user_id'], $_POST['class_id']);


      if (
        (PRODUCT == 'dpp' && $phase1before == 8 && $phase1after == 9) ||
        (PRODUCT == 'esmmwl' &&
            $perfectCountBefore == 13 &&
            getPerfectCount($_POST['user_id'], $_POST['class_id']) == 14) ||
        (PRODUCT == 'esmmwl2' &&
            $perfectCountBefore == 10 &&
            getPerfectCount($_POST['user_id'], $_POST['class_id']) == 11)
      ) {
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

function addMetricsToOkArray() {
  // Add the weight and physact_minutes for a report (if any) that's within
  // 4 days of the attendance date.
  // This facilitates the front end populating those metrics right after
  // attendance is entered. To populate them on page load, a call to
  // weightpa is used.

  if(isset($_POST['attendance_date'])) {
    $qr = pdo_seleqt('
      select
        weight,
        physact_minutes
      from
        reports_with_fitbit_hybrid r
        inner join classes_aw c
          on r.class_id = c.class_id
      where
        r.user_id = ?
        and r.class_id = ?
        and abs(datediff(c.start_dttm + interval cast(week_id as signed) - 1 week, ?)) <= 4
    ', array($_POST['user_id'], $_POST['class_id'], $_POST['attendance_date']));

    if(count($qr) > 0) {
      global $ok_array;
      $ok_array['weight'] = $qr[0]['weight'];
      $ok_array['physact_minutes'] = $qr[0]['physact_minutes'];
    }
  }
}

?>
