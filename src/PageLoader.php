<?php

namespace App;

use GuzzleHttp\Client as GuzzleClient;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
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
        $this->outDir = (str_ends_with($outputDir, '/')) ? $outputDir : $outputDir . '/';

        $this->logger = new Logger('pl_logger');
        $this->logger->pushHandler(new StreamHandler($this->outDir . 'page-loader.log', Level::Debug));
        $this->logger->info('Start pageloading');

        $this->url = $url;
        if ($this->isUrl($this->url) === false) {
            $this->logger->error('Url incorrect');
            throw new \Exception("Url incorrect\n");
        }

        $this->client = new GuzzleClient();
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
        if (empty($files)) {
            return null;
        }

        $splitUrl = explode('/', $this->url);

        // вынести в отдельный метод
        $buildCorrectPathUrl = array_map(function ($itemUrl) use ($splitUrl) {
            // $itemUrl относительный путь
            $protocol = $splitUrl[0];
            $domain = $splitUrl[2];
            if (str_starts_with($itemUrl, '/')) {
                if (str_starts_with($itemUrl, '//')) {
                    $itemUrl = $protocol . $itemUrl;
                } else {
                    $itemUrl = $protocol . "//" . $domain . $itemUrl;
                }
            }

            return $itemUrl;
        }, $files);

        $checkedUrl = array_filter($buildCorrectPathUrl, function ($item) {
            return $this->isUrl($item);
        });

        $filesDir = $this->outputNameWithPath . '_files';
        $nameDir = $this->normUrl . '_files';

        // создание папки _files
        $this->createDir($filesDir);

        // вынести в отдельный метод
        foreach ($checkedUrl as $file) {
            $pathParts = pathinfo($file);
            $exten = $pathParts['extension'] ?? null;

            $nameFile = $this->normUrl . $this->normalizeUrl($file);
            $fullDir = $filesDir . '/';

            if (isset($exten)) {
                if ($createExtension) {
                    $this->createDir($filesDir . '/' . $exten);
                    $fullDir .= $exten . '/';
                }
                $fullDir .= $nameFile . '.' . $exten;

                // заменяет юрл
                $this->htmlAsStr = str_replace(
                    $file,
                    $nameDir . '/' . $exten . '/' . $nameFile . '.' . $exten,
                    $this->htmlAsStr
                );
            } else {
                $fullDir .= $nameFile;

                // заменяет юрл
                $this->htmlAsStr = str_replace(
                    $file,
                    $nameDir . '/' . $nameFile,
                    $this->htmlAsStr
                );
            }
            $this->client->request('GET', $file, ['sink' => $fullDir, 'http_errors' => false]);
//              выводит ошибки 403 (curl)
//            if(@$this->client->get($file)->getStatusCode() === 403) {
//                print_r("403");
//            }

            $this->putHtmlContentFile($this->htmlAsStr);
        }
        //print_r($res2);
    }

    public function createDir($path): void
    {
        if (!file_exists($path)) {
            if (!@mkdir($path)) {
                $this->logger->error(
                    "Failed to create dir \"" . $path . "\""
                );
            }
        }
    }

    public function putHtmlContentFile(string $htmlAsStr): void
    {
        $putRes = @file_put_contents($this->outputNameWithPath . '.html', $htmlAsStr);
        if ($putRes === false) {
            $this->logger->error("Failed to write \"$this->outputNameWithPath.html\"");
            throw new \Exception(
                "Failed to write \"$this->outputNameWithPath.html\"\n"
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
        //$scrSearch = preg_match_all('/(?<=<script).+((?<=src=")[^"]+)(?=")/', $htmlAsStr, $scripts);
        $scrSearch = preg_match_all('/<script.*?src="(.*?)"/', $htmlAsStr, $scripts);
        return ($scrSearch > 0) ? $scripts[1] : [];
    }

    public function getLinks(string $htmlAsStr): array
    {
        //$linkSearch = preg_match_all('/(?<=<link).+((?<=href=")[^"]+)(?=")/', $htmlAsStr, $links);
        $linkSearch = preg_match_all('/<link.*?href="(.*?)"/', $htmlAsStr, $links);

        return ($linkSearch > 0) ? $links[1] : [];
    }

    //
    public function normalizeUrl(string $url)
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $path = (string)parse_url($url, PHP_URL_PATH);

        return strtr("{$host}{$path}", ['.' => '-', '/' => '-', '\\' => '-']);
    }

    // Проверка правильности URL
    public function isUrl($url): bool
    {
        return filter_var($url, \FILTER_VALIDATE_URL) !== false;
    }

}
