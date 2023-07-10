<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location:login.php');
  exit;
}

if (isset($_GET['logout'])) {
  session_destroy();
  header('Location:login.php');
  exit;
}

require 'Configuration.php';
require './class/DatabaseConnector.php';

$db = new DatabaseConnector();

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
  $entries = $db->findBirthdaysByAccountId($_SESSION['account']);
  echo '<h2>Your birthdays</h2>
<table><tr><th>Name</th><th>Date</th></tr>';
  foreach ($entries as $entry) {
    echo '<tr><td>' . htmlspecialchars($entry['name']) . '</td><td>' . date('d. M. Y', strtotime($entry['date'])) . '</td></tr>';
  }
  echo '</table>';
}


// Replace $x in string with a number, $n with name, $d with date, $c for a CSS color
$rowTemplate = '<tr>
  <td><input type="text" name="name$x" value="$n" style="border-color: $c" /></td>
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
      in_array($i, $error_rows, true) ? 'red' : 'black'
    ],
    $rowTemplate);
}
    ?>
  <tr><td colspan="2"><input type="submit" value="Add entries" /></td></tr>
  </table>

</form>


<p>
  <a href="?logout">Log out</a>
</p>