#!/usr/bin/env php
<?php
// application.php

if (file_exists(__DIR__ . '/Libraries/autoload.php')) {
	require __DIR__ . '/Libraries/autoload.php';
} else {
	echo 'Missing autoload.php, update by the composer.' . PHP_EOL;
	exit(2);
}

define('ROOT_DIR', __DIR__);
setlocale(LC_ALL, 'da_DK');
date_default_timezone_set('Europe/Copenhagen');

$application = new \Symfony\Component\Console\Application('DR TV Downloader', '0.3-dev');
$application->add(new Chili\Command\SearchCommand());
$application->add(new Chili\Command\GetCommand());
$application->add(new Chili\Command\ListCommand());
$application->add(new Chili\Command\TestCommand());
$application->run();