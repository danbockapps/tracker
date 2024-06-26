-- Formatted using VS Code entension "SQL Formatter" (adpyke.vscode-sql-formatter)
create
or replace view u as
select
   user_id as id,
   substr(activation, 1, 3) as acti,
   substr(email_reset, 1, 3) as emrt,
   fname,
   lname,
   email,
   participant as p,
   instructor as i,
   administrator as a,
   research as r,
   date_added
from
   wrc_users;

create
or replace view pv as
select
   user_id as u,
   request_uri,
   pv_dttm,
   remote_addr
from
   wrc_pageviews
order by
   pv_dttm desc
limit
   50;

create
or replace view current_classes_for_rosters as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from
   classes_aw
where
   start_dttm < now() + interval 3 day
   and datediff(
      now(),
      start_dttm - interval dayofweek(start_dttm) day
   ) - 2 < weeks * 7;

/* Classes drop off this list Sunday night after the last class. */
create
or replace view current_classes as
select
   *
from
   current_classes_for_rosters
where
   start_dttm < now();

create
or replace view msgfdbk as
select
   mf.user_id,
   mf.recip_id,
   concat(u.fname, ' ', u.lname) as u_name,
   concat(r.fname, ' ', r.lname) as r_name,
   mf.message,
   mf.create_dttm
from
   wrc_messages mf natural
   join wrc_users u
   inner join wrc_users r on mf.recip_id = r.user_id
where
   mf.message is not null
   and mf.message != ""
order by
   create_dttm desc;

create
or replace view beginning_weights as
select
   user_id,
   class_id,
   class_source,
   weight,
   aerobic_minutes + strength_minutes as pa_feb2018
from
   reports_with_fitbit_hybrid
where
   week_id = 1;

create
or replace view last_reports as
select
   r.user_id,
   r.class_id,
   r.class_source,
   max(r.week_id) as week_id
from
   reports_with_fitbit_hybrid r natural
   join classes_aw c
where
   r.week_id >= c.weeks - 1
group by
   user_id,
   class_id,
   class_source;

create
or replace view most_recent_reports as
select
   user_id,
   class_id,
   class_source,
   max(week_id) as week_id
from
   wrc_reports
group by
   user_id,
   class_id,
   class_source;

create
or replace view ending_weights as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.weight,
   r.aerobic_minutes + r.strength_minutes as pa_feb2018
from
   reports_with_fitbit_hybrid r natural
   join last_reports lr;

/*
 attendance_limiter, attendance_summary, attendance_sum and attendance2
 developed December 2015 for new week-by-week attendance entry
 */
create
or replace view attendance_limiter as
select
   max(attendance_id) as attendance_id
from
   wrc_attendance
group by
   user_id,
   class_id,
   class_source,
   week;

create
or replace view attendance_summary as
select
   a.user_id,
   a.class_id,
   a.class_source,
   a.week,
   a.present,
   case
      when week <= 16 then a.present
      else 0
   end as present_phase1,
   case
      when week >= 17 then a.present
      else 0
   end as present_phase2
from
   wrc_attendance a
   inner join attendance_limiter l on a.attendance_id = l.attendance_id;

create
or replace view attendance_sum as
select
   user_id,
   class_id,
   class_source,
   sum(present) as numclasses,
   sum(present_phase1) as numclasses_phase1,
   sum(present_phase2) as numclasses_phase2
from
   attendance_summary
group by
   user_id,
   class_id,
   class_source;

create
or replace view attendance_limiter2 as
select
   max(attendance_id) as attendance_id
from
   wrc_attendance a
   inner join classes_aw c on a.class_id = c.class_id
   and a.class_source = c.class_source
group by
   a.user_id,
   a.week,
   month(c.start_dttm),
   year(c.start_dttm);

create
or replace view attendance_summary2 as
select
   a.user_id,
   month(c.start_dttm) as month,
   year(c.start_dttm) as year,
   a.week,
   a.present,
   a.attendance_type,
   a.attendance_date,
   case
      when week <= 16 then a.present
      else 0
   end as present_phase1,
   case
      when week >= 17 then a.present
      else 0
   end as present_phase2
from
   wrc_attendance a
   inner join attendance_limiter2 l on a.attendance_id = l.attendance_id
   inner join classes_aw c on a.class_id = c.class_id
   and a.class_source = c.class_source;

create
or replace view attendance_counts2 as
SELECT
   user_id,
   month,
   year,
   sum(present) as count,
   max(week) as max
from
   attendance_summary2 a
group by
   user_id,
   month,
   year;

/*
 3 series added in March 2019 for MPP attendance by type and date
 */
create
or replace view attendance_summary3 as
select
   a.attendance_id,
   a.user_id,
   a.class_id,
   month(c.start_dttm) as month,
   year(c.start_dttm) as year,
   a.week as lesson_id,
   ceil(
      (datediff(a.attendance_date, c.start_dttm) + 4) / 7
   ) as week_id,
   a.attendance_type,
   a.attendance_date,
   case
      when a.attendance_type > 0 then 1
      else 0
   end as present,
   case
      when week <= 18
      and a.attendance_type > 0 then 1
      else 0
   end as present_phase1,
   case
      when week >= 19
      and a.attendance_type > 0 then 1
      else 0
   end as present_phase2
from
   wrc_attendance a
   inner join attendance_limiter2 l on a.attendance_id = l.attendance_id
   inner join classes_aw c on a.class_id = c.class_id
   and a.class_source = c.class_source;

create
or replace view attendance_sum2 as
select
   user_id,
   month,
   year,
   sum(present) as numclasses,
   sum(present_phase1) as numclasses_phase1,
   sum(present_phase2) as numclasses_phase2
from
   attendance_summary2
group by
   user_id,
   month,
   year;

create
or replace view reports_full_particip as
select
   user_id,
   class_id,
   week_id,
   case
      when week_id <= 18
      and weight is not null
      and physact_minutes is not null then 1
      else 0
   end as full_participation_phase1,
   case
      when week_id >= 19
      and weight is not null
      and physact_minutes is not null then 1
      else 0
   end as full_participation_phase2
from
   reports_with_fitbit_hybrid;

create
or replace view attendance_sum3 as
select
   user_id,
   month,
   year,
   sum(present) as numclasses,
   sum(present_phase1) as numclasses_phase1,
   sum(present_phase2) as numclasses_phase2,
   sum(full_participation_phase1) as full_participation_phase1,
   sum(full_participation_phase2) as full_participation_phase2
from
   attendance_summary3
   left join reports_full_particip r using(user_id, class_id, week_id)
group by
   user_id,
   month,
   year;

create
or replace view pes_reports_summ as
select
   user_id,
   class_id,
   class_source,
   max(create_dttm) as last_report,
   count(create_dttm) as num_reports,
   count(weight) + count(aerobic_minutes) + count(strength_minutes) as num_p1_fields
from
   wrc_reports
group by
   user_id,
   class_id,
   class_source;

create
or replace view pes_avg_word_counts as
select
   user_id,
   avg(
      length(replace(trim(message), '  ', ' ')) - length(replace(trim(message), ' ', '')) + 1
   ) as avg_word_count
from
   wrc_messages
group by
   user_id;

create
or replace view pes_strategy_summ as
select
   user_id,
   class_id,
   class_source,
   count(num_days) as num_p2_fields
from
   wrc_strategy_report
group by
   user_id,
   class_id,
   class_source;

create
or replace view pes_msgs as
select
   e.user_id,
   e.class_id,
   e.class_source,
   m.message,
   floor(datediff(m.create_dttm, c.start_dttm) / 7) + 1 as week_id
from
   enrollment_view e natural
   join classes_aw c natural
   left join wrc_messages m
where
   m.message_id is not null;

create
or replace view pes_logarithms as
select
   user_id,
   class_id,
   class_source,
   log(
      13,
      sum(
         case
            when week_id = 1 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_1,
   log(
      13,
      sum(
         case
            when week_id = 2 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_2,
   log(
      13,
      sum(
         case
            when week_id = 3 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_3,
   log(
      13,
      sum(
         case
            when week_id = 4 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_4,
   log(
      13,
      sum(
         case
            when week_id = 5 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_5,
   log(
      13,
      sum(
         case
            when week_id = 6 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_6,
   log(
      13,
      sum(
         case
            when week_id = 7 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_7,
   log(
      13,
      sum(
         case
            when week_id = 8 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_8,
   log(
      13,
      sum(
         case
            when week_id = 9 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_9,
   log(
      13,
      sum(
         case
            when week_id = 10 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_10,
   log(
      13,
      sum(
         case
            when week_id = 11 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_11,
   log(
      13,
      sum(
         case
            when week_id = 12 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_12,
   log(
      13,
      sum(
         case
            when week_id = 13 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_13,
   log(
      13,
      sum(
         case
            when week_id = 14 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_14,
   log(
      13,
      sum(
         case
            when week_id = 15 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_15,
   log(
      13,
      sum(
         case
            when week_id = 16 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_16,
   log(
      13,
      sum(
         case
            when week_id = 17 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_17,
   log(
      13,
      sum(
         case
            when week_id = 18 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_18,
   log(
      13,
      sum(
         case
            when week_id = 19 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_19,
   log(
      13,
      sum(
         case
            when week_id = 20 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_20,
   log(
      13,
      sum(
         case
            when week_id = 21 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_21,
   log(
      13,
      sum(
         case
            when week_id = 22 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_22,
   log(
      13,
      sum(
         case
            when week_id = 23 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_23,
   log(
      13,
      sum(
         case
            when week_id = 24 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_24,
   log(
      13,
      sum(
         case
            when week_id = 25 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_25,
   log(
      13,
      sum(
         case
            when week_id = 26 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_26,
   log(
      13,
      sum(
         case
            when week_id = 27 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_27,
   log(
      13,
      sum(
         case
            when week_id = 28 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_28,
   log(
      13,
      sum(
         case
            when week_id = 29 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_29,
   log(
      13,
      sum(
         case
            when week_id = 30 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_30,
   log(
      13,
      sum(
         case
            when week_id = 31 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_31,
   log(
      13,
      sum(
         case
            when week_id = 32 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_32,
   log(
      13,
      sum(
         case
            when week_id = 33 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_33,
   log(
      13,
      sum(
         case
            when week_id = 34 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_34,
   log(
      13,
      sum(
         case
            when week_id = 35 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_35,
   log(
      13,
      sum(
         case
            when week_id = 36 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_36,
   log(
      13,
      sum(
         case
            when week_id = 37 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_37,
   log(
      13,
      sum(
         case
            when week_id = 38 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_38,
   log(
      13,
      sum(
         case
            when week_id = 39 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_39,
   log(
      13,
      sum(
         case
            when week_id = 40 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_40,
   log(
      13,
      sum(
         case
            when week_id = 41 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_41,
   log(
      13,
      sum(
         case
            when week_id = 42 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_42,
   log(
      13,
      sum(
         case
            when week_id = 43 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_43,
   log(
      13,
      sum(
         case
            when week_id = 44 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_44,
   log(
      13,
      sum(
         case
            when week_id = 45 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_45,
   log(
      13,
      sum(
         case
            when week_id = 46 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_46,
   log(
      13,
      sum(
         case
            when week_id = 47 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_47,
   log(
      13,
      sum(
         case
            when week_id = 48 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_48,
   log(
      13,
      sum(
         case
            when week_id = 49 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_49,
   log(
      13,
      sum(
         case
            when week_id = 50 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_50,
   log(
      13,
      sum(
         case
            when week_id = 51 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_51,
   log(
      13,
      sum(
         case
            when week_id = 52 then 1
            else 0
         end
      ) + 1
   ) as log_msgs_week_52
from
   pes_msgs
group by
   user_id,
   class_id,
   class_source;

create
or replace view pes_components as
select
   e.user_id,
   e.class_id,
   e.class_source,
   case
      when e.smart_goal is null then 0
      else 0.75
   end as pts_smart_goal,
   case
      when datediff(now(), c.start_dttm + interval c.weeks week) > 0 then 1
      when pes_reports_summ.last_report is null then 0
      when datediff(now(), pes_reports_summ.last_report) > 10 then 0
      else 1
   end as pts_recent_report,
   coalesce(
      least(pes_avg_word_counts.avg_word_count / 136, 0.5),
      0
   ) as pts_avg_word_count,
   coalesce(pes_reports_summ.num_reports, 0) as pts_num_reports,
   coalesce(pes_reports_summ.num_p1_fields * 0.5, 0) as pts_p1_fields,
   coalesce(pes_strategy_summ.num_p2_fields * 0.1, 0) as pts_p2_fields,
   coalesce(
      pes_logarithms.log_msgs_week_1 + pes_logarithms.log_msgs_week_2 + pes_logarithms.log_msgs_week_3 + pes_logarithms.log_msgs_week_4 + pes_logarithms.log_msgs_week_5 + pes_logarithms.log_msgs_week_6 + pes_logarithms.log_msgs_week_7 + pes_logarithms.log_msgs_week_8 + pes_logarithms.log_msgs_week_9 + pes_logarithms.log_msgs_week_10 + pes_logarithms.log_msgs_week_11 + pes_logarithms.log_msgs_week_12 + pes_logarithms.log_msgs_week_13 + pes_logarithms.log_msgs_week_14 + pes_logarithms.log_msgs_week_15 + pes_logarithms.log_msgs_week_16 + pes_logarithms.log_msgs_week_17 + pes_logarithms.log_msgs_week_18 + pes_logarithms.log_msgs_week_19 + pes_logarithms.log_msgs_week_20 + pes_logarithms.log_msgs_week_21 + pes_logarithms.log_msgs_week_22 + pes_logarithms.log_msgs_week_23 + pes_logarithms.log_msgs_week_24 + pes_logarithms.log_msgs_week_25 + pes_logarithms.log_msgs_week_26 + pes_logarithms.log_msgs_week_27 + pes_logarithms.log_msgs_week_28 + pes_logarithms.log_msgs_week_29 + pes_logarithms.log_msgs_week_30 + pes_logarithms.log_msgs_week_31 + pes_logarithms.log_msgs_week_32 + pes_logarithms.log_msgs_week_33 + pes_logarithms.log_msgs_week_34 + pes_logarithms.log_msgs_week_35 + pes_logarithms.log_msgs_week_36 + pes_logarithms.log_msgs_week_37 + pes_logarithms.log_msgs_week_38 + pes_logarithms.log_msgs_week_39 + pes_logarithms.log_msgs_week_40 + pes_logarithms.log_msgs_week_41 + pes_logarithms.log_msgs_week_42 + pes_logarithms.log_msgs_week_43 + pes_logarithms.log_msgs_week_44 + pes_logarithms.log_msgs_week_45 + pes_logarithms.log_msgs_week_46 + pes_logarithms.log_msgs_week_47 + pes_logarithms.log_msgs_week_48 + pes_logarithms.log_msgs_week_49 + pes_logarithms.log_msgs_week_50 + pes_logarithms.log_msgs_week_51 + pes_logarithms.log_msgs_week_52,
      0
   ) as pts_log_msgs,
   least(datediff(now(), c.start_dttm) / 7, c.weeks) as week_num
from
   enrollment_view e natural
   join classes_aw c natural
   left join pes_reports_summ natural
   left join pes_avg_word_counts natural
   left join pes_strategy_summ natural
   left join pes_logarithms;

create
or replace view pes as
select
   user_id,
   class_id,
   class_source,
   (
      pts_smart_goal + pts_recent_report + pts_avg_word_count + (
         pts_num_reports + pts_p1_fields + pts_p2_fields + pts_log_msgs
      ) / week_num
   ) * 15.85 as pes
from
   pes_components;

create
or replace view fitbit as
select
   m1.*
from
   wrc_fitbit m1
   left join wrc_fitbit m2 on (
      m1.date = m2.date
      and m1.user_id = m2.user_id
      and m1.metric = m2.metric
      and m1.id < m2.id
   )
where
   m2.id is null;

create
or replace view fitbit_with_weeks as
select
   c.class_id,
   floor(datediff(f.date, c.start_dttm) / 7) + 2 as week_id,
   f.*
from
   fitbit_static f
   inner join enrollment_view e on f.user_id = e.user_id
   inner join current_classes c on e.class_id = c.class_id;

create
or replace view fitbit_user_weeks as
select
   distinct user_id,
   class_id,
   week_id
from
   fitbit_with_weeks;

create
or replace view fitbit_weight_limiter as
select
   user_id,
   class_id,
   week_id,
   max(id) as id
from
   fitbit_with_weeks
where
   metric = 'weight'
group by
   user_id,
   class_id,
   week_id;

create
or replace view fitbit_weights as
select
   a.user_id,
   a.class_id,
   a.week_id,
   a.value as weight
from
   fitbit_with_weeks a
   inner join fitbit_weight_limiter b on a.id = b.id;

create
or replace view fitbit_minutes as
select
   user_id,
   class_id,
   week_id,
   sum(value) as minutes
from
   fitbit_with_weeks
where
   metric in (
      "activities-minutesFairlyActive",
      "activities-minutesVeryActive"
   )
group by
   user_id,
   class_id,
   week_id;

create
or replace view fitbit_avgsteps as
select
   user_id,
   class_id,
   week_id,
   avg(value) as avgsteps
from
   fitbit_with_weeks
where
   metric = "activities-steps"
group by
   user_id,
   class_id,
   week_id;

create
or replace view fitbit_by_week as
select
   f.user_id,
   f.class_id,
   f.week_id,
   fw.weight,
   fm.minutes,
   fa.avgsteps
from
   fitbit_user_weeks f
   left join fitbit_weights fw on f.user_id = fw.user_id
   and f.class_id = fw.class_id
   and f.week_id = fw.week_id
   left join fitbit_minutes fm on f.user_id = fm.user_id
   and f.class_id = fm.class_id
   and f.week_id = fm.week_id
   left join fitbit_avgsteps fa on f.user_id = fa.user_id
   and f.class_id = fa.class_id
   and f.week_id = fa.week_id;

/* TODO rename this */
create
or replace view reports_with_fitbit_hybrid as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.week_id,
   r.weight,
   false as weight_f,
   r.aerobic_minutes,
   false as aerobic_minutes_f,
   r.strength_minutes,
   r.a1c,
   r.physact_minutes,
   false as physact_minutes_f,
   r.notes,
   r.create_dttm,
   r.fdbk_dttm,
   r.avgsteps,
   false as avgsteps_f
from
   wrc_reports r;

create
or replace view first_reports_with_weights_weeks as
select
   user_id,
   class_id,
   class_source,
   min(week_id) as week_id
from
   reports_with_fitbit_hybrid
where
   weight > 0
group by
   user_id,
   class_id,
   class_source;

create
or replace view first_reports_with_weights as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r natural
   join first_reports_with_weights_weeks f;

create
or replace view last_reports_with_weights_weeks as
select
   user_id,
   class_id,
   class_source,
   max(week_id) as week_id
from
   reports_with_fitbit_hybrid
where
   weight > 0
group by
   user_id,
   class_id,
   class_source;

create
or replace view last_reports_with_weights as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.week_id,
   r.weight
from
   reports_with_fitbit_hybrid r natural
   join last_reports_with_weights_weeks f;