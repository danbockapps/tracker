<?php

// https://stackoverflow.com/a/36525712/400765
$head= <<< EOD
<head>
<style>

img {
  display: block;
  width: 200px;
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 50px;
}

p {
  font-family: "Arial", sans-serif;
}

.btn {
  display: block;
  margin-top: 50px;
  margin-bottom: 50px;
  margin-left: 30%;
  margin-right: 30%;
  border-radius: 5px;
  background-color: #80298f;
  color: #ffffff;
  padding: 14px 25px;
  text-align: center;
  text-decoration: none;
  font-size: 14px;
  font-family: "Arial", sans-serif;
}

</style>
</head>
EOD;

$img = '<img src="https://esmmpreventdiabetes.com/mpp/_images/logo.png" />';

$buttonAndFooter = <<<EOD
<a class="btn" style="color: #ffffff" href="https://esmmpreventdiabetes.com/mpp">
    My Progress Portal
  </a>

  <p style="font-style: italic; font-size: 10px">
    Visit
    <a style="color: #80298f;" href="https://esmmpreventdiabetes.com/mpp">
      esmmpreventdiabetes.com/mpp
    </a>
    at any time to track your progress and communicate with your instructor.
    Don't forget to enter your weight and physical activity each week to
    receive attendance credit.
  </p>
EOD;

function earnedShirt() {
  global $head, $img, $buttonAndFooter;

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Congratulations on attending 9 Classes in the Eat Smart, Move More, Prevent
    Diabetes program!
  </p>

  <p style="font-size: 12px">
    Please login to My Progress Portal and choose your t-shirt color and size.
  </p>

  $buttonAndFooter
</body>
</html>
EOD;
  return $str;
}

function newMessage() {
  global $head, $img, $buttonAndFooter;

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    You have received a new message from your Eat Smart, Move More, Prevent
    Diabetes instructor:
  </p>

  <p style="font-size: 12px">
    Click below to view your message and communicate with your instructor:
  </p>

  $buttonAndFooter
</body>
</html>
EOD;
  return $str;
}
?>
