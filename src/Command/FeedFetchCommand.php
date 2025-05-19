<?php

namespace App\Command;

use App\Entity\Entry;
use App\Entity\Feed;
use App\Repository\EntryRepository;
use App\Repository\FeedRepository;
use App\Service\FeedFetcherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to fetch and process RSS/Atom feeds.
 *
 * This command can be run in two modes:
 * - Standard mode: fetches all feeds once
 * - Daemon mode: continuously fetches feeds every 20 seconds
 */
#[AsCommand(name: 'app:feed:fetch')]
class FeedFetchCommand extends Command
{
    protected FeedRepository $feedRepository;
    protected EntryRepository $entryRepository;

    /**
     * Command constructor.
     *
     * @param EntityManagerInterface $em The Doctrine entity manager
     */
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options and description.
     */
    protected function configure()
    {
        $this
            ->setDescription('Fetch all feeds')
            ->addOption('daemon', 'd', InputOption::VALUE_OPTIONAL, 'Daemon mode', false)
        ;
    }

    /**
     * Executes the command.
     *
     * @param InputInterface  $input  The command input
     * @param OutputInterface $output The command output
     *
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daemonMode = $input->getOption('daemon');

        $this->feedRepository = $this->em->getRepository(Feed::class);
        $this->entryRepository = $this->em->getRepository(Entry::class);

        if (false !== $daemonMode) {
            while (true) {
                $this->fetchAll($output);
                sleep(20);
            }
        } else {
            $this->fetchAll($output);
        }

        return Command::SUCCESS;
    }

    /**
     * Fetches all feeds that are due for fetching.
     *
     * @param OutputInterface $output The command output for logging
     */
    protected function fetchAll(OutputInterface $output): void
    {
        $feeds = $this->feedRepository->getFeedsToFetch();

        foreach ($feeds as $feed) {
            $output->writeln($feed->getUrl());
            $fetcher = new FeedFetcherService($this->em);
            $fetcher->fetch($feed);
            $this->entryRepository->purge($feed);
        }
    }
}
