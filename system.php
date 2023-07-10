<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
require './class/ValidationException.php';
require './class/DatabaseConnector.php';
require './class/AccountService.php';

$db = new DatabaseConnector();
if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

$accountInfo = $db->getValuesForSession($_SESSION['account']);

if (!$accountInfo || !$accountInfo['is_admin']) {
  Header('Location: index.php');
  exit;
}

if (isset($_POST['unlock'])) {
  $accountToReset = filter_input(INPUT_POST, 'unlock', FILTER_VALIDATE_INT);
  if ($accountToReset) {
    $db->resetFailedLoginAttempts($accountToReset);
    $db->addEvent(EventType::RESET_FAILED_LOGINS, $accountToReset, $_SESSION['account']);
    echo 'Unlocked ' . $accountToReset;
  }
  exit;
}

require './html/header.php';
Header::outputHeader(true, 'System', $accountInfo);

echo '<h2>Recent events</h2>';
$limit = 15;
$offset = -1;
if (isset($_GET['offset'])) {
  $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT);
  if (!$offset || $offset < 0) {
    $offset = 0;
  }
  $limit = 50;
}

$events = $db->getLatestEvents($limit, $offset >= 0 ? $offset : null);
if (empty($events)) {
  echo 'No events to display.';
} else {
  echo '<table class="bordered">
        <tr><th>Date</th><th>Type</th><th>Account ID</th><th>Additional data</th></tr>';
  foreach ($events as $event) {
    echo "<tr>
            <td>" . date('Y-m-d, H:i', strtotime($event['date'])) . "</td>
            <td>{$event['type']}</td><td>{$event['account_id']}</td>
            <td>{$event['info']}</td>
          </tr>";
  }
  echo '</table>';
}
if ($offset === -1) {
  echo '<p><a href="?offset=0">See all events</a></p>';
} else {
  $prevOffset = max(0, $offset - $limit);
  $nextOffset = !empty($events) ? $offset + $limit : -1;
  echo '<p>';
  if ($prevOffset !== $offset) {
    echo " <a href='?offset=$prevOffset'>Previous</a> ";
  }
  if ($nextOffset > 0) {
    echo " <a href='?offset=$nextOffset'>Next</a>";
  }
  echo ' &middot; <a href="?">System main</a></p>';
}

if ($offset === -1) {

  $email = '';
  if (isset($_POST['email'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

    $accountService = new AccountService($db);
    try {
      $accountService->registerUser($email, $password);
      echo '<h2>Success</h2>The user "' . htmlspecialchars($email) . '" was successfully registered.';
      $email = '';
    } catch (ValidationException $e) {
      echo '<h2>Error</h2>Could not create user: ' . $e->getMessage() . '.';
    }
  }

  echo '<h2>Create user</h2>
<form method="post" action="system.php">
<table>
<tr><td>Email</td>
    <td><input type="email" name="email" value="' . htmlspecialchars($email) . '" /></td></tr>
<tr><td>Password</td>
    <td><input type="password" name="password" minlength="6" /></td></tr>
<tr><td colspan="2"><input type="submit" value=" Create " /></td></tr>
</table></form>

<h2>User overview</h2>';
  if (isset($_GET['users'])) {
    $accounts = $db->getAllAccountOverviews();
    echo '<table class="bordered">
        <tr><th>ID</th><th>Email</th><th>Created</th>
            <th>Locked</th><th>Last login</th><th>Admin</th></tr>';
    foreach ($accounts as $account) {
      $locked = $account['failed_logins'] >= DatabaseConnector::MAX_FAILED_LOGINS 
        ? '<a href="#" title="Unlock user" class="unlock" data-id="' . $account['id'] . '">Y</a>'
        : 'n';
      $admin = $account['is_admin'] ? 'Y' : 'n';
      echo "<tr><td>{$account['id']}</td><td>{$account['email']}</td><td>{$account['created']}</td>
              <td>{$locked}</td><td>{$account['last_login']}</td><td>{$admin}</td></tr>";
    }
    echo '</table>';
  } else {
    echo '<a href="?users">Show users</a>';

    echo '<h2>System overview</h2>';
    echo 'Current time: ' . (new DateTime(null, Configuration::getTimeZone()))->format('Y-m-d, H:i');
    echo '<br />PHP version: ' . PHP_VERSION;
    echo '<br />Total birthdays: ' . $db->countBirthdays();
  }
}


?>
<script src="./html/unlock.js" type="text/javascript"></script>
</body></html>