<?php

use App\PageLoader;

function downloadPage(string $url, string $outputDir)
{
        try {
        $loader = new PageLoader($url, $outputDir);

        // start processing
        $loader->filesProcessing();

        $resource = $loader->getDownloadedHtmlPath();
    } catch (\Exception $e) {
        throw new \Exception('Caught Exception: ', $e->getMessage());
    }

    // Page was successfully downloaded into  /mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/tmp/ru-hexlet-io-courses.html.html
    return "\n\nPage was successfully downloaded into " . $resource . "\n";
}