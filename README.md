

Crawl page to seek broken links. It still require some improvements, but may be use as well in this shape.

```
Usage:
  Install project dependencies (php composer.phar install);
  php index.php crawl [options] [--] <url>

Arguments:
  url                   The url to check

Options:

  -m, --mobile=MOBILE   Set flag if you want to run test as a mobile user agent.
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
 Crawl the website to seek for 404s
```