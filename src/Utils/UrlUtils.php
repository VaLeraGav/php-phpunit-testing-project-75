<?php

namespace App\Utils;

class UrlUtils
{

    public string $url;
    public string $outputDir;

    public function __construct($url, $outputDir)
    {
        $this->url = $url;
        $this->outputDir = $outputDir;
    }

    // page-loader https://ru.hexlet.io/courses -o /var/tmp
    // /var/tmp/ru-hexlet-io-courses.html.html # путь к загруженному файлу

    public function normalizeUrl(string $url)
    {
        $host = (string)parse_url($url, PHP_URL_HOST);
        $path = (string)parse_url($url, PHP_URL_PATH);

        return strtr("{$host}{$path}", ['.' => '-', '/' => '-']);
    }

    public function getLocationFullPath(string $normUrl, string $outputDir, string $extensions = ".html")
    {
        $path = $outputDir . $normUrl . '.' . $extensions;
        return $path;
    }

}