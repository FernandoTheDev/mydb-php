<?php

use Fernando\MyDB\MyDB;

require_once __DIR__ . '/../vendor/autoload.php';

$mydb = new MyDB();
$mydb->setDatabase("fernando");

$mydb->prepare("SELECT * FROM users WHERE name = Mateus");
$mydb->execute();

print_r($mydb->getData());