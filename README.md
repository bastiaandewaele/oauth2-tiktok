# TikTok Provider for OAuth 2.0 Client

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/bastiaandewaele/tiktok-oauth2/blob/master/LICENSE.md)

This package provides TikTok OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Requirements

The following versions of PHP are supported. 

* PHP 7.4
* PHP 8.0
* PHP 8.1

## Installation

```bash
$ composer require bastiaandewaele/tiktok-oauth-v1
```

## Flow authorization

```php 
<?php 

use TikTok\OAuth2\Client\Providers\TikTokAuthProvider;

$provider = new TikTokAuthProvider([
    'clientId' => 'xyz',
    'clientSecret' => 'xyz',
    'redirectUri' => 'https://example.com/callback-url'
]);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;
  
// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
 
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
 
} else {
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
 
    // Optional: Now you have a token you can look up a users profile data
    try {
 
        // We got an access token, let's now get the user's details
        $team = $provider->getResourceOwner($token);
 
        // Use these details to create a new profile
        printf('Hello %s!', $team->getName());
 
    } catch (Exception $e) {
 
        // Failed to get user details
        exit('Oh dear...');
    }
 
    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

## Refreshing a Token
```php 
<?php
 
use TikTok\OAuth2\Client\Providers\TikTokAuthProvider;

$provider = new TikTokAuthProvider([
    'clientId' => 'xyz',
    'clientSecret' => 'xyz',
    'redirectUri' => null
]);

$token = $provider->getAccessToken('refresh_token', [
    'refresh_token' => 'xyz'
]);
```

## Running tests

```bash 
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/bastiaandewaele/tiktok-oauth2/blob/master/LICENSE.md) for more information.