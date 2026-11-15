<?php

namespace App\Service;

use Alc\Opml\Entity\Category as OpmlCategory;
use Alc\Opml\Entity\Feed as OpmlFeed;
use Alc\Opml\Entity\Opml;
use Alc\Opml\Exception\InvalidOpmlException;
use Alc\Opml\Reader;
use Alc\Opml\Writer;
use App\Entity\Category;
use App\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Imports and exports the feeds and their categories in OPML format.
 *
 * Reading and writing the OPML documents is delegated to the alc/opml-parser library.
 */
class OpmlService
{
    /**
     * @param EntityManagerInterface $em Entity manager used to read and persist feeds and categories
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
    )
    {
    }

    /**
     * Imports the feeds of an OPML file into the database.
     *
     * Each top-level outline is a category, created when no category has the same name.
     * Its feeds, sub-categories included, are attached to it. Feeds whose URL already
     * exists are skipped, as well as top-level feeds, since a feed must belong to a category.
     *
     * @param string $filename Path of the OPML file to import
     *
     * @return int Number of feeds created
     *
     * @throws InvalidOpmlException When the file is unreadable or is not a valid OPML document
     */
    public function import(string $filename): int
    {
        $opml = (new Reader())->read($filename);

        $feedRepository = $this->em->getRepository(Feed::class);
        $feedCategoryRepository = $this->em->getRepository(Category::class);
        $feedCategoryRepository->findAll();

        $counter = 0;

        foreach ($opml->getOutlines() as $outline) {
            if (!$outline instanceof OpmlCategory) {
                continue;
            }

            $categoryTitle = (string) $outline->getTitle();

            $category = $feedCategoryRepository->findOneBy(['name' => $categoryTitle]);

            if (!$category) {
                $category = new Category();
                $category->setName($categoryTitle);
                $this->em->persist($category);
                $this->em->flush();
            }

            foreach ($outline->getFeeds() as $opmlFeed) {
                $url = $opmlFeed->getXmlUrl();

                $exist = $feedRepository->findOneBy(['url' => $url]);

                if (!$exist) {
                    $feed = new Feed();
                    $feed->setTitle((string) $opmlFeed->getTitle());
                    $feed->setUrl($url);
                    $feed->setCategory($category);
                    $this->em->persist($feed);

                    $counter++;
                }
            }

            $this->em->flush();
        }

        return $counter;
    }

    /**
     * Exports all the categories and their feeds as an OPML 1.0 document.
     *
     * Each category becomes a top-level outline containing its feeds. A feed without
     * title is exported with its URL as title.
     *
     * @return string The OPML document, as an XML string
     */
    public function export(): string
    {
        $categories = $this->em->getRepository(Category::class)->findAll();

        $opml = (new Opml())->setTitle('RSS Feeds');

        foreach ($categories as $category) {
            $opmlCategory = (new OpmlCategory())
                ->setText($category->getName())
                ->setTitle($category->getName());

            foreach ($category->getFeeds() as $feed) {
                $title = $feed->getTitle() ?? $feed->getUrl();

                $opmlCategory->addOutline(
                    (new OpmlFeed())
                        ->setText($title)
                        ->setTitle($title)
                        ->setType('rss')
                        ->setXmlUrl($feed->getUrl())
                        ->setHtmlUrl($feed->getUrl())
                );
            }

            $opml->addOutline($opmlCategory);
        }

        return (new Writer())->write($opml);
    }
}
