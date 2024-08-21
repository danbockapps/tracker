create
or replace view bcbs_voucher_codes as
select
   distinct upper(e.voucher_code) as voucher_code
from
   enrollment_view e natural
   join classes_aw c
where
   upper(e.voucher_code) like 'ASO%';

create
or replace view bcbs_report as
select
   u.fname as First_Name,
   u.lname as Last_Name,
   u.email as Email,
   e.referred_by as Referred_By,
   e.date_added as Date_Joined,
   c.class_id as Class_ID,
   c.start_dttm as Class_Start,
   c.start_dttm + interval (weeks - 1) week + interval 1 hour as Class_End,
   e.coup_voucher as Coupon_Code,
   substring_index(e.claim_id, '-', 1) as BCBS_Subscriber_ID,
   case
      when claim_id like "%-%" then lpad(substring_index(e.claim_id, '-', -1), 2, '0')
      else ''
   end as Member_Number,
   coalesce(a.numclasses, al.numclasses) as Attendance,
   bw.weight as Beginning_Weight,
   ew.weight as Ending_Weight,
   u.height_inches as Height,
   bw.weight * 703 / (u.height_inches * u.height_inches) as Beginning_BMI,
   ew.weight * 703 / (u.height_inches * u.height_inches) as Ending_BMI,
   least(e.waist_start, 99) as Beginning_Waist_Circumference,
   least(e.waist_end, 99) as Ending_Waist_Circumference,
   pes.pes as Tracker_Activity_Score,
   e.smart_goal as Program_Goals
from
   wrc_users u
   inner join registrants e on u.user_id = e.user_id natural
   join classes_aw c
   left join beginning_weights bw on e.tracker_user_id = bw.user_id
   and e.class_id = bw.class_id
   and e.class_source = bw.class_source
   left join ending_weights ew on e.tracker_user_id = ew.user_id
   and e.class_id = ew.class_id
   and e.class_source = ew.class_source
   left join pes on e.tracker_user_id = pes.user_id
   and e.class_id = pes.class_id
   and e.class_source = pes.class_source
   left join attendance_sum a on e.tracker_user_id = a.user_id
   and e.class_id = a.class_id
   and e.class_source = a.class_source
   left join attendance_sum_legacy al on e.tracker_user_id = al.user_id
   and e.class_id = al.class_id
   and e.class_source = al.class_source
where
   paid != '0'
order by
   c.start_dttm desc,
   u.lname,
   u.fname;

create
or replace view all_aso_participants as
select
   r.user_id as Admin_ID,
   b.First_Name,
   b.Last_Name,
   b.Coupon_Code,
   b.Class_Start,
   b.Class_End,
   b.Attendance,
   b.Beginning_Weight,
   b.Ending_Weight,
   b.Height,
   b.Beginning_BMI,
   b.Ending_BMI,
   b.Beginning_Waist_Circumference,
   b.Ending_Waist_Circumference
from
   bcbs_report b
   left join wrc_users u on b.Email = u.email
   left join registrants r on u.user_id = r.tracker_user_id
   and b.Class_ID = r.class_id
where
   b.Coupon_Code like "ASO%";

create
or replace view bcbs_report_v2 as
select
   'ASO' as LOB,
   e.tracker_user_id as Vendor_MBR_ID,
   e.claim_id as CARD_ID,
   concat(u.fname, ' ', u.lname) as PARTICIPANT_NM,
   g.grpNo as Group_Number,
   e.birthdate as DOB,
   date(c.start_dttm) as Prgm_strt_dt,
   date(
      c.start_dttm + interval (weeks - 1) week + interval 1 hour
   ) as PRGM_END_DT,
   '' as Engagement_ind,
   a.numclasses as 'Total classes',
   /*********************/
   /* Outcome Metrics 1 */
   bw.weight * 703 / (u.height_inches * u.height_inches) as 'Outcome Metrics 1',
   date(c.start_dttm) as 'Outcome Metrics 1 Date',
   'Starting BMI' as 'Outcome Metrics 1 Name',
   /*********************/
   /* Outcome Metrics 2 */
   ew.weight * 703 / (u.height_inches * u.height_inches) as 'Outcome Metrics 2',
   date(
      c.start_dttm + interval (weeks - 1) week + interval 1 hour
   ) as 'Outcome Metrics 2 Date',
   'Ending BMI' as 'Outcome Metrics 2 Name',
   /*********************/
   /* Outcome Metrics 3 */
   bw.weight as 'Outcome Metrics 3',
   date(c.start_dttm) as 'Outcome Metrics 3 Date',
   'Starting Weight' as 'Outcome Metrics 3 Name',
   /*********************/
   /* Outcome Metrics 4 */
   ew.weight as 'Outcome Metrics 4',
   date(
      c.start_dttm + interval (weeks - 1) week + interval 1 hour
   ) as 'Outcome Metrics 4 Date',
   'Ending Weight' as 'Outcome Metrics 4 Name',
   /*********************/
   /* Outcome Metrics 5 */
   least(e.waist_start, 99) as 'Outcome Metrics 5',
   date(c.start_dttm) as 'Outcome Metrics 5 Date',
   'Starting Waist Circumference' as 'Outcome Metrics 5 Name',
   /*********************/
   /* Outcome Metrics 6 */
   least(e.waist_end, 99) as 'Outcome Metrics 6',
   date(
      c.start_dttm + interval (weeks - 1) week + interval 1 hour
   ) as 'Outcome Metrics 6 Date',
   'Ending Waist Circumference' as 'Outcome Metrics 6 Name'
from
   registrants e
   left join wrc_users u on e.tracker_user_id = u.user_id
   left join classes_aw c on e.class_id = c.class_id
   left join dbreg_esmmwl_ctrladmin.vouchers v on e.coup_voucher = v.vcode
   left join dbreg_esmmwl_ctrladmin.companies_BCBS_groups g on v.company_id = g.company_id
   left join beginning_weights bw on e.tracker_user_id = bw.user_id
   and e.class_id = bw.class_id
   and e.class_source = bw.class_source
   left join ending_weights ew on e.tracker_user_id = ew.user_id
   and e.class_id = ew.class_id
   and e.class_source = ew.class_source
   left join attendance_sum a on e.tracker_user_id = a.user_id
   and e.class_id = a.class_id
   and e.class_source = a.class_source
where e.coup_voucher like "%ASO%";
