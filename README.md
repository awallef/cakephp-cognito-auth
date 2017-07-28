# cakephp-cognito-auth plugin for CakePHP
This plugin allows you auth cognito users

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require awallef/cakephp-cognito-auth

## Configure
### From
Configure your auth component with your aws credentials & info

	'loginAction' => false, // as u want
	'unauthorizedRedirect' => false, // as u want
	'checkAuthIn' => 'Controller.initialize', // depends where u want it
	'storage' => 'Session', // as u want

	// Authenticate
	'authenticate' => [
		'Awallef/CognitoAuth.Form' => [

			// AWS - REQUIRED
			'region'  => 'eu-central-1',
			'credentials' => [
				'key' => 'XX',
				'secret'  => 'XX',
			],
			'userPoolId' => 'eu-central-1_XX',
			'clientId' => 'XX',
			'clientSecret' => 'XX',

			// traditional stuff - OPTIONAL ( here default values )
		    'fields' => [
		        'username' => 'username',
		        'password' => 'password'
		    ],
		    'userModel' => 'Users',
		    'scope' => [],
		    'finder' => 'all',
		    'contain' => null,
		    'passwordHasher' => 'Default',
	
		    // create users - OPTIONAL ( here default values ) see User model below
		    'create' => false,
	
		    // Groups management - OPTIONAL ( here default values ) whether you want to keep an array or not
		    'groupImplode' => true,
		    'groupImplodeGlue' => ',',
	
			// renaming - OPTIONAL ( here default values + sepcial is_superuser )
		    'fieldsMapping' => [
		      'Username' => 'username',
		      'Enabled' => 'is_active',
		      'UserStatus' => 'status',
		      'sub' => 'id',
		      'Groups' => 'role',
		      'new key' => 'is_superuser', // in order to use cakeDC/Auth
		    ]
	
		    // values functions - OPTIONAL ( default value is [] ) triggered after fieldsMapping (renaming)
		    'valuesPostOperations' => [
				'role' => function($user, $value){
					return empty($user['role'])? 'no role': $user['role'];
				},
				'is_superuser' => function($user, $value){
					return (is_string($user['role']) && $user['role'] == 'superuser');
				},
			],
		],
	]

### Basic
Configure your auth component with your aws credentials & info

	'loginAction' => false,
	'unauthorizedRedirect' => false,
	'checkAuthIn' => 'Controller.initialize',
	'storage' => 'Memory', // means you ask aws for each query... so I suggest you to use my plugin cakephp-redis

	/* so once Basic grant access, you'll find X-Token header with your token
	* then add this X-Token hader with value Bearer XXX ( the previous token )
	* so storage keep you session in redis instead of asking for new grant access
	'storage' => [
		'className' => 'Awallef/Redis.Redis',
		'redis' => [
			'prefix' => 'your_app_id:token:',
		]
	],
	*/

	// Authenticate
	'authenticate' => [
		'Awallef/CognitoAuth.Basic' => [

			// AWS - REQUIRED
			'region'  => 'eu-central-1',
			'credentials' => [
				'key' => 'XX',
				'secret'  => 'XX',
			],
			'userPoolId' => 'eu-central-1_XX',
			'clientId' => 'XX',
			'clientSecret' => 'XX',
	
			// traditional stuff - OPTIONAL ( here default values )
		    'fields' => [
		        'username' => 'username',
		        'password' => 'password'
		    ],
		    'userModel' => 'Users',
		    'scope' => [],
		    'finder' => 'all',
		    'contain' => null,
		    'passwordHasher' => 'Default',
	
		    // create users - OPTIONAL ( here default values ) see User model below
		    'create' => false,
	
		    // Groups management - OPTIONAL ( here default values ) whether you want to keep an array or not
		    'groupImplode' => true,
		    'groupImplodeGlue' => ',',
	
			// renaming - OPTIONAL ( here default values + sepcial is_superuser )
		    'fieldsMapping' => [
		      'Username' => 'username',
		      'Enabled' => 'is_active',
		      'UserStatus' => 'status',
		      'sub' => 'id',
		      'Groups' => 'role',
		      'new key' => 'is_superuser', // in order to use cakeDC/Auth
		    ]
	
		    // values functions - OPTIONAL ( default value is [] ) triggered after fieldsMapping (renaming)
		    'valuesPostOperations' => [
				'role' => function($user, $value){
					return empty($user['role'])? 'no role': $user['role'];
				},
				'is_superuser' => function($user, $value){
					return (is_string($user['role']) && $user['role'] == 'superuser');
				},
			],
		],
	]

## User Model
you can create a copy of the user in your system by setting create config argument to true

	// auth settings
	...
	// create users - OPTIONAL
	'create' => true,
	...

So you will need a User Model that matches aws fields or by using fieldsMapping argument
congito-auth needs to have write permission on id if you rename AWS 'sub' filed into id

	// auth settings
	...
	'fieldsMapping' => [
		'Username' => 'username',
		'Enabled' => 'is_active',
		'UserStatus' => 'status',
		'sub' => 'id',
		'Groups' => 'role'
	],
	...


Model Entity:

	protected $_accessible = [
		'*' => true,
		'id' => true
	];
## Challenge
if you recieve a challenge error such as:
	
	SMS_MFA
	PASSWORD_VERIFIER
	ADMIN_NO_SRP_AUTH
	NEW_PASSWORD_REQUIRED
	
redirect, or ask user to provide responses in request data object:
	
	// + username + password => required 
	// 'USERNAME' and 'SECRET_HASH' => will be filled for you
	$request->data['Challenge']['responses'] // must be filled
	
please refer to : [aws php SDK](http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminrespondtoauthchallenge) #adminrespondtoauthchallenge