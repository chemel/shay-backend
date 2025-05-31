<?php

namespace App\Service;

use App\Entity\Feed;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for handling OPML (Outline Processor Markup Language) operations.
 * 
 * This service provides functionality to export RSS feeds and categories
 * in OPML format, which is commonly used for exchanging feed lists between
 * different RSS readers.
 */
class OpmlService
{
    /**
     * Constructor for the OpmlService.
     * 
     * @param EntityManagerInterface $em The Doctrine Entity Manager for database operations
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
    )
    {
    }

    /**
     * Import an OPML file into the database.
     * 
     * This method processes an OPML file and imports its contents into the database:
     * - Reads the OPML XML file
     * - Creates or retrieves categories based on outline elements
     * - Creates new feeds under their respective categories
     * - Skips existing feeds (based on URL)
     * - Handles the database transactions
     * 
     * Expected OPML structure:
     * <opml version="1.0">
     *   <body>
     *     <outline text="Category Name" title="Category Name">
     *       <outline type="rss" text="Feed Title" title="Feed Title"
     *                xmlUrl="http://feed.url" htmlUrl="http://feed.url"/>
     *     </outline>
     *   </body>
     * </opml>
     * 
     * @param string $filename The path to the OPML file to import
     * @return int The number of new feeds imported (excluding existing ones)
     * 
     * @throws \RuntimeException If the file cannot be read or parsed
     * @throws \SimpleXMLException If the XML is malformed
     * 
     * @example
     * $opmlService = new OpmlService($entityManager);
     * $importedCount = $opmlService->import('feeds.opml');
     * echo sprintf('%d feeds have been imported', $importedCount);
     */
    public function import(string $filename): int
    {
        // Parse the OPML file into a SimpleXMLElement object
        $xml = new \SimpleXMLElement(file_get_contents($filename));

        // Get repositories for database operations
        $feedRepository = $this->em->getRepository(Feed::class);
        $feedCategoryRepository = $this->em->getRepository(Category::class);
        // Pre-load all categories to optimize subsequent database queries
        $feedCategoryRepository->findAll();

        // Counter to track the number of new feeds imported
        $counter = 0;

        // Iterate through each category outline in the OPML
        foreach ($xml->body->outline as $xmlNodeCategory) {
            // Extract category attributes from the XML node
            $attributes = $xmlNodeCategory->attributes();
            $categoryTitle = trim((string) $attributes->title);
            
            // Try to find existing category or create a new one
            $category = $feedCategoryRepository->findOneBy(['name' => $categoryTitle]);

            if (!$category) {
                // Create new category if it doesn't exist
                $category = new Category();
                $category->setName($categoryTitle);
                $this->em->persist($category);
                $this->em->flush();
            }

            // Process all feeds within the current category
            foreach ($xmlNodeCategory->outline as $xmlNodeFeed) {
                $attributes = $xmlNodeFeed->attributes();
                $url = (string) $attributes->xmlUrl;
                
                // Check if feed already exists to avoid duplicates
                $exist = $feedRepository->findOneBy(['url' => $url]);

                if (!$exist) {
                    // Create new feed only if it doesn't exist
                    $feed = new Feed();
                    $feed->setTitle((string) $attributes->title);
                    $feed->setUrl($url);
                    $feed->setCategory($category);
                    $this->em->persist($feed);

                    // Increment counter for new feeds
                    $counter++;
                }
            }

            // Flush changes to database after processing each category
            $this->em->flush();
        }

        // Return the total number of new feeds imported
        return $counter;
    }

    /**
     * Exports all RSS feeds organized by categories to OPML format.
     * 
     * Creates an OPML document with the following structure:
     * <opml version="1.0">
     *   <head>
     *     <title>RSS Feeds</title>
     *   </head>
     *   <body>
     *     <outline text="Category Name" title="Category Name">
     *       <outline text="Feed Title" title="Feed Title" type="rss" 
     *                xmlUrl="http://feed.url" htmlUrl="http://feed.url"/>
     *     </outline>
     *   </body>
     * </opml>
     * 
     * @return \DOMDocument The generated OPML document
     * 
     * @example
     * $opmlService = new OpmlService($entityManager);
     * $opmlDocument = $opmlService->export();
     * echo $opmlDocument->saveXML();
     */
    public function export(): \DOMDocument
    {
        $feeds = $this->em->getRepository(Feed::class)->findAll();
        $categories = $this->em->getRepository(Category::class)->findAll();

        $opml = new \DOMDocument();
        $opml->formatOutput = true;
        $opml->loadXML('<?xml version="1.0" encoding="UTF-8"?><opml version="1.0"></opml>');

        // Create the head section with title
        $head = $opml->createElement('head');
        $title = $opml->createElement('title');
        $title->appendChild($opml->createTextNode('RSS Feeds'));
        $head->appendChild($title);
        $opml->documentElement->appendChild($head);

        // Create the body section
        $body = $opml->createElement('body');
        $opml->documentElement->appendChild($body);

        // Add categories and their feeds
        foreach ($categories as $category) {
            $outlineCategory = $opml->createElement('outline');
            $outlineCategory->setAttribute('text', $category->getName());
            $outlineCategory->setAttribute('title', $category->getName());
            $body->appendChild($outlineCategory);

            foreach ($category->getFeeds() as $feed) {
                $outlineFeed = $opml->createElement('outline');
                $outlineFeed->setAttribute('type', 'rss');
                $outlineFeed->setAttribute('text', $feed->getTitle());
                $outlineFeed->setAttribute('title', $feed->getTitle());
                $outlineFeed->setAttribute('xmlUrl', $feed->getUrl());
                $outlineFeed->setAttribute('htmlUrl', $feed->getUrl());
                $outlineCategory->appendChild($outlineFeed);
            }
        }

        return $opml;
    }
}