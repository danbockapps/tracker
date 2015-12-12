<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("You must be an admin or instructor to view this page.");
   }
   if(!isset($_GET['instr'])) {
      $_GET['instr'] = $_SESSION['user_id'];
   }
   if(!am_i_instructor($_GET['instr'])) {
      exit("The specified user is not an instructor.");
   }
   
   ?><h2>Attendance Entry</h2><h3>Select a class</h3><ul id="classList"><?php

   $qr = pdo_seleqt("
      select
         class_id,
         class_source,
         start_dttm
      from classes_aw
      where instructor_id = ?
      order by start_dttm desc
   ", $_GET['instr']);
   
   foreach($qr as $row) {
      ?><li><a href="attendance_entry.php?class_id=<?php
         echo $row['class_id'];
      ?>&class_source=<?php
         echo $row['class_source'];
      ?>"><?php
         echo class_times($row['start_dttm']);
      ?></a></li><?php
   }

   ?></ul><?php

}

?>
