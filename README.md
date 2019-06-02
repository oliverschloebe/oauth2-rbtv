# Rocket Beans TV Provider for OAuth 2.0 Client

This package provides Rocket Beans TV OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

[![Build Status](https://travis-ci.com/oliverschloebe/oauth2-rbtv.svg?branch=master)](https://travis-ci.com/oliverschloebe/oauth2-rbtv)
[![License](https://img.shields.io/packagist/l/oliverschloebe/oauth2-rbtv.svg)](https://github.com/oliverschloebe/oauth2-rbtv/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/oliverschloebe/oauth2-rbtv.svg)](https://packagist.org/packages/oliverschloebe/oauth2-rbtv)
[![Source Code](http://img.shields.io/badge/source-oliverschloebe/oauth2--rbtv-blue.svg?style=flat-square)](https://github.com/oliverschloebe/oauth2-rbtv)
[![Coverage Status](https://img.shields.io/coveralls/oliverschloebe/oauth2-rbtv/master.svg?style=flat-square)](https://coveralls.io/r/oliverschloebe/oauth2-rbtv?branch=master)

---

## Installation

```
composer require oliverschloebe/oauth2-rbtv
```

## Rocket Beans TV API

https://github.com/rocketbeans/rbtv-apidoc

## Usage

[Register your apps on rocketbeans.tv](https://rocketbeans.tv/accountsettings/apps) to get `clientId` and `clientSecret`.

```php
require_once __DIR__ . '/vendor/autoload.php';

$rbtvProvider = new \OliverSchloebe\OAuth2\Client\Provider\Rbtv([
	'clientId'	=> 'yourId',          // The client ID of your RBTV app
	'clientSecret'	=> 'yourSecret',      // The client password of your RBTV app
	'redirectUri'	=> 'yourRedirectUri'  // The return URL you specified for your app on RBTV
]);

// Get authorization code
if (!isset($_GET['code'])) {
    // Options are optional, scope defaults to ['user.info']
	$options = ['scope' => ['user.info', 'user.email.read', 'user.notification.list', 'user.notification.manage', 'user.subscription.manage', 'user.subscriptions.read']];
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
		
		// We have an access token, which we may use in authenticated
		// requests against the RBTV API.
		echo 'Access Token: ' . $accessToken->getToken() . "<br />";
		echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br />";
		echo 'Expired in: ' . $accessToken->getExpires() . "<br />";
		echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br />";
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
		$resourceOwner->getName(),
		$resourceOwner->getEmail(),
		$resourceOwner->getAttribute('email'), // allows dot notation, e.g. $resourceOwner->getAttribute('group.field')
		$resourceOwner->toArray()
	);
	
	// The RBTV OAuth provider provides a way to get an authenticated API request for
	// the service, using the access token; it returns an object conforming
	// to Psr\Http\Message\RequestInterface.
	$subscriptionsRequest = $rbtvProvider->getAuthenticatedRequest(
		'GET',
		'https://api.rocketbeans.tv/v1/subscription/mysubscriptions', // see https://github.com/rocketbeans/rbtv-apidoc#list-all-subscriptions
		$accessToken
	);
	
	// Get parsed response of authenticated user's current subscriptions; returns array|mixed
	$mySubscriptions = $rbtvProvider->getParsedResponse($subscriptionsRequest);
	
	var_dump($mySubscriptions);
	
	// Or send a non-authenticated API request to public endpoints
	// of the RBTV API; it returns an object conforming
	// to Psr\Http\Message\RequestInterface.
	$blogParams = [ 'limit' => 10 ];
	$blogRequest = $rbtvProvider->getRequest(
		'GET',
		'https://api.rocketbeans.tv/v1/blog/preview/all?' . http_build_query($blogParams)
	);
	
	// Get parsed response of non-authenticated API request; returns array|mixed
	$blogPosts = $rbtvProvider->getParsedResponse($blogRequest);
	
	var_dump($blogPosts);
}
```

For more information see the PHP League's general usage examples.

## Requirements

PHP 5.6 or higher.

## Testing

``` bash
$ ./vendor/bin/parallel-lint src test
$ ./vendor/bin/phpunit --coverage-text
$ ./vendor/bin/phpcs src --standard=psr2 -sp
```

## License

The MIT License (MIT). Please see [License File](https://github.com/oliverschloebe/oauth2-rbtv/blob/master/LICENSE) for more information.
