<?php

namespace App;

use App\Utils\FileUtils;
use App\Utils\UrlUtils;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class PageLoader
{
    public string $url;
    public string $outDir;
    public string $outputNameWithPath;
    public string $htmlAsStr;

    public GuzzleClient $client;
    protected Logger $logger;

    public function __construct(string $url, string $outputDir)
    {
        $this->url = $url;
        $this->outDir = (str_ends_with($outputDir, '/')) ? $outputDir : $outputDir . '/';

        $this->client = new GuzzleClient();
        $this->logger = new Logger('pl_logger');

        $this->logger->pushHandler(new StreamHandler($this->outDir . 'page-loader.log', Logger::DEBUG));
        $this->logger->info('Start pageloading');
    }

    public function filesProcessing()
    {
        $this->htmlAsStr = $this->getHtmlData();

        $urlNew = new UrlUtils($this->url, $this->outDir);
        $normUrl = $urlNew->normalizeUrl($this->url);
        $this->outputNameWithPath = $urlNew->getLocationFullPath($normUrl, $this->outDir, 'html');


        $regIm = $this->getImages($this->htmlAsStr);
        $regLin = $this->getLinks($this->htmlAsStr);
        $regScr = $this->getScripts($this->htmlAsStr);

        $reg = array_merge($regIm, $regLin, $regScr);
        $this->createLocalResources($reg);
        // FileUtils::create($date, $fullPath, $this->outputDir);

    }

    public function createLocalResources(array $files, bool $createExtension = false)
    {
        // чтобы отделить ссылки
        foreach ($files as $item) {
            $path_parts = pathinfo($item);
            if (!empty($path_parts['extension'])) {
                $res[] = $item;
            }
        }
        if (!empty($files)) {
            print_r($res);
        }
    }

    public function getDownloadedHtmlPath()
    {
        return $this->outputNameWithPath;
    }

    public function getHtmlData()
    {
        return $this->client->get($this->url)->getBody()->getContents();
    }

    public function getImages(string $htmlAsStr): array
    {
        $imgSearch = preg_match_all('/(?<=")[^"]+\.(png|jpg)(?=")/', $htmlAsStr, $images);
        return ($imgSearch > 0) ? $images[0] : [];
    }

    public function getScripts(string $htmlAsStr): array
    {
        $scrSearch = preg_match_all('/(?<=<script src=")[^"]+(?=")/', $htmlAsStr, $scripts);
        return ($scrSearch > 0) ? $scripts[0] : [];
    }

    public function getLinks(string $htmlAsStr): array
    {
        $linkSearch = preg_match_all('/(?<=<link).+((?<=href=")[^"]+)(?=")/', $htmlAsStr, $links);
        return ($linkSearch > 0) ? $links[1] : [];
    }

}