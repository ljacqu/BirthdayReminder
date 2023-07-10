<?php

class Configuration {

  const DB_HOST = 'localhost';
  const DB_USER = 'root';
  const DB_PASS = '';
  const DB_NAME = 'birthday_reminder';

  const MAIL_FROM = 'birthdays@example.org';
  const MAIL_MODE = 'P'; // S for 'send mails', P for 'print outputs'

  const TIME_ZONE = '';
  const LOCAL_DEV = true;


  private function __construct() {
  }

  static function getTimeZone() {
    if (self::TIME_ZONE) {
      return new DateTimeZone(self::TIME_ZONE);
    }
    return null;
  }
}