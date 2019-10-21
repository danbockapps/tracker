<?php
function earnedShirt() {
  // https://stackoverflow.com/a/36525712/400765
  $str = <<<EOD
<!DOCTYPE html>
<html>
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
}

</style>
</head>
<body>
  <img src="https://esmmpreventdiabetes.com/mpp/_images/logo.png" />

  <p style="font-size: 12px">
    Congratulations on attending 9 Classes in the Eat Smart, Move More, Prevent
    Diabetes program!
  </p>

  <p style="font-size: 12px">
    Please login to My Progress Portal and choose your t-shirt color and size.
  </p>

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
</body>
</html>
EOD;
  return $str;
}
?>