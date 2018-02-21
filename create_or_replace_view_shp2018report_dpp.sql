create or replace view reports_p1q1_limiter as
select
   user_id,
   class_id,
   min(week_id) as week_id
from reports_with_fitbit_hybrid
where
   weight > 0
   and week_id <= 4
group by
   user_id,
   class_id;

create or replace view reports_p1q2_limiter as
select
   user_id,
   class_id,
   min(week_id) as week_id
from reports_with_fitbit_hybrid
where
   weight > 0
   and week_id between 5 and 8
group by
   user_id,
   class_id;

create or replace view reports_p1q3_limiter as
select
   user_id,
   class_id,
   min(week_id) as week_id
from reports_with_fitbit_hybrid
where
   weight > 0
   and week_id between 9 and 12
group by
   user_id,
   class_id;

create or replace view reports_p1q4_limiter as
select
   user_id,
   class_id,
   max(week_id) as week_id
from reports_with_fitbit_hybrid
where
   weight > 0
   and week_id between 13 and 16
group by
   user_id,
   class_id;

create or replace view reports_p2_limiter as
select
   user_id,
   class_id,
   min(week_id) as first_week_id,
   max(week_id) as last_week_id
from reports_with_fitbit_hybrid
where
   weight > 0
   and week_id > 16
group by
   user_id,
   class_id;

create or replace view reports_a1c_limiter as
select
   user_id,
   class_id,
   max(week_id) as week_id
from reports_with_fitbit_hybrid
where
   a1c > 0
   and week_id > 16
group by
   user_id,
   class_id;

create or replace view reports_p1q1 as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   natural join reports_p1q1_limiter;

create or replace view reports_p1q2 as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   natural join reports_p1q2_limiter;

create or replace view reports_p1q3 as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   natural join reports_p1q3_limiter;

create or replace view reports_p1q4 as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   natural join reports_p1q4_limiter;

create or replace view reports_p2f as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   inner join reports_p2_limiter p2
      on r.user_id = p2.user_id
      and r.class_id = p2.class_id
      and r.week_id = p2.first_week_id;

create or replace view reports_p2l as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r
   inner join reports_p2_limiter p2
      on r.user_id = p2.user_id
      and r.class_id = p2.class_id
      and r.week_id = p2.last_week_id;

create or replace view reports_a1c as
select
   r.user_id,
   r.class_id,
   r.week_id,
   r.a1c
from
   reports_with_fitbit_hybrid r
   inner join reports_a1c_limiter a1c
      on r.user_id = a1c.user_id
      and r.class_id = a1c.class_id
      and r.week_id = a1c.week_id;

create or replace view shp_report as
select
   r.claim_id as Subscriber_ID,
   '' as Retiree_Number,
   '' as Medicare_Flag,
   'Online' as Class_Location,
   c.start_dttm as Class_Start,
   c.phase2_end as Class_End,
   am.numclasses as Number_of_Classes_Attended,
   substring_index(med_scores, '-', 1) as Enrollment_A1c,
   substring_index(med_scores, '-', -1) as Enrollment_CDC_Screening_Results,
   c.start_dttm + interval p1q1.week_id - 1 week as First_Entered_Tracker_Entry_Date,
   u.height_inches as Member_Height,
   p1q1.weight as First_Entered_Weight,
   r.waist_start as First_Entered_Waist,
   703 * p1q1.weight / (u.height_inches * u.height_inches) as First_Entered_BMI,
   r.syst_start as First_Entered_Systolic,
   r.dias_start as First_Entered_Diastolic,
   c.start_dttm + interval p1q2.week_id - 1 week as Second_Tracker_Entry_Date,
   p1q2.weight as Second_Weight,
   703 * p1q2.weight / (u.height_inches * u.height_inches) as Second_BMI,
   c.start_dttm + interval p1q3.week_id - 1 week as Third_Tracker_Entry_Date,
   p1q3.weight as Third_Weight,
   703 * p1q3.weight / (u.height_inches * u.height_inches) as Third_BMI,
   c.start_dttm + interval p1q4.week_id - 1 week as Fourth_Tracker_Entry_Date,
   p1q4.weight as Fourth_Weight,
   703 * p1q4.weight / (u.height_inches * u.height_inches) as Fourth_BMI,
   r.syst_mid as Fourth_Systolic,
   r.dias_mid as Fourth_Diastolic,
   c.start_dttm + interval p2f.week_id - 1 week as First_Phase_II_Tracker_Entry_Date,
   p2f.weight as First_Phase_II_Weight,
   703 * p2f.weight / (u.height_inches * u.height_inches) as First_Phase_II_BMI,
   c.start_dttm + interval p2l.week_id - 1 week as Second_Phase_II_Tracker_Entry_Date,
   p2l.weight as Second_Phase_II_Weight,
   r.waist_end as Second_Phase_II_Waist,
   703 * p2l.weight / (u.height_inches * u.height_inches) as Second_Phase_II_BMI,
   r.syst_end as Second_Phase_II_Systolic,
   r.dias_end as Second_Phase_II_Diastolic,
   a1c.a1c as Exit_A1c
from
   dbreg_diab_ctrladminOnline.registrants r
   inner join dbreg_diab_ctrladminOnline.shp_agreements shpa
      on r.user_id = shpa.registrant_id
   left join classes_aw c on
      r.class_id = c.class_id
   left join wrc_users u on
      r.tracker_user_id = u.user_id
   left join attendance_sum2 am on
      r.tracker_user_id = am.user_id and
      month(c.start_dttm) = am.month and
      year(c.start_dttm) = am.year
   left join reports_p1q1 p1q1
      on r.tracker_user_id = p1q1.user_id
      and r.class_id = p1q1.class_id
   left join reports_p1q2 p1q2
      on r.tracker_user_id = p1q2.user_id
      and r.class_id = p1q2.class_id
   left join reports_p1q3 p1q3
      on r.tracker_user_id = p1q3.user_id
      and r.class_id = p1q3.class_id
   left join reports_p1q4 p1q4
      on r.tracker_user_id = p1q4.user_id
      and r.class_id = p1q4.class_id
   left join reports_p2f p2f
      on r.tracker_user_id = p2f.user_id
      and r.class_id = p2f.class_id
   left join reports_p2l p2l
      on r.tracker_user_id = p2l.user_id
      and r.class_id = p2l.class_id
   left join reports_a1c a1c
      on r.tracker_user_id = a1c.user_id
      and r.class_id = a1c.class_id
where
   shpa.permission_agreement = 'I give permission for data associated with my name to be shared with the State Health Plan for evaluation purposes.'
   and c.start_dttm is not null
order by c.start_dttm asc;
