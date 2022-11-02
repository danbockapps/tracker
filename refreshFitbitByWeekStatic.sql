start transaction;
delete from fitbit_by_week_static;
insert into fitbit_by_week_static select * from fitbit_by_week;
commit;
