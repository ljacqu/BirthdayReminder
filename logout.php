<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
require './class/DatabaseConnector.php';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];
$accountInfo = $db->getValuesForSession($_SESSION['account']);

if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

if (isset($_POST['logout'])) {
  session_destroy();
  header('Location: login.php');
  exit;
}

require './html/header.php';
Header::outputHeader(true, 'Log out', $accountInfo);
?>
<h2>Log out</h2>
Please press the button to log out.
<p></p>
<form method="post" action="logout.php">
  <input type="hidden" name="logout" value="1" />
  <input type="submit" value="Log out" />
</form>
</body></html>