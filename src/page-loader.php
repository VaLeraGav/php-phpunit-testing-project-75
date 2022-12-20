<?php

use App\PageLoader\PageLoader;

function downloadPage(string $url, string $outputDir, string $clientClass = '')
{
    try {
        $resource = PageLoader::getDownloadedHtmlPath($url, $outputDir, $clientClass);
        return $resource;
    } catch (\Exception $e) {
        fwrite(STDERR, $e->getMessage());
        exit($e->getCode());
    }
}