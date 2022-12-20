<?php

namespace App\PageLoader;

class PageLoader
{
    public static function getDownloadedHtmlPath(string $url, string $rootDirectory, $client): string
    {
        //$url = $this->normalize($url);
        print_r($url);
        return $url;
    }

    public function normalize($url)
    {
        return $url;
    }
}