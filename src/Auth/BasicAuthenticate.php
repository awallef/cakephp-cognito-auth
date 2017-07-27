<?php
namespace Awallef\CognitoAuth\Auth;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\Core\Configure;

use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Exception\BadRequestException;
use Cake\Auth\BasicAuthenticate AS CakeBasicAuthenticate;

class BasicAuthenticate extends CakeBasicAuthenticate
{
  protected $_client;

  protected $_defaultConfig = [
    'version' => 'latest',
    'realm' => 'exemple.ch WEB API',
    'region'  => 'eu-central-1',
    'credentials' => [
      'key' => 'XXX',
      'secret'  => 'XXX',
    ],
    'userPoolId' => 'eu-central-1_XXX',
    'clientId' => 'XXX',
    'clientSecret' => 'XXX',
    'groupImplode' => true,
    'groupImplodeGlue' => ',',
    'groupField' => 'role'
  ];

  public $error = 'Ah ah ah! You didn\'t say the magic word!';

  public $errorClass = 'Cake\Network\Exception\UnauthorizedException';

  public function client()
  {
    return $this->_client? $this->_client: new CognitoIdentityProviderClient(['version' => $this->config('version'),'region'  => $this->config('region'), 'credentials' => $this->config('credentials') ]);
  }

  public function secretHash($username)
  {
    return  base64_encode(hash_hmac('sha256',$username.$this->config('clientId'), $this->config('clientSecret'), true ));
  }

  public function badRequest(AwsException $e)
  {
    $this->errorClass = 'Cake\Network\Exception\BadRequestException';
    $this->error = $e->getStatusCode().' - '.$e->getAwsErrorCode().': '.$e->getAwsErrorMessage();
    return false;
  }

  public function unauthenticated(ServerRequest $request, Response $response)
  {
    $Exception = new $this->errorClass($this->error);
    $Exception->responseHeader([$this->loginHeaders($request)]);
    throw $Exception;
  }

  public function afterIdentify(Event $event, array $user)
  {
    $event->getSubject()->response = $event->getSubject()->response->withHeader('X-Token', $user['X-Token']);
  }

  public function authenticate(ServerRequest $request, Response $response)
  {
    return $this->getUser($request);
  }

  public function respondToAuthChallenge(ServerRequest $request)
  {
    $ChallengeResponses = $request->data['Challenge']['responses'];
    $ChallengeResponses += [
      'USERNAME'  => $request->data['Challenge']['username'],
      'SECRET_HASH' => $this->secretHash($request->data['Challenge']['username'])
    ];
    try {
      $response = $this->client()->adminRespondToAuthChallenge([
        'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        'ClientId' => $this->config('clientId'),
        'UserPoolId' => $this->config('userPoolId'),
        'Session' => $request->data['Challenge']['session'],
        'ChallengeName' => $request->data['Challenge']['name'],
        'ChallengeResponses' => $ChallengeResponses
      ]);
    } catch (AwsException $e) {
      return $this->badRequest($e);
    }

    return $response;
  }

  public function getUser(ServerRequest $request)
  {
    $username = $request->env('PHP_AUTH_USER');
    $password = $request->env('PHP_AUTH_PW');
    if (!is_string($username) || $username === '' || !is_string($password) || $password === '') {
      return false;
    }

    try {
      $response = $this->client()->adminInitiateAuth([
        'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
        'ClientId' => $this->config('clientId'),
        'UserPoolId' => $this->config('userPoolId'),
        'AuthParameters' => [ 'USERNAME' => $username, 'PASSWORD' => $password, 'SECRET_HASH' => $this->secretHash($username)],
      ]);
    } catch (AwsException $e) {
      return $this->badRequest($e);
    }

    if($response->get('ChallengeName') && empty($request->data['Challenge'])){
      $this->error = $response->get('ChallengeName');
      return false;
    }
    if($response->get('ChallengeName') && !empty($request->data['Challenge'])){
      $request->data['Challenge']['session'] = $response->get('Session');
      $request->data['Challenge']['username'] = $response->get('ChallengeParameters')['USER_ID_FOR_SRP'];
      $response = $this->respondToAuthChallenge($request);
      if(!$response) return false;
    }

    $token = $response->get('AuthenticationResult')['AccessToken'];
    $awsUser = $this->client()->adminGetUser(['UserPoolId' => $this->config('userPoolId'), 'Username' => $username]);
    $awsGroup = $this->client()->adminListGroupsForUser(['UserPoolId' => $this->config('userPoolId'), 'Username' => $username]);

    $user = [
      'X-Token' => $token,
      'Username' => $awsUser->get('Username'),
      'Enabled' => $awsUser->get('Enabled'),
      'UserStatus' => $awsUser->get('UserStatus'),
    ];
    foreach($awsUser->get('UserAttributes') as $attr ){
      $user[$attr['Name']] = $attr['Value'];
    }
    $groups = $awsGroup->get('Groups');
    if(!empty($groups)){
      if($this->config('groupImplode')){
        $groupNames = [];
        foreach($groups as $group){
          $groupNames[] = $group['GroupName'];
        }
        $user[$this->config('groupField')] = implode($this->config('groupImplodeGlue'), $groupNames);
      }else{
        $user[$this->config('groupField')] = $groups;
      }
    }
    return $user;
  }

  public function implementedEvents()
  {
    return ['Auth.afterIdentify' => 'afterIdentify'];
  }
}
