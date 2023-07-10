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

if (isset($_POST['export'])) {
  $birthdays = $db->findBirthdaysByAccountId($accountId);
  $output = '';

  foreach ($birthdays as $entry) {
    $output .= "\"{$entry['name']}\",\"" . date('Y-m-d', strtotime($entry['date'])) . "\"\n";
  }

  header("Content-type:text/csv; charset=utf-8");
  header("Content-Disposition: attachment; filename=birthdays.csv");
  echo $output;
  exit;
}

require './html/header.php';
Header::outputHeader(true, 'Export', $accountInfo);
?>
<h2>Export data</h2>
You can export all your birthday entries as CSV with the button below.
<p></p>
<form method="post" action="export.php">
  <input type="hidden" name="export" value="1" />
  <input type="submit" value="Download" />
</form>
</body></html>