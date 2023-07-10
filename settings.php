<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './class/DatabaseConnector.php';

require './html/header.html';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];

$weeklyMailOptions = [
  ['value' => '-1', 'label' => 'Disabled'],
  ['value' => '0',  'label' => 'Sunday'],
  ['value' => '1',  'label' => 'Monday'],
  ['value' => '2',  'label' => 'Tuesday'],
  ['value' => '3',  'label' => 'Wednesday'],
  ['value' => '4',  'label' => 'Thursday'],
  ['value' => '5',  'label' => 'Friday'],
  ['value' => '6',  'label' => 'Saturday']
];

if (isset($_POST['weekly'])) {
  $newDaily = !!filter_input(INPUT_POST, 'daily', FILTER_VALIDATE_BOOL);
  $newWeekly = (int) filter_input(INPUT_POST, 'weekly', FILTER_VALIDATE_INT);

  if ($newWeekly < -1 || $newWeekly > 6) {
    die('Error: invalid inputs');
  }

  $db->updatePreferences($accountId, $newDaily, $newWeekly);
  echo '<h2>Settings updated</h2>Thanks! Your preferences have been updated.';
}

$settings = $db->getPreferences($accountId);
$dailyChecked = $settings['daily_mail'] ? 'checked="checked"' : '';

echo <<<HTML
<h2>Preferences</h2>
<form method="post" action="settings.php">
  <table>
    <tr>
      <th>Daily mail</th>
      <th>Weekly mail</th>
    </tr>
    <tr>
      <td><input type="checkbox" name="daily" $dailyChecked/></td>
      <td><select name="weekly">
HTML;

foreach ($weeklyMailOptions as $opt) {
  $selected = $settings['weekly_mail'] === $opt['value'] ? 'selected="selected"' : '';
  echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
}

echo <<<HTML
      </select></td>

    </tr>
    <tr><td colspan="2"><input type="submit" value="Update settings" /></td></tr>
  </table>

</form>
HTML;
?>
</body></html>