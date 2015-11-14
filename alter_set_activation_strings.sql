update wrc_users
set activation = md5(rand())
where
   password = "TRACKER_NO_REG"
   and
   activation is null;
