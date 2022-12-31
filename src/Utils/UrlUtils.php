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

    public function getLocationFullPath(string $normUrl, string $outputDir)
    {
        return $outputDir . $normUrl;
    }

}