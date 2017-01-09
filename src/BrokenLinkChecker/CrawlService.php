<?php
namespace Dictionary\BrokenLinkChecker;

use GuzzleHttp\Client,
    GuzzleHttp\Promise,
    Psr\Http\Message\UriInterface,
    Symfony\Component\DomCrawler\Crawler,
    GuzzleHttp\Psr7\Request,
    GuzzleHttp\Pool,
    GuzzleHttp\Exception\RequestException,
    Psr\Http\Message\ResponseInterface;

/**
 * Class CrawlService
 * @package Dictionary\BrokenLinkChecker
 */
class CrawlService
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var CrawlObserver
     */
    protected $observer;

    protected $numberOfFoundLinks = 0;

    protected $numberOfInspectedLinks = 0;

    protected $inspectedLinks = [];

    protected $pagesToBeCrawled = [];

    protected $baseDomain;

    protected $currentlyCrawledPage;

    protected $currentlyInspectedLinks = [];

    /**
     * @param $url
     * @param bool|false $mobile
     */
    public function __construct($url, $mobile = false)
    {
        $this->pagesToBeCrawled[] = $this->parseUri($url);
        $header = $mobile ?
            'Mozilla/5.0 (iPhone;U;CPUiPhoneOS4_0likeMacOSX;en-us)AppleWebKit/532.9(KHTML,likeGecko)Version/4.0.5Mobile/8A293Safari/6531.22.7' :
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';

        $this->client = new Client([
            'base_uri' => $this->pagesToBeCrawled[0],
            'allow_redirects' => false,
            'http_errors' => false,
            'headers' => ['User-Agent' => $header],
            'timeout' => 20
        ]);


    }

    /**
     * @param CrawlObserver $observer
     */
    public function setObserver(CrawlObserver $observer)
    {
        $this->observer = $observer;
    }

    /**
     * @param $url
     */
    protected function crawl($url)
    {
        $request = new Request('GET', $url);
        $response = $this->client->send($request);

        $this->currentlyCrawledPage = $this->retrieveUri($request->getUri());
        $this->observer->showCrawledPage($this->currentlyCrawledPage, $response->getStatusCode());

        $body = (string) $response->getBody();
        $links = $this->getAllLinks($body);
        $this->numberOfFoundLinks += count($links);

        //optimise links to be crawled
        $links = $this->removeUnnecessaryLinks($links);
        $this->numberOfInspectedLinks += count($links);

        $this->crawlLinksOnPage($links);
        $this->observer->showNumber($this->numberOfFoundLinks, $this->numberOfInspectedLinks);
    }

    public function start() {
        while (count($this->pagesToBeCrawled)) {
            $page = array_shift($this->pagesToBeCrawled);
            if ($this->hasToBeCrawled($page)) {
                $this->crawl($page);
            }
        }

    }

    /**
     * @param $body
     * @return array
     */
    protected function getAllLinks($body)
    {
        $dom = new Crawler();
        $dom->add($body);
        $links = $dom->filterXpath('//a')
            ->extract(['href']);

        return $links;
    }

    /**
     * @param $links
     */
    protected function crawlLinksOnPage($links)
    {
        $this->inspectedLinks = array_merge($this->inspectedLinks, $links);
        $this->currentlyInspectedLinks = array_values($links);


        $requests = function ($links) {
            foreach ($links as $link) {
                yield new Request('GET', $link);
            }
        };

        $pool = new Pool($this->client, $requests($links), [
            'concurrency'   => 20,
            'fulfilled'     => [$this, 'requestSuccess'],
            'rejected'      => [$this, 'requestFailure'],
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }


    /**
     * @param $url
     * @return string
     * @throws \Exception
     */
    protected function parseUri($url)
    {
        $uri = parse_url($url);
        if (!isset($uri['scheme'])) {
            throw new \Exception("Invalid url. The url should start with host name.");
        }
        if (!isset($uri['host'])) {
            throw new \Exception("Invalid url. The url should be valid domain.");
        }
        $url = $uri['scheme'] . '://' . $uri['host'];

        $this->baseDomain = $url;

        isset($uri['path']) ? $url .= $uri['path'] : 0;
        isset($uri['query']) ? $url .= '?' . $uri['query'] : 0;

        return $url;
    }

    /**
     * @param $links
     * @return array
     */
    protected function removeUnnecessaryLinks($links)
    {
        $result = [];
        foreach ($links as $link) {
            //if links has not started with #
            if (strpos($link, '#') !== 0) {
                //remove everything after #
                $part = strstr($link, '#', true);

                $link = empty($part) ? $link : $part;

                if (!in_array($link, $result)) {
                    $result[] = $link;
                }
            }
        }
        $result = array_diff($result, $this->inspectedLinks);
        return $result;
    }

    /**
     * @param $link
     * @return bool
     */
    protected function hasToBeCrawled($link)
    {
        $link = parse_url($link);
        $crawl = false;

        if (!isset($link['host'])) {
            $crawl = true;
        }
        if (isset($link['host']) && $link['scheme'] . '://' . $link['host'] === $this->baseDomain) {
            $crawl = true;
        }
        if (isset($link['path'])) {
            $removeAssets = '/[.]png|[.]|[.]jpg$/';
            preg_match($removeAssets, $link['path'], $matches);
            if (count($matches) > 0) {
                $crawl = false;
            }
        }


        return $crawl;
    }

    /**
     * @param UriInterface $request
     * @return string
     */
    protected function retrieveUri(UriInterface $request)
    {
        $uri = $request->getScheme();

        empty($uri) ?: $uri .= '://';

        $request->getHost()
            ? $uri .= $request->getHost()
            : $uri .= $this->baseDomain;

        $uri .= $request->getPath();

        $request->getQuery() ? $uri .= '?' . $request->getHost() : 0;

        return $uri;
    }

    /**
     * @param ResponseInterface $response
     * @param $index
     */
    public function requestSuccess(ResponseInterface $response, $index)
    {
        if ($response->getStatusCode() === 200) {
            $this->pagesToBeCrawled[] = $this->currentlyInspectedLinks[$index];
        }
        $this->observer->showResponse(
            $this->currentlyCrawledPage,
            $this->currentlyInspectedLinks[$index],
            $response->getStatusCode()
        );
    }

    /**
     * @param RequestException $reason
     * @param $index
     */
    public function requestFailure(RequestException $reason, $index)
    {
        if (($key = array_search($this->currentlyInspectedLinks[$index], $this->inspectedLinks)) !== false) {
            unset($this->inspectedLinks[$key]);
        }
        $this->observer->errorMessage($reason->getMessage() . ' ' . $this->currentlyInspectedLinks[$index]);

    }
}