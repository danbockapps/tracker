/*
6/22/2017
Database changes will go in this file.
Expect to copy and paste statements from here into MySQL rather
than sourcing the whole file, which you will rarely want to do.
*/

-- 6/22/2017

alter table wrc_users
add column fitbit_access_token varchar(1024),
add column fitbit_refresh_token varchar(1024);


-- 6/23/2017

create table wrc_fitbit (
   id int unsigned auto_increment primary key,
   user_id int unsigned,
   date date,
   metric varchar(30),
   value float,
   updated timestamp,
   constraint foreign key (user_id) references wrc_users (user_id)
) engine=innodb;


-- 7/7/2017

alter table wrc_reports
add column avgsteps smallint unsigned;


-- 7/9/2017

create index dumi on wrc_fitbit (date, user_id, metric, id);


-- 9/23/2017

CREATE TABLE `reports_with_fitbit_static` (
  `user_id` int(11) unsigned DEFAULT NULL,
  `class_id` int(11) unsigned NOT NULL DEFAULT '0',
  `class_source` varchar(1) NOT NULL DEFAULT '',
  `week_id` bigint(20) unsigned DEFAULT NULL,
  `weight` double DEFAULT NULL,
  `weight_f` bigint(20) NOT NULL DEFAULT '0',
  `aerobic_minutes` double DEFAULT NULL,
  `aerobic_minutes_f` bigint(20) NOT NULL DEFAULT '0',
  `strength_minutes` smallint(6) DEFAULT NULL,
  `a1c` decimal(5,2) DEFAULT NULL,
  `physact_minutes` double DEFAULT NULL,
  `physact_minutes_f` bigint(20) NOT NULL DEFAULT '0',
  `notes` text,
  `create_dttm` datetime DEFAULT NULL,
  `fdbk_dttm` datetime DEFAULT NULL,
  `avgsteps` double DEFAULT NULL,
  `avgsteps_f` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=innodb;

create index uc on reports_with_fitbit_static (user_id, class_id);
