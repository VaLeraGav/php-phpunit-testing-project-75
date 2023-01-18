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

        //загрузка html
        $this->putHtmlContentFile($this->htmlAsStr, $this->outputNameWithPath);

        $dirtyLinks = $this->getReplacementLinks();
        $cleanLinks = $this->buildCorrectPathUrls($dirtyLinks);

        // убирает лишнии ссылки для замены
        foreach ($cleanLinks as $key => $val) {
            $filterUrl[$key] = $dirtyLinks[$key];
        }

        $this->replacingResourcesHtml(array_values($filterUrl), array_values($cleanLinks));

        $this->createLocalResources($cleanLinks);


        //$this->putHtmlContentFile($this->htmlAsStr);
    }

    public function getReplacementLinks()
    {
        $regIm = $this->getImages($this->htmlAsStr);
        $regLin = $this->getLinks($this->htmlAsStr);
        $regScr = $this->getScripts($this->htmlAsStr);

        return array_merge($regIm, $regLin, $regScr);
    }

    // создает файлы, а также папки для разных расширений
    public function createLocalResources(array $files, bool $createExtension = true)
    {
        if (empty($files)) {
            return null;
        }

        $filesDir = $this->outputNameWithPath . '_files';
        $nameDir = $this->normUrl . '_files';

        // создание папки _files
        $this->createDir($filesDir);

        // вынести в отдельный метод
        foreach ($files as $file) {
            $pathParts = pathinfo($file);
            $exten = $pathParts['extension'] ?? null;

            $nameFile = $this->normUrl . $this->normalizeUrl($file);
            $fullDir = $filesDir . '/';

            if (isset($exten)) {
                $this->replacingResourcesHtml($file, $nameDir . '/' . $exten . '/' . $nameFile . '.' . $exten);

                if ($createExtension) {
                    $this->createDir($filesDir . '/' . $exten);
                    $fullDir .= $exten . '/';
                }

                $fullDir .= $nameFile . '.' . $exten;
            } else {
                $this->replacingResourcesHtml($file, $nameDir . '/' . $nameFile);

                $fullDir .= $nameFile;
            }
            $this->putHtmlContentFile($this->htmlAsStr, $this->outputNameWithPath);

            $this->uploadFiles($file, $fullDir);
        }
    }

    public function uploadFiles($file, $dir)
    {
        // https://www.googletagservices.com/tag/js/gpt.js  после ошибки загрузка перестает
        $this->client->request('GET', $file, ['sink' => $dir, 'http_errors' => false]);
//              выводит ошибки 403 (curl)
//            if(@$this->client->get($file)->getStatusCode() === 403) {
//                print_r("403");
//            }

    }

    public function replacingResourcesHtml($fileNameSearch, $path): void
    {
        $this->htmlAsStr = str_replace(
            $fileNameSearch,
            $path,
            $this->htmlAsStr
        );
    }

    public function buildCorrectPathUrls($urls): array
    {
        $splitUrl = explode('/', $this->url);
        $protocol = $splitUrl[0];
        $domain = $splitUrl[2];

        $buildUrl = array_map(function ($itemUrl) use ($splitUrl, $protocol, $domain) {
            // $itemUrl относительный путь
            if (str_starts_with($itemUrl, '/')) {
                if (str_starts_with($itemUrl, '//')) {
                    $itemUrl = $protocol . $itemUrl;
                } else {
                    $itemUrl = $protocol . "//" . $domain . $itemUrl;
                }
            }

            return $itemUrl;
        }, $urls);

        return array_filter($buildUrl, fn($item) => $this->isUrl($item));
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

    public function putHtmlContentFile(string $htmlAsStr, $placeDownload): void
    {
        $putRes = @file_put_contents($placeDownload . '.html', $htmlAsStr);
        if ($putRes === false) {
            $this->logger->error("Failed to write \"$placeDownload.html\"");
            throw new \Exception(
                "Failed to write \"$placeDownload.html\"\n"
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
