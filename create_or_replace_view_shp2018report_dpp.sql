create or replace view shp_report as
select
   r.claim_id as Subscriber_ID,
   '' as Retiree_Number,
   '' as Medicare_Flag,
   'Online' as Class_Location,
   c.start_dttm as Class_Start,
   c.phase2_end as Class_End,
   am.numclasses as Num_Classes,
   substring_index(med_scores, '-', 1) as Enrollment_A1c,
   substring_index(med_scores, '-', -1) as Enrollment_CDC_Screening_Result
from
   dbreg_diab_ctrladminOnline.registrants r
   left join classes_aw c on
      r.class_id = c.class_id
   left join attendance_sum2 am on
      r.tracker_user_id = am.user_id and
      month(c.start_dttm) = am.month and
      year(c.start_dttm) = am.year;
