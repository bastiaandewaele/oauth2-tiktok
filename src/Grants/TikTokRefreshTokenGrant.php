<?php

declare(strict_types=1);

namespace TikTok\OAuth2\Client\Grants;

use League\OAuth2\Client\Grant\AbstractGrant;

/**
 * @link
 */
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
            'client_key' => $defaults['client_id'],
            'client_secret' => $defaults['client_secret'],
            'refresh_token' => $options['refresh_token'],
            'grant_type' => $this->getName(),
        ];
    }
}
