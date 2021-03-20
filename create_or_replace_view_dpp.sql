create
or replace view enrollment_view as
select
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
   shirtchoice,
   shirt_id,
   address1,
   address2,
   city,
   state,
   zip,
   phone,
   refund_method,
   refund_email_address,
   refund_postal_address,
   ifnc,
   amount
from
   registrants
where
   paid != '0'
   and status = '1';

create
or replace view classes_aw as
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

create
or replace view classes_deadline_today as
select
   *
from
   classes_aw
where
   eligibilty_deadline = curdate();

source create_or_replace_view.sql;

-- Overriding the default for this view
create
or replace view current_classes as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from
   classes_aw
where
   start_dttm < now()
   and datediff(now(), phase2_end) < 8;

-- Reports within 2 weeks before eligibilty deadline
create
or replace view reports_near_ed0 as
select
   user_id,
   class_id,
   class_source,
   week_id,
   start_dttm + interval (week_id -1) week as report_date,
   weight,
   eligibilty_deadline
from
   wrc_reports natural
   join classes_aw
where
   start_dttm + interval (week_id -1) week < eligibilty_deadline
   and start_dttm + interval (week_id -1) week > eligibilty_deadline - interval 2 week;

create
or replace view reports_near_ed as
select
   user_id,
   class_id,
   class_source,
   max(weight) as weight
from
   reports_near_ed0
group by
   user_id,
   class_id,
   class_source;

create
or replace view attendance3 as
select
   e.tracker_user_id,
   e.user_id as admin_db_user_id,
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
      when bw.weight > 0
      and rne.weight > 0 then "Yes"
      else "No"
   end as beginning_and_ending_weight,
   case
      when u.height_inches > 0 then "Yes"
      else "No"
   end as height,
   '' as incentive_type,
   s.shirt_desc,
   '' as dob,
   e.refund_method,
   e.refund_email_address,
   e.refund_postal_address
from
   registrants e
   inner join wrc_users u on e.tracker_user_id = u.user_id natural
   join classes_aw c
   left join beginning_weights bw on e.tracker_user_id = bw.user_id
   and e.class_id = bw.class_id
   and e.class_source = bw.class_source
   left join ending_weights ew on e.tracker_user_id = ew.user_id
   and e.class_id = ew.class_id
   and e.class_source = ew.class_source
   left join reports_near_ed rne on e.tracker_user_id = rne.user_id
   and e.class_id = rne.class_id
   and e.class_source = rne.class_source
   left join z_classes zc on e.class_id = zc.id
   left join wrc_users instrs on c.instructor_id = instrs.user_id
   left join attendance_sum3 am on e.tracker_user_id = am.user_id
   and month(c.start_dttm) = am.month
   and year(c.start_dttm) = am.year
   left join shirts s on e.shirt_id = s.shirt_id
where
   instrs.instructor = 1 -- datediff on c.start_dttm was here. Not sure why.
order by
   start_dttm desc,
   lname,
   fname;

/*
 May 2019: New CDC reports
 */
create
or replace view cdc_transposed_reports as
select
   a.attendance_id,
   a.attendance_date,
   a.attendance_type,
   a.lesson_id,
   a.present_phase1,
   a.present_phase2,
   a.user_id,
   a.class_id,
   i.weight as wi,
   i.physact_minutes as pai,
   r.weight as w0,
   r.physact_minutes as pa0
from
   attendance_summary3 a
   left join wrc_ireports i on a.lesson_id = i.lesson_id
   and a.user_id = i.user_id
   and a.class_id = i.class_id
   inner join classes_aw c on a.class_id = c.class_id
   left join reports_with_fitbit_hybrid r on a.user_id = r.user_id
   and a.class_id = r.class_id
   and a.week_id = r.week_id
where
   a.attendance_date is not null;

create
or replace view cdc_report0 as
select
   case
      when c.class_type in (1, 2) then '2173125'
      when c.class_type in (4, 5) then '8471188'
      else null
   end as ORGCODE,
   r.user_id as PARTICIP,
   '' as ENROLL,
   '' as PAYER,
   r.state as STATE,
   '' as GLUCTEST,
   '' as GDM,
   '' as RISKTEST,
   r.age as AGE,
   case
      r.ethnicity
      when 'Hispanic' then 1
      when 'Not Hispanic' then 2
      else 9
   end as ETHNIC,
   '' as AIAN,
   '' as ASIAN,
   '' as BLACK,
   '' as NHOPI,
   '' as WHITE,
   case
      r.sex
      when 'M' then 1
      when 'F' then 2
      else null
   end as SEX,
   u.height_inches as HEIGHT,
   '' as EDU,
   case
      when t.attendance_type = 2 then 2 -- makeup class
      when c.class_type in (1, 2) then 3 -- online
      when c.class_type in (4, 5) then 1 -- onsite
      else null
   end as DMODE,
   case
      when t.lesson_id <= 18 then t.lesson_id
      else 99
   end as SESSID,
   case
      when t.attendance_type = 2 then 'MU'
      when t.present_phase1 = 1
      and t.attendance_type = 1 then 'C'
      when t.present_phase2 = 1
      and t.attendance_type = 1 then 'CM'
   end as SESSTYPE,
   t.attendance_date as DATE,
   case
      when c.class_type in (1, 2) then t.w0
      when c.class_type in (4, 5) then t.wi
   end as WEIGHT,
   case
      when c.class_type in (1, 2) then t.pa0
      when c.class_type in (4, 5) then t.pai
   end as PA
from
   cdc_transposed_reports t
   inner join wrc_users u on t.user_id = u.user_id
   inner join registrants r on t.user_id = r.tracker_user_id
   inner join classes_aw c on t.class_id = c.class_id;

create
or replace view cdc_report as
select
   c.*,
   case
      when c.DATE is not null
      and c.WEIGHT is not null
      and c.PA is not null then 1
      else 0
   end as FULL_PARTICIPATION
from
   cdc_report0 c;

create
or replace view cdc_report_online as
select
   *
from
   cdc_report
where
   orgcode = '2173125'
order by
   particip,
   date;

create
or replace view cdc_report_onsite as
select
   *
from
   cdc_report
where
   orgcode = '8471188'
order by
   particip,
   date;