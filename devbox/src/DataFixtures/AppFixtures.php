<?php

namespace App\DataFixtures;

use App\Entity\Log;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $i = 0;
        $logs = [
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:21:53 +0000','status' => 201],
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:21:54 +0000','status' => 400],
            [ 'name' => 'INVOICE-SERVICE', 'date' => '17/Aug/2021:09:21:55 +0000','status' => 201],
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:21:56 +0000','status' => 201],
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:21:57 +0000','status' => 201],
            [ 'name' => 'INVOICE-SERVICE', 'date' => '17/Aug/2021:09:22:58 +0000','status' => 201],
            [ 'name' => 'INVOICE-SERVICE', 'date' => '17/Aug/2021:09:22:59 +0000','status' => 400],
            [ 'name' => 'INVOICE-SERVICE', 'date' => '17/Aug/2021:09:23:53 +0000','status' => 201],
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:23:54 +0000','status' => 400],
            [ 'name' => 'USER-SERVICE', 'date' => '17/Aug/2021:09:23:55 +0000','status' => 201],
        ];

        foreach ($logs as $l) {
            $log = new Log();
            $log->setName($l['name']);
            $log->setTimestamp(strtotime($l['date']));
            $log->setStatus($l['status']);
            $log->setLine($i);
            $manager->persist($log);
            $i++;
        }

        $manager->flush();
    }
}
