create table if not exists wrc_users (
   user_id int unsigned auto_increment primary key,
   password varchar(9999) not null,
   activation varchar(40),
   email_reset varchar(40) default null,
   fname varchar(50) not null,
   lname varchar(50) not null,
   email varchar(100) not null,
   participant boolean not null,
   instructor boolean not null,
   administrator boolean not null,
   research boolean, -- This may have some other name in existing database
   height_inches decimal(3,1),
   date_added datetime,
   last_login datetime,
   last_message_from datetime,
   last_message_to datetime,
   constraint unique (email)
) engine=innodb;

create table if not exists wrc_messages (
   message_id int unsigned auto_increment primary key,
   user_id int unsigned,
   recip_id int unsigned,
   subject text,
   message text,
   mread boolean default false,
   create_dttm datetime,
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (recip_id) references wrc_users (user_id)
) engine=innodb;

create table if not exists wrc_user_agents (
   ua_id bigint unsigned auto_increment primary key,
   ua_desc text
) engine=innodb;

create table if not exists wrc_pageviews (
   pageview_id bigint unsigned auto_increment primary key,
   user_id int unsigned,
   request_uri text,
   remote_addr varchar(30),
   user_agent text,
   width int,
   height int,
   pv_dttm timestamp,
   ua_id bigint unsigned,
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (ua_id) references wrc_user_agents (ua_id)
) engine=innodb;

create table if not exists wrc_classes (
   class_id int unsigned auto_increment primary key,
   start_dttm datetime,
   instructor_id int unsigned,
   weeks tinyint unsigned default 15,
   constraint foreign key (instructor_id) references wrc_users (user_id)
) engine=innodb;

create table if not exists wrc_enrollment (
   user_id int unsigned,
   class_id int unsigned,
   smart_goal text,
   reg_date timestamp,
   syst_start tinyint unsigned,
   dias_start tinyint unsigned,
   waist_start tinyint unsigned,
   syst_end tinyint unsigned,
   dias_end tinyint unsigned,
   waist_end tinyint unsigned,
   voucher_code varchar(100) default null,
   class_source varchar(1) default "w", /* w=wrc database, a=admin database */
   referrer varchar(200) default null,
   subscriber_id varchar(50) default null,
   member_number varchar(2) default null,
   numclasses tinyint unsigned,
   shirtsize varchar(3) default null,
   shirtcolor varchar(20) default null,
   welcome_sent datetime,
   constraint primary key (user_id, class_id, class_source),
   constraint foreign key (user_id) references wrc_users (user_id)
) engine=innodb;

create table if not exists wrc_reports (
   user_id int unsigned,
   class_id int unsigned,
   class_source varchar(1) default "w",
   week_id int unsigned,
   weight decimal(4,1),
   aerobic_minutes smallint,
   strength_minutes smallint,
   a1c decimal(5,2) default null,
   physact_minutes smallint default null,
   notes text,
   create_dttm datetime,
   fdbk_dttm datetime,
   constraint primary key (user_id, class_id, class_source, week_id),
   constraint foreign key (user_id) references wrc_users (user_id)
) engine=innodb;

create table if not exists wrc_strategies (
   strategy_id int unsigned auto_increment primary key,
   custom boolean default true,
   public boolean default false,
   /* current_timestamp is default for inserts, not auto-updated on updates. */
   strategy_dttm timestamp default current_timestamp,
   strategy_description varchar(100) not null
) engine=innodb;

create table if not exists wrc_strategy_user (
   user_id int unsigned,
   strategy_id int unsigned,
   constraint primary key (user_id, strategy_id),
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (strategy_id) references wrc_strategies (strategy_id)
) engine=innodb;

create table if not exists wrc_strategy_report (
   user_id int unsigned,
   class_id int unsigned,
   class_source varchar(1) default "w",
   week_id int unsigned,
   strategy_id int unsigned,
   num_days tinyint unsigned,
   constraint primary key (user_id, class_id, class_source, week_id, strategy_id),
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (strategy_id) references wrc_strategies (strategy_id)
) engine=innodb;

insert into wrc_strategies (custom, strategy_description) values
(0, "Eat breakfast"),
(0, "Eat at least 1.5 cups of fruit"),
(0, "Eat at least 2 cups of vegetables"),
(0, "Control portion sizes"),
(0, "Prepare and eat meals at home"),
(0, "Watch 2 or fewer hours of TV"),
(0, "Drink 1 or fewer sugar-sweetened beverages"),
(0, "Participate in at least 30 minutes of physical activity"),
(0, "Participate in strength training");

create table if not exists wrc_attendance (
   attendance_id int unsigned auto_increment primary key,
   user_id int unsigned,
   class_id int unsigned,
   class_source varchar(1),
   week int unsigned,
   present boolean,
   date_entered timestamp
) engine=innodb;
