<?php
require_once("template.php");
generate_page(true, false);

function page_content() {
   if(!am_i_admin() && !am_i_instructor()) {
      exit("<p>You must be an admin or instructor to view this page.</p>");
   }
   if(!isset($_GET['instr'])) {
      $_GET['instr'] = $_SESSION['user_id'];
   }
   if(!am_i_instructor($_GET['instr'])) {
      exit("<p>The specified user is not an instructor.</p>");
   }
   
   ?>
   <div class="fade">
      <h2>Attendance Entry for <?php echo full_name($_GET['instr']); ?></h2>
      <h3>Select a class</h3>
      <ul id="classList"><?php

      $qr = pdo_seleqt("
         select
            class_id,
            start_dttm
         from classes_aw
         where instructor_id = ?
         order by start_dttm desc
      ", $_GET['instr']);

      foreach($qr as $row) {
         ?><li><a href="attendance_entry<?php
            if(PRODUCT == 'dpp') {
               echo '_detailed';
            }
         ?>.php?class_id=<?php
            echo $row['class_id'];
         ?>"><?php
            echo class_times($row['start_dttm']);
         ?></a></li><?php
      }

   ?></ul></div><?php

}

?>
