#!/usr/bin/env php
<?php
// create with alias "project.phar"
$phar = new Phar('../drtv.phar', 0, 'drtv.phar');

$phar->buildFromDirectory(__DIR__ . '/');
$phar->setStub("<?php Phar::mapPhar(); include 'phar://drtv.phar/application.php'; __HALT_COMPILER(); ?>");


chmod('../drtv.phar', 0755);