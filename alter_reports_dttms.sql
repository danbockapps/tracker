alter table wrc_reports modify create_dttm datetime;
alter table wrc_reports add fdbk_dttm datetime;

create or replace view msgfdbk0 as
select
   user_id,
   recip_id,
   message,
   create_dttm,
   null as class_id,
   null as class_source,
   null as week_id,
   null as start_dttm,
   0 as feedback
from wrc_messages
union
select
   c.instructor_id as user_id,
   user_id as recip_id,
   notes as message,
   coalesce(fdbk_dttm, create_dttm) as create_dttm,
   class_id,
   class_source,
   week_id,
   c.start_dttm,
   1 as feedback
from
   wrc_reports r
   natural join classes_aw c;

create or replace view msgfdbk as
select
   mf.user_id,
   mf.recip_id,
   concat(u.fname, ' ', u.lname) as u_name,
   concat(r.fname, ' ', r.lname) as r_name,
   mf.message,
   mf.create_dttm,
   mf.class_id,
   mf.class_source,
   mf.week_id,
   mf.start_dttm,
   feedback
from
   msgfdbk0 mf
   natural join wrc_users u
   inner join wrc_users r
      on mf.recip_id = r.user_id
where
   mf.message is not null
   and mf.message != ""
order by create_dttm desc;

