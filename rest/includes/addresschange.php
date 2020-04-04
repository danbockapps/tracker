<?php
if(!isset($_SESSION['user_id'])) {
  exit('You must be logged in to perform this function.');
}

if($_SERVER['REQUEST_METHOD'] == 'GET') {
  $ok_array['data'] = getOldAddress();
}

else if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $oldAddressQr = getOldAddress();
  
  $addressChangeResult = insertAddressChange($oldAddressQr);

  if(!$addressChangeResult) {
    logtxt('ERROR: unknown database error while changing the address.');
    exit('ERROR: unknown database error while changing the address.');
  }

  $updateRegistrantsResult = updateRegistrants();

  if(!$updateRegistrantsResult) {
    logtxt('ERROR: unknown database error while updating registrants.');
    exit('ERROR: unknown database error while updating registrants.');
  }
}

function getOldAddress() {
  return seleqt_one_record('
    select
      address1,
      address2,
      city,
      state,
      zip,
      phone
    from ' . ENR_VIEW . '
    where
      user_id = ? and
      class_id = ?
  ', array($_SESSION['user_id'], current_class_id_for_user($_SESSION['user_id'])));
}

function insertAddressChange($oldAddressQr) {
  $dbh = pdo_connect(DB_PREFIX . '_insert');
  $sth = $dbh->prepare('
    insert into wrc_addresschanges (
      user_id,
      class_id,
      old_address1,
      old_address2,
      old_city,
      old_state,
      old_zip,
      old_phone,
      new_address1,
      new_address2,
      new_city,
      new_state,
      new_zip,
      new_phone
    ) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ');

  global $fetchPost;

  return $sth->execute(array(
    $_SESSION['user_id'],
    current_class_id_for_user($_SESSION['user_id']),
    $oldAddressQr['address1'],
    $oldAddressQr['address2'],
    $oldAddressQr['city'],
    $oldAddressQr['state'],
    $oldAddressQr['zip'],
    $oldAddressQr['phone'],
    $fetchPost['address1'],
    $fetchPost['address2'],
    $fetchPost['city'],
    $fetchPost['state'],
    $fetchPost['zip'],
    $fetchPost['phone'],
  ));
}

function updateRegistrants() {
  $dbh = pdo_connect(DB_PREFIX . '_update');

  $sth = $dbh->prepare('
     update ' . ENR_TBL . '
     set
        address1 = ?,
        address2 = ?,
        city = ?,
        state = ?,
        zip = ?,
        phone = ?
     where
        tracker_user_id = ?
        and class_id = ?
  ');

  global $fetchPost;

  return $sth->execute(array(
    $fetchPost['address1'],
    $fetchPost['address2'],
    $fetchPost['city'],
    $fetchPost['state'],
    $fetchPost['zip'],
    $fetchPost['phone'],
    $_SESSION['user_id'],
    current_class_id_for_user($_SESSION['user_id'])
  ));
}

?>