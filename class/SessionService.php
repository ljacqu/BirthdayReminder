<?php

final class SessionService {

  private function __construct() {
  }

  static function isSessionValid(string $storedSecret) {
    if ($storedSecret !== $_SESSION['session_secret']) {
      session_destroy();
      return false;
    }

    $sessionCreated = $_SESSION['login_time'];
    if (!$sessionCreated || (time() - $sessionCreated) > Configuration::SESSION_TIMEOUT_SECONDS) {
      session_destroy();
      return false;
    }

    return true;
  }
}
