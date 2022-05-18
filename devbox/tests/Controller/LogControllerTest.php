<?php

declare(strict_types=1);

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LogControllerTest extends WebTestCase
{
    public function testCheckEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/check');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('json');
    }
}
