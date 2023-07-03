<?php

/** @noinspection ALL */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use TikTok\OAuth2\Client\Providers\TikTokAuthProvider;
use Mockery as M;
use League\OAuth2\Client\Token\AccessToken;

class MainTest extends TestCase
{

    protected TikTokAuthProvider $provider;

    public function setUp(): void
    {
        $this->provider = new TikTokAuthProvider([
            'clientId' => 'xyz_client_id',
            'clientSecret' => 'xyz_client_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        M::close();
        parent::tearDown();
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $path = parse_url($url, PHP_URL_PATH);

        // Test the path
        $this->assertEquals('/v2/auth/authorize/', $path);

        // Test out the query parameters
        $this->assertEquals($query['client_key'], 'xyz_client_id');
        $this->assertEquals($query['scope'], implode(',', $this->provider->getDefaultScopes()));
        $this->assertEquals($query['redirect_uri'], 'none');
        $this->assertEquals($query['response_type'], 'code');
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        $token = new AccessToken([
            'access_token' => 'xyz',
            'open_id' => 'xyz',
        ]);

        $mockProvider = Mockery::mock(TikTokAuthProvider::class)
            ->makePartial()
            ->shouldReceive('fetchResourceOwnerDetails')
            ->once()
            ->andReturn([
                'data' => [
                    'user' => [
                        'open_id' => 'xyz_open_id',
                        'union_id' => 'xyz',
                        'avatar_url' => 'xyz',
                        'avatar_url_100' => 'xyz',
                        'avatar_url_200' => 'xyz',
                        'avatar_large_url' => 'xyz',
                        'display_name' => 'xyz',
                        'profile_deep_link' => 'xyz',
                        'bio_description' => 'xyz',
                    ],
                ],
                'error' => [
                    'code' => 0,
                    'message' => ''
                ]
            ])
            ->getMock();

        /** @var TikTokAuthProvider $mockProvider */
        $resourceOwner = $mockProvider->getResourceOwner($token);

        $data = $resourceOwner->toArray();

        $this->assertArrayHasKey('open_id', $data['user']);
        $this->assertArrayHasKey('union_id', $data['user']);
        $this->assertEquals($resourceOwner->getId(), 'xyz_open_id');
    }
}