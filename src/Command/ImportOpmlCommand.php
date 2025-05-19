<?php

namespace App\Command;

use App\Entity\Feed;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to import feeds from an OPML file
 * 
 * This command allows importing RSS/Atom feeds and their categories
 * from an OPML file format.
 */
#[AsCommand(
    name: 'app:import-opml',
    description: 'Import an OPML file',
)]
class ImportOpmlCommand extends Command
{
    /**
     * Command constructor
     * 
     * @param EntityManagerInterface $em The Doctrine entity manager
     */
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    /**
     * Configures the command arguments
     * 
     * Adds a required 'filename' argument for the OPML file to import
     */
    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'The OPML file to import')
        ;
    }

    /**
     * Executes the command
     * 
     * The command performs the following steps:
     * 1. Validates the input file exists
     * 2. Parses the OPML XML file
     * 3. For each category in the OPML:
     *    - Creates or retrieves the category
     *    - Processes all feeds within the category
     *    - Creates new feeds if they don't exist
     * 4. Saves all changes to the database
     * 
     * @param InputInterface $input The command input
     * @param OutputInterface $output The command output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // The file to import
        $filename = $input->getArgument('filename');

        // Check if the file exist
        if (!file_exists($filename)) {
            $io->note('The input file do not exist');
        }
        
        // Parse the xml file
        $xml = new \SimpleXMLElement(file_get_contents($filename));

        // Get Feed & Category repository
        $feedRepository = $this->em->getRepository(Feed::class);
        $feedCategoryRepository = $this->em->getRepository(Category::class);
        // Caching all categories
        $feedCategoryRepository->findAll();

        // Foreach Categories
        foreach($xml->body->outline as $xmlNodeCategory) {

            $attributes = $xmlNodeCategory->attributes();

            $categoryTitle = trim((string) $attributes->title);

            $output->writeLn('');
            $output->writeLn('Category : ' . $categoryTitle);

            // Check if category exist
            $category = $feedCategoryRepository->findOneBy(['name' => $categoryTitle]);

            // If not exist, create it !
            if(!$category) {
                $category = new Category();
                $category->setName($categoryTitle);
                $this->em->persist($category);
                $this->em->flush();
            }

            // Foreach Feeds
            foreach($xmlNodeCategory->outline as $xmlNodeFeed) {
                $attributes = $xmlNodeFeed->attributes();

                // The url of the feed
                $url = (string) $attributes->xmlUrl;

                // Check if the Feed exist in the database
                $exist = $feedRepository->findOneBy(['url' => $url]);

                if(!$exist) {
                    $output->writeLn($url);

                    // Create the Feed
                    $feed = new Feed();
                    $feed->setTitle((string) $attributes->title);
                    $feed->setUrl($url);

                    $feed->setCategory($category);
    
                    // Save the Feed in database
                    $this->em->persist($feed);
                }
            }

            $this->em->flush();
        }

        $io->success('The file has been imported.');

        return Command::SUCCESS;
    }
}
