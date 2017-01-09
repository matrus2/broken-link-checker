<?php
namespace Dictionary\BrokenLinkChecker;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\input\InputArgument,
    Symfony\Component\Console\Input\InputOption;

/**
 * Class CrawlCommand
 * @package Dictionary\BrokenLinkChecker
 */
class CrawlCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('crawl')
            ->setDescription('Crawl the website to seek for 404s')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url to check'
            )
            ->addOption(
                'mobile',
                'm',
                InputArgument::OPTIONAL | InputOption::VALUE_NONE,
                'Set flag if you want to run test as a mobile user agent.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $crawler = new CrawlService(
            $input->getArgument('url'),
            $input->getOption('mobile')
        );
        $crawler->setObserver(new CrawlObserver($output));
        $crawler->start();
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new MyCommand();

        return $defaultCommands;
    }

}

