<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AdminFixtures extends Fixture
{

    public function __construct(protected PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new Admin();
        $admin->setUsername('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->hasherFactory
                ->getPasswordHasher(Admin::class)
                ->hash('admin')
            )
        ;
        $manager->flush();
    }
}
