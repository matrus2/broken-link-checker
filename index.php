<?php

if (!$loader = include __DIR__.'/vendor/autoload.php') {
    die('You must set up the project dependencies.');
}


$application = new Symfony\Component\Console\Application('Broken link checker', '1.0.0');

$crawlCommand = new Dictionary\BrokenLinkChecker\CrawlCommand();

$application->add($crawlCommand);

$application->run();