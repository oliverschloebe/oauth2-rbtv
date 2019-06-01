# Rocket Beans TV Provider for OAuth 2.0 Client

This package provides Rocket Beans TV OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

[![Build Status](https://travis-ci.com/oliverschloebe/oauth2-rbtv.svg?branch=master)](https://travis-ci.com/oliverschloebe/oauth2-rbtv)

---

## Installation

```
composer require oliverschloebe/oauth2-rbtv
```

## Usage

```php
$rbtvProvider = new \OliverSchloebe\OAuth2\Client\Provider\Rbtv([
	'clientId'		=> 'yourId',          // The client ID of your RBTV app
	'clientSecret'	=> 'yourSecret',      // The client password of your RBTV app
	'redirectUri'	=> 'yourRedirectUri'  // The return URL you specified for your app on RBTV
]);

// Get authorization code
if (!isset($_GET['code'])) {
    // Scopes are optional, defaults to ['user.info']
    $scopes = ['user.info', 'user.email.read', 'user.notification.list', 'user.notification.manage', 'user.subscription.manage', 'user.subscriptions.read'];
    // Get authorization URL
    $authorizationUrl = $rbtvProvider->getAuthorizationUrl($scopes);

    // Get state and store it to the session
    $_SESSION['oauth2state'] = $rbtvProvider->getState();

    // Redirect user to authorization URL
    header('Location: ' . $authorizationUrl);
    exit;
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) { // Check for errors
    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    exit('Invalid state');
} else {
    // Get access token
    try {
        $accessToken = $rbtvProvider->getAccessToken(
            'authorization_code',
            [
                'code' => $_GET['code']
            ]
        );
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }

    // Get resource owner
    try {
        $resourceOwner = $rbtvProvider->getResourceOwner($accessToken);
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }
        
    // Store the results to session or whatever
    $_SESSION['accessToken'] = $accessToken;
    $_SESSION['resourceOwner'] = $resourceOwner;
    
    var_dump(
        $resourceOwner->getId(),
        $resourceOwner->getDisplayName(),
        $resourceOwner->getEmail(),
        $resourceOwner->toArray()
    );
}
```

For more information see the PHP League's general usage examples.

## Testing

``` bash
$ ./vendor/bin/parallel-lint src test
$ ./vendor/bin/phpunit --coverage-text
$ ./vendor/bin/phpcs src --standard=psr2 -sp
```

## License

The MIT License (MIT). Please see [License File](https://github.com/oliverschloebe/oauth2-rbtv/blob/master/LICENSE) for more information.
