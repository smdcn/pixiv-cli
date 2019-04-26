<?php

namespace SmdCn\Pixiv\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Symfony\Component\Console\Command\Command;

class CommonCmd extends Command
{
  protected function initPath() {
    $dir = dirname(COOKIE_PATH);
    if (!is_dir($dir)) {
      mkdir($dir);
    }
  }

  public function __construct()
  {
    parent::__construct();
    $this->initPath();
    $this->cookies = new FileCookieJar(COOKIE_PATH, true);
    $this->headers = [
      'Referer' => 'https://www.pixiv.net/',
      'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36'
    ];
  }

}