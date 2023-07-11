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
if ($birthdayId) {
  $db = new DatabaseConnector();
  $db->deleteBirthday($_SESSION['account'], $birthdayId);
}