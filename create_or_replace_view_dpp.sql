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
   shirtchoice
from registrants
where
   paid != '0' and
   status = '1';

create or replace view classes_aw as
select
   c.id as class_id,
   c.class_type,
   c.start_date_time as start_dttm,
   c.instructor_tracker_id as instructor_id,
   floor(datediff(c.phase2_end, c.start_date_time) / 7) + 1 as weeks,
   convert("w" using latin1) as class_source,
   c.eligibilty_deadline,
   c.phase1_end,
   c.phase2_end
from
   z_classes c;

create or replace view classes_deadline_today as
select *
from classes_aw
where eligibilty_deadline = curdate();

source create_or_replace_view.sql;

-- Overriding the default for this view
create or replace view current_classes as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from classes_aw
where
   start_dttm < now()
   and datediff(now(), phase2_end) < 8;

-- Reports within 2 weeks before eligibilty deadline
create or replace view reports_near_ed0 as
select
   user_id,
   class_id,
   class_source,
   week_id,
   start_dttm + interval (week_id-1) week as report_date,
   weight,
   eligibilty_deadline
from
   wrc_reports
   natural join classes_aw
where
   start_dttm + interval (week_id-1) week < eligibilty_deadline
   and start_dttm + interval (week_id-1) week > eligibilty_deadline - interval 2 week;

create or replace view reports_near_ed as
select
   user_id,
   class_id,
   class_source,
   max(weight) as weight
from reports_near_ed0
group by
   user_id,
   class_id,
   class_source;

create or replace view attendance2 as
select
   e.tracker_user_id,
   e.class_id,
   zc.class_name,
   e.voucher_code,
   u.fname,
   u.lname,
   coalesce(am.numclasses, 0) as numclasses,
   coalesce(am.numclasses_phase1, 0) as numclasses_phase1,
   coalesce(am.numclasses_phase2, 0) as numclasses_phase2,
   e.address1,
   e.address2,
   e.city,
   e.state,
   e.zip,
   concat(instrs.fname, " ", instrs.lname) as instructor_name,
   bw.weight as bw_weight,
   ew.weight as ew_weight,
   case
      when bw.weight > 0 and rne.weight > 0 then "Yes"
      else "No"
   end as beginning_and_ending_weight,
   case
      when u.height_inches > 0 then "Yes"
      else "No"
   end as height,
   '' as incentive_type,
   e.shirtchoice,
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
   left join reports_near_ed rne on
      e.tracker_user_id = rne.user_id and
      e.class_id = rne.class_id and
      e.class_source = rne.class_source
   left join z_classes zc
      on e.class_id = zc.id
   left join wrc_users instrs
      on c.instructor_id = instrs.user_id
   left join attendance_sum2 am on
      e.tracker_user_id = am.user_id and
      month(c.start_dttm) = am.month and
      year(c.start_dttm) = am.year
where
   instrs.instructor = 1
   -- datediff on c.start_dttm was here. Not sure why.
order by
   start_dttm desc,
   lname,
   fname;

create or replace view reports_with_attendance as
select
   r.user_id,
   r.class_id,
   r.week_id as week,
   r.weight,
   r.physact_minutes,
   r.a1c,
   a.present
from
   wrc_reports r
   left join wrc_attendance a
      on r.user_id = a.user_id
      and r.class_id = a.class_id
      and r.week_id = a.week
union
select
   a.user_id,
   a.class_id,
   a.week,
   r.weight,
   r.physact_minutes,
   r.a1c,
   a.present
from
   wrc_reports r
   right join wrc_attendance a
      on r.user_id = a.user_id
      and r.class_id = a.class_id
      and r.week_id = a.week;

create or replace view shp_members_current as
select
   b.registrant_id as user_id
from
   shp_members_base b
   inner join shp_members_updated u
      on password(to_base64(b.subscriber_id)) = u.SubscriberId
      and b.birthdate = u.BirthDate
where u.CoverageEffectiveDate < now();
