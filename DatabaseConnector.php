<?php

class DatabaseConnector {

  private $conn;

  private $host;
  private $name;


  function __construct() {
    $host = Configuration::DB_HOST;
    $this->name = Configuration::DB_NAME;
    $this->conn = new PDO("mysql:host={$host};
    dbname={$this->name}", Configuration::DB_USER, Configuration::DB_PASS,
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

  function findBirthdays(DateTime $from, DateTime $to) {
    $from2020 = '2020-' . $from->format('m-d');
    $to2020   = '2020-' .   $to->format('m-d');

    $query = 'select account_id, email as account_email, name, date
              from br_birthday
              inner join br_account
                on br_account.id = br_birthday.account_id
              where date_2020 between :from and :to
              order by account_id, date';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':from', $from2020);
    $stmt->bindParam(':to', $to2020);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function createTables() {
    // Create br_account
    $this->conn->exec('create table br_account (
      id int auto_increment,
      email varchar(255) not null,
      created date not null,
      password varchar(255) not null,
      failed_logins int not null,
      last_login timestamp,
      primary key (id));');

    // Create br_birthday
    $this->conn->exec('create table br_birthday (
      id int auto_increment,
      account_id int not null,
      date date not null,
      date_2020 date not null,
      name varchar(255) not null,
      primary key (id),
      foreign key (account_id) references br_account(id));');

    // Create br_event
    $this->conn->exec('create table br_event (
      id int auto_increment,
      account_id int,
      date timestamp not null,
      info varchar(255),
      primary key (id),
      foreign key (account_id) references br_account(id));');
  }
}