<?php
if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if(isset($_POST['user_id'], $_POST['class_id'], $_POST['shirt_id'])) {
    $dbh = pdo_connect(DB_PREFIX . '_update');
    $sth = $dbh->prepare('
      update ' . ENR_TBL . '
      set shirt_id = ?
      where
        user_id = ?
        and class_id = ?
    ');
    $ok_array['result'] = $sth->execute(array(
      $_POST['shirt_id'],
      $_POST['user_id'],
      $_POST['class_id']
    ));
  }
}
?>