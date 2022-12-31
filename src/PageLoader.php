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
    public string $normUrl;

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

        $this->normUrl = $this->normalizeUrl($this->url);

        $this->outputNameWithPath = $this->outDir . $this->normUrl;

        $regIm = $this->getImages($this->htmlAsStr);
        $regLin = $this->getLinks($this->htmlAsStr);
        $regScr = $this->getScripts($this->htmlAsStr);

        $reg = array_merge($regIm, $regLin, $regScr);
        $this->createLocalResources($reg);
        // FileUtils::create($date, $fullPath, $this->outputDir);

    }

    // создает файлы, а также папки для разных расширений
    public function createLocalResources(array $files, bool $createExtension = true)
    {
        if (!empty($files)) {
            $filesDir = $this->outputNameWithPath . '_files';

            if (!file_exists($filesDir)) {
                if (!mkdir($filesDir)) {
                    $this->logger->error(
                        "Failed to create dir \"$this->outputNameWithPath" . "_files\""
                    );
                }
            }

            // разбить на методы
            // сделать так, чтобы при нахождении сразу менялся путь, проходил по htmlAsStr только 3 раза
            foreach ($files as $file) {
                $modifiedPath = $filesDir . '/' . $this->normUrl . $this->normalizeUrl($file);
                $path_parts = pathinfo($file);
                if (!empty($path_parts['extension'])) {
                    if ($createExtension) {
                        @mkdir($filesDir . '/' . $path_parts['extension']);
                        $modifiedPath = $filesDir . '/' . $path_parts['extension'] . '/' . $this->normUrl . $this->normalizeUrl(
                                $file
                            );
                    }
                    $dir = $modifiedPath . '.' . $path_parts['extension'];
                    $this->client->request('GET', $file, ['sink' => $dir]);

                    // записывает весь путь
                    $this->htmlAsStr = str_replace($file, $dir, $this->htmlAsStr);

                } else {

                    // создает пустой файл для ссылок
                    file_put_contents($modifiedPath, '');
                    $this->htmlAsStr = str_replace($file, $modifiedPath, $this->htmlAsStr);
                    //var_dump($modifiedPath);
                }

            }
           $this->writeHtml($this->htmlAsStr);
        }
        //print_r($res2);
    }

    public function writeHtml(string $htmlAsStr): void
    {
        $putRes = @file_put_contents($this->outputNameWithPath . '.html', $htmlAsStr);
        if ($putRes === false) {
            $this->logger->error("Failed to write \"$this->outputNameWithPath.html\"");
            throw new \Exception(
                "Failed to write \"$this->outputNameWithPath.html\"\n",
                1
            );
        }
    }


    public function getDownloadedHtmlPath()
    {
        return $this->outputNameWithPath . '.html';
    }

    public function getHtmlData()
    {
        return $this->client->get($this->url)->getBody()->getContents();
    }

    // get parsing
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

    //
    public function normalizeUrl(string $url)
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $path = (string)parse_url($url, PHP_URL_PATH);

        return strtr("{$host}{$path}", ['.' => '-', '/' => '-', '\\' => '-']);
    }


}