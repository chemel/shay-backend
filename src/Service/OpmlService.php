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