<?php

namespace App\DataFixtures;

use App\Factory\CategoryFactory;
use App\Factory\EquipmentFactory;
use App\Factory\SupplierFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createAdmin();
        UserFactory::createMany(20);

        //
        SupplierFactory::createMany(10);
        CategoryFactory::createMany(15);
        EquipmentFactory::createMany(30);

        $manager->flush();
    }
}
