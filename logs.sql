select
  user_id,
  class_id,
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
from (
  select
    u.user_id,
    e.class_id,
    m.message,
    floor(datediff(m.create_dttm, c.start_dttm) / 7) + 1 as week_id
  from
    wrc_users u
    natural join wrc_enrollment e
    natural join wrc_classes c
    natural left join wrc_messages m
  where m.message_id is not null
  ) x
group by user_id, class_id;
