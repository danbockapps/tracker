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
  border-radius: 5px;
  background-color: $backgroundColor;
  color: #ffffff;
  padding: 14px 25px;
  text-align: center;
  text-decoration: none;
  font-size: 14px;
  font-family: "Arial", sans-serif;
}

.btn-big-margin {
  display: block;
  margin-top: 50px;
  margin-bottom: 50px;
  margin-left: 30%;
  margin-right: 30%;
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
  <a class="btn btn-big-margin" style="color: #ffffff" href="$websiteUrl$queryString">
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

function instructorFeedback() {
  global $head, $img;

  $buttonAndFooter = buttonAndFooter();

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    You have received instructor feedback. Please login to $programName to see it.
  </p>

  $buttonAndFooter
</body>
</html>
EOD;
  return $str;
}

function resetPassword($recipientEmail, $emailResetKey) {
  global $head, $img, $websiteUrl;

  $queryString =
    "/reset.php?email=" .
    urlencode($recipientEmail) .
    "&key=$emailResetKey";

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    To reset your password, please click this button. If you did not make this
    request, please disregard this message. Your password has not been changed.
  </p>

  <a class="btn btn-big-margin" style="color: #ffffff" href="$websiteUrl$queryString">
    Reset Password
  </a>

</body>
</html>
EOD;
  return $str;
}

function welcomeEmailPd($fname, $email, $activation) {
  global $head, $img;
  $setPwUrl = WEBSITE_URL . "/setpw.php?email=" . urlencode($email) . "&key=" . $activation;

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Hello $fname,
  </p>

  <p style="font-size: 12px">
    We hope you enjoyed your first Eat Smart, Move More, Prevent Diabetes class.
  </p>

  <p style="font-size: 12px">
    As you heard from your instructor, the next step in your Eat Smart, Move More, Prevent Diabetes journey is to get started using My Progress Portal. Entering information in My Progress Portal and engaging with your instructor through My Progress Portal is essential to your success in the program.
  </p>

  <p style="font-size: 12px">
    To activate My Progress Portal:
    <ol>
      <li>
        Click here:
        <a class="btn btn-big-margin" style="color: #ffffff" href="$setPwUrl">
          Set your password
        </a>
      </li>
      <li>Create a password; you will use this email address and the password you create to login to My Progress Portal.</li>
      <li>You can now login to My Progress Portal. Please make sure to bookmark the site so you can access easily it in the future. You can also find it through the My Progress Portal button on our website home page, $websiteUrl. If you forget your password, you can click on the link on the login page to have your password emailed to you.</li>
    </ol>
  </p>

  <p style="font-size: 12px">
    Your instructor will be sending out more detailed instructions throughout the week.
  </p>

  <p style="font-size: 12px">
    Please email administrator@esmmpreventdiabetes.com if you have issues setting up My Progress Portal account.
  </p>

  <p style="font-size: 12px">
    Thanks and hope that you enjoy the program!
  </p>

  <p style="font-size: 12px">
    Sincerely,<br />
    The Eat Smart, Move More, Prevent Diabetes Team
  </p>


EOD;
  return $str;
}

function welcomeEmailWl($fname, $email, $activation, $aso) {
  global $head, $img;
  $setPwUrl = WEBSITE_URL . "/setpw.php?email=" . urlencode($email) . "&key=" . $activation;
  $websiteUrl = WEBSITE_URL;

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Hello $fname,
  </p>

  <p style="font-size: 12px">
    We hope you enjoyed your first Eat Smart, Move More, Weigh Less class.
  </p>

  <p style="font-size: 12px">
    As you heard from your instructor, the next step in your Eat Smart, Move More, Weigh Less journey is to get started using My Dashboard. Tracking your progress and engaging with your instructor using My Dashboard is essential to your success in the program. Each week, you will use it to record your: 1) weight, 2) minutes of aerobic activity, and 3) minutes of strength training activity. During weeks #1 and #15, you will record your measurements for: 1) blood pressure and 2) waist circumference. Your week #1 report will also collect your height in order to calculate your BMI.
  </p>
EOD;

  if($activation == null) {
    // Old participant already has an activated Tracker account
    $str .= <<<EOD
      <p style="font-size: 12px">
        To log into My Dashboard:
        <li>
          1. Click here:
          <a class="btn btn-big-margin" style="color: #ffffff" href="$websiteUrl">
            My Dashboard
          </a>
        </li>

        <li>
          2. Log in using the password you set the last time you participated in ESMMWL.
        </li>
      </p>
EOD;
  }
  else {
    // New participant needs to activate her Tracker account
    $str .= <<<EOD
      <p style="font-size: 12px">
        To activate your My Dashboard:
        <li>
          1. Click here:
          <a class="btn btn-big-margin" style="color: #ffffff" href="$setPwUrl">
            Set your password
          </a>
        </li>

        <li>
          2. Create a password; you will use this email address and the password you create to login to My Dashboard.
        </li>

        <li>
          3. You can now login to My Dashboard.
        </li>
EOD;
  }

  $str .= <<<EOD
    <p style="font-size: 12px">
      Please make sure to bookmark the site so you can access easily it in the future. You can also find it through the My Dashboard button on our website home page, www.esmmweighless.com. If you forget your password, you can click on the link on the login page to have your password emailed to you.
    </p>

    <p style="font-size: 12px">
      Enter your weekly numbers
    </p>

    <p style="font-size: 12px">
      Each week, click on the date representing the current week (in green) to enter your: 1) weight
EOD;

  if($aso) {
    $str .= "*";
  }

  $str .= <<<EOD
      , 2) total minutes of aerobic activity, and 3) total minutes of strength training activity. The minutes of activity you enter should be based on the previous seven days, but the weight you enter should represent your current weight.
    </p>
EOD;

  if($aso) {
    $str .= <<<EOD
      <p style="font-size: 12px">
        *To receive your $30 completion incentive, you will need to AT LEAST enter your weight in weeks #1 and #15 in addition to attending at least 10 of the 15 classes.
      </p>
EOD;
  }

  $str .= <<<EOD

  <p style="font-size: 12px">
    During weeks #1 and #15 only, you will record your measurements for: 1) blood pressure and 2) waist circumference. Please refer to the instructions that you received in your packet when taking these measurements.
  </p>

  <p style="font-size: 12px">
    Communicate with Your Instructor
  </p>

  <p style="font-size: 12px">
    You can communicate at any time with your instructor by clicking on the green "Messages" tab at the top of the screen. When your instructor responds, you will receive notification in this email inbox. Please use this tool to ask your instructor questions or get feedback about your experience in the program.
  </p>

  <p style="font-size: 12px">
    Optional Reflection on Healthy Strategies
  </p>

  <p style="font-size: 12px">
    Feel free to use this list to reflect on how many days you adopted recommended strategies during the past week. Let your instructor know if you want feedback on your entries in this section.
  </p>

  <p style="font-size: 12px">
    SMART Goal
  </p>

  <p style="font-size: 12px">
    After week #2, you will enter your SMART goal. In your second class, your instructor will provide detailed guidance on setting your SMART goal, an essential component of your Eat Smart, Move More, Weigh Less experience.
  </p>

  <p style="font-size: 12px">
    Please email administrator@esmmweighless.com if you have issues setting up your My Dashboard account.
  </p>

  <p style="font-size: 12px">
    Thanks and hope that you enjoy the program!
  </p>

  <p style="font-size: 12px">
    Sincerely,
  </p>

  <p style="font-size: 12px">
    The Eat Smart, Move More, Weigh Less Team
  </p>

EOD;

  return $str;
}

function welcomeEmailWl2($fname, $email, $activation) {
  global $head, $img;
  $setPwUrl = WEBSITE_URL . "/setpw.php?email=" . urlencode($email) . "&key=" . $activation;

  $str = <<<EOD
<!DOCTYPE html>
<html>
$head
<body>
  $img

  <p style="font-size: 12px">
    Hello $fname,
  </p>

  <p style="font-size: 12px">
    We hope you enjoyed your first Eat Smart, Move More, Weigh Less2 class.
  </p>

  <p style="font-size: 12px">
    As you heard from your instructor, the next step in your Eat Smart, Move More, Weigh Less2 journey is to get started using My Dashboard 2. Tracking your progress and engaging with your instructor using My Dashboard 2 is essential to your success in the program. Each week, you will use it to record your: 1) weight, 2) minutes of aerobic activity or steps per day, and 3) minutes of strength training activity. During weeks #1 and #12, you will record your measurements for: 1) blood pressure and 2) waist circumference. Your week #1 report will also collect your height in order to calculate your BMI.
  </p>

  <p style="font-size: 12px">
    To activate your My Dashboard 2 account:
    <ol>
      <li>Click this button:
        <a class="btn btn-big-margin" style="color: #ffffff" href="$setPwUrl">
          My Dashboard
        </a>
      </li>
      <li>If you don't already have a My Dashboard 2 password, create one; you will use this email address and the password you create to login to My Dashboard 2.</li>
      <li>You can now login to your My Dashboard 2 account. Please make sure to bookmark the site so you can access easily it in the future. You can also find it through the My Dashboard 2 button on our website home page, www.esmmweighless.com. If you forget your password, you can click on the link on the login page to have your password emailed to you.</li>
    </ol>
  </p>

  <p style="font-size: 12px">
    Enter your weekly numbers
  </p>

  <p style="font-size: 12px">
    Each week, click on the date representing the current week (in green) to enter your: 1) weight, 2) total minutes of aerobic activity, and 3) total minutes of strength training activity. The minutes of activity you enter should be based on the previous seven days, but the weight you enter should represent your current weight.
  </p>

  <p style="font-size: 12px">
    During weeks #1 and #12 only, you will record your measurements for: 1) blood pressure and 2) waist circumference. Please refer to the instructions that you received in your packet when taking these measurements.
  </p>

  <p style="font-size: 12px">
    Communicate with Your Instructor
  </p>

  <p style="font-size: 12px">
    You can communicate at any time with your instructor by clicking on the green "Messages" tab at the top of the screen. When your instructor responds, you will receive notification in this email inbox. Please use this tool to ask your instructor questions or get feedback about your experience in the program.
  </p>

  <p style="font-size: 12px">
    Optional Reflection on Healthy Strategies
  </p>

  <p style="font-size: 12px">
    Feel free to use this list to reflect on how many days you adopted recommended strategies during the past week. Let your instructor know if you want feedback on your entries in this section.
  </p>

  <p style="font-size: 12px">
    SMART Goal
  </p>

  <p style="font-size: 12px">
    After week #1, you will enter a NEW SMART goal. During the first class, your instructor will provide detailed guidance on setting your SMART goal, an essential component of your Eat Smart, Move More, Weigh Less2 experience.
  </p>

  <p style="font-size: 12px">
    Please email administrator@esmmweighless.com if you have issues setting up your My Dashboard 2 account.
  </p>
EOD;

  return $str;
}

?>
