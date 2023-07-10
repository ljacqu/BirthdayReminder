<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './class/DatabaseConnector.php';
require './class/AgeCalculator.php';

$db = new DatabaseConnector();
if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

require './html/header.php';
Header::outputHeader(true, 'Main');

$form_data = [];
$error_rows = [];
if (isset($_POST['name1'])) {
  $entries = [];
  for ($i = 1; $i <= 5; ++$i) {
    $name = filter_input(INPUT_POST, 'name' . $i, FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    $date = filter_input(INPUT_POST, 'date' . $i, FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
    if (!empty($name) && !empty($date)) {
      $entries[] = [$name, new DateTime($date)];
    } else if (!empty($name) || !empty($date)) {
      $error_rows[] = $i;
    }

    $form_data['name' . $i] = $name;
    $form_data['date' . $i] = $date;
  }

  if (!empty($error_rows)) {
    echo '<h2>Error</h2>';
    echo 'Birthdays could not be added: one or more rows has a name without date, or vice versa.';
  } else if (!empty($entries)) {
    foreach ($entries as $entry) {
      $db->addBirthday($_SESSION['account'], $entry[0], $entry[1]);
    }
    echo '<h2>Birthdays added</h2>';
    echo count($entries) . ' birthdays have been added.';

    $form_data = [];
  }
}

if (empty($error_rows)) {

  $from = new DateTime();
  $from->modify('-2 days');
  $entries = $db->findNextBirthdaysForAccountId($_SESSION['account'], $from, 20);
  $settings = $db->getPreferences($_SESSION['account']);

  echo '<h2>Upcoming birthdays</h2>';
  if (empty($entries)) {
    echo "You haven't saved any birthdays.";
  } else {
    $ageCalculator = new AgeCalculator();

    echo '<table class="bordered"><tr><th>Name</th><th>Date</th><th>Age</th><th>&nbsp;</th></tr>';
    $alt = true;

    foreach ($entries as $entry) {
      $id = htmlspecialchars($entry['id']);

      echo '<tr id="br' . $id . '" ' . ($alt ? 'class="alt"' : '') . '>
       <td>' . htmlspecialchars($entry['name']) . '</td>
       <td>' . date($settings['date_format'], strtotime($entry['date'])) . '</td>
       <td>' . $ageCalculator->calculateFutureAge($from, $entry['date']) . '</td>
       <td><a href="?" class="delete" data-id="' . htmlspecialchars($entry['id']) . '">Delete</a></td>
      </tr>';
      $alt = !$alt;
    }
    echo '</table>';
  }
}


// Replace $x in string with a number, $n with name, $d with date, $c for a CSS class
$rowTemplate = '<tr>
  <td><input type="text" name="name$x" value="$n" class="$c" /></td>
  <td><input type="date" name="date$x" value="$d" /></td>
</tr>';

?>
<h2>Add birthdays</h2>
<form method="post" action="index.php">
  <table>
    <tr><th>Name</th><th>Date</th></tr>
    <?php
for ($i = 1; $i <= 5; ++$i) {
  echo str_replace(
    ['$x', '$n', '$d', '$c'],
    [
      $i,
      htmlspecialchars($form_data['name' . $i] ?? ''),
      htmlspecialchars($form_data['date' . $i] ?? ''),
      in_array($i, $error_rows, true) ? 'error' : ''
    ],
    $rowTemplate);
}
    ?>
  <tr><td colspan="2"><input type="submit" value="Add entries" /></td></tr>
  </table>
</form>

<script type="text/javascript" src="./html/delete.js"></script>
</body></html>