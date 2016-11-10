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

create or replace view attendance2 as
select
   e.tracker_user_id,
   e.class_id,
   zc.class_name,
   e.voucher_code,
   u.fname,
   u.lname,
   coalesce(am.numclasses, 0) as numclasses,
   e.address1,
   e.address2,
   e.city,
   e.state,
   e.zip,
   concat(instrs.fname, " ", instrs.lname) as instructor_name,
   bw.weight as bw_weight,
   ew.weight as ew_weight,
   case
      when bw.weight > 0 and ew.weight > 0 then "Yes"
      else "No"
   end as beginning_and_ending_weight,
   '' as incentive_type,
   e.shirtsize,
   e.shirtcolor,
   '' as dob
from
   registrants e
   inner join wrc_users u on
      e.tracker_user_id = u.user_id
   natural join classes_aw c
   left join beginning_weights bw on
      e.tracker_user_id = bw.user_id and
      e.class_id = bw.class_id and
      e.class_source = bw.class_source
   left join ending_weights ew on
      e.tracker_user_id = ew.user_id and
      e.class_id = ew.class_id and
      e.class_source = ew.class_source
   left join z_classes zc
      on e.class_id = zc.id
   left join wrc_users instrs
      on c.instructor_id = instrs.user_id
   left join attendance_sum am on
      e.tracker_user_id = am.user_id and
      e.class_id = am.class_id and
      e.class_source = am.class_source
where
   instrs.instructor = 1
   -- datediff on c.start_dttm was here. Not sure why.
order by
   start_dttm desc,
   lname,
   fname;

