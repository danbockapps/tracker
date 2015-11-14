create table if not exists wrc_addresses (
   address_id int unsigned auto_increment primary key,
   user_id int unsigned,
   address1 varchar(255),
   address2 varchar(255),
   city varchar(50),
   state varchar(2),
   zip varchar(10),
   address_dttm timestamp default current_timestamp
) engine=innodb;

create or replace view latest_enrollment_limiter as
select
   tracker_id,
   max(id) as id
from regn_tracker
group by tracker_id;

create or replace view latest_address_limiter as
select
   user_id,
   max(address_dttm) as address_dttm
from wrc_addresses
group by user_id;

create or replace view latest_wrc_addresses as
select *
from
   wrc_addresses
   natural join latest_address_limiter;


create or replace view latest_addresses as
select
   rt.tracker_id as user_id,
   coalesce(a.address1, zr.address1) as address1,
   coalesce(a.address2, zr.address2) as address2,
   coalesce(a.city, zr.city) as city,
   coalesce(a.state, zr.state) as state,
   coalesce(a.zip, zr.zip) as zip
from
   z_registration zr
   inner join regn_tracker rt
      on zr.unique_id = rt.unique_id
   inner join latest_enrollment_limiter lel
      on
         rt.tracker_id = lel.tracker_id and
         rt.id = lel.id
   left join latest_wrc_addresses a
      on rt.tracker_id = a.user_id
