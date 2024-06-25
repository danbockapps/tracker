<?php
// TODO consolidate this logic with logic in the other login.php

logtxt(print_r($fetchPost, true));

logtxt(print_r(getallheaders(), true));

// TODO this throws an error if the email address isn't found
$result = seleqt_one_record("
   select
      user_id,
      password,
      activation
   from wrc_users
   where
      email = ?
", array($fetchPost['email']));

logtxt("Checking activation status for user ". $fetchPost['email']);

if ($result['activation'] != null) {
   logtxt('Account not activated.');
} else {
   logtxt('Account activated.');
}

if (verifyPassword($fetchPost['password'], $result['password'])) {
   logtxt('Password is correct. Logging in...');
   // Login successful
   $_SESSION['user_id'] = $result['user_id'];
   $_SESSION['envt'] = ENVIRONMENT;
   $_SESSION['product'] = PRODUCT;

   $ok_array['responseString'] = "Login successful.";
} else {
   logtxt('Password is incorrect.');
   http_response_code(401); // Unauthorized
}
?>