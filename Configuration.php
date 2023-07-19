<?php

class Configuration {

  // ------
  // Database connection details
  // ------

  /** Database host. */
  const DB_HOST = 'localhost';
  /** Database name. */
  const DB_NAME = 'birthday_reminder';
  /** Database user. */
  const DB_USER = 'root';
  /** Database password. */
  const DB_PASS = '';

  // ------
  // Email details
  // ------

  /**
   * When emails are sent to users, from which email should it be sent from?
   */
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
  /**
   * Secret that must be provided to pages in the cron/ folder so that someone cannot manually send out emails.
   */
  const CRON_SECRET = 'oKErMTsh2';
  const SESSION_TIMEOUT_SECONDS = 2 * 24 * 3600;
  const EVENTS_KEEP_DAYS = 45;


  private function __construct() {
  }

  static function getTimeZone() {
    if (self::TIME_ZONE) {
      return new DateTimeZone(self::TIME_ZONE);
    }
    return null;
  }
}