<?php

declare(strict_types=1);

namespace TikTok\OAuth2\Client\Providers;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class TikTokResourceOwner implements ResourceOwnerInterface
{
    protected array $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function toArray(): array
    {
        return $this->response['data'];
    }

    public function getId(): string
    {
        return $this->response['data']['user']['open_id'];
    }
}
