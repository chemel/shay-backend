<?php

namespace App\Service;

use App\Entity\Entry;
use App\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;
use SimplePie\SimplePie;

/**
 * Service for fetching and processing RSS/Atom feeds.
 */
class FeedFetcherService
{
    protected SimplePie $simplePie;

    /**
     * Service constructor.
     *
     * @param EntityManagerInterface $em The Doctrine entity manager
     */
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * Fetches and processes an RSS/Atom feed.
     *
     * @param Feed $feed The feed to fetch
     *
     * @return bool True if the fetch was successful, false otherwise
     */
    public function fetch(Feed &$feed): bool
    {
        $entryRepository = $this->em->getRepository(Entry::class);

        $this->init();
        $response = $this->request($feed->getUrl());
        $success = $this->handleError($feed, $response);

        if (false === $success) {
            $this->nextFetch($feed, 0);

            $this->em->persist($feed);
            $this->em->flush();

            return false;
        }

        $items = $this->simplePie->get_items();
        $nbEntriesAdded = 0;

        foreach ($items as $item) {
            $entry = $this->mapData($item, $feed);

            $exist = $entryRepository->exists($feed, $entry->getHash());
            if ($exist) {
                continue;
            }

            // On check si la date du post est pas plus ancienne que la date de purge du feed
            $purgeDate = $feed->getPurgeDate();
            if ($entry->getDate() < $purgeDate) {
                continue;
            }

            $this->em->persist($entry);
            $this->em->flush();

            ++$nbEntriesAdded;
        }

        // Le flux a été fetch avec succès
        $feed->setFetchedAt(new \DateTime());

        $this->nextFetch($feed, $nbEntriesAdded);

        $this->em->persist($feed);
        $this->em->flush();

        return true;
    }

    /**
     * Initializes the SimplePie instance for feed fetching.
     *
     * @return SimplePie The configured SimplePie instance
     */
    public function init(): SimplePie
    {
        $simplepieCacheDirectory = __DIR__.'/../../var/cache/simplepie';
        @mkdir($simplepieCacheDirectory);

        $sp = new SimplePie();
        $sp->set_cache_location($simplepieCacheDirectory);

        return $this->simplePie = $sp;
    }

    /**
     * Performs the request to fetch the feed content.
     *
     * @param string $url The URL of the feed to fetch
     *
     * @return array Array containing the request status and optional message
     */
    public function request(string $url): array
    {
        $this->simplePie->set_feed_url($url);

        $success = false;
        $message = null;

        try {
            $success = $this->simplePie->init();
        } catch (\Throwable $th) {
            $success = false;
            $message = 'Simplepie return exception : '.$th->getMessage();
        }

        if (true !== $success) {
            $success = false;
            $message = 'Simplepie fail to parse the feed';
        } else {
            $success = true;
        }

        return ['success' => $success, 'message' => $message];
    }

    /**
     * Handles feed fetching errors.
     *
     * @param Feed  $feed     The concerned feed
     * @param array $response The request response
     *
     * @return bool True if no error occurred, false otherwise
     */
    public function handleError(Feed &$feed, array $response): bool
    {
        extract($response);

        if ($success) {
            $feed->setErrorMessage(null);
            $feed->setErrorCount(0);
        } else {
            $feed->setFetchedAt(new \DateTime());
            $feed->setErrorMessage($message);
            $feed->incrementErrorCount();

            if ($feed->getErrorCount() >= 100) { // Disable feed after 100 errors
                $feed->setEnabled(false);
            }
        }

        return $success;
    }

    /**
     * Transforms a SimplePie item into a feed entry.
     *
     * @param mixed $item The SimplePie item to transform
     * @param Feed  $feed The parent feed
     *
     * @return Entry The created entry
     */
    public function mapData($item, Feed &$feed): Entry
    {
        // La date du post
        $date = $item->get_date('Y-m-d H:i:s');
        if ($date) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        } else {
            $date = new \DateTime('now');
        }

        // Empecher d'avoir une date de post dans le futur
        $dateNow = new \DateTime('now');
        if ($date > $dateNow) {
            $date = $dateNow;
        }

        $entry = new Entry();
        $entry->setTitle(strip_tags($item->get_title()));
        $entry->setPermalink($item->get_permalink());
        $entry->setDate($date);
        $entry->setContent($item->get_description());

        $hash = md5($item->get_permalink().$item->get_title());
        $entry->setHash($hash);

        $entry->setFeed($feed);

        return $entry;
    }

    /**
     * Calculates the next feed fetch date.
     *
     * @param Feed $feed           The concerned feed
     * @param int  $nbEntriesAdded Number of entries added during the last fetch
     */
    public function nextFetch(Feed &$feed, int $nbEntriesAdded): void
    {
        // On détermine l'interval et la prochaine date de fetch
        if ($nbEntriesAdded > 0) {
            $feed->setFetchEvery(2);
        } else {
            $feed->setFetchEvery($feed->getFetchEvery() + 2);
            if ($feed->getFetchEvery() >= 10) {
                $feed->setFetchEvery(10);
            }
        }

        $nextFetch = new \DateTime();
        $nextFetch->modify('+'.$feed->getFetchEvery().' minutes');
        $feed->setFetchAt($nextFetch);
    }
}
