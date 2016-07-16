/* 

This script creates the tables in the test environment that correspond
to tables outside the tracker database on prod.
Run this after create.sql (or before. it probably doesn't matter). 

*/

create table if not exists z_classes (
  id int unsigned,
  class_name varchar(50),
  start_date_time datetime,
  num_wks tinyint unsigned,
  instructor_tracker_id int unsigned
) engine=innodb;

create table if not exists regn_tracker (
  tracker_id int,
  unique_id varchar(20)
) engine=innodb;

create table if not exists z_shpmember (
  unique_id varchar(20),
  class_id int,
  bdate date
) engine=innodb;

create table if not exists z_incentives (
  unique_id varchar(20),
  class_id int,
  incentive_type enum('required', 'optional')
) engine=innodb;

create table if not exists latest_addresses (
  user_id int,
  address1 varchar(50),
  address2 varchar(50),
  city varchar(50),
  state varchar(2),
  zip varchar(10)
) engine = innodb;
