<?php

namespace SmdCn\Pixiv\Client;
use GuzzleHttp\Client;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;


class Download extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'download';

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client([
		    'base_uri' => 'https://www.pixiv.net/',
		    'cookies' => true
		]);
		$this->headers = [
			'Referer' => 'https://www.pixiv.net/',
			'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36'
		];
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

    protected function pixivGet($url) {

		$response = $this->client->request('GET', $url, $this->headers);
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
		$this->preparePath(DOWNLOAD_PATH) && $this->preparePath(DOWNLOAD_PATH."/{$illust_id}");
		file_put_contents(DOWNLOAD_PATH."/{$illust_id}/meta.json", json_encode($metaBody, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
    	file_put_contents(DOWNLOAD_PATH."/{$illust_id}/pages.json", json_encode($pagesBody, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

    	$total_imgs = count($pagesBody);

    	$output->writeln("<info>illust <{$illust_id}> [{$metaBody['illustTitle']}] has {$total_imgs} images.</info>");

    	foreach($pagesBody as $vo) {
    		
    		$url = $vo['urls']['original'];

    		if (defined('SPD_URL')) {
				$url = str_replace("https://i.pximg.net", SPD_URL, $url);
    		}
    		$output->writeln($url);
    	}
    }
}