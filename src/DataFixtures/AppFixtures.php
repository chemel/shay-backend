<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Feed;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $feed = new Feed();
        $feed->setTitle('Korben');
        $feed->setUrl('https://korben.info/feed');
        $manager->persist($feed);

        $feed = new Feed();
        $feed->setTitle('Pixels');
        $feed->setUrl('https://www.lemonde.fr/pixels/rss_full.xml');
        $manager->persist($feed);

        $manager->flush();
    }
}
