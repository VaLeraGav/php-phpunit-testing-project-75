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
    private string $htmlAsStr;
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


    // разбить на части чтобы можно было тестировать
    public function filesProcessing()
    {
        $this->htmlAsStr = $this->getHtmlData();

        $this->normUrl = $this->normalizeUrl($this->url);

        $this->outputNameWithPath = $this->outDir . $this->normUrl;

        //загрузка html
        $this->putHtmlContentFile($this->htmlAsStr, $this->outputNameWithPath);

        $dirtyLinks = $this->getReplacementLinks($this->htmlAsStr);
        $cleanLinks = $this->buildCorrectPathUrls($dirtyLinks);

        // убирает лишнии ссылки для замены
        foreach ($cleanLinks as $key => $val) {
            $filterUrl[$key] = $dirtyLinks[$key];
        }

        $this->htmlAsStr = $this->replacingResourcesHtml($filterUrl, $cleanLinks, $this->htmlAsStr);

        $nameFailes = $this->createLocalResources($cleanLinks);

        $this->htmlAsStr = $this->replacingResourcesHtml($cleanLinks, $nameFailes, $this->htmlAsStr);

        $this->putHtmlContentFile($this->htmlAsStr, $this->outputNameWithPath);

        // загружает не все
        $this->uploadFiles($cleanLinks, $nameFailes);

    }

    public function getReplacementLinks($htmlAsStr): array
    {
        $regIm = $this->getImages($htmlAsStr);
        $regLin = $this->getLinks($htmlAsStr);
        $regScr = $this->getScripts($htmlAsStr);

        return array_merge($regIm, $regLin, $regScr);
    }

    // создает dir, а также папки для разных расширений, возвращает имя файла до $this->outputNameWithPath
    public function createLocalResources(array $files, bool $createExtension = true): array|null
    {
        if (empty($files)) {
            return null;
        }

        $rootDir = $this->outputNameWithPath . '_files';
        $nameDir = $this->normUrl . '_files';

        // создание папки _files
        $this->createDir($rootDir);

        foreach ($files as $file) {
            $pathParts = pathinfo($file);
            $expansion = $pathParts['extension'] ?? null;

            $nameFile = $this->normUrl . $this->normalizeUrl($file);

            if (isset($expansion)) {
                $dir[] = $nameDir . '/' . $expansion . '/' . $nameFile . '.' . $expansion;

                if ($createExtension) {
                    $this->createDir($rootDir . '/' . $expansion);
                }
            } else {
                $dir[] = $nameDir . '/' . $nameFile;
            }
        }
        return $dir;
    }

    public function uploadFiles($urls, $nameFiles): void
    {
        $rootDir = $this->outDir;

        foreach (array_values($urls) as $k => $url) {
            $this->uploadFile($url, $rootDir . $nameFiles[$k]);
        }
    }

    public function uploadFile($file, $dir)
    {
        // https://www.googletagservices.com/tag/js/gpt.js  после ошибки загрузка перестает
        $this->client->request('GET', $file, ['sink' => $dir, 'http_errors' => false]);
//              выводит ошибки 403 (curl)
//            if(@$this->client->get($file)->getStatusCode() === 403) {
//                print_r("403");
//            }

    }

    public function replacingResourcesHtml($fileNameSearch, $path, $htmlAsStr): array|string
    {
        return str_replace(
            $fileNameSearch,
            $path,
            $htmlAsStr
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

    public function getDownloadedHtmlPath(): string
    {
        return $this->outputNameWithPath . '.html';
    }

    public function getHtmlAsStr(): string
    {
        return $this->htmlAsStr;
    }

    public function getHtmlData(): string
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
    public function normalizeUrl(string $url): string
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
