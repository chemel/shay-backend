<?php

namespace App\Command;

use App\Service\OpmlService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-opml',
    description: 'Export the feeds to an OPML file',
)]
class ExportOpmlCommand extends Command
{
    public function __construct(
        private readonly OpmlService $opmlService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $opml = $this->opmlService->export();
        $opml->save('feeds.opml');

        $io->success('Feeds exported to feeds.opml');

        return Command::SUCCESS;
    }
}
