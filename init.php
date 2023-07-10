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
<br /><a href="index.php">Main page</a>
HTML;
  }
}