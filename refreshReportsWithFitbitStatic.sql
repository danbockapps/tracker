start transaction;
delete from reports_with_fitbit_static;
insert into reports_with_fitbit_static select * from reports_with_fitbit;
commit;
