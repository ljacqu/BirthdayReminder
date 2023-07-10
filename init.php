<?php

require 'Configuration.php';
require './class/DatabaseConnector.php';
require './class/ValidationException.php';
require './class/AccountService.php';

$db = new DatabaseConnector();
$db->initTablesIfNeeded();
$hasAccount = $db->hasAnyAccount();

if (isset($_POST['email'])) {

  if ($hasAccount) {
    die('An account already exists!');
  }

  try {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

    $accountService = new AccountService($db);
    $accountService->registerInitialAdmin($email, $password);

    echo '<h2>Registration successful</h2>';
    echo 'Your account has been registered. Please consider deleting this file now.';
    echo '<br /><a href="index.php">Main page</a>';
  } catch (ValidationException $e) {
    echo '<h2>Error</h2>';
    echo 'The account could not be registered: ' . $e->getMessage() . '.';
    echo '<br /><a href="init.php">Reload</a>';
  }


} else if (isset($_GET['demodata'])) {

  if (!$hasAccount) {
    die('Please create an account first.<br /><a href="init.php">Initialization page</a>');
  } else if ($db->hasAnyBirthday()) {
    die('Cannot create demo birthdays: table is not empty');
  }

  $accountId = $db->fetchMinAccountId();
  $db->addBirthday($accountId, 'John Quincy Adams', new DateTime('1767-07-11'));
  $db->addBirthday($accountId, 'Albert Einstein',   new DateTime('1879-03-14'));
  $db->addBirthday($accountId, 'Marie Curie',       new DateTime('1867-11-07'));
  $db->addBirthday($accountId, 'Leonardo da Vinci', new DateTime('1452-04-15'));
  $db->addBirthday($accountId, 'Amelia Earhart',    new DateTime('1897-07-24'));
  $db->addBirthday($accountId, 'Walt Disney',       new DateTime('1901-12-05'));
  $db->addBirthday($accountId, 'Jane Austen',       new DateTime('1775-12-16'));
  $db->addBirthday($accountId, 'Nelson Mandela',    new DateTime('1918-07-18'));

  $date = new DateTime(null);
  for ($i = 0; $i < 10; ++$i) {
    $total = rand(1, 3);
    for ($n = 0; $n < $total; ++$n) {
      $name = generateDemoName();
      $db->addBirthday($accountId, $name, $date);
    }

    $date->modify('+1 day');
  }
  echo 'Successfully created demo birthdays! <a href="index.php">Main</a>';

} else {

  if (!$hasAccount) {
    echo <<<HTML
<h2>Define initial admin account</h2>
<form method="post">
Email: <input type="email" name="email" value="admin@example.org" />
<br />Password: <input type="password" name="password" value="birthday" />
<br /><input type="submit" value="Register" />
</form>
HTML;
  } else {
    echo <<<HTML
<h2>You're all set!</h2>
You may want to delete this file.
<br /><a href="index.php">Main page</a> &middot; <a href="?demodata">Create demo data</a>
HTML;
  }
}

function generateDemoName() {
  $consonants = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T', 'V', 'W', 'X', 'Z'];
  $firstNames = ['ulia', 'eorge', 'efan', 'ike', 'evin', 'yle', 'elson', 'ora', 'ophie', 'icolas', 'alf', 'alter', 'aniel'];
  $lastNames = ['Demo', 'Sample', 'Example', 'Specimen', 'Template', 'von Sample', 'Samplesor', 'Fakename'];

  $first = $consonants[rand(0, count($consonants)-1)] . $firstNames[rand(0, count($firstNames)-1)];
  $last = $lastNames[rand(0, count($lastNames) - 1)];
  return $first . ' ' . $last;
}