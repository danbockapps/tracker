<?php
/**
 * This file is included from attendance_entry_detailed.php.
 * $_GET['class_id'] is required.
 */
?>

<p id="showGridLink">
  <a href="#">Show grid</a>
</p>
<div id="staticGrid" style="display:none">
  <p>0 or blank: No class attended</p>
  <p>1: Regular class attended</p>
  <p>2: Make-up class attended</p>
</div>

<script>
$('#showGridLink a').click(function(){
  showGrid();
  return false; // prevent browser from following the link
});

function showGrid() {
  $('#showGridLink').hide();
  $('#staticGrid').show();
  $('#staticGrid').prepend('<table><tr id="header"></tr></table>');
  $('#header').append('<th>Participant name</th>');
  $('#header').append('<th><!-- attendance sum --></th>');
  for(var i=1; i<=24; i++) {
    $('#header').append('<th class="checkboxCell">' + i + '</th>');
  }

  $.get('rest/api.php?q=attendance&class_id=<?php echo $_GET['class_id']; ?>', function(data) {
    data.attendance.forEach(function(item) {
      if(!userIsInGrid(item.user_id)) {
        addUserToGrid(item.user_id, item.fname, item.lname);
      }

      $(
        '#staticGrid ' +
        'tr[user-id=' + item.user_id + '] ' +
        'td[lesson-id=' + item.week + ']'
      ).html(item.attendance_type);
    });
  });
}

function userIsInGrid(userId) {
  return $('#staticGrid tr[user-id=' + userId + ']').length;
}

function addUserToGrid(userId, fname, lname) {
  $('#staticGrid table').append(
    '<tr user-id="' + userId + '">' +
    '<td>' + fname + ' ' + lname + '</td>' +
    '<td class="checkboxCell"></td>' +
    '</tr>'
  );

  for(var i=1; i<=24; i++) {
    $('#staticGrid tr[user-id=' + userId + ']').append(
      '<td lesson-id="' + i + '"></td>'
    );
  }
}
</script>