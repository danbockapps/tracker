create or replace view enrollment_view as select
   tracker_user_id as user_id,
   class_id,
   smart_goal,
   reg_date,
   syst_start,
   dias_start,
   waist_start,
   syst_end,
   dias_end,
   waist_end,
   voucher_code,
   class_source,
   referrer,
   subscriber_id,
   member_number,
   welcome_sent,
   numclasses,
   shirtsize,
   shirtcolor
from registrants
where paid != '0';

/* 'registrants' is a view that points to dbreg_diab_ctrladminOnline.registrants
in prod and a test version of that table in test. */
