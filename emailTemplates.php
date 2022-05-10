<?php
require_once('config.php');

$backgroundColor = PRODUCT == 'dpp' ? '#80298f' : '#0094dd';

// https://stackoverflow.com/a/36525712/400765
$head = <<<EOD
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
  background-color: $backgroundColor;
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

$img = PRODUCT == 'dpp' ?
  '<img src="https://esmmpreventdiabetes.com/mpp/_images/logo.png" />' :
  '<img src="https://esmmweighless.com/tracker/esmmwl_logo.png" />';

$websiteUrl = WEBSITE_URL;
$programName = PROGRAM_NAME;
$productTitle = PRODUCT_TITLE;
$dontForget = PRODUCT == 'dpp' ? 
  "Don't forget to enter your weight and physical activity each week to receive attendance credit." :
  '';

function buttonAndFooter($participantId=0) {
  global $websiteUrl, $programName, $productTitle, $backgroundColor, $dontForget;

  $queryString = $participantId > 0 ? "/all_messages.php?user=$participantId" : '';

  return <<<EOD
  <a class="btn" style="color: #ffffff" href="$websiteUrl$queryString">
    $productTitle
  </a>

  <p style="font-style: italic; font-size: 10px">
    Visit
    <a style="color: $backgroundColor;" href="$websiteUrl">$websiteUrl</a>
    at any time to track your progress and communicate with your instructor.
    $dontForget
  </p>
EOD;
}

function earnedShirtPd() {
  global $head, $img;

  $buttonAndFooter = buttonAndFooter();

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

function earnedShirtWl() {
  global $head, $img;

  $buttonAndFooter = buttonAndFooter();

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Congratulations on attending 14 Classes in the Eat Smart, Move More, Weigh Less program!
  </p>

  <p style="font-size: 12px">
    If you attend your final class, you will earn your t-shirt.
  </p>

  <p style="font-size: 12px">
    Please login to My Dashboard and choose your t-shirt color and size.
  </p>

  $buttonAndFooter
</body>
</html>
EOD;
  return $str;
}

function earnedShirtWl2() {
  global $head, $img;

  $buttonAndFooter = buttonAndFooter();

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Congratulations on attending 11 Classes in the Eat Smart, Move More, Weigh Less 2 program!
  </p>

  <p style="font-size: 12px">
    If you attend your final class, you will earn your t-shirt.
  </p>

  <p style="font-size: 12px">
    Please login to My Dashboard 2 and choose your t-shirt color and size.
  </p>

  $buttonAndFooter
</body>
</html>
EOD;
  return $str;
}

function newMessage($participantId) {
  global $head, $img, $programName;

  $buttonAndFooter = buttonAndFooter($participantId);

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    You have received a new message from your $programName instructor:
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
