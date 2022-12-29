<?php

use App\PageLoader\PageLoader;
use App\Client\Client;
use GuzzleHttp\Client as GuzzleClient;

function downloadPage(string $url, string $outputDir)
{
    $clientClass = new Client(new GuzzleClient());

    try {
        $resource = PageLoader::getDownloadedHtmlPath($url, $outputDir, $clientClass);
    } catch (\Exception $e) {
        throw new \Exception('Caught Exception: ', $e->getMessage());
    }

    // Page was successfully downloaded into  /mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/tmp/ru-hexlet-io-courses.html.html
    return "\n\nPage was successfully downloaded into " . $resource . "\n";
}