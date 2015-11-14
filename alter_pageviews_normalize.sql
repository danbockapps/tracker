create table if not exists wrc_user_agents (
   ua_id bigint unsigned auto_increment primary key,
   ua_desc text
) engine=innodb;

alter table wrc_pageviews
add ua_id bigint unsigned;

alter table wrc_pageviews
add foreign key (ua_id)
references wrc_user_agents (ua_id);
