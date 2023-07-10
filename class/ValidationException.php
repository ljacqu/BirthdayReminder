<?php

class ValidationException extends Exception {
  
  function __construct($msg) {
    parent::__construct($msg);
  }
}