<?php

namespace Tests;

use App\PageLoader;
use App\Utils\UrlUtils;
use http\Encoding\Stream;
use PHPUnit\Framework\TestCase;

class PageLoaderTest extends TestCase
{
    private string $outputDir;
    private string $url;
    private string $unreachableAddress;
    private PageLoader $pl;
    private string $html;

    public function setUp(): void
    {
        $this->outputDir = __DIR__;
        $this->url = 'https://php.net';
        $this->unreachableAddress = 'http://test.test';

        $this->pl = new PageLoader($this->unreachableAddress, $this->outputDir);
        $this->html = (string)file_get_contents($this->outputDir . '/fixtures/mypage.html');
    }

    public function testLinksSearch()
    {
        $html = (string)file_get_contents($this->outputDir . '/fixtures/mypage.html');

        $links = [
            'https://mypage/assets/menu.css',
            '/assets/application.css',
            '/courses',
            '/courses-1'
        ];
        $this->assertEquals($links, $this->pl->getLinks($html));
    }

    public function testImagesSearch()
    {
        $images = [
            'https://mypage/assets/img-2.png',
            '/assets/img-1.png',
            '/assets/img-2.png',
        ];
        $this->assertEquals($images, $this->pl->getImages($this->html));
    }

    public function testScriptsSearch()
    {
        $scripts = [
            'https://js.stripe.com/v3/',
            '//www.googletagservices.com/tag/js/gpt.js',
            '/assets/index.js',
            '/assets/index-1.js',
        ];
        $this->assertEquals($scripts, $this->pl->getScripts($this->html));
    }
}
