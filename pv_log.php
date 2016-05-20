<?php
require_once('config.php');
session_start();

$count = seleqt_one_record("
   select count(*) as count
   from wrc_user_agents
   where ua_desc = ?
", array($_SERVER['HTTP_USER_AGENT']));

if($count['count'] == 0) {
   $dbh0 = pdo_connect($ini['db_prefix'] . "_insert");
   $sth = $dbh0->prepare("
      insert into wrc_user_agents
      (ua_desc) values (?)
   ");
   $sth->execute(array($_SERVER['HTTP_USER_AGENT']));
}

$ua_id = seleqt_one_record("
   select ua_id
   from wrc_user_agents
   where ua_desc = ?
", array($_SERVER['HTTP_USER_AGENT']));

$dbh=pdo_connect($ini['db_prefix'] . "_insert");

$data = array("user" => (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null),
              "uri" => $_GET['r'],
              "ip" => $_SERVER['REMOTE_ADDR'],
              "ua" => $ua_id['ua_id'],
              "w" => $_GET['w'],
              "h" => $_GET['h']
);

$sth = $dbh->prepare("
   insert into wrc_pageviews (
      user_id,
      request_uri,
      remote_addr,
      ua_id,
      width,
      height
   )
   values (
      :user,
      :uri,
      :ip,
      :ua,
      :w,
      :h
   )
");

$sth->execute($data);

if(isset($_SESSION['user_id'])) {
   $dbh0 = pdo_connect($ini['db_prefix'] . "_update");
   $sth0 = $dbh0->prepare("
      update wrc_users
      set last_login = now()
      where user_id = ?
   ");
   $sth0->execute(array($_SESSION['user_id']));
}



?>
