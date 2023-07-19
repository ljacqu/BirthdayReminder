<?php

class Configuration {

  const DB_HOST = 'localhost';
  const DB_USER = 'root';
  const DB_PASS = '';
  const DB_NAME = 'birthday_reminder';

  const MAIL_FROM = 'birthdays@example.org';
  const MAIL_MODE = 'P'; // S for 'send mails', P for 'print outputs'

  /**
   * If set to true, when sending out the daily mail, the date that will be used is the next day and
   * the email will refer to the date as "tomorrow". This makes sense to set to true if you run 
   * send_emails.php daily at 11 PM or so. If you run the job at 3 AM, this should be false to get 
   * the same day's birthdays per mail.
   */
  const MAIL_FOR_TOMORROW = false;
  const TIME_ZONE = '';
  const LOCAL_DEV = true;
  const SESSION_TIMEOUT_SECONDS = 2 * 24 * 3600;


  private function __construct() {
  }

  static function getTimeZone() {
    if (self::TIME_ZONE) {
      return new DateTimeZone(self::TIME_ZONE);
    }
    return null;
  }
}