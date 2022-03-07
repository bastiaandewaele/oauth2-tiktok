<?php

declare(strict_types=1);

namespace TikTok\OAuth2\Client\Providers;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use TikTok\OAuth2\Client\Grants\TikTokAuthorizationCodeGrant;
use TikTok\OAuth2\Client\Grants\TikTokRefreshTokenGrant;

class TikTokAuthProvider extends AbstractProvider
{
    /**
     * Default host
     */
    protected string $host = 'https://open-api.tiktok.com';

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $this->getGrantFactory()->setGrant('authorization_code', new TikTokAuthorizationCodeGrant());
        $this->getGrantFactory()->setGrant('refresh_token', new TikTokRefreshTokenGrant());
        $this->setOptionProvider(new TikTokOptionProvider());
    }

    /**
     * Get authorization url to start the oauth-flow
     */
    public function getBaseAuthorizationUrl(): string
    {
        return 'https://open-api.tiktok.com/platform/oauth/connect/';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://open-api.tiktok.com/oauth/access_token/';
    }

    public function getAccessTokenUrl(array $params): string
    {
        if ($params['grant_type'] === 'refresh_token') {
            // Refresh token requires calling a different URL
            return 'https://open-api.tiktok.com/oauth/refresh_token/';
        }

        return 'https://open-api.tiktok.com/oauth/access_token/';
    }

    /**
     * Set authorization parameters
     */
    protected function getAuthorizationParameters(array $options): array
    {
        $options = parent::getAuthorizationParameters($options);

        $options['client_key'] = $options['client_id'];

        unset($options['client_id']);

        return $options;
    }

    protected function prepareAccessTokenResponse(array $result): array
    {
        $result['data']['resource_owner_id'] = $result['data']['open_id'];

        return $result['data'];
    }

    /**
     * @param null|AccessToken $token
     * @return string[]
     */
    protected function getAuthorizationHeaders($token = null): array
    {
        return ['Authorization' => 'Bearer ' . $token->getToken()];
    }

    /**
     * Get provider URl to fetch the user info.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        // Documentation: https://developers.tiktok.com/doc/login-kit-user-info-basic

        return 'https://open-api.tiktok.com/user/info/';
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @throws IdentityProviderException
     */
    public function fetchResourceOwnerDetails(AccessToken $token): array
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $options = [
            'headers' => $this->getDefaultHeaders(),
            'body' => json_encode(
                [
                    'open_id' => $token->getResourceOwnerId(),
                    'access_token' => $token->getToken(),
                    'fields' => [
                        "open_id",
                        "union_id",
                        "avatar_url",
                        "avatar_url_100",
                        "avatar_url_200",
                        "avatar_large_url",
                        "display_name"
                    ],
                ]
            ),
        ];

        $request = $this->createRequest(self::METHOD_POST, $url, null, $options);

        return $this->getParsedResponse($request);
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     */
    public function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error']['code']) && $data['error']['code']) {
            throw new IdentityProviderException(
                $data['error']['message'],
                $data['error']['code'],
                $data
            );
        }

        if (isset($data['data']['error_code']) && $data['data']['error_code']) {
            throw new IdentityProviderException(
                $data['data']['description'],
                $data['data']['error_code'],
                $data
            );
        }

        if ($response->getStatusCode() === 401) {
            throw new IdentityProviderException(
                $response->getReasonPhrase(),
                $response->getStatusCode(),
                $data
            );
        }
    }

    public function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new TikTokResourceOwner($response);
    }

    public function getDefaultScopes(): array
    {
        return [
            'user.info.basic',
            'video.list',
            'video.upload',
        ];
    }
}
