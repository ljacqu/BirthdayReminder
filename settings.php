<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
require './class/ValidationException.php';
require './model/DateFormat.php';
require './model/FlagHandling.php';
require './class/DatabaseConnector.php';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];

if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

$accountInfo = $db->getValuesForSession($_SESSION['account']);
require './html/header.php';
Header::outputHeader(true, 'Settings', $accountInfo);

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
  $newDailyFlag = filter_input(INPUT_POST, 'dailyflag', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $newWeekly = (int) filter_input(INPUT_POST, 'weekly', FILTER_VALIDATE_INT);
  $newWeeklyFlag = filter_input(INPUT_POST, 'weeklyflag', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $dateFormat = filter_input(INPUT_POST, 'dateformat', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

  if ($newWeekly < -1 || $newWeekly > 6) {
    die('Error: invalid inputs');
  }
  DateFormat::validateFormat($dateFormat);
  FlagHandling::validate($newDailyFlag);
  FlagHandling::validate($newWeeklyFlag);

  $db->updatePreferences($accountId, $newDaily, $newDailyFlag, $newWeekly, $newWeeklyFlag, $dateFormat);
  echo '<h2>Settings updated</h2>Thanks! Your preferences have been updated.';


} else if (isset($_POST['current'])) { // password change
  require './class/AccountService.php';
  $accountService = new AccountService($db);

  $currentPwd = filter_input(INPUT_POST, 'current', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $newPwd     = filter_input(INPUT_POST, 'new',     FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $confirmPwd = filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

  $resultOrError = $accountService->handlePasswordChange($accountId, $currentPwd, $newPwd, $confirmPwd);
  if ($resultOrError === true) {
    echo '<h2>Password updated</h2>Your password has been updated!';
  } else {
    echo '<h2>Error</h2>Your password could not be updated: ' . $resultOrError . '.';
  }
}

$settings = $db->getPreferences($accountId);
$dailyChecked = $settings['daily_mail'] ? 'checked="checked"' : '';

echo <<<HTML
<h2>Preferences</h2>
<form method="post" action="settings.php">
  <table>
    <tr>
      <td><label for="daily">Daily mail:</label></td>
      <td style="text-align: center"><input type="checkbox" id="daily" name="daily" $dailyChecked/></td>
    </tr>
    <tr>
      <td><label for="dailyflag">Daily mail flag:</label></td>
      <td><select id="dailyflag" name="dailyflag">
HTML;
$flagOptions = [
  ['value' => 'none', 'label' => 'No relevance'],
  ['value' => 'ignore', 'label' => 'Ignore for mails'],
  ['value' => 'filter', 'label' => 'Only for entries with flag']
];
printOptions($flagOptions, $settings['daily_flag']);

echo '</select></td></tr>
    <tr>
      <td><label for="weekly">Weekly mail:</label></td>
      <td><select id="weekly" name="weekly">';
printOptions($weeklyMailOptions, $settings['weekly_mail']);

echo '</select></td>
    </tr>
    <tr>
      <td><label for="weeklyflag">Weekly mail flag:</label></td>
      <td><select id="weeklyflag" name="weeklyflag">';
printOptions($flagOptions, $settings['weekly_flag']);

echo '</select></td></tr>
      <tr>
      <td><label for="dateformat">Date format:</label></td>
      <td><select id="dateformat" name="dateformat">';

$dateOptions = array_map(function ($opt) {
  return [
    'value' => $opt,
    'label' => date($opt, strtotime('2020-03-14'))
  ];
}, DateFormat::getAllFormats());
printOptions($dateOptions, $settings['date_format']);

echo <<<HTML
</select></td>
    </tr>
    <tr><td colspan="2"><input type="submit" value="Update settings" /></td></tr>
  </table>

</form>

<h2>Change password</h2>
<form method="post" action="settings.php">
<table>
 <tr><td><label for="current">Current password:</label></td>
     <td><input type="password" id="current" name="current" /></td></tr>
 <tr><td><label for="new">New password:</label></td>
     <td><input type="password" id="new" name="new" minlength="6" /></td></tr>
 <tr><td><label for="confirm">Confirm password:</label></td>
     <td><input type="password" id="confirm" name="confirm" minlength="6" /></td></tr>
 <tr><td colspan="2"><input type="submit" value="Update password" /></td></tr>
</table>
</form>
HTML;

function printOptions($options, $selectedValue) {
  foreach ($options as $opt) {
    $selected = $selectedValue === $opt['value'] ? 'selected="selected"' : '';
    echo '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
  }
}

?>
</body></html>