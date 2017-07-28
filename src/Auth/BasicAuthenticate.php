<?php
namespace Awallef\CognitoAuth\Auth;

use Cake\Http\ServerRequest;

class BasicAuthenticate extends BaseAuthenticate
{
  public function getUser(ServerRequest $request)
  {
    // retrieve user inputs
    $username = $request->env('PHP_AUTH_USER');
    $password = $request->env('PHP_AUTH_PW');
    if (!is_string($username) || $username === '' || !is_string($password) || $password === '') {
      return false;
    }

    return $this->_getUserData($username, $this->_getToken($request, $this->_findUser($username, $password)));
  }
}
