<?php
session_start();

require 'Configuration.php';
require './model/EventType.php';
require './class/DatabaseConnector.php';
require './class/AccountService.php';

$db = new DatabaseConnector();
$accountService = new AccountService($db);

$email = '';
if (isset($_GET['logout'])) {
  session_destroy();
} else if (isset($_SESSION['account'])) {
  echo '<h2>Welcome, ID ' . $_SESSION['account'] . '</h2>';

  echo '<a href="?logout">Log out</a>';
  exit;
} else if (isset($_POST['email'])) {
  $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
  $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

  $accountId = $accountService->handleLogin($email, $password);
  if (!$accountId) {
    echo 'Invalid login! Please try again.';
  } else {
    $_SESSION['account'] = $accountId;
    header('Location: index.php');
    exit;
  }
}

?>
<form method="post" action="index.php">
  Email: <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" />
  <br />Password: <input type="password" name="password" />
  <br /><input type="submit" value="Log in" />

</form>