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
 * Command to import RSS feeds from an OPML file.
 * 
 * This command provides a CLI interface to import RSS/Atom feeds and their categories
 * from an OPML (Outline Processor Markup Language) file. It uses the OpmlService
 * to process the file and store the feeds in the database.
 * 
 * Usage:
 *     php bin/console app:import-opml path/to/file.opml
 * 
 * The command will:
 * - Validate the existence of the input file
 * - Process the OPML file through OpmlService
 * - Create categories and feeds as needed
 * - Display the number of imported feeds
 */
#[AsCommand(
    name: 'app:import-opml',
    description: 'Import an OPML file',
)]
class ImportOpmlCommand extends Command
{
    /**
     * Constructor for the ImportOpmlCommand.
     * 
     * @param OpmlService $opmlService Service responsible for OPML file processing
     */
    public function __construct(
        private readonly OpmlService $opmlService,
    ) {
        parent::__construct();
    }

    /**
     * Configures the command.
     * 
     * Adds a required 'filename' argument that specifies the path to the OPML
     * file that should be imported.
     */
    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'The OPML file to import');
    }

    /**
     * Executes the command.
     * 
     * This method:
     * 1. Validates the existence of the input file
     * 2. Processes the OPML file using OpmlService
     * 3. Displays the results to the user
     * 
     * @param InputInterface $input Command input interface
     * @param OutputInterface $output Command output interface
     * 
     * @return int Command exit code (0 for success, non-zero for failure)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create Symfony Style helper for better CLI output
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        // Validate file existence
        if (!file_exists($filename)) {
            $io->error('The input file does not exist');
            return Command::FAILURE;
        }

        // Process the OPML file and get number of imported feeds
        $counter = $this->opmlService->import($filename);

        // Display success message with import count
        $io->success($counter.' feeds have been imported.');

        return Command::SUCCESS;
    }
}
