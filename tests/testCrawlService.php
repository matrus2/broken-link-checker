<?php

use Dictionary\BrokenLinkChecker\CrawlService;

class testCrawlService extends PHPUnit_Framework_TestCase
{

    public function testCrawler()
    {
        $crawler = new \Dictionary\BrokenLinkChecker\CrawlService('', false);


    }

    public function testHasToBeCrawled()
    {

        $url = 'http://www.twojeartykuly.info';
        $stub = $this->getMockBuilder('CrawlService')
            ->disableOriginalConstructor()
            ->setMethods('hasToBeCrawled')
            ->getMock();


        $stub->method('doSomething')
            ->willReturn($url);

        $this->assertEquals($url, $stub->doSomething($url));
    }


}