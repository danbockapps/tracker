<?php
if(
  $_SERVER['REQUEST_METHOD'] == 'POST' &&
  isset($_POST['shirt_id'], $_POST['instock']) &&
  am_i_admin()
) {
  $dbh = pdo_connect(DB_PREFIX . '_update');
  $sth = $dbh->prepare('
    update shirts
    set shirt_instock = ?
    where shirt_id = ?
  ');

  $ok_array['result'] = $sth->execute(
    array($_POST['instock'], $_POST['shirt_id'])
  );
}

?>