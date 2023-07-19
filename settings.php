<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
require './model/ValidationException.php';
require './model/DateFormat.php';
require './model/FlagHandling.php';
require './model/UserPreference.php';
require './class/DatabaseConnector.php';
require './class/SessionService.php';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];

if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

$accountInfo = $db->getValuesForSession($accountId);
if (!SessionService::isSessionValid($accountInfo['session_secret'])) {
  header('Location: login.php');
  exit;
}


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

  $pref = new UserPreference();
  $pref->setDailyMail($newDaily);
  $pref->setDailyFlag($newDailyFlag);
  $pref->setWeeklyMail($newWeekly);
  $pref->setWeeklyFlag($newWeeklyFlag);
  $pref->setDateFormat($dateFormat);

  $db->updatePreferences($accountId, $pref);
  echo '<h2>Settings updated</h2>Thanks! Your preferences have been updated.';


} else if (isset($_POST['emailnew'])) {
  $newEmail     = filter_input(INPUT_POST, 'emailnew',  FILTER_VALIDATE_EMAIL);
  $confirmEmail = filter_input(INPUT_POST, 'emailconf', FILTER_VALIDATE_EMAIL);

  if (!$newEmail) {
    echo '<h2>Error</h2>Your email could not be updated. Please provide a valid email address.';
  } else if ($newEmail !== $confirmEmail) {
    echo '<h2>Error</h2>Your email could not be updated. The confirmation field did not match.';
  } else if ($db->findAccountIdByEmail($newEmail) !== null) {
    echo '<h2>Error</h2>The email address is already in use by another account.';
  } else {
    $oldEmail = $db->fetchEmail($accountId);
    $db->updateEmail($accountId, $newEmail);
    $db->addEvent(EventType::EMAIL_CHANGE, "$oldEmail -> $newEmail", $accountId);
    echo '<h2>Email updated</h2>Your email address has been updated!';
  }


} else if (isset($_POST['current'])) { // password change
  require './class/AccountService.php';
  $accountService = new AccountService($db);

  $currentPwd = filter_input(INPUT_POST, 'current', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $newPwd     = filter_input(INPUT_POST, 'new',     FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $confirmPwd = filter_input(INPUT_POST, 'confirm', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

  $resultOrError = $accountService->handlePasswordChange($accountId, $currentPwd, $newPwd, $confirmPwd);
  if ($resultOrError === true) {
    echo '<h2>Password updated</h2>Your password has been updated! <b>Please log in again.</b> <a href="login.php">Login</a></body></html>';
    exit;
  } else {
    echo '<h2>Error</h2>Your password could not be updated: ' . $resultOrError . '.';
  }
}

$settings = $db->getPreferences($accountId);
$dailyChecked = $settings->getDailyMail() ? 'checked="checked"' : '';

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
printOptions($flagOptions, $settings->getDailyFlag());

echo '</select></td></tr>
    <tr>
      <td><label for="weekly">Weekly mail:</label></td>
      <td><select id="weekly" name="weekly">';
printOptions($weeklyMailOptions, (string) $settings->getWeeklyMail());

echo '</select></td>
    </tr>
    <tr>
      <td><label for="weeklyflag">Weekly mail flag:</label></td>
      <td><select id="weeklyflag" name="weeklyflag">';
printOptions($flagOptions, $settings->getWeeklyFlag());

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
printOptions($dateOptions, $settings->getDateFormat());

$currentEmail = $db->fetchEmail($accountId);

echo <<<HTML
</select></td>
    </tr>
    <tr><td colspan="2"><input type="submit" value="Update settings" /></td></tr>
  </table>
</form>

<h2>Change email</h2>
<p>Change your email here. When you log in in the future, make sure to use the new email address.</p>
<form method="post" action="settings.php">
<table>
 <tr><td>Current email:</td>
     <td><b>{$currentEmail}</b></td></tr>
 <tr><td><label for="emailnew">New email:</label></td>
     <td><input type="email" id="emailnew" name="emailnew" minlength="6" /></td></tr>
 <tr><td><label for="emailconf">Confirm email:</label></td>
     <td><input type="email" id="emailconf" name="emailconf" minlength="6" /></td></tr>
 <tr><td colspan="2"><input type="submit" value="Update email" /></td></tr>
</table>
</form>

<h2>Change password</h2>
<p>Note: Changing password will invalidate all existing sessions, and you will be required to log in again.</p>

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