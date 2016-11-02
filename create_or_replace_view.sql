create or replace view u as
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
   from wrc_users;

create or replace view pv as
   select
      user_id as u,
      request_uri,
      pv_dttm,
      remote_addr
   from wrc_pageviews
   order by pv_dttm desc
   limit 50;

create or replace view current_classes_for_rosters as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from classes_aw
where
   start_dttm < now() + interval 3 day and
   datediff(
      now(),
      start_dttm - interval dayofweek(start_dttm) day
   ) - 2 < weeks * 7;
/* Classes drop off this list Sunday night after the last class. */

create or replace view current_classes as
select *
from current_classes_for_rosters
where start_dttm < now();

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

create or replace view beginning_weights as
select
   user_id,
   class_id,
   class_source,
   weight
from wrc_reports
where week_id = 1;

create or replace view last_reports as
select
   r.user_id,
   r.class_id,
   r.class_source,
   max(r.week_id) as week_id
from
   wrc_reports r
   natural join classes_aw c
where
   r.week_id >= c.weeks - 1
group by
   user_id,
   class_id,
   class_source;

create or replace view ending_weights as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.weight
from
   wrc_reports r
   natural join last_reports lr;


/*
attendance_limiter, attendance_summary, attendance_sum and attendance2
developed December 2015 for new week-by-week attendance entry
*/

create or replace view attendance_limiter as
   select max(attendance_id) as attendance_id
   from wrc_attendance
   group by
      user_id,
      class_id,
      class_source,
      week;

create or replace view attendance_summary as
select
   a.user_id,
   a.class_id,
   a.class_source,
   a.week,
   a.present
from
   wrc_attendance a
   inner join attendance_limiter l
   on a.attendance_id = l.attendance_id;

create or replace view attendance_sum as
select
   user_id,
   class_id,
   class_source,
   sum(present) as numclasses
from attendance_summary
group by
   user_id,
   class_id,
   class_source;

create or replace view pes_reports_summ as
select
  user_id,
  class_id,
  class_source,
  max(create_dttm) as last_report,
  count(create_dttm) as num_reports,
  count(weight) + count(aerobic_minutes) + count(strength_minutes) as num_p1_fields
from wrc_reports
group by
  user_id,
  class_id,
  class_source;

create or replace view pes_avg_word_counts as
select
  user_id,
  avg(
    length(replace(trim(message), '  ', ' ')) -
    length(replace(trim(message), ' ', '')) + 1
  ) as avg_word_count
from wrc_messages
group by user_id;

create or replace view pes_strategy_summ as
select
  user_id,
  class_id,
  class_source,
  count(num_days) as num_p2_fields
from wrc_strategy_report
group by
  user_id,
  class_id,
  class_source;

create or replace view pes_msgs as
select
  e.user_id,
  e.class_id,
  e.class_source,
  m.message,
  floor(datediff(m.create_dttm, c.start_dttm) / 7) + 1 as week_id
from
  enrollment_view e
  natural join classes_aw c
  natural left join wrc_messages m
where m.message_id is not null;

create or replace view pes_logarithms as
select
  user_id,
  class_id,
  class_source,
  log(13, sum(case when week_id=1 then 1 else 0 end) + 1) as log_msgs_week_1,
  log(13, sum(case when week_id=2 then 1 else 0 end) + 1) as log_msgs_week_2,
  log(13, sum(case when week_id=3 then 1 else 0 end) + 1) as log_msgs_week_3,
  log(13, sum(case when week_id=4 then 1 else 0 end) + 1) as log_msgs_week_4,
  log(13, sum(case when week_id=5 then 1 else 0 end) + 1) as log_msgs_week_5,
  log(13, sum(case when week_id=6 then 1 else 0 end) + 1) as log_msgs_week_6,
  log(13, sum(case when week_id=7 then 1 else 0 end) + 1) as log_msgs_week_7,
  log(13, sum(case when week_id=8 then 1 else 0 end) + 1) as log_msgs_week_8,
  log(13, sum(case when week_id=9 then 1 else 0 end) + 1) as log_msgs_week_9,
  log(13, sum(case when week_id=10 then 1 else 0 end) + 1) as log_msgs_week_10,
  log(13, sum(case when week_id=11 then 1 else 0 end) + 1) as log_msgs_week_11,
  log(13, sum(case when week_id=12 then 1 else 0 end) + 1) as log_msgs_week_12,
  log(13, sum(case when week_id=13 then 1 else 0 end) + 1) as log_msgs_week_13,
  log(13, sum(case when week_id=14 then 1 else 0 end) + 1) as log_msgs_week_14,
  log(13, sum(case when week_id=15 then 1 else 0 end) + 1) as log_msgs_week_15,
  log(13, sum(case when week_id=16 then 1 else 0 end) + 1) as log_msgs_week_16,
  log(13, sum(case when week_id=17 then 1 else 0 end) + 1) as log_msgs_week_17,
  log(13, sum(case when week_id=18 then 1 else 0 end) + 1) as log_msgs_week_18,
  log(13, sum(case when week_id=19 then 1 else 0 end) + 1) as log_msgs_week_19,
  log(13, sum(case when week_id=20 then 1 else 0 end) + 1) as log_msgs_week_20
from pes_msgs
group by
  user_id,
  class_id,
  class_source;

create or replace view pes_components as
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
  coalesce(least(pes_avg_word_counts.avg_word_count / 136, 0.5), 0) as pts_avg_word_count,
  coalesce(pes_reports_summ.num_reports, 0) as pts_num_reports,
  coalesce(pes_reports_summ.num_p1_fields * 0.5, 0) as pts_p1_fields,
  coalesce(pes_strategy_summ.num_p2_fields * 0.1, 0) as pts_p2_fields,
  coalesce(
    pes_logarithms.log_msgs_week_1 +
    pes_logarithms.log_msgs_week_2 +
    pes_logarithms.log_msgs_week_3 +
    pes_logarithms.log_msgs_week_4 +
    pes_logarithms.log_msgs_week_5 +
    pes_logarithms.log_msgs_week_6 +
    pes_logarithms.log_msgs_week_7 +
    pes_logarithms.log_msgs_week_8 +
    pes_logarithms.log_msgs_week_9 +
    pes_logarithms.log_msgs_week_10 +
    pes_logarithms.log_msgs_week_11 +
    pes_logarithms.log_msgs_week_12 +
    pes_logarithms.log_msgs_week_13 +
    pes_logarithms.log_msgs_week_14 +
    pes_logarithms.log_msgs_week_15 +
    pes_logarithms.log_msgs_week_16 +
    pes_logarithms.log_msgs_week_17 +
    pes_logarithms.log_msgs_week_18 +
    pes_logarithms.log_msgs_week_19 +
    pes_logarithms.log_msgs_week_20,
    0
  ) as pts_log_msgs,
  least(datediff(now(), c.start_dttm) / 7, c.weeks) as week_num
from
  enrollment_view e
  natural join classes_aw c
  natural left join pes_reports_summ
  natural left join pes_avg_word_counts
  natural left join pes_strategy_summ
  natural left join pes_logarithms;

create or replace view pes as
select
  user_id,
  class_id,
  class_source,
  (
    pts_smart_goal +
    pts_recent_report +
    pts_avg_word_count + (
      pts_num_reports +
      pts_p1_fields +
      pts_p2_fields +
      pts_log_msgs
    ) / week_num
  ) * 15.85 as pes
from pes_components;
