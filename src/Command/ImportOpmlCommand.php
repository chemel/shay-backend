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

#[AsCommand(
    name: 'app:import-opml',
    description: 'Import an OPML file',
)]
class ImportOpmlCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'The OPML file to import')
        ;
    }

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
