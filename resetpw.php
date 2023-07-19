<?php
session_start();

require 'Configuration.php';
require './model/EventType.php';
require './class/DatabaseConnector.php';
require './class/AccountService.php';
require './class/AgeCalculator.php';
require './class/Mailer.php';

$db = new DatabaseConnector();
if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

$accountService = new AccountService($db);

$email = '';
if (isset($_SESSION['account'])) {
  header('Location: index.php');
  exit;
}

require './html/header.php';
Header::outputHeader();

if (!Configuration::MAIL_SEND || isset($_POST['email'])) {
  $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

  $messages = [
    'mail_missing' => 'Please provide an email.',
    'mails_disabled' => 'Emails cannot be sent from this instance. Please contact the system administrator.',
    'email_sent_maybe' => 'An email has been sent to your account (if it exists). Please follow the link in the email.'
  ];

  if (!Configuration::MAIL_SEND) {
    $message = $messages['mails_disabled'];
  } else if (!$email) {
    $message = $messages['mail_missing'];
  } else { // $email && Configuration::MAIL_SEND
    $token = $db->setResetToken($email);
    if ($token) {
      $mailer = new Mailer(new AgeCalculator());
      $mailSuccess = $mailer->sendPasswordResetEmail($email, $token);
      $db->addEvent(EventType::TOKEN_REQUEST, $email);
      $message = $mailSuccess ? $messages['email_sent_maybe'] : $messages['mails_disabled'];
    } else {
      $message = $messages['email_sent_maybe'];
      $db->addEvent(EventType::TOKEN_REQUEST_INVALID, $email);
    }
  }

  echo '<h2>Password reset</h2>' . $message;
  if ($email || !Configuration::MAIL_SEND) {
    echo '<p><a href="login.php">Back to login</a></p>';
    echo '</body></html>';
    exit;
  }


} else if (isset($_POST['newpass'])) {
  $accountService = new AccountService($db);
  $token = filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $accountDetails = $accountService->getAccountOrErrorForResetToken($token);
  if (isset($accountDetails['error'])) {
    echo '<h2>Invalid token</h2>The link to reset your password is invalid, or it has expired.';
    unset($token);
  } else {
    $newPass = filter_input(INPUT_POST, 'newpass', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    $confirmPass = filter_input(INPUT_POST, 'confirmpass', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    $passChangeResult = $accountService->setNewPassword($accountDetails['id'], $newPass, $confirmPass);
    if ($passChangeResult === true) {
      echo '<h2>Password reset</h2>Your password has been reset successfully. <br /><a href="login.php">Log in</a></body></html>';
      exit;
    } else {
      echo '<h2>Error</h2>' . htmlspecialchars($passChangeResult);
      unset($token);
    }
  }
} 

if (isset($_GET['t']) || isset($token)) {
  $accountService = new AccountService($db);
  $token = isset($token) ? $token : filter_input(INPUT_GET, 't', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  $accountDetails = $accountService->getAccountOrErrorForResetToken($token);
  if (isset($accountDetails['error'])) {
    $error = $accountDetails['error'] === 'locked' ? 'The account is locked. Please contact a system administrator.' : 'The link is invalid, or it has expired.';
    echo '<h2>Invalid token</h2>' . $error;
  } else {
    echo <<<HTML
<h2>Reset password</h2>
<form method="post" action="resetpw.php">
<table>
  <tr><td>New password:</td><td><input type="password" name="newpass" /></td></tr>
  <tr><td>Confirm password:</td><td><input type="password" name="confirmpass" /></td></tr>
  <tr><td colspan="2"><input type="submit" value="Reset password" /></td></tr>
</table>
<input type="hidden" name="token" value="{$token}" />
</form>
</body></html>
HTML;
    exit;
  }
}
?>

<h2>Reset password</h2>
<p>Enter your email address below. An email will be sent with instructions to reset your password.</p>
<form method="post" action="resetpw.php">
  <table>
    <tr><td><label for="email">Email:</label></td>
        <td><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" /></td></tr>
    <tr><td colspan="2"><input type="submit" value="Reset password" /></td></tr>
  </table>
</form>
<p><a href="login.php">Back to login</a></p>
</body></html>