<?php

namespace TikTok\OAuth2\Client\Providers;

use League\OAuth2\Client\OptionProvider\OptionProviderInterface;

class TikTokOptionProvider implements OptionProviderInterface
{
    public function getAccessTokenOptions($method, array $params): array
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query(
                $params
            ),
        ];
    }
}
