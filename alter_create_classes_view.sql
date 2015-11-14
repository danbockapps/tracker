create or replace view classes_aw as
select
   w.class_id,
   w.start_dttm,
   w.instructor_id,
   w.weeks,
   convert("w" using latin1) as class_source
from wrc_classes w
union
select
   c.id as class_id,
   c.start_date_time as start_dttm,
   i.tracker_id,
   c.num_wks as weeks,
   convert("a" using latin1) as class_source
from
   esmmwl_wpnew.z_classes c
   left join esmmwl_wpnew.z_instructors i
      on c.instructor_id = i.id;

