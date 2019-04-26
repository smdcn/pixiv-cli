<?php
require 'vendor/autoload.php';

define('DOWNLOAD_PATH', __DIR__."/download");

use Symfony\Component\Console\Application;
use SmdCn\Pixiv\Client\Download;

$application = new Application();
$application->add(new Download());
$application->run();

