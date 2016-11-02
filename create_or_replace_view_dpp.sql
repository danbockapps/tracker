create or replace view enrollment_view as select
   tracker_user_id as user_id,
   class_id,
   smart_goal,
   reg_date,
   syst_start,
   dias_start,
   waist_start,
   syst_mid,
   dias_mid,
   waist_mid,
   syst_end,
   dias_end,
   waist_end,
   coup_voucher as voucher_code,
   class_source,
   referrer,
   subscriber_id,
   member_number,
   welcome_sent,
   numclasses,
   shirtsize,
   shirtcolor
from registrants
where
   paid != '0' and
   status = '1';

create or replace view classes_aw as
select
   w.class_id,
   w.start_dttm,
   w.instructor_id,
   w.weeks,
   convert("w" using latin1) as class_source,
   null as eligibilty_deadline
from wrc_classes w
union
select
   c.id as class_id,
   c.start_date_time as start_dttm,
   c.instructor_tracker_id,
   c.num_wks as weeks,
   convert("w" using latin1) as class_source,
   c.eligibilty_deadline
from
   z_classes c;

create or replace view classes_deadline_today as
select *
from classes_aw
where eligibilty_deadline = curdate();

source create_or_replace_view.sql;