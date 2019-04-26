<?php
require 'vendor/autoload.php';

define('DOWNLOAD_PATH', __DIR__."/download");
define('COOKIE_PATH', __DIR__."/work/cookies");
use Symfony\Component\Console\Application;
use SmdCn\Pixiv\Client\Download;
use SmdCn\Pixiv\Client\Login;

$application = new Application();
$application->add(new Download());
$application->add(new Login());
$application->run();

