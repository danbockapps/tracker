create view current_classes as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from classes_aw
where
   start_dttm < now() and
   datediff(
      now(),
      start_dttm - interval dayofweek(start_dttm) day
   ) - 2 < weeks * 7;

/* Classes drop off this list Sunday night after the last class. */
