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
