<?php

namespace App\Tests\ApiController\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserControllerTest extends WebTestCase
{

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function createToken(): string
    {
        $this->client->request(
            'POST',
            '/api/oauth/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'email' => 'jane_admin@symfony.com',
                'password' => 'kitten',
            ])
        );
        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }
    public function testIndex(): void
    {
        $token = $this->createToken();
        $this->client->request(
            'GET',
            '/api/user/',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer ' . $token,
            ]
        );
        $this->assertResponseIsSuccessful();
    }
}
