<?php

namespace App\PageLoader;

use App\Utils\FileUtils;
use App\Utils\UrlUtils;

class PageLoader
{
    public string $url;

    public static function getDownloadedHtmlPath(string $url, string $outputDir, $client)
    {

        try {
            $date = $client->get($url)->getBody()->getContents();

            $normUrl = UrlUtils::normalizeUrl($url);
            $fullPath = UrlUtils::getLocationFullPath($normUrl, $outputDir);

            FileUtils::create($date, $fullPath, $outputDir);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $fullPath;
    }

}