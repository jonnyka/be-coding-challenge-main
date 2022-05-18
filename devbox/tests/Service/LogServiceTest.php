<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Service\LogService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LogServiceTest extends KernelTestCase
{
    public function testGetLogs(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $logService = $container->get(LogService::class);

        $params = [
            ['data' => [], 'count' => 10],
            ['data' => ['serviceNames' => 'USER-SERVICE'], 'count' => 6],
            ['data' => ['serviceNames' => 'USER-SERVICEE'], 'count' => 0],
            ['data' => ['serviceNames' => 'INVOICE-SERVICE', 'statusCode' => 201], 'count' => 3],
            ['data' => ['serviceNames' => 'INVOICE-SERVICE,USER-SERVICE', 'statusCode' => 400], 'count' => 3],
            ['data' => ['serviceNames' => 'NONEXISTANT-SERVICE,USER-SERVICE,A GIRAFFE'], 'count' => 6],
            ['data' => ['startDate' => '2021-08-17 09:22'], 'count' => 5],
            ['data' => 'gibberish nonarray', 'count' => 10],
            ['data' => ['startDate' => 'IamNotADate'], 'count' => 10],
            ['data' => ['strange key' => 42, 'endDate' => '2021-08-17 09:22'], 'count' => 5],
        ];
        foreach ($params as $param) {
            $data = $param['data'];
            $dataString = json_encode($data);
            $count = $param['count'];
            print "Testing data $dataString, expecting count to be $count.\r\n";

            $logsCount = $logService->getLogs($data);
            $this->assertIsInt($logsCount);
            $this->assertEquals($logsCount, $count);
            print "done.\r\n";
        }
    }
}
