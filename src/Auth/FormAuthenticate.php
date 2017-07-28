<?php
namespace Awallef\CognitoAuth\Auth;

use Cake\Http\ServerRequest;

class FormAuthenticate extends BaseAuthenticate
{
  protected function _checkFields(ServerRequest $request, array $fields)
  {
    foreach ([$fields['username'], $fields['password']] as $field) {
      $value = $request->getData($field);
      if (empty($value) || !is_string($value)) {
        return false;
      }
    }

    return true;
  }

  public function getUser(ServerRequest $request)
  {
    $fields = $this->_config['fields'];
    if (!$this->_checkFields($request, $fields)) {
        return false;
    }

    // retrieve user inputs
    $username = $request->getData($fields['username']);
    $password = $request->getData($fields['password']);

    return $this->_getUserData($username, $this->_getToken($request, $this->_findUser($username, $password)));
  }
}
