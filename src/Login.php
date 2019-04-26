<?php

namespace SmdCn\Pixiv\Client;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

use Symfony\Component\DomCrawler\Crawler;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;


class Login extends CommonCmd
{

  protected static $defaultName = 'login';

  public function __construct()
  {
    parent::__construct();

    $this->client = new Client([
      'cookies' => $this->cookies,
      'headers' => $this->headers,
    ]);
  }

  protected function configure()
  {
    $this->setDescription('Login Pixiv Account');
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $output->writeln('<info>load login pages</info>');
    $response = $this->client->request("GET", "https://accounts.pixiv.net/login?return_to=https%3A%2F%2Fwww.pixiv.net%2F&source=touch&view_type=page", [
      'allow_redirects' => false
    ]);

    $body = (string) $response->getBody();

    $code = $response->getStatusCode();
    if ($code >= 300 && $code <= 400) {
      $output->writeln('<info>! it seem already logined </info>');
      return ;
    }

    $crawler = new Crawler($body);
    try {
      $m = $crawler->filter('input[name=post_key]');
      if ($m) {
        $post_key = $m->attr('value');
      }
    } catch (\Exception $e) {
      $output->writeln("<error>{$e->getMessage()}</error><comment>{$e->getTraceAsString()}</comment>");
      return ;
    }
    
    $output->writeln('<info>! login account</info>');

    $helper = $this->getHelper('question');

    $question = new Question('account: ');
    $account = $password = $helper->ask($input, $output, $question);

    $question = new Question('password: ');
    $question->setHidden(true);
    $question->setHiddenFallback(false);

    $password = $helper->ask($input, $output, $question);

    $response = $this->client->request("POST", "https://accounts.pixiv.net/api/login?lang=en", [
      'form_params' => [
        "captcha" => "", 
        "g_recaptcha_response" => "",
        "password" => $password,
        "pixiv_id" => $account,
        "post_key" => $post_key,
        "source" => "touch",
        "ref" => "", 
        "return_to" => "https://www.pixiv.net/"
      ]
    ]);

    $data = json_decode($response->getBody(), TRUE);

    if (!$data || !isset($data['error']) || !isset($data['body'])) {
      $output->writeln("<error>failed</error>\n <comment>response body not expect</comment>");
      return ;
    }
    if ($data['error']) {
      $output->writeln("<error>response error</error>");
      return ;
    }
    if (isset($data['body']['success'])) {
      $output->writeln('<info>login success</info>');  
    } else {
      if (isset($data['body']['validation_errors'])) {
        foreach($data['body']['validation_errors'] as $k=>$v) {
          $output->writeln("<error>{$k}</error>: {$v}");  
        }
      }
    }
  }
}