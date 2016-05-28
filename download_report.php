<?php

// Necessary because results report was throwing a memory error.
ini_set("memory_limit","128M");

require_once("config.php");

session_start();
if(!am_i_admin()) {
   exit("You must be an administrator to view this page");
}

if(!isset($_GET['report'])) {
   exit("No report specified.");
}

$whitelist = [
   'users_table',
   'date_test',
   'attendance',
   'attendance2',
   'results'
];

if(!in_array($_GET['report'],  $whitelist)) {
   exit('Invalid report specified.');
}

if($_GET['report'] == "users_table") {
   // For demonstration purposes only
   $qr = pdo_seleqt("
      select user_id, date_added
      from wrc_users
   ", array());
}
else if($_GET['report'] == "date_test") {
   // For demonstration purposes only
   require_get_vars("start_date");
   $qr = pdo_seleqt("
      select user_id, date_added
      from wrc_users
      where date(date_added) > ?
   ", array($_GET['start_date']));
}
else if($_GET['report'] == "attendance") {
   require_get_vars("class");
   $qr = pdo_seleqt("
      select
         instructor_name,
         class_name,
         voucher_code,
         fname,
         lname,
         numclasses,
         beginning_and_ending_weight,
         incentive_type,
         address1,
         address2,
         city,
         state,
         zip,
         shirtsize,
         shirtcolor
      from attendance
      where class_id in (" . join(",", array_keys($_GET['class'])) . ")
   ", array());
}
else if($_GET['report'] == "attendance2") {
   require_get_vars("class");
   $qr = pdo_seleqt("
      select
         instructor_name,
         class_name,
         voucher_code,
         fname,
         lname,
         numclasses,
         beginning_and_ending_weight,
         incentive_type,
         address1,
         address2,
         city,
         state,
         zip,
         shirtsize,
         shirtcolor
      from attendance2
      where class_id in (" . join(",", array_keys($_GET['class'])) . ")
   ", array());
}
else if($_GET['report'] == "results") {
   $qr = pdo_seleqt("
      select
         e.user_id as User_ID,
         c.start_dttm as Class_Start,
         concat(instr.fname, ' ', instr.lname) as Instructor,
         e.smart_goal as SMART_Goal,
         bw.weight as Beginning_Weight,
         ew.weight as Ending_Weight,
         bw.weight * 703 / (u.height_inches * u.height_inches) as Beginning_BMI,
         ew.weight * 703 / (u.height_inches * u.height_inches) as Ending_BMI,
         e.syst_start as Beginning_Systolic_BP,
         e.dias_start as Beginning_Diastolic_BP,
         e.syst_end as Ending_Systolic_BP,
         e.dias_end as Ending_Diastolic_BP,
         e.waist_start as Beginning_Waist_Circumference,
         e.waist_end as Ending_Waist_Circumference,
         pes.pes as Participant_Engagement_Score,
         null as Instructor_Engagement_Score,
         s1.count as Weeks_Entered_Eat_Breakfast,
         s1.num_days as Combined_Days_Eat_Breakfast,
         s2.count as Weeks_Entered_Eat_1_5_Cups_Fruit,
         s2.num_days as Combined_Days_Eat_1_5_Cups_Fruit,
         s3.count as Weeks_Entered_Eat_2_Cups_Vegetables,
         s3.num_days as Combined_Days_Eat_2_Cups_Vegetables,
         s4.count as Weeks_Entered_Control_Portion_Sizes,
         s4.num_days as Combined_Days_Control_Portion_Sizes,
         s5.count as Weeks_Entered_Prepare_Meals_At_Home,
         s5.num_days as Combined_Days_Prepare_Meals_At_Home,
         s6.count as Weeks_Entered_Watch_2_Or_Fewer_Hrs_TV,
         s6.num_days as Combined_Days_Watch_2_Or_Fewer_Hrs_TV,
         s7.count as Weeks_Entered_Drink_1_Or_Fewer_Sugar_Bevs,
         s7.num_days as Combined_Days_Drink_1_Or_Fewer_Sugar_Bevs,
         s8.count as Weeks_Entered_Physical_Activity_30_Mins,
         s8.num_days as Combined_Days_Physical_Activity_30_Mins,
         s9.count as Weeks_Entered_Strength_Training,
         s9.num_days as Combined_Days_Strength_Training
      from
         " . ENR_VIEW . " e
         natural left join classes_aw c
         natural left join wrc_users u
         left join wrc_users instr
            on c.instructor_id = instr.user_id
         left join beginning_weights bw on
            e.user_id = bw.user_id and
            e.class_id = bw.class_id and
            e.class_source = bw.class_source
         left join ending_weights ew on
            e.user_id = ew.user_id and
            e.class_id = ew.class_id and
            e.class_source = ew.class_source
         left join pes on
            e.user_id = pes.user_id and
            e.class_id = pes.class_id and
            e.class_source = pes.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 1 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s1 on
            e.user_id = s1.user_id and
            e.class_id = s1.class_id and
            e.class_source = s1.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 2 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s2 on
            e.user_id = s2.user_id and
            e.class_id = s2.class_id and
            e.class_source = s2.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 3 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s3 on
            e.user_id = s3.user_id and
            e.class_id = s3.class_id and
            e.class_source = s3.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 4 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s4 on
            e.user_id = s4.user_id and
            e.class_id = s4.class_id and
            e.class_source = s4.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 5 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s5 on
            e.user_id = s5.user_id and
            e.class_id = s5.class_id and
            e.class_source = s5.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 6 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s6 on
            e.user_id = s6.user_id and
            e.class_id = s6.class_id and
            e.class_source = s6.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 7 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s7 on
            e.user_id = s7.user_id and
            e.class_id = s7.class_id and
            e.class_source = s7.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 8 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s8 on
            e.user_id = s8.user_id and
            e.class_id = s8.class_id and
            e.class_source = s8.class_source
         left join (
            select
               user_id,
               class_id,
               class_source,
               count(*) as count,
               sum(num_days) as num_days
            from wrc_strategy_report
            where
               strategy_id = 9 and
               num_days > 0
            group by
               user_id,
               class_id,
               class_source
         ) s9 on
            e.user_id = s9.user_id and
            e.class_id = s9.class_id and
            e.class_source = s9.class_source
      order by
         c.start_dttm desc,
         instr.lname,
         instr.fname,
         e.user_id;
   ", array());
}

if(empty($qr)) {
   exit("No report returned.");
}

header("Content-Type: application/csv");
header("Content-Disposition: attachment; filename=" . $_GET['report'] . ".csv");
header("Pragma: no-cache");
echo array_to_csv($qr);
?>
