<?php

if(!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
   echo "Error: args not set";
}
else {
   require_once("config.php");
   syncMail($argv[1], $argv[2], $argv[3]);
}

?>
