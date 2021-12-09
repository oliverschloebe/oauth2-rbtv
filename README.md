# Rocket Beans TV Provider for OAuth 2.0 Client

This package provides Rocket Beans TV OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

[![CircleCI](https://circleci.com/gh/oliverschloebe/oauth2-rbtv/tree/master.svg?style=svg)](https://circleci.com/gh/oliverschloebe/oauth2-rbtv/tree/master) [![Build Status](https://travis-ci.com/oliverschloebe/oauth2-rbtv.svg?branch=master)](https://travis-ci.com/oliverschloebe/oauth2-rbtv)
[![StyleCI](https://github.styleci.io/repos/189750356/shield?branch=master)](https://github.styleci.io/repos/189750356)
[![License](https://img.shields.io/packagist/l/oliverschloebe/oauth2-rbtv.svg)](https://github.com/oliverschloebe/oauth2-rbtv/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/oliverschloebe/oauth2-rbtv.svg)](https://packagist.org/packages/oliverschloebe/oauth2-rbtv)
[![Latest Version](https://img.shields.io/github/release/oliverschloebe/oauth2-rbtv.svg?style=flat-square)](https://github.com/oliverschloebe/oauth2-rbtv/releases)
[![Source Code](https://img.shields.io/badge/source-oliverschloebe/oauth2--rbtv-blue.svg?style=flat-square)](https://github.com/oliverschloebe/oauth2-rbtv)

---

## Installation

```
composer require oliverschloebe/oauth2-rbtv
```

## Rocket Beans TV API

https://github.com/rocketbeans/rbtv-apidoc

## Usage

[Register your apps on rocketbeans.tv](https://rocketbeans.tv/accountsettings/apps) to get `clientId` and `clientSecret`.

### OAuth2 Authentication Flow

```php
require_once __DIR__ . '/vendor/autoload.php';

//session_start(); // optional, depending on your used data store

$rbtvProvider = new \OliverSchloebe\OAuth2\Client\Provider\Rbtv([
	'clientId'	=> 'yourId',          // The client ID of your RBTV app
	'clientSecret'	=> 'yourSecret',      // The client password of your RBTV app
	'redirectUri'	=> 'yourRedirectUri'  // The return URL you specified for your app on RBTV
]);

// Get authorization code
if (!isset($_GET['code'])) {
	// Options are optional, scope defaults to ['user.info']
	$options = [ 'scope' => ['user.info', 'user.email.read', 'user.notification.list', 'user.notification.manage', 'user.subscription.manage', 'user.subscriptions.read', 'user.rbtvevent.read', 'user.rbtvevent.manage'] ];
	// Get authorization URL
	$authorizationUrl = $rbtvProvider->getAuthorizationUrl($options);

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
}
```

### Refreshing a Token

Once your application is authorized, you can refresh an expired token using a refresh token rather than going through the entire process of obtaining a brand new token. To do so, simply reuse this refresh token from your data store to request a refresh.

```php
$rbtvProvider = new \OliverSchloebe\OAuth2\Client\Provider\Rbtv([
	'clientId'	=> 'yourId',          // The client ID of your RBTV app
	'clientSecret'	=> 'yourSecret',      // The client password of your RBTV app
	'redirectUri'	=> 'yourRedirectUri'  // The return URL you specified for your app on RBTV
]);

$existingAccessToken = getAccessTokenFromYourDataStore();

if ($existingAccessToken->hasExpired()) {
	$newAccessToken = $rbtvProvider->getAccessToken('refresh_token', [
		'refresh_token' => $existingAccessToken->getRefreshToken()
	]);

	// Purge old access token and store new access token to your data store.
}
```

### Sending authenticated API requests

The RBTV OAuth 2.0 provider provides a way to get an authenticated API request for the service, using the access token; it returns an object conforming to `Psr\Http\Message\RequestInterface`.

```php
$subscriptionsRequest = $rbtvProvider->getAuthenticatedRequest(
	'GET',
	'https://api.rocketbeans.tv/v1/subscription/mysubscriptions', // see https://github.com/rocketbeans/rbtv-apidoc#list-all-subscriptions
	$accessToken
);

// Get parsed response of current authenticated user's subscriptions; returns array|mixed
$mySubscriptions = $rbtvProvider->getParsedResponse($subscriptionsRequest);

var_dump($mySubscriptions);
```

### Sending non-authenticated API requests

Send a non-authenticated API request to public endpoints of the RBTV API; it returns an object conforming to `Psr\Http\Message\RequestInterface`.

```php
$blogParams = [ 'limit' => 10 ];
$blogRequest = $rbtvProvider->getRequest(
	'GET',
	'https://api.rocketbeans.tv/v1/blog/preview/all?' . http_build_query($blogParams)
);

// Get parsed response of non-authenticated API request; returns array|mixed
$blogPosts = $rbtvProvider->getParsedResponse($blogRequest);

var_dump($blogPosts);
```

### Using a proxy

It is possible to use a proxy to debug HTTP calls made to RBTV. All you need to do is set the proxy and verify options when creating your RBTV OAuth2 instance. Make sure to enable SSL proxying in your proxy.

```php
$rbtvProvider = new \OliverSchloebe\OAuth2\Client\Provider\Rbtv([
	'clientId'	=> 'yourId',          // The client ID of your RBTV app
	'clientSecret'	=> 'yourSecret',      // The client password of your RBTV app
	'redirectUri'	=> 'yourRedirectUri'  // The return URL you specified for your app on RBTV
	'proxy'		=> '192.168.0.1:8888',
	'verify'	=> false
]);
```

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
