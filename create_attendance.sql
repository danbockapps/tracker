   create table if not exists wrc_attendance (
      user_id int unsigned,
      class_id int unsigned,
      class_source varchar(1),
      week int unsigned,
      present boolean,
      date_entered timestamp
   ) engine=innodb;
