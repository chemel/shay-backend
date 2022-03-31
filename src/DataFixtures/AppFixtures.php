<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Feed;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $category = new Category();
        $category->setName('Uncategorized');
        $manager->persist($category);

        $feed = new Feed();
        $feed->setTitle('Korben');
        $feed->setUrl('https://korben.info/feed');
        $feed->setCategory($category);
        $manager->persist($feed);

        $feed = new Feed();
        $feed->setTitle('Pixels');
        $feed->setUrl('https://www.lemonde.fr/pixels/rss_full.xml');
        $feed->setCategory($category);
        $manager->persist($feed);

        $manager->flush();
    }
}
