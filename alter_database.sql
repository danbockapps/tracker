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


-- 10/13/2017

drop table reports_with_fitbit_static;
drop view reports_with_fitbit;

CREATE TABLE `fitbit_by_week_static` (
  `user_id` int(10) unsigned DEFAULT NULL,
  `class_id` int(11) unsigned DEFAULT NULL,
  `week_id` bigint(10) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `minutes` double DEFAULT NULL,
  `avgsteps` double DEFAULT NULL
) ENGINE=innodb;

create index uc on fitbit_by_week_static (user_id, class_id);


-- 9/15/2018

alter table wrc_attendance
add column date_attended datetime,
add column attendance_type tinyint unsigned; -- 1 = normal class. 2 = makeup class.


-- 10/9/2018

alter table wrc_attendance
drop column date_attended,
add column attendance_date date;


-- 2/2/2019

drop view shp_report;


-- 2/5/2019
-- Add display order. Strategies will be ordered in ascending order on this field.

alter table wrc_strategies
add column display_order smallint unsigned default 65535;


-- Original strategies
update wrc_strategies set display_order=100 where !custom and strategy_description="Eat breakfast";
update wrc_strategies set display_order=200 where !custom and strategy_description="Eat at least 1.5 cups of fruit"; -- MD only
update wrc_strategies set display_order=300 where !custom and strategy_description="Eat at least 2 cups of vegetables";
update wrc_strategies set display_order=400 where !custom and strategy_description="Control portion sizes";
update wrc_strategies set display_order=500 where !custom and strategy_description="Prepare and eat meals at home";
update wrc_strategies set display_order=600 where !custom and strategy_description="Watch 2 or fewer hours of TV";
update wrc_strategies set display_order=700 where !custom and strategy_description="Drink 1 or fewer sugar-sweetened beverages";
update wrc_strategies set display_order=800 where !custom and strategy_description="Participate in at least 30 minutes of physical activity";
update wrc_strategies set display_order=900 where !custom and strategy_description="Participate in strength training";

-- Add to MPP 2/5/2019
update wrc_strategies set display_order=350 where !custom and strategy_description="Eat at least 1 cup of fruit";
update wrc_strategies set display_order=650 where !custom and strategy_description="Limit the amount of screen time I had";
update wrc_strategies set display_order=375 where !custom and strategy_description="Choose healthy fats";
update wrc_strategies set display_order=950 where !custom and strategy_description="Get at least 7-9 hours of sleep";
update wrc_strategies set display_order=1050 where !custom and strategy_description="Manage stress";
