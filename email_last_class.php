<?php
require_once("config.php");

$qr = pdo_seleqt("
  select
    u.fname,
    u.email,
    c.start_dttm,
    c.weeks
  from
    wrc_enrollment e
    natural left join classes_aw c
    natural left join wrc_users u
  /* It's 3 days before the end of class */
  where datediff(now(), c.start_dttm) = (c.weeks - 1) * 7 - 4;
", array());

foreach($qr as $row) {
  $msg =
    "Hi " . $row['fname'] . "," .
    "\n\n" .

    "Thanks so much for participating in Eat Smart, Move More, Weigh Less " .
    "Online. Please be ready to fill out your final report in the Weekly " .
    "Tracker (" . WEBSITE_URL . ") in time for your last class this " .
    date("l", strtotime($row['start_dttm'])) . " at " .
    date("g\:i\ A", strtotime($row['start_dttm'])) . ". Please note that " .
    "the Monday after your last class you will no longer be able to access " .
    "your report in the Weekly Tracker." .
    "\n\n" .

    "Like week one, your final report will contain fields to record your " .
    "blood pressure and waist circumference in addition to the regular " .
    "fields for weight and physical activity. Please remember to have these " .
    "final measurements with you in your last class in order to complete " .
    "your final evaluation of the program. Completion of this evaluation " .
    "helps us understand how well the program is working and how we " .
    "might improve it. Thanks in advance for your help!" .
    "\n\n" .

    "If you have moved since you registered for the course, please inform " .
    "your instructor of your new address." .
    "\n\n" .

    "If you would like to take Eat Smart, Move More, Weigh Less again, " .
    "just visit our website, https://esmmweighless.com/ and click on " .
    "\"Enroll\"." .
    "\n\n" .

    "We hope your experience with the program has been successful!" .
    "\n\n" .

    "Sincerely," .
    "\n\n" .

    "The Eat Smart, Move More, Weigh Less Team";
  sendmail($row['email'], "Eat Smart, Move More, Weigh Less Reminder", $msg);
}

?>
