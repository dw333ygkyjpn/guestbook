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
            ->setState('published')
            ->setText('Great!');
        $manager->persist($comment1);

        $comment1 = new Comment();
        $comment1->setConference($lima)
            ->setAuthor('Nicolas Silva')
            ->setEmail('ringsofsaturn@gmail.com')
            ->setState('submitted')
            ->setText('This comment is not spam!');
        $manager->persist($comment1);

        $comment2 = new Comment();
        $comment2->setConference($santiago)
            ->setAuthor('akismet‑guaranteed‑spam')
            ->setEmail('akismet-guaranteed-spam@example.com')
            ->setState('submitted')
            ->setText('This is spam!');
        $manager->persist($comment2);

        $manager->flush();
    }
}
