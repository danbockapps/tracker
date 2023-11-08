<?php
if($_SERVER['REQUEST_METHOD']!=='POST') http_response_code(405);

else if(!am_i_admin()) {
  logtxt('Error 403');
  http_response_code(403);
}

else {
  $dbh = pdo_connect($ini['db_prefix'] . "_update");

  $sth = $dbh->prepare("
      update wrc_users
      set fname = ?, lname = ?
      where user_id = ?
  ");

  if(!$sth->execute(array($fetchPost['fname'], $fetchPost['lname'], $fetchPost['user_id'])))
    http_response_code(500);
}
?>