<?php
session_start();
require_once("config.php");
header("Location: " . my_home_page());
?>