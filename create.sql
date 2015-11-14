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
   date_added timestamp,
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

create table if not exists wrc_pageviews (
   pageview_id bigint unsigned auto_increment primary key,
   user_id int unsigned,
   request_uri text,
   remote_addr varchar(30),
   user_agent text,
   width int,
   height int,
   pv_dttm timestamp,
   constraint foreign key (user_id) references wrc_users (user_id)
) engine=innodb;

create table if not exists wrc_classes (
   class_id int unsigned auto_increment primary key,
   start_dttm datetime,
   instructor_id int unsigned,
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
   constraint primary key (user_id, class_id),
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (class_id) references wrc_classes (class_id)
) engine=innodb;

create table if not exists wrc_reports (
   user_id int unsigned,
   class_id int unsigned,
   week_id int unsigned,
   weight decimal(4,1), 
   aerobic_minutes smallint,
   strength_minutes smallint,
   notes text,
   create_dttm timestamp,
   constraint primary key (user_id, class_id, week_id),
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (class_id) references wrc_classes (class_id)
) engine=innodb;

create or replace view u as
   select
      user_id as id,
      substr(activation, 1, 3) as acti,
      substr(email_reset, 1, 3) as emrt,
      fname,
      lname,
      email,
      participant as p,
      instructor as i,
      administrator as a,
      research as r,
      date_added
   from wrc_users;

create or replace view pv as
   select
      user_id as u,
      request_uri,
      pv_dttm,
      remote_addr
   from wrc_pageviews
   order by pv_dttm desc
   limit 50;
