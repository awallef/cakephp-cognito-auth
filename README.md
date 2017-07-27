# cakephp-cognito-auth plugin for CakePHP
This plugin allows you auth cognito users

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require awallef/cakephp-cognito-auth

Load it in your config/boostrap.php

	Plugin::load('Awallef/CognitoAuth');

## Configure
Configure your auth component with your aws credentials & info

	'loginAction' => false,
	'unauthorizedRedirect' => false,
	'checkAuthIn' => 'Controller.initialize',
	'storage' => 'Memory',

	// Authenticate
	'authenticate' => [
		'Awallef/CognitoAuth.Basic' => [
			'realm' => 'exemple.ch',
			'region'  => 'eu-central-1',
			'credentials' => [
				'key' => 'XX',
				'secret'  => 'XX',
			],
			'userPoolId' => 'eu-central-1_XX',
			'clientId' => 'XX',
			'clientSecret' => 'XX',
		],
	]
