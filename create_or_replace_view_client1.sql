create or replace view bcbs_voucher_codes as
select distinct voucher_code
from
   enrollment_view e
   natural join classes_aw c
where
   e.voucher_code like 'ASO%';

create or replace view bcbs_report as
select
   u.fname as First_Name,
   u.lname as Last_Name,
   u.email as Email,
   e.referred_by as Referred_By,
   date_joined as Date_Joined,
   c.start_dttm as Class_Start,
   c.start_dttm + interval (weeks - 1) week + interval 1 hour as Class_End,
   e.voucher_code as Coupon_Code,
   substring_index(e.claim_id, '-', 1) as BCBS_Subscriber_ID,
   case
      when claim_id like "%-%" then lpad(substring_index(e.claim_id, '-', -1), 2, '0')
      else ''
   end as Member_Number,
   coalesce(a.numclasses, al.numclasses) as Attendance,
   bw.weight as Beginning_Weight,
   ew.weight as Ending_Weight,
   u.height_inches as Height,
   bw.weight * 703 / (u.height_inches * u.height_inches) as Beginning_BMI,
   ew.weight * 703 / (u.height_inches * u.height_inches) as Ending_BMI,
   e.waist_start as Beginning_Waist_Circumference,
   e.waist_end as Ending_Waist_Circumference,
   pes.pes as Tracker_Activity_Score,
   e.smart_goal as Program_Goals
from
   wrc_users u
   natural join enrollment_view e
   natural join classes_aw c
   left join beginning_weights bw on
      e.user_id = bw.user_id and
      e.class_id = bw.class_id and
      e.class_source = bw.class_source
   left join ending_weights ew on
      e.user_id = ew.user_id and
      e.class_id = ew.class_id and
      e.class_source = ew.class_source
   left join pes on
      e.user_id = pes.user_id and
      e.class_id = pes.class_id and
      e.class_source = pes.class_source
   left join attendance_sum a on
      e.user_id = a.user_id and
      e.class_id = a.class_id and
      e.class_source = a.class_source
   left join attendance_sum_legacy al on
      e.user_id = al.user_id and
      e.class_id = al.class_id and
      e.class_source = al.class_source
/* 9/2/17: BCBSNC wants all records now
where
   datediff(
      now(),
      c.start_dttm - interval dayofweek(c.start_dttm) day
   ) - 2 >= c.weeks * 7
*/
order by
   c.start_dttm desc,
   u.lname,
   u.fname;
