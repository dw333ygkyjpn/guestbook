<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ConferenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $santiago = new Conference();
        $santiago->setCity('[TEST] Santiago')
            ->setYear('2023')
            ->setIsInternational(false);
        $manager->persist($santiago);

        $lima = new Conference();
        $lima->setCity('[TEST] Lima')
            ->setYear('2024')
            ->setIsInternational(true);
        $manager->persist($lima);

        $comment1 = new Comment();
        $comment1->setConference($santiago)
            ->setAuthor('Nicolas Silva')
            ->setEmail('ringsofsaturn@gmail.com')
            ->setText('Great!');
        $manager->persist($comment1);
        $manager->flush();
    }
}
