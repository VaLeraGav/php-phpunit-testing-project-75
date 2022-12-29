<?php

namespace App\Utils;

class UrlUtils
{
    // page-loader https://ru.hexlet.io/courses -o /var/tmp
    // /var/tmp/ru-hexlet-io-courses.html.html # путь к загруженному файлу

    public static function normalizeUrl(string $url)
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $path = (string)parse_url($url, PHP_URL_PATH);

        $normUrl = strtr("{$host}{$path}", ['.' => '-', '/' => '-']);
        return $normUrl;
    }

    public static function getLocationFullPath(string $normUrl, string $outputDir)
    {

        $path = $outputDir . "/" . $normUrl;
        return $path;
    }





    public static function fullPath(string $url, ?string $postfix = '.html'): string
    {
        $tmp = dirname(__DIR__);
        return $url;
    }
}