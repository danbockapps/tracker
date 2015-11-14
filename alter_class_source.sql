alter table wrc_reports add class_source varchar(1) default "w" after class_id;
alter table wrc_strategy_report add class_source varchar(1) default "w" after class_id;

alter table wrc_enrollment drop primary key,
add primary key (user_id, class_id, class_source);

alter table wrc_reports drop primary key,
add primary key (user_id, class_id, class_source, week_id);

alter table wrc_strategy_report drop primary key,
add primary key (user_id, class_id, class_source, week_id, strategy_id);

alter table wrc_enrollment add welcome_sent datetime;
update wrc_enrollment set welcome_sent = "2013-12-31 00:00:00" where class_source = "w";

