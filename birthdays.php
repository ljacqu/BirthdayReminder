﻿<?php
session_start();

if (!isset($_SESSION['account'])) {
  header('Location: login.php');
  exit;
}

require 'Configuration.php';
require './model/EventType.php';
require './model/ValidationException.php';
require './model/DateFormat.php';
require './model/FlagHandling.php';
require './model/UserPreference.php';
require './class/DatabaseConnector.php';
require './class/AgeCalculator.php';
require './class/SessionService.php';

$db = new DatabaseConnector();
$accountId = $_SESSION['account'];

if (!$db->birthdayTableExists()) {
  header('Location: init.php');
  exit;
}

$accountInfo = $db->getValuesForSession($accountId);
if (!SessionService::isSessionValid($accountInfo['session_secret'])) {
  header('Location: login.php');
  exit;
}

require './html/header.php';
Header::outputHeader(true, 'Birthdays', $accountInfo);

$form_data = [];
$error_rows = [];
$total_rows = 2;

if (isset($_POST['name']) && isset($_POST['date'])) {
  $sizeNames = is_array($_POST['name']) ? count($_POST['name']) : 0;
  $sizeDates = is_array($_POST['date']) ? count($_POST['date']) : 0;
  if ($sizeNames === $sizeDates) {
    $entries = [];
    $index = 1;

    $date = reset($_POST['date']);
    foreach ($_POST['name'] as $name) {
      $nameValid = !empty($name) && is_scalar($name);
      $dateValid = !empty($date) && is_scalar($date);
      if ($nameValid && $dateValid) {
        $entries[] = [$name, new DateTime($date)];
      } else if ($nameValid || $dateValid) {
        $error_rows[] = $index;
      }

      if ($nameValid) {
        $form_data['name' . $index] = $name;
      }
      if ($dateValid) {
        $form_data['date' . $index] = $date;
      }

      ++$index;
      $date = next($_POST['date']);
    }

    if (empty($error_rows) && !empty($entries)) {
      foreach ($entries as $entry) {
        $db->addBirthday($_SESSION['account'], $entry[0], $entry[1]);
      }
      echo '<h2>Birthdays added</h2>';
      echo count($entries) . ' birthdays have been added.';
      $form_data = [];
    } else if (!empty($error_rows)) {
      echo '<h2>Error</h2>';
      echo 'Birthdays could not be added: one or more rows has a name without date, or vice versa.';
      $total_rows = $index;
    }
  }
}

$limit = empty($form_data) ? null : 10;
$sort = empty($form_data) ? getSortColumn() : null;

$entries = $db->findBirthdaysByAccountId($_SESSION['account'], $limit, null, $sort ? $sort['sql'] : null);
$settings = $db->getPreferences($_SESSION['account']);
$flagTextInfo = FlagHandling::getFlagText($settings);

echo '<h2>Birthdays</h2>';
if (empty($entries)) {
  echo "You haven't saved any birthdays.";
} else {
  echo '<table class="bordered"><tr>';
  $columns = createColumnDefinitions($flagTextInfo, $sort);
  foreach ($columns as $k => $col) {
    echo '<th>';
    if (isset($col['title'])) {
      echo '<acronym title="' . htmlspecialchars($col['title']) . '">';
    }

    echo empty($col['nextsort'])
      ? $col['text']
      : '<a href="?sort=' . trim($col['nextsort']) . '" class="internal">' . $col['text'] . '</a>';

    if (isset($col['title'])) {
      echo '</acronym>';
    }
    if (isset($col['sortsymbol'])) {
      echo ' ' . $col['sortsymbol'];
    }
    echo '</th>';
  }
  echo '</tr>';

  $alt = true;

  foreach ($entries as $entry) {
    $id = htmlspecialchars($entry['id']);
    $flagChecked = $entry['flag'] ? 'checked="checked"' : '';

    echo '<tr id="br' . $id . '" ' . ($alt ? 'class="alt"' : '') . ' data-id="' . htmlspecialchars($entry['id']) . '">
      <td class="dbleditname">' . htmlspecialchars($entry['name']) . '</td>
      <td class="dbleditdate" data-date="' . $entry['date'] .'">' . date($settings->getDateFormat(), strtotime($entry['date'])) . '</td>';
    if ($flagTextInfo) {
      echo '<td style="text-align: center"><input disabled="disabled" type="checkbox" class="flag" ' . $flagChecked . ' /></td>';
    }
    echo '<td><a href="#" class="delete internal">Delete</a></td>
      </tr>';
    $alt = !$alt;
  }
  echo '</table>';
}

function getSortColumn() {
  $sort = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);
  switch ($sort) {
    case 'n':
      return ['sql' => 'name', 'param' => 'n'];
    case 'nd':
      return ['sql' => 'name desc', 'param' => 'nd'];
    case 'd':
    default: // <-- default
      return ['sql' => 'date_2020, year(date) desc', 'param' => 'd'];
    case 'dd':
      return ['sql' => 'date_2020 desc, year(date) asc', 'param' => 'dd'];
    case 'c': // complete date
      return ['sql' => 'date', 'param' => 'c'];
    case 'cd':
      return ['sql' => 'date desc', 'param' => 'cd'];
    case 'f':
      return ['sql' => 'flag', 'param' => 'f'];
    case 'fd':
      return ['sql' => 'flag desc', 'param' => 'fd'];
  }
}

function createColumnDefinitions($flagTextInfo, $sort) {
  $columns = [    
    'name' => ['text' => 'Name', 'sort' => ['n', 'nd', ' ']],
    'date' => ['text' => 'Date', 'sort' => ['d', 'dd', 'c', 'cd']]
  ];
  if ($flagTextInfo) {
    $columns['flag'] = ['text' => $flagTextInfo['text'], 'title' => $flagTextInfo['help'], 'sort' => ['fd', 'f', ' ']];
  }
  $columns['del'] = ['text' => '', 'sort' => []];
  
  foreach ($columns as &$col) {
    $key = $sort ? array_search($sort['param'], $col['sort'], true) : false;
    if ($key !== false) {
      $nextKey = ++$key;
      if ($nextKey >= count($col['sort'])) {
        $nextKey = 0;
      }
      $col['nextsort'] = $col['sort'][$nextKey];
      
      if ($sort['param'] === 'c') {
        $col['sortsymbol'] = '↑↑';
      } else if ($sort['param'] === 'cd') {
        $col['sortsymbol'] = '↓↓';
      } else {
        $col['sortsymbol'] = (strlen($sort['param']) === 2) ? '↓' : '↑';  
      }
    } else {
      $col['nextsort'] = empty($col['sort']) ? '' : $col['sort'][0];
    }
  }

  return $columns;
}

// Replace $n with name, $d with date, $c for a CSS class
$rowTemplate = '<tr class="addrow">
  <td><input type="text" name="name[]" value="$n" class="$c" /></td>
  <td><input type="date" name="date[]" value="$d" /></td>
</tr>';

?>
<h2>Add birthdays</h2>
<form method="post" action="birthdays.php">
  <table>
    <tr><th>Name</th><th>Date</th></tr>
    <?php
    for ($i = 1; $i <= $total_rows; ++$i) {
      echo str_replace(
        ['$n', '$d', '$c'],
        [
          htmlspecialchars($form_data['name' . $i] ?? ''),
          htmlspecialchars($form_data['date' . $i] ?? ''),
          in_array($i, $error_rows, true) ? 'error' : ''
        ],
        $rowTemplate);
    }
    ?>
  <tr><td colspan="2"><input type="submit" value="Add entries" />
                      &nbsp; <input type="reset" value="Reset" id="addreset" /></td></tr>
  </table>
</form>

<script type="text/javascript" src="./js/edit.js"></script>
<script type="text/javascript" src="./js/toggle_flag.js"></script>
<script type="text/javascript" src="./js/delete.js"></script>

<script type="text/javascript" src="./js/add_rows.js"></script>
<script type="text/javascript" src="./js/reset_addform.js"></script>
</body></html>