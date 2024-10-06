<?php

use Fernando\MyDB\MyDB;

require_once __DIR__ . '/../vendor/autoload.php';

$mydb = new MyDB();

$mydb->prepare("CREATE DATABASE test");
$mydb->execute();

$mydb->setDatabase("test");

$mydb->prepare("CREATE TABLE users (name, age)");
$mydb->prepare("INSERT INTO users VALUES (Fernando, 16)");
$mydb->prepare("INSERT INTO users VALUES (Jonas, 99)");
$mydb->prepare("SELECT * FROM users");
$mydb->execute();

print_r($mydb->getData());

$mydb->prepare("UPDATE users SET age = 18, name = Joses WHERE name = Fernando AND age = 17");
$mydb->prepare("SELECT * FROM users");
$mydb->execute();

print_r($mydb->getData());