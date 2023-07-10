<?php

final class Header {

  private function __construct() {
  }

  static function outputHeader($showLinks=false, $currentPage=null) {
    $links = [
      ['page' => 'index.php', 'label' => 'Main'],
      ['page' => 'settings.php', 'label' => 'Settings'],
      ['page' => 'index.php?logout', 'label' => 'Log out']
    ];

    echo '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="content-type" value="text/html; charset=utf-8" />
    <title>Birthday reminder</title>
    <link rel="stylesheet" type="text/css" href="./html/style.css" />
  </head>
  <body>';

    if ($showLinks) {
      echo '<p class="navigation">';
      $isFirst = true;
      foreach ($links as $link) {
        if ($isFirst) {
          $isFirst = false;
        } else {
          echo ' &middot; ';
        }

        if ($link['label'] === $currentPage) {
          echo $link['label'];
        } else {
          echo "<a href='{$link['page']}'>{$link['label']}</a>";
        }
      }
      echo '</p>';
    }
  }
}