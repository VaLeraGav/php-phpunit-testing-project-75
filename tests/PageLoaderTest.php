<?php

namespace Tests;

use App\PageLoader;
use App\Utils\UrlUtils;
use PHPUnit\Framework\TestCase;

class PageLoaderTest extends TestCase
{
    private string $outputDir;
    private string $url;
    private string $unreachableAddress;

    public function setUp(): void
    {
        $this->outputDir = __DIR__;
        $this->url = 'https://php.net';
        $this->unreachableAddress = 'http://test.test';
    }

    public function testParsingHtml()
    {
        $html = (string)file_get_contents($this->outputDir . '/fixtures/test.html');

        $links = [
            "https://test.test/assets/menu.css",
            "/assets/application.css",
            "/courses"
        ];
        $images = ["/assets/php.png"];
        $scripts = ["https://js.stripe.com/v3/", "https://test.test/packs/js.js"];

        $pl = new PageLoader($this->unreachableAddress, $this->outputDir);
        $this->assertEquals($links, $pl->getLinks($html));
        $this->assertEquals($images , $pl->getImages($html));
        $this->assertEquals($scripts, $pl->getScripts($html));
    }
}
