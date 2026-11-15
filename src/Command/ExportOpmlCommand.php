<?php

namespace App\Command;

use App\Service\OpmlService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Exports all the feeds to an OPML file.
 *
 * Usage: php bin/console app:export-opml [<file>]
 */
#[AsCommand(
    name: 'app:export-opml',
    description: 'Export the feeds to an OPML file',
)]
class ExportOpmlCommand extends Command
{
    /**
     * @param OpmlService $opmlService Service used to generate the OPML content
     */
    public function __construct(
        private readonly OpmlService $opmlService,
    )
    {
        parent::__construct();
    }

    /**
     * Declares the optional "file" argument (defaults to feeds.opml).
     */
    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::OPTIONAL, 'Path of the OPML file to write', 'feeds.opml');
    }

    /**
     * Writes the OPML export to the given file.
     *
     * In interactive mode, asks for confirmation before overwriting an existing file.
     *
     * @param InputInterface  $input  Console input, holding the "file" argument
     * @param OutputInterface $output Console output
     *
     * @return int Command::SUCCESS on export or cancellation, Command::FAILURE if the file cannot be written
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');

        if ($input->isInteractive() && file_exists($file)
            && !$io->confirm(sprintf('The file %s already exists. Overwrite it?', $file), false)) {
            $io->warning('Export cancelled.');

            return Command::SUCCESS;
        }

        $opml = $this->opmlService->export();

        if (false === @file_put_contents($file, $opml)) {
            $io->error(sprintf('Unable to write to %s', $file));

            return Command::FAILURE;
        }

        $io->success(sprintf('Feeds exported to %s', $file));

        return Command::SUCCESS;
    }
}
