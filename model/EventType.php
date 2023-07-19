<?php

final class EventType {

  const DAILY_MAIL = 'mail.daily';
  const WEEKLY_MAIL = 'mail.weekly';

  const LOGIN_SUCCESS = 'login.success';
  const LOGIN_FAILED = 'login.failed';
  const LOGIN_LOCKED = 'login.locked';

  const PASSWORD_CHANGE = 'account.pwdChange';
  const EMAIL_CHANGE = 'account.emailChange';
  const RESET_FAILED_LOGINS = 'account.resetFailedLogins';

  const TOKEN_RESET_PASSWORD = 'account.resetPwd.success';
  const TOKEN_REQUEST = 'account.resetPwd.request';
  const TOKEN_REQUEST_INVALID = 'account.resetPwd.invalidRequest';

  const EVENTS_PRUNE = 'events.prune';

  private function __construct() {
  }

}