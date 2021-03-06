/* 

This script creates the tables in the dev/test environment that correspond
to tables outside the tracker database on prod.
Run this after create.sql (or before. it probably doesn't matter). 
In prod, there need to be views with these names that point to the admin db tables.

*/

create table if not exists z_classes (
  id int unsigned,
  class_type int unsigned,
  class_name varchar(50),
  start_date_time datetime,
  phase1_end date,
  eligibilty_deadline date,
  num_wks tinyint unsigned,
  instructor_tracker_id int unsigned
) engine=innodb;

create table if not exists z_shpmember (
  unique_id varchar(20),
  class_id int,
  bdate date
) engine=innodb;

create table if not exists wp_posts (
  post_title varchar(99),
  guid varchar(99),
  post_type varchar(99),
  post_status varchar(99),
  post_date datetime
) engine=innodb;

create table if not exists registrants (
  `user_id` int(11) AUTO_INCREMENT,
  `class_id` int(11),
  `fname` varchar(50),
  `lname` varchar(50),
  `email` varchar(255),
  `address1` varchar(255),
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(50),
  `state` varchar(2) DEFAULT 'NC',
  `zip` varchar(10),
  `phone` varchar(20),
  `status` enum('1','0') DEFAULT '0' COMMENT '0 for inactive 1 for active',
  `needs_medical` enum('yes','no') DEFAULT 'yes',
  `user_report` enum('yes','no') DEFAULT 'no',
  `follow_up` enum('yes','no') DEFAULT 'no',
  `incentive` varchar(20),
  `shp_member` enum('yes','no','n-a') DEFAULT 'n-a',
  `coup_voucher` varchar(255) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `txnid` varchar(60) DEFAULT '0',
  `processed` enum('yes','no') DEFAULT 'no',
  `unique_id` varchar(30) DEFAULT NULL,
  `date_added` datetime,
  `password` varchar(9999) DEFAULT NULL,
  `activation` varchar(40) DEFAULT NULL,
  `email_reset` varchar(40) DEFAULT NULL,
  `participant` tinyint(1),
  `instructor` tinyint(1),
  `administrator` tinyint(1),
  `research` tinyint(1) DEFAULT NULL,
  `height_inches` decimal(3,1) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_message_from` datetime DEFAULT NULL,
  `last_message_to` datetime DEFAULT NULL,
  `med_scores` varchar(50),
  `sex` enum('M','F'),
  `age` int(11),
  `race` varchar(255),
  `paid` varchar(50) DEFAULT '0',
  `birthdate` date,
  `smart_goal` text,
  `reg_date` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `syst_start` tinyint(3) unsigned DEFAULT NULL,
  `dias_start` tinyint(3) unsigned DEFAULT NULL,
  `waist_start` tinyint(3) unsigned DEFAULT NULL,
  `syst_mid` tinyint(3) unsigned DEFAULT NULL,
  `dias_mid` tinyint(3) unsigned DEFAULT NULL,
  `waist_mid` tinyint(3) unsigned DEFAULT NULL,
  `syst_end` tinyint(3) unsigned DEFAULT NULL,
  `dias_end` tinyint(3) unsigned DEFAULT NULL,
  `waist_end` tinyint(3) unsigned DEFAULT NULL,
  `voucher_code` varchar(100) DEFAULT NULL,
  `class_source` varchar(1) DEFAULT 'w',
  `referrer` varchar(200) DEFAULT NULL,
  `claim_id` varchar(255) DEFAULT NULL,
  `claim_type` varchar(255) DEFAULT NULL,
  `welcome_sent` datetime DEFAULT NULL,
  `numclasses` tinyint(3) unsigned DEFAULT NULL,
  `shirtchoice` varchar(20) DEFAULT NULL,
  `shirt_id` int unsigned,
  `tracker_user_id` int(10) unsigned DEFAULT NULL,
  `refund_method` enum('check', 'paypal'),
  `refund_email_address` varchar(1024),
  `refund_postal_address` varchar(1024),
  `ifNC` enum('1', '0'),
  `amount` float,
  PRIMARY KEY (`user_id`)
) engine=innodb;

create table if not exists shp_members_base (
  id int,
  registrant_id int,
  birthdate date,
  subscriber_id varchar(50),
  pcp_provider varchar(255),
  date_added datetime
) engine=innodb;

create table if not exists shp_members_updated (
  SubscriberId varchar(255) not null,
  BirthDate date,
  CoverageEffectiveDate date
) engine=innodb;
