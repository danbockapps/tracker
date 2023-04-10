create or replace view fitbit_conflicts as
select user_id, class_id, week_id
from fitbit_by_week_static
inner join wrc_reports
using (user_id, class_id, week_id);

create or replace view fitbit_archive as
select
   user_id,
   class_id,
   'w' as class_source,
   week_id,
   coalesce(r.weight, f.weight) as weight,
   coalesce(r.aerobic_minutes, nullif(minutes, 0)) as aerobic_minutes,
   coalesce(r.physact_minutes, nullif(minutes, 0)) as physact_minutes,
   coalesce(r.avgsteps, nullif(f.avgsteps, 0)) as avgsteps
from fitbit_by_week_static f
left join wrc_reports r
using (user_id, class_id, week_id)
left join fitbit_conflicts c
using (user_id, class_id, week_id)
where c.user_id is null and c.class_id is null
and f.week_id > 0;

/*
insert into wrc_reports (
   user_id,
   class_id,
   class_source,
   week_id,
   weight,
   aerobic_minutes,
   physact_minutes,
   avgsteps,
   create_dttm
) select
   user_id,
   class_id,
   class_source,
   week_id,
   weight,
   aerobic_minutes,
   physact_minutes,
   avgsteps,
   '2023-04-09 22:00:00' as create_dttm
from fitbit_archive;
*/
