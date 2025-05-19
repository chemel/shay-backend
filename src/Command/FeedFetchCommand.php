<?php

namespace App\Command;

use App\Entity\Feed;
use App\Entity\Entry;
use App\Repository\EntryRepository;
use App\Repository\FeedRepository;
use App\Service\FeedFetcherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:feed:fetch')]
class FeedFetchCommand extends Command
{
    protected EntityManagerInterface $em;
    protected FeedRepository $feedRepository;
    protected EntryRepository $entryRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Fetch all feeds')
            ->addOption('daemon', 'd', InputOption::VALUE_OPTIONAL, 'Daemon mode', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daemonMode = $input->getOption('daemon');

        $this->feedRepository = $this->em->getRepository(Feed::class);
        $this->entryRepository = $this->em->getRepository(Entry::class);

        if($daemonMode !== false) {
            while(true) {
                $this->fetchAll($output);
                sleep(20);
            }
        }
        else {
            $this->fetchAll($output);
        }

        return Command::SUCCESS;
    }

    protected function fetchAll(OutputInterface $output) {
        $feeds = $this->feedRepository->getFeedsToFetch();

        foreach($feeds as $feed)
        {
            $output->writeln(($feed->getUrl()));
            $fetcher = new FeedFetcherService($this->em);
            $fetcher->fetch($feed);
            $this->entryRepository->purge($feed);
        }
    }
}
