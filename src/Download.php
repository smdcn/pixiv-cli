<?php

namespace SmdCn\Pixiv\Client;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\ProgressBar;



class Download extends CommonCmd
{
    // the name of the command (the part after "bin/console")
  protected static $defaultName = 'download';

  public function __construct()
  {
    parent::__construct();
    $this->client = new Client([
      'base_uri' => 'https://www.pixiv.net/',
      'cookies' => $this->cookies,
      'headers' => $this->headers,
    ]);
  }

  protected function configure()
  {
    $this->setDescription('Download Images');
    $this->addArgument('illust_id', InputArgument::REQUIRED, 'The id of the illust.');
  }

  protected function preparePath($dir) {
    if (!is_dir($dir)) {
      return mkdir($dir);
    }
    return true;
  }

  protected function pixivGet($url) 
  {
    $response = $this->client->request('GET', $url);
    $body = $response->getBody();
    $data = json_decode($body, TRUE);
    if (!$data || !isset($data['error'])) {
      throw new Exception("response body not expect");
    }
    if ($data['error']) {
      if (!empty($data['message'])) {
        throw new Exception("response error | {$data['message']}");
      }
      throw new Exception("response error without message");
    }
    if (!isset($data['body'])) {
      throw new Exception("response object without body");
    }
    return $data['body'];
  }

  protected function download($url, $dir, $progress) {
    $index = strrpos($url, "/");
    if ($index) {
      $filename = substr($url, $index+1);
      $savepath = "{$dir}/{$filename}";
      if (!file_exists($savepath)) {
        $response = $this->client->get($url, [
          'save_to' => $savepath,
          'progress' => $progress
        ]);
        return ['response_code'=>$response->getStatusCode(), 'name' => $filename];
      } else {
        return ['message' => "{$savepath} exist", 'name' => $filename];
      }
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // echo DOWNLOAD_PATH."\n";
    $illust_id = $input->getArgument('illust_id');

    $output->writeln("<info>get illust <{$illust_id}> meta</info>");
    try {
      $metaBody = $this->pixivGet("ajax/illust/{$illust_id}");
    } catch (\Exception $e) {
      $output->writeln("<error>get meta failed </error>\n <comment>{$e->getMessage()}</comment>");
      return ;
    }
    
    $output->writeln("<info>get illust <{$illust_id}> pages</info>");
    try {
      $pagesBody = $this->pixivGet("ajax/illust/{$illust_id}/pages");
    } catch (\Exception $e) {
      $output->writeln("<error>get pages failed </error>\n <comment>{$e->getMessage()}</comment>");
      return ;
    }

    $illust_dir = DOWNLOAD_PATH."/{$illust_id}";

    $this->preparePath(DOWNLOAD_PATH) && $this->preparePath($illust_dir);
    file_put_contents("{$illust_dir}/meta.json", json_encode($metaBody, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
    file_put_contents("{$illust_dir}/pages.json", json_encode($pagesBody, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

    $total_imgs = count($pagesBody);

    $output->writeln("<info>illust <{$illust_id}> [{$metaBody['illustTitle']}] has {$total_imgs} images.</info>");

    foreach($pagesBody as $vo) {
      $url = $vo['urls']['original'];
      if (defined('SPD_URL')) {
        $url = str_replace("https://i.pximg.net", SPD_URL, $url);
      }
      $output->writeln("<info> download {$url}</info>");
      $progressBar = new ProgressBar($output);
      $lastProgress = 0;
      $progressBar->setMaxSteps(100);
      $progressBar->start();
      $ret = $this->download($url, $illust_dir, function(
            $downloadTotal,
            $downloadedBytes,
            $uploadTotal,
            $uploadedBytes
        ) use (&$progressBar, &$lastProgress) {
          $progressBar->setMaxSteps($downloadTotal);
          $progressBar->advance($downloadedBytes - $lastProgress);
          $lastProgress = $downloadedBytes;
            //do something
        });
      $progressBar->finish();
      if (isset($ret['response_code'])) {
        $output->writeln("\n({$ret['response_code']}) | ({$ret['name']})");
      } else if (isset($ret['message'])) {
        $output->writeln("\n({$ret['message']}) | ({$ret['name']})");
      }
      
    }
  }
}