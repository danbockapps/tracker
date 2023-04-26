<?php
if($_SERVER['REQUEST_METHOD'] == 'GET') {
  getIReports();
}
else if($_SERVER['REQUEST_METHOD'] == 'POST') {
  postReports();
}
else {
  exit('ERROR: Unsupported request method.');
}

function getIReports() {
  if(!isset($_GET['class_id'])) {
    exit('Error: no class ID');
  }

  global $ok_array;
  $ok_array['reports'] = pdo_seleqt('
    select
      user_id,
      lesson_id,
      weight,
      physact_minutes
    from wrc_ireports
    where class_id = ?
  ', $_GET['class_id']);
}

function postReports() {
  if(!isset($_POST['user_id'], $_POST['class_id'], $_POST['lesson_id'])) {
    exit('Error: missing variable.');
  }


  if(ireportExists($_POST['user_id'], $_POST['class_id'], $_POST['lesson_id'])) {
    updateReport(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['lesson_id'],
      nullIfBlank($_POST['weight']),
      nullIfBlank($_POST['physact_minutes'])
    );
  }
  else {
    insertReport(
      $_POST['user_id'],
      $_POST['class_id'],
      $_POST['lesson_id'],
      nullIfBlank($_POST['weight']),
      nullIfBlank($_POST['physact_minutes'])
    );
  }
}

function updateReport($userId, $classId, $lessonId, $weight, $physactMinutes) {
  $dbh = pdo_connect(DB_PREFIX . "_update");
  $sth = $dbh->prepare('
    update wrc_ireports
    set
      weight = ?,
      physact_minutes = ?
    where
      user_id = ? and
      class_id = ? and
      lesson_id = ?
  ');
  $sth->execute(array($weight, $physactMinutes, $userId, $classId, $lessonId));
}

function insertReport($userId, $classId, $lessonId, $weight, $physactMinutes) {
  $dbh = pdo_connect(DB_PREFIX . '_insert');
  $sth = $dbh->prepare('
    insert into wrc_ireports (user_id, class_id, lesson_id, weight, physact_minutes)
    values (?, ?, ?, ?, ?)
  ');
  $sth->execute(array($userId, $classId, $lessonId, $weight, $physactMinutes));
}

?>
