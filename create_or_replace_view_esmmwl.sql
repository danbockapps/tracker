create
or replace view enrollment_view as
select
   tracker_user_id as user_id,
   class_id,
   smart_goal,
   date_added as date_joined,
   /* because wrc_users has date_added */
   syst_start,
   dias_start,
   waist_start,
   syst_end,
   dias_end,
   waist_end,
   coup_voucher as voucher_code,
   class_source,
   referred_by,
   claim_id,
   subscriber_id,
   member_number,
   claim_type,
   welcome_sent,
   shirtchoice
from
   registrants
where
   paid != '0'
   and status = '1';

create
or replace view classes_aw as
select
   w.class_id,
   0 as class_type,
   w.start_dttm,
   w.instructor_id,
   w.weeks,
   convert("w" using latin1) as class_source
from
   wrc_classes w
union
select
   c.id as class_id,
   c.class_type,
   c.start_date_time as start_dttm,
   c.instructor_tracker_id,
   c.num_wks as weeks,
   convert("w" using latin1) as class_source
from
   z_classes c;

source create_or_replace_view.sql;

create
or replace view attendance2 as
select
   e.tracker_user_id,
   e.class_id,
   zc.class_name,
   e.coup_voucher as voucher_code,
   u.fname,
   u.lname,
   coalesce(am.numclasses, 0) as numclasses,
   '' as numclasses_phase1,
   '' as numclasses_phase2,
   e.address1,
   e.address2,
   e.city,
   e.state,
   e.zip,
   concat(instrs.fname, " ", instrs.lname) as instructor_name,
   bw.weight as bw_weight,
   ew.weight as ew_weight,
   case
      when bw.weight > 0
      and ew.weight > 0 then "Yes"
      else "No"
   end as beginning_and_ending_weight,
   '' as height,
   e.incentive as incentive_type,
   e.shirtchoice,
   e.birthdate as dob
from
   registrants e
   inner join wrc_users u on e.tracker_user_id = u.user_id natural
   join classes_aw c
   left join beginning_weights bw on e.tracker_user_id = bw.user_id
   and e.class_id = bw.class_id
   and e.class_source = bw.class_source
   left join ending_weights ew on e.tracker_user_id = ew.user_id
   and e.class_id = ew.class_id
   and e.class_source = ew.class_source
   left join z_classes zc on e.class_id = zc.id
   left join wrc_users instrs on c.instructor_id = instrs.user_id
   left join attendance_sum2 am on e.tracker_user_id = am.user_id
   and month(zc.start_date_time) = am.month
   and year(zc.start_date_time) = am.year
where
   instrs.instructor = 1 -- datediff on c.start_dttm was here. Not sure why.
order by
   start_dttm desc,
   lname,
   fname;

create
or replace view attendance_sum_legacy as
select
   tracker_user_id as user_id,
   class_id,
   class_source,
   numclasses
from
   registrants;

create
or replace view aso_codes as
select
   c.BCBS_CompanyName as 'BCBSNC Company Name',
   g.grpNo as 'BCBSNC Group Number',
   v.vcode as Code,
   c.insuranceType as 'ASO or FI'
from
   dbreg_esmmwl_ctrladmin.vouchers v
   left join dbreg_esmmwl_ctrladmin.companies_BCBS_groups g on v.company_id = g.company_id
   left join dbreg_esmmwl_ctrladmin.companies c on g.company_id = c.id
where
   v.form_type = 'aso'
   and v.status = 1
order by
   vcode;