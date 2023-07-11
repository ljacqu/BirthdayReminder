<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './class/DatabaseConnector.php';
require './class/AgeCalculator.php';

$db = new DatabaseConnector();
if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}
$accountInfo = $db->getValuesForSession($_SESSION['account']);

require './html/header.php';
Header::outputHeader(true, 'Main', $accountInfo);

$from = new DateTime();
$from->modify('-2 days');
$entries = $db->findNextBirthdaysForAccountId($_SESSION['account'], $from, 20);
$settings = $db->getPreferences($_SESSION['account']);

echo '<h2>Upcoming birthdays</h2>';
if (empty($entries)) {
  echo "You haven't saved any birthdays.";
} else {
  $ageCalculator = new AgeCalculator();

  echo '<table class="bordered"><tr><th>Name</th><th>Date</th><th>Age</th><th>Flag</th><th>&nbsp;</th></tr>';
  $alt = true;

  foreach ($entries as $entry) {
    $id = htmlspecialchars($entry['id']);
    $flagChecked = $entry['flag'] ? 'checked="checked"' : '';

    echo '<tr id="br' . $id . '" ' . ($alt ? 'class="alt"' : '') . '>
      <td>' . htmlspecialchars($entry['name']) . '</td>
      <td>' . date($settings['date_format'], strtotime($entry['date'])) . '</td>
      <td>' . $ageCalculator->calculateFutureAge($from, $entry['date']) . '</td>
      <td><input type="checkbox" class="flag" data-id="' . htmlspecialchars($entry['id']) . '" ' . $flagChecked . ' />
      <td><a href="?" class="delete" data-id="' . htmlspecialchars($entry['id']) . '">Delete</a></td>
    </tr>';
    $alt = !$alt;
  }
  echo '</table>';
}


?>
<h2>Add birthdays</h2>
<form method="post" action="birthdays.php">
  <table>
    <tr><th>Name</th><th>Date</th></tr>
    <?php
    for ($i = 1; $i <= 5; ++$i) {
          echo '<tr>
      <td><input type="text" name="name[]" /></td>
      <td><input type="date" name="date[]" /></td>
    </tr>';
    }
    ?>
  <tr><td colspan="2"><input type="submit" value="Add entries" /></td></tr>
  </table>
</form>

<script type="text/javascript" src="./js/delete.js"></script>
<script type="text/javascript" src="./js/toggle_flag.js"></script>
</body></html>