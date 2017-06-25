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
