<?php
use Cake\Core\Configure;
use Cake\Core\Plugin;

Plugin::load('Awallef/Redis');

try {
  Configure::load('auth', 'default');
} catch (Exception $ex) {
  throw new Exception(__('Missing configuration file: "config/{0}.php"!!!', 'auth'), 1);
}
