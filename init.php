<?php

require 'Configuration.php';
require './model/ValidationException.php';
require './class/DatabaseConnector.php';
require './class/AccountService.php';
require './html/header.php';

Header::outputHeader();

$db = new DatabaseConnector();
$db->initTablesIfNeeded();
$hasAccount = $db->hasAnyAccount();

if (isset($_POST['email'])) {

  if ($hasAccount) {
    die('Error: an account already exists! <a href="index.php">Main page</a>');
  }

  try {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

    $accountService = new AccountService($db);
    $accountService->registerInitialAdmin($email, $password);

    echo '<h2>Registration successful</h2>';
    echo 'Your account has been registered. You can delete this file now if you like.';
    echo '<br /><a href="index.php">Main page</a> &middot; <a href="?demodata">Create demo data</a>';
  } catch (ValidationException $e) {
    echo '<h2>Error</h2>';
    echo 'The account could not be registered: ' . $e->getMessage() . '.';
    echo '<br /><a href="init.php">Reload</a>';
  }


} else if (isset($_GET['demodata'])) {

  if (!$hasAccount) {
    die('Error: please create an account first.<br /><a href="init.php">Initialization page</a>');
  } else if ($db->countBirthdays() > 0) {
    die('Error: cannot create demo data. You already have entries! <a href="index.php">Main page</a>');
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
      $date->setDate(rand(1980, 2020), $date->format('m'), $date->format('d'));
      $db->addBirthday($accountId, $name, $date);
    }

    $date->modify('+1 day');
  }
  echo 'Successfully created demo entries! <a href="index.php">Main page</a>';

} else {

  if (!$hasAccount) {
    echo <<<HTML
<h2>Define initial admin account</h2>
<p>Welcome to the <em>Birthday Reminder</em> setup! Please create a user to administer the system.</p>
<form method="post">
<table>
<tr><td>Email:</td><td><input type="email" name="email" /></td></tr>
<tr><td>Password:</td><td><input type="password" name="password" minlength="6" /></td></tr>
<tr><td><input type="submit" value="Register" /></td></tr>
</table>
</form>
HTML;
  } else {
    echo <<<HTML
<h2>You're all set!</h2>
You've already created an initial user. Go to the main page to log in with that user. You can now delete this file.
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
?>
</body></html>