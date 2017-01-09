<?php

namespace Dictionary\BrokenLinkChecker;

use Symfony\Component\Console\Formatter\OutputFormatter,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Formatter\OutputFormatterInterface,
    Symfony\Component\Console\Formatter\OutputFormatterStyle;


/**
 * Class CrawlObserver
 * @package Dictionary\BrokenLinkChecker
 */
class CrawlObserver
{

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $file = 'file.csv';

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        unlink($this->file);
        $handle = fopen($this->file, 'w');
        fclose($handle);
    }

    public function showMainPage($status, $url = null)
    {
        $this->output->writeln($status . '  ' . $url);
    }

    /**
     * @param $status
     * @param $url
     */
    public function showResponse($source, $url, $status)
    {

        switch ($status) {
            case ($status < 300):
                if ($this->output->isVerbose()) {
                    $tag = '<fg=green>';
                }
                break;
            case ($status >= 300 && $status < 400):
                $tag = '<fg=yellow>';
                break;
            case ($status >= 400 && $status > 500):
                $tag = '<fg=red>';
                break;
            case ($status >= 500):
                $tag = '<fg=magenta>';
                break;
        }

        if (isset($tag)) {
            $output = [$source, $url, $status];
            $this->writeToCsv($output);
            $output = $tag . $source . '   ' . $url . '   ' . $status . '</>';
            $this->output->writeln($output);
        }
    }

    private function writeToCsv($output)
    {
        $fp = fopen($this->file, 'a');
        fputcsv($fp, $output);
        fclose($fp);
    }

    public function showNumber($number, $optimized)
    {
        $this->output->writeln('<fg=cyan>Crawled ' . $number . '/'. $optimized . ' links</>');

    }
    public function errorMessage($message)
    {
        $output = explode(' ', $message);
        $this->output->writeln($message);
        $this->writeToCsv($output);

    }
    public function showCrawledPage($source, $status)
    {
        $this->output->writeln('<fg=blue>' . $status . '   '. $source . '</>');
    }

}