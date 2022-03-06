<?php

declare(strict_types=1);

namespace TikTok\OAuth2\Client\Grants;

use League\OAuth2\Client\Grant\AbstractGrant;

class TikTokRefreshTokenGrant extends AbstractGrant
{
    protected function getName(): string
    {
        return 'refresh_token';
    }

    protected function getRequiredRequestParameters(): array
    {
        return [
            'refresh_token',
        ];
    }

    public function prepareRequestParameters(array $defaults, array $options): array
    {
        return [
            'grant_type' => $this->getName(),
            'client_key' => $defaults['client_id'],
            'refresh_token' => $options['refresh_token'],
        ];
    }
}
