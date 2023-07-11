<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

if (!isset($_POST['id'])) {
  header('Location: index.php');
  exit;
}

require '../Configuration.php';
require '../class/DatabaseConnector.php';

$birthdayId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$flag = !!filter_input(INPUT_POST, 'enabled', FILTER_VALIDATE_BOOL);
if ($birthdayId) {
  $db = new DatabaseConnector();
  $db->updateFlag($_SESSION['account'], $birthdayId, $flag);
}