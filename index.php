<?php

require 'Configuration.php';
require 'DatabaseConnector.php';

$db = new DatabaseConnector();

$db->initTablesIfNeeded();