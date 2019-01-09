<?php
if($_SERVER['REQUEST_METHOD'] == 'GET') {
  getReports();
}
else if($_SERVER['REQUEST_METHOD'] == 'POST') {
  postReports();
}
else {
  exit('ERROR: Unsupported request method.');
}

function getReports() {
  if(!isset($_GET['class_id'])) {
    exit('Error: no class ID');
  }

  global $ok_array;
  $ok_array['reports'] = pdo_seleqt('
    select
      user_id,
      week_id,
      weight,
      physact_minutes
    from wrc_reports
    where class_id = ?
  ', $_GET['class_id']);
}

function postReports() {
  if(!isset($_POST['user_id'], $_POST['class_id'], $_POST['week_id'])) {
    exit('Error: missing variable.');
  }


  if(reportExists($_POST['user_id'], $_POST['class_id'], $_POST['week_id'])) {
    updateReport(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['week_id'],
      $_POST['weight'],
      $_POST['physact_minutes']
    );
  }
  else {
    insertReport(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['week_id'],
      $_POST['weight'],
      $_POST['physact_minutes']
    );
  }
}

function updateReport($userId, $classId, $weekId, $weight, $physactMinutes) {
  $dbh = pdo_connect(DB_PREFIX . "_update");
  $sth = $dbh->prepare('
    update wrc_reports
    set
      weight = ?,
      physact_minutes = ?
    where
      user_id = ? and
      class_id = ? and
      week_id = ?
  ');
  $sth->execute(array($weight, $physactMinutes, $userId, $classId, $weekId));
}

function insertReport($userId, $classId, $weekId, $weight, $physactMinutes) {
  $dbh = pdo_connect(DB_PREFIX . '_insert');
  $sth = $dbh->prepare('
    insert into wrc_reports (user_id, class_id, week_id, weight, physact_minutes)
    values (?, ?, ?, ?, ?)
  ');
  $sth->execute(array($userId, $classId, $weekId, $weight, $physactMinutes));
}

?>