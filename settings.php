<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
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

<h2>Change password</h2>
<form method="post" action="settings.php">
<table>
 <tr><td>Current password:</td>
     <td><input type="password" name="current" /></td></tr>
 <tr><td>New password:</td>
     <td><input type="password" name="new" minlength="6" /></td></tr>
 <tr><td>Confirm password:</td>
     <td><input type="password" name="confirm" minlength="6" /></td></tr>
 <tr><td colspan="2"><input type="submit" value="Update password" /></td></tr>
</table>
</form>
HTML;
?>
</body></html>