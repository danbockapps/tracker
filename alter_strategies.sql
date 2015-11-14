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
   week_id int unsigned,
   strategy_id int unsigned,
   num_days tinyint unsigned,
   constraint primary key (user_id, class_id, week_id, strategy_id),
   constraint foreign key (user_id) references wrc_users (user_id),
   constraint foreign key (class_id) references wrc_classes (class_id),
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
