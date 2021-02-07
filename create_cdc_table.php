<?php
function createCdcTable() {
  $dbh = pdo_connect(DB_PREFIX . "_delete");
  $sth = $dbh->prepare("
  create table cdc_transposed_reports as
  select
     a.attendance_id,
     a.attendance_date,
     a.attendance_type,
     a.week,
     a.present_phase1,
     a.present_phase2,
     a.user_id,
     a.class_id,
     i.weight as wi,
     i.physact_minutes as pai,
     r0.weight as w0,
     r0.physact_minutes as pa0,
     r1.weight as w1,
     r1.physact_minutes as pa1,
     r2.weight as w2,
     r2.physact_minutes as pa2,
     r3.weight as w3,
     r3.physact_minutes as pa3,
     r4.weight as w4,
     r4.physact_minutes as pa4,
     rn1.weight as wn1,
     rn1.physact_minutes as pan1,
     rn2.weight as wn2,
     rn2.physact_minutes as pan2,
     rn3.weight as wn3,
     rn3.physact_minutes as pan3,
     rn4.weight as wn4,
     rn4.physact_minutes as pan4
  from
     attendance_summary3 a
     left join wrc_ireports i
        on a.week = i.lesson_id
        and a.user_id = i.user_id
        and a.class_id = i.class_id
     left join cdc_reports_by_date r0
        on a.user_id = r0.user_id
        and date(a.attendance_date) = date(r0.report_date)
     left join cdc_reports_by_date r1
        on a.user_id = r1.user_id
        and date(a.attendance_date + interval 1 day) = date(r1.report_date)
     left join cdc_reports_by_date r2
        on a.user_id = r2.user_id
        and date(a.attendance_date + interval 2 day) = date(r2.report_date)
     left join cdc_reports_by_date r3
        on a.user_id = r3.user_id
        and date(a.attendance_date + interval 3 day) = date(r3.report_date)
     left join cdc_reports_by_date r4
        on a.user_id = r4.user_id
        and date(a.attendance_date + interval 4 day) = date(r4.report_date)
     left join cdc_reports_by_date rn1
        on a.user_id = rn1.user_id
        and date(a.attendance_date - interval 1 day) = date(rn1.report_date)
     left join cdc_reports_by_date rn2
        on a.user_id = rn2.user_id
        and date(a.attendance_date - interval 2 day) = date(rn2.report_date)
     left join cdc_reports_by_date rn3
        on a.user_id = rn3.user_id
        and date(a.attendance_date - interval 3 day) = date(rn3.report_date)
     left join cdc_reports_by_date rn4
        on a.user_id = rn4.user_id
        and date(a.attendance_date - interval 4 day) = date(rn4.report_date)
  where a.attendance_date is not null;
  ");

  $sth->execute();
}

function deleteCdcTable() {
  $dbh = pdo_connect(DB_PREFIX . "_delete");
  $sth = $dbh->prepare("drop table cdc_transposed_reports");
  $sth->execute();
}
?>
