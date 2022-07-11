<?php
if($_SERVER['REQUEST_METHOD']!=='POST') http_response_code(405);
else if(!am_i_admin()) http_response_code(403);

else if(!email_already_in_db($fetchPost['oldEmail'])) {
  http_response_code(404);
  $ok_array['oldEmail'] = $fetchPost['oldEmail'];
}

else if(email_already_in_db($fetchPost['newEmail'])) {
  http_response_code(409);
  $ok_array['newEmail'] = $fetchPost['newEmail'];
}

else {
  $dbh = pdo_connect($ini['db_prefix'] . "_update");

  $sth = $dbh->prepare("
      update wrc_users
      set email = ?
      where email = ?
  ");

  if(!$sth->execute(array($fetchPost['newEmail'], $fetchPost['oldEmail']))) http_response_code(500);
}
?>