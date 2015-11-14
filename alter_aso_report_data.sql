alter table wrc_enrollment
   add voucher_code varchar(100) default null,
   add class_source varchar(1) default "w", /* w=wrc database, a=admin database */
   add referrer varchar(200) default null,
   add subscriber_id varchar(50) default null,
   add member_number varchar(2) default null;

alter table wrc_enrollment
   drop foreign key wrc_enrollment_ibfk_2;
