-- Formatted using VS Code entension "SQL Formatter" (adpyke.vscode-sql-formatter)
create
or replace view enrollment_view as
select
   tracker_user_id as user_id,
   class_id,
   smart_goal,
   reg_date,
   syst_start,
   dias_start,
   waist_start,
   syst_mid,
   dias_mid,
   waist_mid,
   syst_end,
   dias_end,
   waist_end,
   coup_voucher as voucher_code,
   class_source,
   referrer,
   subscriber_id,
   member_number,
   welcome_sent,
   shirtchoice,
   shirt_id,
   address1,
   address2,
   city,
   state,
   zip,
   phone,
   refund_method,
   refund_email_address,
   refund_postal_address,
   ifnc,
   amount,
   birthdate,
   race,
   ethnicity,
   age,
   education,
   sex,
   claim_id,
   referred_by,
   providerName,
   providerState,
   cdc,
   glucoseScore
from
   registrants
where
   paid != '0'
   and status = '1';

create
or replace view classes_aw as
select
   c.id as class_id,
   c.class_type,
   c.start_date_time as start_dttm,
   c.instructor_tracker_id as instructor_id,
   floor(datediff(c.phase2_end, c.start_date_time) / 7) + 1 as weeks,
   convert("w" using latin1) as class_source,
   c.eligibilty_deadline,
   c.phase1_end,
   c.phase2_end
from
   z_classes c;

create
or replace view classes_deadline_today as
select
   *
from
   classes_aw
where
   eligibilty_deadline = curdate();

source create_or_replace_view.sql;

-- Overriding the default for this view
create
or replace view current_classes as
select
   class_id,
   start_dttm,
   instructor_id,
   weeks,
   class_source
from
   classes_aw
where
   start_dttm < now()
   and datediff(now(), phase2_end) < 8;

-- Reports within 2 weeks before eligibilty deadline
create
or replace view reports_near_ed0 as
select
   user_id,
   class_id,
   class_source,
   week_id,
   start_dttm + interval (week_id -1) week as report_date,
   weight,
   eligibilty_deadline
from
   wrc_reports natural
   join classes_aw
where
   start_dttm + interval (week_id -1) week < eligibilty_deadline
   and start_dttm + interval (week_id -1) week > eligibilty_deadline - interval 2 week;

create
or replace view reports_near_ed as
select
   user_id,
   class_id,
   class_source,
   max(weight) as weight
from
   reports_near_ed0
group by
   user_id,
   class_id,
   class_source;

create
or replace view total_physact_minutes as
select
   user_id,
   class_id,
   sum(pa0) as physact_minutes
from
   cdc_transposed_reports
group by
   user_id,
   class_id;

create
or replace view attendance3 as
select
   e.tracker_user_id,
   e.user_id as admin_db_user_id,
   e.class_id,
   zc.class_name,
   e.coup_voucher as voucher_code,
   e.amount,
   e.incentive_type,
   u.fname,
   u.lname,
   u.email,
   coalesce(am.numclasses, 0) as numclasses,
   coalesce(am.numclasses_phase1, 0) as numclasses_phase1,
   coalesce(am.numclasses_phase2, 0) as numclasses_phase2,
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
      and rne.weight > 0 then "Yes"
      else "No"
   end as beginning_and_ending_weight,
   lrww.weight - frww.weight as weight_change,
   (lrww.weight - frww.weight) / frww.weight as weight_change_pct,
   case
      when u.height_inches > 0 then "Yes"
      else "No"
   end as height,
   s.shirt_desc,
   '' as dob,
   e.refund_method,
   e.refund_email_address,
   e.refund_postal_address,
   am.full_participation_phase1,
   am.full_participation_phase2,
   tpm.physact_minutes
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
   left join reports_near_ed rne on e.tracker_user_id = rne.user_id
   and e.class_id = rne.class_id
   and e.class_source = rne.class_source
   left join z_classes zc on e.class_id = zc.id
   left join wrc_users instrs on c.instructor_id = instrs.user_id
   left join attendance_sum3 am on e.tracker_user_id = am.user_id
   and month(c.start_dttm) = am.month
   and year(c.start_dttm) = am.year
   left join shirts s on e.shirt_id = s.shirt_id
   left join total_physact_minutes tpm on e.tracker_user_id = tpm.user_id
   and e.class_id = tpm.class_id
   left join first_reports_with_weights frww on e.tracker_user_id = frww.user_id
   and e.class_id = frww.class_id
   and e.class_source = frww.class_source
   left join last_reports_with_weights lrww on e.tracker_user_id = lrww.user_id
   and e.class_id = lrww.class_id
   and e.class_source = lrww.class_source
where
   instrs.instructor = 1 -- datediff on c.start_dttm was here. Not sure why.
   and e.paid != '0'
   and e.status = '1'
order by
   start_dttm desc,
   lname,
   fname;

/*
 May 2019: New CDC reports
 */
create
or replace view cdc_transposed_reports as
select
   a.attendance_id,
   a.attendance_date,
   a.attendance_type,
   a.lesson_id,
   a.present_phase1,
   a.present_phase2,
   a.user_id,
   a.class_id,
   i.weight as wi,
   i.physact_minutes as pai,
   r.weight as w0,
   r.physact_minutes as pa0,
   r.a1c
from
   attendance_summary3 a
   left join wrc_ireports i on a.lesson_id = i.lesson_id
   and a.user_id = i.user_id
   and a.class_id = i.class_id
   inner join classes_aw c on a.class_id = c.class_id
   left join reports_with_fitbit_hybrid r on a.user_id = r.user_id
   and a.class_id = r.class_id
   and a.week_id = r.week_id
where
   a.attendance_date is not null;

create
or replace view cdc_report0 as
select
   case
      when c.class_type in (1, 2) then '2173125'
      when c.class_type in (4, 5) then '8471188'
      else null
   end as ORGCODE,
   r.user_id as PARTICIP,
   r.state as STATE,
   r.age as AGE,
   case
      r.ethnicity
      when 'Hispanic' then 1
      when 'Not Hispanic' then 2
      else 9
   end as ETHNIC,
   case
      r.sex
      when 'M' then 1
      when 'F' then 2
      else 9
   end as SEX,
   u.height_inches as HEIGHT,
   case
      when t.attendance_type = 2 then 2 -- makeup class
      when c.class_type in (1, 2) then 3 -- online
      when c.class_type in (4, 5) then 1 -- onsite
      else null
   end as DMODE,
   case
      when t.lesson_id <= 18 then t.lesson_id
      else 99
   end as SESSID,
   case
      when t.attendance_type = 2 then 'MU'
      when t.present_phase1 = 1
      and t.attendance_type = 1 then 'C'
      when t.present_phase2 = 1
      and t.attendance_type = 1 then 'CM'
   end as SESSTYPE,
   t.attendance_date as DATE,
   case
      when c.class_type in (1, 2) then t.w0
      when c.class_type in (4, 5) then t.wi
   end as WEIGHT,
   case
      when c.class_type in (1, 2) then t.pa0
      when c.class_type in (4, 5) then t.pai
   end as PA,
   t.a1c,
   -- Fields added 10/5/2023
   r.motivation as ENROLLMOT,
   r.healthcare_provider as ENROLLHC,
   r.health_insurance as PAYERSOURCE,
   r.glucoseScore as GLUCTEST,
   r.gestationalDiabetes as GDM,
   r.cdc as RISKTEST,
   case
      when r.race like "%AI%" then 1
      else 2
   end as AIAN,
   case
      when r.race like "%AS%" then 1
      else 2
   end as ASIAN,
   case
      when r.race like "%AA%" then 1
      else 2
   end as BLACK,
   case
      when r.race like "%PI%" then 1
      else 2
   end as NHOPI,
   case
      when r.race like "%C%" then 1
      else 2
   end as WHITE,
   case
      when r.gender = 'M' then 1
      when r.gender = 'F' then 2
      when r.gender = 'T' then 3
      else 9
   end as GENDER,
   r.education as EDU -- End fields added 10/5/2023
from
   cdc_transposed_reports t
   inner join wrc_users u on t.user_id = u.user_id
   inner join registrants r on t.user_id = r.tracker_user_id
   inner join classes_aw c on t.class_id = c.class_id;

create
or replace view cdc_report as
select
   c.ORGCODE,
   c.PARTICIP,
   c.ENROLLMOT,
   c.ENROLLHC,
   c.PAYERSOURCE,
   c.STATE,
   c.GLUCTEST,
   c.a1c,
   c.GDM,
   c.RISKTEST,
   c.AGE,
   c.ETHNIC,
   c.AIAN,
   c.ASIAN,
   c.BLACK,
   c.NHOPI,
   c.WHITE,
   c.SEX,
   c.GENDER,
   c.HEIGHT,
   c.EDU,
   c.DMODE,
   c.SESSID,
   c.SESSTYPE,
   c.DATE,
   case
      when c.DATE is not null
      and c.WEIGHT is null then 999
      else c.WEIGHT
   end as WEIGHT,
   case
      when c.DATE is not null
      and c.WEIGHT is not null
      and c.PA is null then 0
      else c.PA
   end as PA
from
   cdc_report0 c;

create
or replace view cdc_report_online as
select
   *
from
   cdc_report
where
   orgcode = '2173125'
order by
   particip,
   date;

create
or replace view cdc_report_onsite as
select
   *
from
   cdc_report
where
   orgcode = '8471188'
order by
   particip,
   date;

create
or replace view first_reports_with_a1cs_weeks as
select
   user_id,
   class_id,
   class_source,
   min(week_id) as week_id
from
   reports_with_fitbit_hybrid
where
   a1c is not null
group by
   user_id,
   class_id,
   class_source;

create
or replace view first_reports_with_a1cs as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.week_id,
   r.a1c
from
   reports_with_fitbit_hybrid r natural
   join first_reports_with_a1cs_weeks f;

create
or replace view last_reports_with_a1cs_weeks as
select
   user_id,
   class_id,
   class_source,
   max(week_id) as week_id
from
   reports_with_fitbit_hybrid
where
   a1c is not null
group by
   user_id,
   class_id,
   class_source;

create
or replace view last_reports_with_a1cs as
select
   r.user_id,
   r.class_id,
   r.class_source,
   r.week_id,
   r.a1c
from
   reports_with_fitbit_hybrid r natural
   join last_reports_with_a1cs_weeks f;

-- TODO rework these two
create
or replace view average_pa as
select
   r.user_id,
   r.class_id,
   month(c.start_dttm) as month,
   year(c.start_dttm) as year,
   avg(r.physact_minutes) as pa
from
   reports_with_fitbit_hybrid r
   inner join classes_aw c using (class_id)
group by
   r.user_id,
   month(c.start_dttm),
   year(c.start_dttm);

create
or replace view average_steps as
select
   r.user_id,
   r.class_id,
   month(c.start_dttm) as month,
   year(c.start_dttm) as year,
   avg(r.avgsteps) as avgsteps
from
   reports_with_fitbit_hybrid r
   inner join classes_aw c using (class_id)
group by
   r.user_id,
   month(c.start_dttm),
   year(c.start_dttm);

create
or replace view attendance_months_years0 as
select
   user_id,
   month,
   year,
   month(attendance_date) as attendance_month,
   year(attendance_date) as attendance_year
from
   attendance_summary3;

create
or replace view attendance_months_years as
select
   user_id,
   month,
   year,
   attendance_month,
   attendance_year,
   count(*) as Attendance_CurrentMonth
from
   attendance_months_years0
group by
   user_id,
   month,
   year,
   attendance_month,
   attendance_year;

create
or replace view performance_file as
select
   u.fname as First_Name,
   u.lname as Last_Name,
   u.email as Email,
   e.zip as Zipcode,
   e.sex as Gender,
   case
      when e.race like "%,%" then 'M'
      else e.race
   end as Race,
   e.ethnicity as Ethnicity,
   e.age as Age,
   e.education as Education_Level,
   e.state as Member_State,
   e.claim_id as BCBS_Subscriber_ID,
   e.referred_by as Referred_By,
   e.providerName as Provider_Name,
   e.providerState as Provider_State,
   c.start_dttm as Date_Joined,
   c.start_dttm as Class_Start,
   c.phase2_end as Class_End,
   a.Attendance_CurrentMonth,
   a.attendance_month,
   a.attendance_year,
   '' as Termination,
   e.cdc as CDC_Risk_Score,
   round(u.height_inches, 0) as Height,
   round(frww.weight, 1) as Beginning_Weight,
   round(lrww.weight, 1) as Current_Weight,
   round(lrww.weight, 1) as Ending_Weight,
   round(
      frww.weight * 703 / (u.height_inches * u.height_inches),
      2
   ) as Beginning_BMI,
   round(
      lrww.weight * 703 / (u.height_inches * u.height_inches),
      2
   ) as Current_BMI,
   round(
      lrww.weight * 703 / (u.height_inches * u.height_inches),
      2
   ) as Ending_BMI,
   round(e.waist_start, 0) as Beginning_Waist_Circumference,
   round(e.waist_end, 0) as Ending_Waist_Circumference,
   substring(e.smart_goal, 1, 255) as Program_Goal,
   frwa.a1c as Beginning_HbA1c,
   lrwa.a1c as Ending_HbA1c,
   e.glucoseScore as Beginning_Fasting_Glucose,
   '' as Ending_Fasting_Glucose,
   e.syst_start as Syst_Start,
   e.syst_end as Syst_End,
   e.dias_start as Dias_Start,
   e.dias_end as Dias_End,
   round(apa.pa, 0) as Physical_Activity_Minutes_Avg,
   round(ast.avgsteps, 0) as Steps_Per_Week_Avg,
   '' as NPS_Score
from
   enrollment_view e
   left join wrc_users u using(user_id)
   left join classes_aw c using (class_id)
   left join first_reports_with_weights frww using (user_id, class_id)
   left join last_reports_with_weights lrww using(user_id, class_id)
   left join first_reports_with_a1cs frwa using (user_id, class_id)
   left join last_reports_with_a1cs lrwa using (user_id, class_id)
   left join average_pa apa using(user_id, class_id)
   left join average_steps ast using (user_id, class_id)
   left join attendance_months_years a on a.month = month(c.start_dttm)
   and a.year = year(c.start_dttm)
   and a.user_id = e.user_id
where
   e.voucher_code = 'FIBCBSNC'
   and c.start_dttm < now()
   and c.phase2_end > now();

create
or replace view interaction_file as
select
   *
from
   enrollment_view;