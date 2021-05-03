start transaction;
insert into fitbit_static
select * from fitbit f
where updated > date_sub(now(), interval 1 day)
on duplicate key update id=f.id, value=f.value, updated=f.updated;

delete from fitbit_by_week_static;
insert into fitbit_by_week_static select * from fitbit_by_week;
commit;
