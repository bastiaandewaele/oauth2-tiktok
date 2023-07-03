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
        return 'https://www.tiktok.com/v2/auth/authorize/';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://open.tiktokapis.com/v2/oauth/token/';
    }

    public function getAccessTokenUrl(array $params): string
    {
        return 'https://open.tiktokapis.com/v2/oauth/token/';
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

    protected function getAccessTokenResourceOwnerId(): string
    {
        return 'open_id';
    }

    /**
     * Used for retrieving user information
     */
    protected function getAuthorizationHeaders($token = null): array
    {
        return ['Authorization' => 'Bearer '.$token->getToken()];
    }

    /**
     * Get provider URl to fetch the user info.
     *
     * @link https://developers.tiktok.com/doc/login-kit-user-info-basic
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://open.tiktokapis.com/v2/user/info/';
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @throws IdentityProviderException
     * @link https://developers.tiktok.com/doc/tiktok-api-v2-get-user-info/
     */
    public function fetchResourceOwnerDetails(AccessToken $token): array
    {
        $fields = [
            "open_id",
            "union_id",
            "avatar_url",
            "avatar_url_100",
            "avatar_url_200",
            "avatar_large_url",
            "display_name",
        ];

        $scopes = explode(',', $token->getValues()['scope'] ?? '');

        if (in_array('user.info.profile', $scopes)) {
            $fields[] = 'bio_description';
            $fields[] = 'profile_deep_link';
            $fields[] = 'is_verified';
        }

        if (in_array('user.info.stats', $scopes)) {
            $fields[] = 'follower_count';
            $fields[] = 'following_count';
            $fields[] = 'likes_count';
            $fields[] = 'video_count';
        }

        $url = $this->getResourceOwnerDetailsUrl($token);
        $url .= '?fields='.implode(',', $fields);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        $request = $this->createRequest(self::METHOD_GET, $url, $token, $options);

        return $this->getParsedResponse($request);
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     */
    public function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $response->getReasonPhrase(),
                $response->getStatusCode(),
                $data
            );
        }

        if (isset($data['error']['code']) && $data['error']['code'] !== 'ok') {
            // Errors from user info
            throw new IdentityProviderException(
                $data['error']['message'],
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
            'video.publish',
        ];
    }
}
