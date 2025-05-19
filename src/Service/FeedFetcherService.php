<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use SimplePie\SimplePie;
use App\Entity\Feed;
use App\Entity\Entry;

class FeedFetcherService
{
    protected EntityManagerInterface $em;
    protected SimplePie $simplePie;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function fetch(Feed &$feed): bool
    {
        $entryRepository = $this->em->getRepository(Entry::class);

        $this->init();
        $response = $this->request($feed->getUrl());
        $success = $this->handleError($feed, $response);

        if($success === false) {
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
            if($exist) {
                continue;
            }

            // On check si la date du post est pas plus ancienne que la date de purge du feed
            $purgeDate = $feed->getPurgeDate();
            if($entry->getDate() < $purgeDate) {
                continue;
            }

            $this->em->persist($entry);
            $this->em->flush();

            $nbEntriesAdded++;
        }

        // Le flux a été fetch avec succès
        $feed->setFetchedAt(new \DateTime());

        $this->nextFetch($feed, $nbEntriesAdded);

        $this->em->persist($feed);
        $this->em->flush();

        return true;
    }

    public function init(): SimplePie
    {
        $simplepieCacheDirectory = __DIR__.'/../../var/cache/simplepie';
        @mkdir($simplepieCacheDirectory);

        $sp = new SimplePie();
        $sp->set_cache_location($simplepieCacheDirectory);

        return $this->simplePie = $sp;
    }

    public function request(string $url): array
    {
        $this->simplePie->set_feed_url($url);

        $success = false;
        $message = null;

        try {
            $success = $this->simplePie->init();
        } catch (\Throwable $th) {
            $success = false;
            $message = 'Simplepie return exception : ' . $th->getMessage();
        }

        if($success !== true) {
            $success = false;
            $message = 'Simplepie fail to parse the feed';
        }
        else {
            $success = true;
        }

        return ['success' => $success, 'message' => $message];
    }

    public function handleError(Feed &$feed, array $response): bool
    {
        extract($response);

        if($success)
        {
            $feed->setErrorMessage(null);
            $feed->setErrorCount(0);
        }
        else {
            $feed->setFetchedAt(new \DateTime());
            $feed->setErrorMessage($message);
            $feed->incrementErrorCount();

            if($feed->getErrorCount() >= 100) { // Disable feed after 100 errors
                $feed->setEnabled(false);
            }
        }

        return $success;
    }

    public function mapData($item, Feed &$feed): Entry
    {
        // La date du post
        $date = $item->get_date('Y-m-d H:i:s');
        if($date) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        }
        else {
            $date = new \DateTime('now');
        }

        // Empecher d'avoir une date de post dans le futur
        $dateNow = new \DateTime('now');
        if($date > $dateNow) {
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

    public function nextFetch(Feed &$feed, int $nbEntriesAdded): void
    {
        // On détermine l'interval et la prochaine date de fetch
        if($nbEntriesAdded > 0) {
            $feed->setFetchEvery(2);
        }
        else {
            $feed->setFetchEvery($feed->getFetchEvery() + 2);
            if($feed->getFetchEvery() >= 10) {
                $feed->setFetchEvery(10);
            }
        }

        $nextFetch = new \DateTime();
        $nextFetch->modify('+' . $feed->getFetchEvery() . ' minutes');
        $feed->setFetchAt($nextFetch);
    }
}
