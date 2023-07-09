<?php

require 'Configuration.php';
require 'DatabaseConnector.php';

$conf = Configuration::getInstance();
$db = new DatabaseConnector($conf);

$db->initTablesIfNeeded();