<?php

class DatabaseConnector {

  private $conn;

  private $host;
  private $name;


  function __construct($conf) {
    $this->host = $conf::DB_HOST;
    $this->name = $conf::DB_NAME;
    $this->conn = new PDO(
      "mysql:host={$this->host};
    dbname={$this->name}", $conf::DB_USER, $conf::DB_PASS,
      array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
  }

  function initTablesIfNeeded() {
    $stmt = $this->conn->query('select exists 
(select table_name from information_schema.TABLES where table_schema = "' . $this->name . '" and table_name = "br_birthday");');
    $tableExists = $stmt->fetch()[0];
    if (!$tableExists) {
      $this->createTables();
    }
  }

  private function createTables() {
    // Create br_account
    $this->conn->exec('create table br_account ( 
      id int not null,
      email varchar(255) not null,
      created date not null,
      password varchar(255) not null,
      failed_logins int not null,
      last_login timestamp,
      primary key (id));');

    // Create br_birthday
    $this->conn->exec('create table br_birthday (
      id int not null,
      account_id int not null,
      date date not null,
      name varchar(255) not null,
      primary key (id),
      foreign key (account_id) references br_account(id));');

    // Create br_event
    $this->conn->exec('create table br_event (
      id int not null,
      account_id int,
      date timestamp not null,
      info varchar(255),
      primary key (id),
      foreign key (account_id) references br_account(id));');
  }
}