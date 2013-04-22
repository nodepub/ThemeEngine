<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('THEMES_DIR', __DIR__.'/../public/themes');

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('NodePub\ThemeEngine', __DIR__.'/../lib');