<?php

class Configuration {

  const DB_HOST = 'localhost';
  const DB_USER = 'root';
  const DB_PASS = '';
  const DB_NAME = 'birthday_reminder';

  private static $instance = null;


  private function __construct() {
  }

  public static function getInstance() {
    if (self::$instance == null) {
      self::$instance = new Configuration();
    }
    return self::$instance;
  }
}